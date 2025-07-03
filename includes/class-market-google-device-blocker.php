<?php

/**
 * Device & IP Blocker Class
 * بلاک کردن دستگاه‌ها و IP های مشکوک
 */

if (!defined('ABSPATH')) {
    exit;
}

class Market_Google_Device_Blocker {
    
    private $table_name;
    private $settings_key = 'market_google_device_blocker_settings';
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'market_google_blocked_devices';
        
        add_action('init', array($this, 'check_device_access'));
        add_action('wp_ajax_market_google_block_device', array($this, 'block_device'));
        add_action('wp_ajax_market_google_unblock_device', array($this, 'unblock_device'));
        add_action('wp_ajax_market_google_get_blocked_list', array($this, 'get_blocked_list'));
        add_action('wp_ajax_market_google_toggle_auto_block', array($this, 'toggle_auto_block'));
        add_action('wp_ajax_market_google_save_blocker_settings', array($this, 'save_settings'));
        
        // Create table if not exists
        add_action('admin_init', array($this, 'maybe_create_table'));
        add_action('init', array($this, 'maybe_create_table')); // Also on init for frontend
        
        // Force create table immediately  
        $this->maybe_create_table();
    }
    
    /**
     * Create blocked devices table if it doesn't exist
     */
    public function maybe_create_table() {
        global $wpdb;
        
        $table_name = $this->table_name;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            block_type enum('ip','user_agent','device_fingerprint') NOT NULL,
            block_value text NOT NULL,
            blocked_by enum('manual','auto') DEFAULT 'manual',
            block_reason text NULL,
            attempt_count int(11) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            block_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_attempt datetime DEFAULT CURRENT_TIMESTAMP,
            unblock_date datetime NULL,
            notes text NULL,
            PRIMARY KEY (id),
            KEY block_type (block_type),
            KEY blocked_by (blocked_by),
            KEY is_active (is_active),
            KEY block_date (block_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Static method to create blocked devices table
     */
    public static function create_blocked_devices_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'market_google_blocked_devices';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            block_type enum('ip','user_agent','device_fingerprint') NOT NULL,
            block_value text NOT NULL,
            blocked_by enum('manual','auto') DEFAULT 'manual',
            block_reason text NULL,
            attempt_count int(11) DEFAULT 1,
            is_active tinyint(1) DEFAULT 1,
            block_date datetime DEFAULT CURRENT_TIMESTAMP,
            last_attempt datetime DEFAULT CURRENT_TIMESTAMP,
            unblock_date datetime NULL,
            notes text NULL,
            PRIMARY KEY (id),
            KEY block_type (block_type),
            KEY blocked_by (blocked_by),
            KEY is_active (is_active),
            KEY block_date (block_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get block statistics
     */
    public static function get_block_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_blocked_devices';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return array(
                'total_blocked' => 0,
                'blocked_ips' => 0,
                'blocked_user_agents' => 0,
                'blocked_auto' => 0,
                'total_attempts' => 0
            );
        }
        
        return array(
            'total_blocked' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_active = 1") ?: 0,
            'blocked_ips' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE block_type = 'ip' AND is_active = 1") ?: 0,
            'blocked_user_agents' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE block_type = 'user_agent' AND is_active = 1") ?: 0,
            'blocked_auto' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE blocked_by = 'auto' AND is_active = 1") ?: 0,
            'total_attempts' => $wpdb->get_var("SELECT SUM(attempt_count) FROM $table_name WHERE is_active = 1") ?: 0
        );
    }
    
    /**
     * Analyze attack patterns
     */
    public static function analyze_attack_patterns() {
        global $wpdb;
        $tracking_table = $wpdb->prefix . 'market_google_user_tracking';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$tracking_table'") == $tracking_table;
        if (!$table_exists) {
            return array();
        }
        
        $query = "
            SELECT 
                user_ip,
                COUNT(DISTINCT session_id) as session_count,
                AVG(CASE 
                    WHEN event_type = 'bot_analysis' THEN 
                        JSON_EXTRACT(browser_info, '$.bot_score')
                    ELSE 0 
                END) as avg_bot_score
            FROM $tracking_table 
            WHERE user_ip != '' 
            GROUP BY user_ip 
            HAVING session_count > 1 OR avg_bot_score > 50
            ORDER BY avg_bot_score DESC, session_count DESC
            LIMIT 50
        ";
        
        return $wpdb->get_results($query) ?: array();
    }
    
    /**
     * Get default settings
     */
    private function get_default_settings() {
        return array(
            'auto_block_enabled' => false,
            'bot_score_threshold' => 70,
            'max_attempts' => 3,
            'block_duration' => 24, // hours
            'whitelist_ips' => array(),
            'blacklist_ips' => array()
        );
    }
    
    /**
     * Get current settings
     */
    public function get_settings() {
        $defaults = $this->get_default_settings();
        $saved = get_option($this->settings_key, array());
        return wp_parse_args($saved, $defaults);
    }
    
    /**
     * Save settings via AJAX
     */
    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        $settings = array();
        $settings['auto_block_enabled'] = isset($_POST['auto_block_enabled']) ? true : false;
        $settings['bot_score_threshold'] = intval($_POST['bot_score_threshold']);
        $settings['max_attempts'] = intval($_POST['max_attempts']);
        $settings['block_duration'] = intval($_POST['block_duration']);
        
        update_option($this->settings_key, $settings);
        
        wp_send_json_success(array('message' => 'تنظیمات با موفقیت ذخیره شد'));
    }
    
    /**
     * Toggle auto block feature
     */
    public function toggle_auto_block() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        $settings = $this->get_settings();
        $settings['auto_block_enabled'] = !$settings['auto_block_enabled'];
        
        update_option($this->settings_key, $settings);
        
        wp_send_json_success(array(
            'enabled' => $settings['auto_block_enabled'],
            'message' => $settings['auto_block_enabled'] ? 'بلاک خودکار فعال شد' : 'بلاک خودکار غیرفعال شد'
        ));
    }
    
    /**
     * Get blocked devices list
     */
    public function get_blocked_list() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT * FROM {$this->table_name} 
            WHERE is_active = 1 
            ORDER BY block_date DESC 
            LIMIT 100
        ");
        
        wp_send_json_success($results ?: array());
    }
    
    /**
     * Unblock a device
     */
    public function unblock_device() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        global $wpdb;
        
        $block_id = intval($_POST['block_id']);
        
        $result = $wpdb->update(
            $this->table_name,
            array(
                'is_active' => 0,
                'unblock_date' => current_time('mysql')
            ),
            array('id' => $block_id),
            array('%d', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => 'دستگاه با موفقیت آنبلاک شد'));
        } else {
            wp_send_json_error(array('message' => 'خطا در آنبلاک کردن دستگاه'));
        }
    }
    
    /**
     * Block a device manually
     */
    public function block_device() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        global $wpdb;
        
        $block_type = sanitize_text_field($_POST['block_type']);
        $block_value = sanitize_text_field($_POST['block_value']);
        $reason = sanitize_text_field($_POST['reason']);
        
        // تعریف نام‌های فارسی برای انواع بلاک
        $type_names = array(
            'ip' => 'آی‌پی',
            'device_fingerprint' => 'دستگاه',
            'user_agent' => 'User Agent'
        );
        
        $type_name = isset($type_names[$block_type]) ? $type_names[$block_type] : $block_type;
        
        // Check if already blocked
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE block_type = %s AND block_value = %s AND is_active = 1",
            $block_type, $block_value
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => "این {$type_name} قبلاً بلاک شده است"));
            return;
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'block_type' => $block_type,
                'block_value' => $block_value,
                'blocked_by' => 'manual',
                'block_reason' => $reason,
                'attempt_count' => 1,
                'is_active' => 1,
                'block_date' => current_time('mysql'),
                'last_attempt' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array('message' => "{$type_name} با موفقیت بلاک شد"));
        } else {
            wp_send_json_error(array('message' => "خطا در بلاک کردن {$type_name}"));
        }
    }
    
    /**
     * Check device access on each request
     */
    public function check_device_access() {
        // Skip admin pages and AJAX requests
        if (is_admin() || wp_doing_ajax()) {
            return;
        }
        
        global $wpdb;
        
        $user_ip = $this->get_user_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check if IP is blocked
        $blocked_ip = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE block_type = 'ip' AND block_value = %s AND is_active = 1",
            $user_ip
        ));
        
        // Check if User Agent is blocked
        $blocked_ua = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE block_type = 'user_agent' AND block_value = %s AND is_active = 1",
            $user_agent
        ));
        
        if ($blocked_ip || $blocked_ua) {
            wp_die('Access Denied', 'Blocked', array('response' => 403));
        }
    }
    
    /**
     * Get user IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
} 