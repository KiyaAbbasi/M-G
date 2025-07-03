<?php

/**
 * User Tracking System
 * سیستم ردیابی رفتار کاربران
 */

if (!defined('ABSPATH')) {
    exit;
}

class Market_Google_User_Tracking {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // Debug logging
            error_log('Market_Google_User_Tracking: Constructor called at ' . date('Y-m-d H:i:s'));
            
            // Register AJAX actions - مهم!
            add_action('wp_ajax_track_user_progress', array($this, 'track_user_progress'));
            add_action('wp_ajax_nopriv_track_user_progress', array($this, 'track_user_progress'));
            add_action('wp_ajax_user_heartbeat', array($this, 'user_heartbeat'));
            add_action('wp_ajax_nopriv_user_heartbeat', array($this, 'user_heartbeat'));
            
            // Add a test action to check if tracking works
            add_action('wp_ajax_test_tracking', array($this, 'test_tracking'));
            add_action('wp_ajax_nopriv_test_tracking', array($this, 'test_tracking'));
            
            error_log('Market_Google_User_Tracking: AJAX actions registered successfully');

            // اکشن AJAX برای درج داده فیک انبوه
            add_action('wp_ajax_insert_fake_tracking_data', array($this, 'ajax_insert_fake_data'));
          

            // Create table immediately - مهم!
            $this->maybe_create_table();
            
            error_log('Market_Google_User_Tracking: Constructor completed successfully');
            
        } catch (Exception $e) {
            error_log('Market_Google_User_Tracking: Constructor error: ' . $e->getMessage());
        }
    }
    
    /**
     * بررسی و ایجاد جدول
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'");
        
        if ($table_exists) {
            error_log('Market_Google_User_Tracking: Table exists, checking structure...');
            
            // بررسی ساختار جدول موجود
            $columns = $wpdb->get_results("DESCRIBE {$this->table_name}");
            $existing_columns = array();
            
            foreach ($columns as $column) {
                $existing_columns[] = $column->Field;
            }
            
            // اگر ستون‌های جدید وجود ندارند، جدول را دوباره بسازیم
            $required_columns = array(
                'element_name', 'element_type', 'element_value', 'keystroke_data', 
                'form_progress', 'conversion_funnel_step'
            );
            
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            if (!empty($missing_columns)) {
                error_log('Market_Google_User_Tracking: Missing columns detected, recreating table...');
                
                // پشتیبان‌گیری از داده‌های موجود
                $backup_data = $wpdb->get_results("SELECT * FROM {$this->table_name} LIMIT 1000");
                
                // حذف جدول قدیمی
                $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
                
                // ایجاد جدول جدید
                $this->create_table();
                
                // بازگردانی داده‌های قابل استفاده
                if (!empty($backup_data)) {
                    foreach ($backup_data as $row) {
                        $insert_data = array(
                            'session_id' => $row->session_id,
                            'user_ip' => $row->user_ip,
                            'event_type' => isset($row->event_type) ? $row->event_type : 'page_load',
                            'user_agent' => isset($row->user_agent) ? $row->user_agent : '',
                            'timestamp' => $row->timestamp,
                            'created_at' => $row->timestamp
                        );
                        
                        $wpdb->insert($this->table_name, $insert_data);
                    }
                    error_log('Market_Google_User_Tracking: Restored ' . count($backup_data) . ' records');
                }
            } else {
                error_log('Market_Google_User_Tracking: Table structure is up to date');
            }
        } else {
            error_log('Market_Google_User_Tracking: Creating new table...');
            $this->create_table();
        }
        
        return true;
    }
    
    /**
     * ایجاد جدول جدید با ساختار کامل
     */
    private function create_table() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_ip varchar(45) DEFAULT NULL,
            ip_country varchar(100) DEFAULT NULL,
            ip_city varchar(100) DEFAULT NULL,
            ip_region varchar(100) DEFAULT NULL,
            ip_isp varchar(200) DEFAULT NULL,
            ip_location_string varchar(300) DEFAULT NULL,
            device_model varchar(100) DEFAULT NULL,
            device_fingerprint varchar(100) DEFAULT NULL,
            event_type varchar(50) NOT NULL DEFAULT 'page_load',
            element_id varchar(100) DEFAULT NULL,
            element_name varchar(100) DEFAULT NULL,
            element_type varchar(50) DEFAULT NULL,
            element_value longtext DEFAULT NULL,
            previous_value longtext DEFAULT NULL,
            keystroke_data longtext DEFAULT NULL,
            keystroke_timing longtext DEFAULT NULL,
            backspace_count int(11) DEFAULT 0,
            copy_paste_count int(11) DEFAULT 0,
            mouse_x int(11) DEFAULT NULL,
            mouse_y int(11) DEFAULT NULL,
            mouse_movements longtext DEFAULT NULL,
            scroll_position int(11) DEFAULT NULL,
            scroll_depth int(11) DEFAULT NULL,
            viewport_width int(11) DEFAULT NULL,
            viewport_height int(11) DEFAULT NULL,
            screen_width int(11) DEFAULT NULL,
            screen_height int(11) DEFAULT NULL,
            window_focus tinyint(1) DEFAULT 1,
            typing_speed float DEFAULT NULL,
            pause_duration float DEFAULT NULL,
            value_length int(11) DEFAULT NULL,
            time_spent int(11) DEFAULT NULL,
            time_on_element int(11) DEFAULT NULL,
            hesitation_time float DEFAULT 0,
            form_progress float DEFAULT 0,
            form_completion_time int(11) DEFAULT NULL,
            conversion_funnel_step varchar(50) DEFAULT NULL,
            browser_info longtext DEFAULT NULL,
            device_info longtext DEFAULT NULL,
            connection_type varchar(50) DEFAULT NULL,
            page_load_time int(11) DEFAULT NULL,
            cpu_class varchar(50) DEFAULT NULL,
            memory_size int(11) DEFAULT NULL,
            battery_level int(11) DEFAULT NULL,
            touch_support tinyint(1) DEFAULT 0,
            orientation varchar(20) DEFAULT NULL,
            language varchar(10) DEFAULT NULL,
            timezone varchar(50) DEFAULT NULL,
            page_url text DEFAULT NULL,
            page_title varchar(500) DEFAULT NULL,
            previous_page text DEFAULT NULL,
            referrer text DEFAULT NULL,
            utm_source varchar(200) DEFAULT NULL,
            utm_campaign varchar(200) DEFAULT NULL,
            utm_medium varchar(200) DEFAULT NULL,
            page_referrer text DEFAULT NULL,
            exit_intent tinyint(1) DEFAULT 0,
            rage_click_count int(11) DEFAULT 0,
            dead_click_count int(11) DEFAULT 0,
            error_count int(11) DEFAULT 0,
            javascript_errors longtext DEFAULT NULL,
            network_speed varchar(50) DEFAULT NULL,
            session_duration int(11) DEFAULT NULL,
            interaction_depth int(11) DEFAULT 0,
            confidence_score float DEFAULT 0,
            bot_score float DEFAULT 0,
            fraud_indicators longtext DEFAULT NULL,
            user_agent text DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_ip (user_ip),
            KEY device_fingerprint (device_fingerprint),
            KEY ip_country (ip_country),
            KEY device_model (device_model),
            KEY event_type (event_type),
            KEY timestamp (timestamp),
            KEY form_progress (form_progress),
            KEY bot_score (bot_score),
            KEY conversion_funnel_step (conversion_funnel_step),
            KEY session_duration (session_duration)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if ($result) {
            error_log('Market_Google_User_Tracking: Table created successfully');
            return true;
        } else {
            error_log('Market_Google_User_Tracking: Failed to create table');
            return false;
        }
    }
    
    /**
     * Track user action via AJAX - Real Data Collection Version
     */
    public function track_user_progress() {
        // Log debug information for troubleshooting
        error_log('Market_Google_User_Tracking: track_user_progress called at ' . date('Y-m-d H:i:s'));
        // Send immediate response to verify AJAX is working
        if (!isset($_POST['session_id']) && !isset($_POST['event_type'])) {
            error_log('Market_Google_User_Tracking: No tracking data - sending test response');
            wp_send_json_success('AJAX endpoint is working - no tracking data received');
            return;
        }
        try {
            $session_id = sanitize_text_field(isset($_POST['session_id']) ? $_POST['session_id'] : '');
            $event_type = sanitize_text_field(isset($_POST['event_type']) ? $_POST['event_type'] : '');
            $element_id = sanitize_text_field(isset($_POST['element_id']) ? $_POST['element_id'] : '');
            $page_url = esc_url_raw(isset($_POST['page_url']) ? $_POST['page_url'] : '');
            error_log("Market_Google_User_Tracking: Parsed data - Session: $session_id, Event: $event_type");
            if (empty($session_id) || empty($event_type)) {
                error_log('Market_Google_User_Tracking: Missing required parameters');
                wp_send_json_success('Missing required parameters but AJAX is working');
                return;
            }
            global $wpdb;
            $user_ip = $this->get_user_ip();
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $current_time = current_time('mysql');
            $today_start = date('Y-m-d 00:00:00');
            $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
            // بررسی وجود رکورد تکراری برای session_id و event_type و امروز
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table_name} WHERE session_id = %s AND event_type = %s AND timestamp >= %s AND timestamp < %s LIMIT 1",
                $session_id, $event_type, $today_start, $tomorrow_start
            ));
            if ($exists) {
                // فقط زمان آخرین فعالیت را آپدیت کن
                $wpdb->update(
                    $this->table_name,
                    array('timestamp' => $current_time, 'updated_at' => $current_time),
                    array('id' => $exists)
                );
                error_log("Market_Google_User_Tracking: Updated timestamp for session $session_id, event $event_type");
            } else {
                // رکورد جدید ثبت کن
                $wpdb->insert($this->table_name, array(
                    'session_id' => $session_id,
                    'user_ip' => $user_ip,
                    'event_type' => $event_type,
                    'element_id' => $element_id,
                    'user_agent' => $user_agent,
                    'timestamp' => $current_time,
                    'created_at' => $current_time,
                    'page_url' => $page_url
                ));
                error_log("Market_Google_User_Tracking: Inserted new record for session $session_id, event $event_type");
            }
            wp_send_json_success('Tracking updated');
        } catch (Exception $e) {
            error_log('Market_Google_User_Tracking: track_user_progress error: ' . $e->getMessage());
            wp_send_json_error('Error in tracking');
        }
    }
    
    /**
     * Get tracking statistics
     */
    public static function get_tracking_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return array(
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'avg_session_time' => 0,
                'drop_off_points' => array()
            );
        }
        
        try {
            // فقط آمار امروز
            $today_start = date('Y-m-d 00:00:00');
            $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
            $total_sessions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp < %s", $today_start, $tomorrow_start)) ?: 0;
            $completed_sessions = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT session_id) 
                FROM $table_name 
                WHERE event_type = 'form_submit' AND timestamp >= %s AND timestamp < %s
            ", $today_start, $tomorrow_start)) ?: 0;
            $avg_session_time = $wpdb->get_var($wpdb->prepare("
                SELECT AVG(session_duration) FROM (
                    SELECT TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp)) as session_duration
                    FROM $table_name 
                    WHERE timestamp >= %s AND timestamp < %s
                    GROUP BY session_id
                ) as session_times
            ", $today_start, $tomorrow_start)) ?: 0;
            $drop_off_points = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    element_id as field_name,
                    COUNT(*) as exit_count
                FROM $table_name 
                WHERE event_type = 'field_exit' AND timestamp >= %s AND timestamp < %s
                GROUP BY element_id 
                ORDER BY exit_count DESC 
                LIMIT 10
            ", $today_start, $tomorrow_start)) ?: array();
            return array(
                'total_sessions' => $total_sessions,
                'completed_sessions' => $completed_sessions,
                'avg_session_time' => round($avg_session_time),
                'drop_off_points' => $drop_off_points
            );
        } catch (Exception $e) {
            error_log('Get tracking stats error: ' . $e->getMessage());
            return array(
                'total_sessions' => 0,
                'completed_sessions' => 0,
                'avg_session_time' => 0,
                'drop_off_points' => array()
            );
        }
    }
    
    /**
     * Get incomplete sessions
     */
    public static function get_incomplete_sessions($limit = 20) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        if (!$table_exists) {
            return array();
        }
        
        try {
            // فقط کاربران امروز که شروع کردند ولی تکمیل نشدند
            $today_start = date('Y-m-d 00:00:00');
            $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT
                    session_id,
                    MIN(timestamp) as start_time,
                    MAX(timestamp) as last_activity
                FROM $table_name
                WHERE timestamp >= %s AND timestamp < %s
                AND session_id NOT IN (
                    SELECT DISTINCT session_id 
                    FROM $table_name 
                    WHERE event_type = 'form_submit'
                )
                GROUP BY session_id
                ORDER BY last_activity DESC
            ", $today_start, $tomorrow_start)) ?: array();
            return $results;
            
        } catch (Exception $e) {
            error_log('Get incomplete sessions error: ' . $e->getMessage());
            return array();
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
            return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        }
    }
    
    /**
     * Test method to check if tracking system works
     */
    public function test_tracking() {
        error_log('Market_Google_User_Tracking: test_tracking called');
        
        $result = $this->test_insert();
        if ($result) {
            wp_send_json_success('Test tracking successful');
        } else {
            wp_send_json_error('Test tracking failed');
        }
    }
    
    /**
     * Test insert to verify database works
     */
    public function test_insert() {
        global $wpdb;
        
        // Make sure table exists
        $this->maybe_create_table();
        
        $test_data = array(
            'session_id' => 'TEST_' . time(),
            'user_ip' => '127.0.0.1',
            'event_type' => 'test_event',
            'element_id' => 'test_element',
            'user_agent' => 'Test User Agent',
            'page_url' => 'http://test.com',
            'timestamp' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        error_log('Market_Google_User_Tracking: Attempting test insert: ' . print_r($test_data, true));
        
        $result = $wpdb->insert($this->table_name, $test_data);
        
        if ($result === false) {
            $error = $wpdb->last_error;
            error_log('Market_Google_User_Tracking: Test insert failed: ' . $error);
            return false;
        } else {
            error_log('Market_Google_User_Tracking: Test insert successful');
            return true;
        }
    }
    
    /**
     * درج تعداد زیادی داده فیک متنوع برای تست فیلترها
     */
    public function insert_fake_data_bulk($count = 200) {
        global $wpdb;
        $this->maybe_create_table();
        $devices = ['mobile', 'desktop', 'tablet'];
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $events = ['login', 'order', 'click', 'exit', 'other', 'form_submit', 'field_input', 'field_exit', 'page_load'];
        $locations = ['تهران', 'اصفهان', 'شیراز', 'مشهد', 'تبریز', 'اهواز', 'کرج', 'قم', 'رشت', 'یزد'];
        $pages = ['/home', '/product/1', '/product/2', '/cart', '/checkout', '/about', '/contact'];
        $utm_campaigns = ['spring_sale', 'black_friday', 'newuser', 'retarget', 'none'];
        $utm_sources = ['google', 'direct', 'instagram', 'telegram', 'facebook', 'linkedin', 'none'];
        $utm_mediums = ['cpc', 'organic', 'referral', 'social', 'none'];
        $referrers = ['Google', 'Direct', 'Instagram', 'Telegram', 'Facebook', 'LinkedIn', 'none'];
        $ips = ['192.168.1.1', '10.0.0.2', '172.16.0.3', '185.12.34.56', '5.120.45.67', '37.98.123.4'];
        $names = ['علی', 'رضا', 'مریم', 'سارا', 'حسین', 'زهرا', 'محمد', 'فاطمه', 'امیر', 'نگار'];
        $phones = ['09120000001', '09120000002', '09120000003', '09120000004', '09120000005'];
        $funnels = ['step1', 'step2', 'step3', 'step4', 'completed'];
        $now = current_time('mysql');
        for ($i = 0; $i < $count; $i++) {
            $session_id = 'FAKE_' . uniqid() . "_" . $i;
            $base_data = array(
                'session_id' => $session_id,
                'user_ip' => $ips[array_rand($ips)],
                'ip_country' => 'ایران',
                'ip_city' => $locations[array_rand($locations)],
                'device_model' => $devices[array_rand($devices)],
                'user_agent' => $browsers[array_rand($browsers)] . ' TestAgent',
                'browser_info' => $browsers[array_rand($browsers)],
                'device_info' => $devices[array_rand($devices)],
                'utm_campaign' => $utm_campaigns[array_rand($utm_campaigns)],
                'utm_source' => $utm_sources[array_rand($utm_sources)],
                'utm_medium' => $utm_mediums[array_rand($utm_mediums)],
                'page_url' => $pages[array_rand($pages)],
                'page_title' => 'صفحه ' . rand(1, 10),
                'referrer' => $referrers[array_rand($referrers)],
                'timestamp' => ($i < 40 ? $now : date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'))),
                'created_at' => ($i < 40 ? $now : date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days'))),
                'conversion_funnel_step' => $funnels[array_rand($funnels)],
                'form_progress' => rand(0, 100),
                'form_completion_time' => rand(10, 300),
                'bot_score' => rand(0, 100) / 100,
                'session_duration' => rand(10, 600),
                'interaction_depth' => rand(1, 20),
                'confidence_score' => rand(0, 100) / 100,
                'exit_intent' => rand(0, 1),
                'rage_click_count' => rand(0, 5),
                'dead_click_count' => rand(0, 3),
                'error_count' => rand(0, 2),
                'window_focus' => 1,
                'typing_speed' => rand(10, 200),
                'pause_duration' => rand(0, 10),
                'value_length' => rand(1, 20),
                'time_spent' => rand(5, 300),
                'time_on_element' => rand(1, 60),
                'hesitation_time' => rand(0, 10),
                'form_progress' => rand(0, 100),
                'form_completion_time' => rand(10, 300),
                'language' => 'fa',
                'timezone' => 'Asia/Tehran',
            );
            // رکوردهای مختلف برای هر session_id
            // 1. رکورد page_load
            $data1 = $base_data;
            $data1['event_type'] = 'page_load';
            $data1['element_id'] = 'page';
            $data1['element_name'] = 'page';
            $data1['element_type'] = 'page';
            $data1['element_value'] = '';
            $wpdb->insert($this->table_name, $data1);
            // 2. رکورد ورود نام
            $data2 = $base_data;
            $data2['event_type'] = 'field_input';
            $data2['element_id'] = 'full_name';
            $data2['element_name'] = 'full_name';
            $data2['element_type'] = 'input';
            $data2['element_value'] = $names[array_rand($names)];
            $wpdb->insert($this->table_name, $data2);
            // 3. رکورد ورود شماره
            $data3 = $base_data;
            $data3['event_type'] = 'field_input';
            $data3['element_id'] = 'phone';
            $data3['element_name'] = 'phone';
            $data3['element_type'] = 'input';
            $data3['element_value'] = $phones[array_rand($phones)];
            $wpdb->insert($this->table_name, $data3);
            // 4. رکورد خروج از فیلد
            $data4 = $base_data;
            $data4['event_type'] = 'field_exit';
            $data4['element_id'] = 'phone';
            $data4['element_name'] = 'phone';
            $data4['element_type'] = 'input';
            $data4['element_value'] = $phones[array_rand($phones)];
            $wpdb->insert($this->table_name, $data4);
            // 5. رکورد کلیک
            $data5 = $base_data;
            $data5['event_type'] = 'click';
            $data5['element_id'] = 'button_submit';
            $data5['element_name'] = 'button_submit';
            $data5['element_type'] = 'button';
            $data5['element_value'] = 'ارسال';
            $wpdb->insert($this->table_name, $data5);
            // 6. رکورد ارسال فرم
            $data6 = $base_data;
            $data6['event_type'] = 'form_submit';
            $data6['element_id'] = 'form';
            $data6['element_name'] = 'form';
            $data6['element_type'] = 'form';
            $data6['element_value'] = '';
            $wpdb->insert($this->table_name, $data6);
        }
        return true;
    }

    /**
     * اکشن AJAX برای درج داده فیک انبوه
     */
    public function ajax_insert_fake_data() {
        $count = isset($_POST['count']) ? intval($_POST['count']) : 150;
        $result = $this->insert_fake_data_bulk($count);
        if ($result) {
            wp_send_json_success('داده تستی با موفقیت درج شد.');
        } else {
            wp_send_json_error('درج داده تستی با خطا مواجه شد.');
        }
    }

    /**
     * User heartbeat to keep session alive
     */
    public function user_heartbeat() {
        $session_id = sanitize_text_field(isset($_POST['session_id']) ? $_POST['session_id'] : '');
        
        if (empty($session_id)) {
            wp_send_json_error('Missing session_id');
            return;
        }
        
        $insert_data = array(
            'session_id' => $session_id,
            'user_ip' => $this->get_user_ip(),
            'event_type' => 'heartbeat',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'page_url' => sanitize_url(isset($_POST['page_url']) ? $_POST['page_url'] : ''),
            'timestamp' => current_time('mysql'),
            'created_at' => current_time('mysql')
        );
        
        global $wpdb;
        $result = $wpdb->insert($this->table_name, $insert_data);
        
        if ($result !== false) {
            wp_send_json_success('Heartbeat recorded');
        } else {
            wp_send_json_error('Failed to record heartbeat');
        }
    }

    /**
     * حذف و ایجاد مجدد جدول (برای حل مشکل ساختار)
     */
    public function recreate_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // حذف جدول قدیمی
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        error_log('Market_Google_User_Tracking: Dropped old table');
        
        // ایجاد مجدد
        $this->maybe_create_table();
        error_log('Market_Google_User_Tracking: Recreated table');
        
        return true;
    }

    /**
     * Debug method to check table structure
     */
    public static function debug_table_structure() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        error_log('=== Market Google User Tracking Debug ===');
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'");
        error_log('Table exists: ' . ($table_exists ? 'YES' : 'NO'));
        
        if ($table_exists) {
            // Get table structure
            $columns = $wpdb->get_results("DESCRIBE {$table_name}");
            error_log('Table columns: ' . print_r(array_map(function($col) { return $col->Field; }, $columns), true));
            
            // Check for required columns
            $required_columns = array('element_name', 'element_type', 'element_value', 'form_progress', 'conversion_funnel_step');
            $existing_columns = array_map(function($col) { return $col->Field; }, $columns);
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            if (!empty($missing_columns)) {
                error_log('Missing columns: ' . implode(', ', $missing_columns));
            } else {
                error_log('All required columns exist');
            }
            
            // Check data count
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
            error_log('Total records: ' . $count);
        }
        
        error_log('=== End Debug ===');
    }
    
    /**
     * Add admin menu and hooks for real data analytics
     */
    public static function add_real_data_menu() {
        add_action('admin_menu', function() {
            // Check if main menu exists first
            if (function_exists('add_submenu_page')) {
                add_submenu_page(
                    'market-google-location', // Parent slug
                    'تحلیل کاربران واقعی',      // Page title
                    '📊 داده‌های واقعی',        // Menu title
                    'manage_options',           // Capability
                    'market-google-real-analytics', // Menu slug
                    array('Market_Google_Tracking_Admin', 'display_real_user_analytics') // Callback
                );
                
                error_log('Market_Google_User_Tracking: Real data menu added successfully');
            } else {
                error_log('Market_Google_User_Tracking: add_submenu_page function not available');
            }
        });
        
        // Add debug info
        add_action('admin_init', function() {
            if (isset($_GET['debug_tracking']) && current_user_can('manage_options')) {
                self::debug_table_structure();
                
                // Force recreate table if requested
                if (isset($_GET['reset_table'])) {
                    $result = self::force_recreate_table();
                    if ($result) {
                        wp_redirect(admin_url('admin.php?page=market-google-real-analytics&table_reset=success'));
                        exit;
                    }
                }
            }
        });
    }

    /**
     * Force recreate table (for debugging/testing)
     */
    public static function force_recreate_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        error_log('Market_Google_User_Tracking: Force recreating table...');
        
        // Drop existing table
        $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
        error_log('Market_Google_User_Tracking: Dropped existing table');
        
        // Create new instance to recreate table
        $instance = new self();
        $result = $instance->create_table();
        
        if ($result) {
            error_log('Market_Google_User_Tracking: Table recreated successfully');
            return true;
        } else {
            error_log('Market_Google_User_Tracking: Failed to recreate table');
            return false;
        }
    }

    /**
     * لیست کاربران آنلاین (آخرین فعالیت کمتر از ۱۵ دقیقه)
     */
    public static function get_online_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $now = current_time('mysql');
        $threshold = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT session_id, MAX(timestamp) as last_activity
            FROM $table_name
            WHERE timestamp >= %s
            GROUP BY session_id
            HAVING last_activity >= %s
            ORDER BY last_activity DESC
        ", date('Y-m-d 00:00:00'), $threshold));
        return $results ?: array();
    }

    /**
     * لیست فعالیت‌های امروز (همه کاربران امروز)
     */
    public static function get_today_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $today_start = date('Y-m-d 00:00:00');
        $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT session_id, MIN(timestamp) as start_time, MAX(timestamp) as last_activity
            FROM $table_name
            WHERE timestamp >= %s AND timestamp < %s
            GROUP BY session_id
            ORDER BY last_activity DESC
        ", $today_start, $tomorrow_start));
        return $results ?: array();
    }

    /**
     * لیست لیدهای مارکتینگ (کاربرانی که fullname و phone را تکمیل کردند)
     */
    public static function get_marketing_leads() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $today_start = date('Y-m-d 00:00:00');
        $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT t1.session_id, MIN(t1.timestamp) as start_time, MAX(t1.timestamp) as last_activity
            FROM $table_name t1
            WHERE t1.timestamp >= %s AND t1.timestamp < %s
            AND EXISTS (
                SELECT 1 FROM $table_name t2 WHERE t2.session_id = t1.session_id AND t2.element_id = 'full_name' AND t2.element_value != ''
            )
            AND EXISTS (
                SELECT 1 FROM $table_name t3 WHERE t3.session_id = t1.session_id AND t3.element_id = 'phone' AND t3.element_value != ''
            )
            GROUP BY t1.session_id
            ORDER BY last_activity DESC
        ", $today_start, $tomorrow_start));
        return $results ?: array();
    }

    /**
     * لیست کاربران ناتمام (کاربرانی که fullname یا phone را تکمیل نکردند و فرم را کامل نکردند)
     */
    public static function get_incomplete_leads() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $today_start = date('Y-m-d 00:00:00');
        $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT t1.session_id, MIN(t1.timestamp) as start_time, MAX(t1.timestamp) as last_activity
            FROM $table_name t1
            WHERE t1.timestamp >= %s AND t1.timestamp < %s
            AND (
                NOT EXISTS (
                    SELECT 1 FROM $table_name t2 WHERE t2.session_id = t1.session_id AND t2.element_id = 'full_name' AND t2.element_value != ''
                )
                OR NOT EXISTS (
                    SELECT 1 FROM $table_name t3 WHERE t3.session_id = t1.session_id AND t3.element_id = 'phone' AND t3.element_value != ''
                )
            )
            AND t1.session_id NOT IN (
                SELECT session_id FROM $table_name WHERE event_type = 'form_submit'
            )
            GROUP BY t1.session_id
            ORDER BY last_activity DESC
        ", $today_start, $tomorrow_start));
        return $results ?: array();
    }

    /**
     * لیست کاربران تکمیل‌کننده فرم (event_type = 'form_submit')
     */
    public static function get_completed_users() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $today_start = date('Y-m-d 00:00:00');
        $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT session_id, MIN(timestamp) as start_time, MAX(timestamp) as last_activity
            FROM $table_name
            WHERE event_type = 'form_submit' AND timestamp >= %s AND timestamp < %s
            GROUP BY session_id
            ORDER BY last_activity DESC
        ", $today_start, $tomorrow_start));
        return $results ?: array();
    }
}