<?php
/**
 * Plugin Name: ثبت لوکیشن
 * Plugin URI: https://KiyaHolding.com
 * Description: افزونه ثبت موقعیت کسب و کار با سیستم پرداخت آنلاین و مدیریت کامل
 * Version: 1.2.0
 * Author: Kiya Holding
 * License: GPL2
 * Text Domain: market-google-location
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف مسیرهای افزونه
define('MARKET_GOOGLE_LOCATION_VERSION', '1.2.0');
define('MARKET_GOOGLE_LOCATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MARKET_GOOGLE_LOCATION_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * کلاس اصلی افزونه
 */
class Market_Google_Location {

    /**
     * سازنده
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * راه‌اندازی افزونه
     */
    public function init() {
        // بارگذاری فایل‌های ترجمه
        load_plugin_textdomain('market-google-location', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // بارگذاری کلاس‌ها
        $this->load_dependencies();
        
        // راه‌اندازی کلاس‌ها
        $this->init_classes();
        
        // Hook ها
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * بارگذاری کلاس‌های مورد نیاز
     */
    private function load_dependencies() {
        // تابع‌های کمکی (شامل jdate)
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/functions.php';
        
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-public.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-shortcode.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-ajax.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-payment.php';
        
        // کلاس تقویم شمسی
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-jalali-calendar.php';        
       
        // کلاس‌های SMS - ترتیب مهم است
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-sms-shortcode-handler.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-sms-handler.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-sms-service.php';
        
        
        // فقط در صورت وجود فایل analytics
        if (file_exists(MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-analytics.php')) {
            require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-analytics.php';
        }
        
        // کلاس‌های tracking و device management
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-user-tracking.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'admin/class-market-google-tracking-admin.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'includes/class-market-google-device-blocker.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'admin/class-market-google-device-manager.php';
        
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'admin/class-market-google-admin.php';
        require_once MARKET_GOOGLE_LOCATION_PLUGIN_PATH . 'admin/class-market-google-sms-settings.php';
    }

    /**
     * راه‌اندازی کلاس‌ها
     */
    private function init_classes() {
        // راه‌اندازی کلاس Public برای frontend
        if (class_exists('Market_Google_Public')) {
            $public_instance = new Market_Google_Public();
            $public_instance->init(); // مهم: فراخوانی متد init
        }
        
        Market_Google_Shortcode::init();
        Market_Google_Ajax::init();
        
        // راه‌اندازی کلاس پرداخت
        $payment_class = new Market_Google_Payment();
        $payment_class->init();
        
        // راه‌اندازی کلاس‌های SMS
        if (class_exists('Market_Google_SMS_Handler')) {
            Market_Google_SMS_Handler::init();
        }
        
        if (class_exists('Market_Google_SMS_Service')) {
            Market_Google_SMS_Service::init();
        }
        
        // فقط در صورت وجود کلاس analytics
        if (class_exists('Market_Google_Analytics')) {
            Market_Google_Analytics::init();
        }
        
        // راه‌اندازی User Tracking
        if (class_exists('Market_Google_User_Tracking')) {
            new Market_Google_User_Tracking();
        }
        
        // راه‌اندازی Device Blocker
        if (class_exists('Market_Google_Device_Blocker')) {
            new Market_Google_Device_Blocker();
        }
        
        if (is_admin()) {
            Market_Google_Admin::init();
            
            // راه‌اندازی Tracking Admin
            if (class_exists('Market_Google_Tracking_Admin')) {
                new Market_Google_Tracking_Admin();
            }
            
            // اضافه کردن منوی داده‌های واقعی
            if (class_exists('Market_Google_User_Tracking')) {
                Market_Google_User_Tracking::add_real_data_menu();
            }
            
            // راه‌اندازی Device Manager
            if (class_exists('Market_Google_Device_Manager')) {
                new Market_Google_Device_Manager();
            }
            
            // راه‌اندازی تنظیمات SMS در ادمین
            if (class_exists('Market_Google_SMS_Settings')) {
                Market_Google_SMS_Settings::init();
            }
            
            // بررسی و به‌روزرسانی وضعیت‌های پرداخت در صورت نیاز
            add_action('admin_init', array($this, 'check_payment_status_update'));
        }
    }

    /**
     * بررسی و به‌روزرسانی وضعیت‌های پرداخت
     */
    public function check_payment_status_update() {
        // فقط یک بار در هر نشست بررسی کن
        if (get_transient('market_google_payment_status_checked')) {
            return;
        }
        
        // بررسی نیاز به به‌روزرسانی
        if (class_exists('Market_Google_Payment_Status_Updater') && 
            Market_Google_Payment_Status_Updater::needs_update()) {
            
            // اجرای به‌روزرسانی
            $result = Market_Google_Payment_Status_Updater::update_payment_statuses();
            
            if ($result['success']) {
                // ثبت لاگ موفقیت
                error_log('Market Google: Payment statuses updated successfully');
                
                // نمایش پیام موفقیت در ادمین
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p><strong>Market Google:</strong> وضعیت‌های پرداخت با موفقیت به‌روزرسانی شدند.</p>';
                    echo '</div>';
                });
            } else {
                // ثبت لاگ خطا
                error_log('Market Google: Failed to update payment statuses - ' . $result['message']);
            }
        }
        
        // تنظیم transient برای جلوگیری از بررسی مکرر
        set_transient('market_google_payment_status_checked', true, HOUR_IN_SECONDS);
    }



    /**
     * بارگذاری اسکریپت‌ها و استایل‌ها در فرانت‌اند
     */
    public function enqueue_scripts() {
        // CSS
        wp_enqueue_style(
            'market-google-location-public',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/css/market-google-public.css',
            array(),
            MARKET_GOOGLE_LOCATION_VERSION
        );

        // Leaflet CSS برای نقشه
        wp_enqueue_style(
            'leaflet-css',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        // Select2 CSS برای dropdown های قابل جستجو
        wp_enqueue_style(
            'select2-css',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
            array(),
            '4.1.0'
        );

        // Material Icons
        wp_enqueue_style(
            'material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            array(),
            MARKET_GOOGLE_LOCATION_VERSION
        );

        // JavaScript
        wp_enqueue_script('jquery');
        
        // Leaflet JS برای نقشه
        wp_enqueue_script(
            'leaflet-js',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        // Select2 JS برای dropdown های قابل جستجو
        wp_enqueue_script(
            'select2-js',
            'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
            array('jquery'),
            '4.1.0',
            true
        );

        // اسکریپت اصلی افزونه
        wp_enqueue_script(
            'market-google-location-public',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/js/market-google-public.js',
            array('jquery', 'leaflet-js', 'select2-js'),
            MARKET_GOOGLE_LOCATION_VERSION,
            true
        );

        // User Tracking Script
        wp_enqueue_script(
            'market-google-user-tracking',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/js/market-google-user-tracking.js',
            array('jquery'),
            MARKET_GOOGLE_LOCATION_VERSION,
            true
        );

        // متغیرهای JavaScript برای AJAX
        wp_localize_script('market-google-location-public', 'marketLocationVars', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('market_location_nonce'),
            'pluginUrl' => MARKET_GOOGLE_LOCATION_PLUGIN_URL
        ));

        // متغیرهای JavaScript برای User Tracking
        wp_localize_script('market-google-user-tracking', 'marketTrackingVars', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('market_tracking_nonce'),
            'pluginUrl' => MARKET_GOOGLE_LOCATION_PLUGIN_URL
        ));
    }

    /**
     * بارگذاری اسکریپت‌ها و استایل‌ها در پنل مدیریت
     */
    public function admin_enqueue_scripts($hook) {
        // بررسی صفحات مربوط به افزونه
        $plugin_pages = array(
            'toplevel_page_market-google-location',
            'market-google-location_page_market-google-locations-list',
            'market-google-location_page_market-google-reports',
            'market-google-location_page_market-google-settings',
            'market-google-location_page_market-google-user-tracking',
            'market-google-location_page_market-google-device-manager'
        );
        
        // اگر در صفحات افزونه هستیم یا اگر شامل نام افزونه است
        if (!in_array($hook, $plugin_pages) && strpos($hook, 'market-google') === false) {
            return;
        }

        // WordPress Media Library - حتماً لود شود
        wp_enqueue_media();
        
        // اسکریپت‌های مورد نیاز برای رسانه
        wp_enqueue_script('media-upload');
        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');

        // CSS
        wp_enqueue_style(
            'market-google-location-admin',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/css/market-google-admin.css',
            array(),
            MARKET_GOOGLE_LOCATION_VERSION
        );

        wp_enqueue_style(
            'market-google-location-analytics',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/css/market-google-analytics.css',
            array(),
            MARKET_GOOGLE_LOCATION_VERSION
        );

        // JavaScript
        wp_enqueue_script('jquery');
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'market-google-location-admin',
            MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/js/market-google-admin.js',
            array('jquery', 'chart-js'),
            MARKET_GOOGLE_LOCATION_VERSION,
            true
        );

        // CSS و JS برای صفحه تنظیمات
        if (strpos($hook, 'market-google-settings') !== false) {
            wp_enqueue_style(
                'market-google-settings',
                MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/css/market-google-settings.css',
                array(),
                MARKET_GOOGLE_LOCATION_VERSION
            );
            
            // CSS و JS برای SMS
            wp_enqueue_style(
                'market-google-sms-settings',
                MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/css/market-google-sms-settings.css',
                array(),
                MARKET_GOOGLE_LOCATION_VERSION
            );
            
            wp_enqueue_script(
                'market-google-settings',
                MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/js/market-google-settings.js',
                array('jquery'),
                MARKET_GOOGLE_LOCATION_VERSION,
                true
            );
            
            wp_enqueue_script(
                'market-google-sms-settings',
                MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'admin/js/market-google-sms-settings.js',
                array('jquery'),
                MARKET_GOOGLE_LOCATION_VERSION,
                true
            );
        }

        // Leaflet برای نقشه در ادمین
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');
        
        // متغیرهای JavaScript برای admin
        wp_localize_script('market-google-location-admin', 'marketAdminVars', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('market_google_admin'),
            'pluginUrl' => MARKET_GOOGLE_LOCATION_PLUGIN_URL,
            'strings' => array(
                'saveSuccess' => 'محصول با موفقیت ذخیره شد',
                'saveError' => 'خطا در ذخیره محصول',
                'deleteConfirm' => 'آیا از حذف این محصول مطمئن هستید؟',
                'deleteSuccess' => 'محصول با موفقیت حذف شد',
                'deleteError' => 'خطا در حذف محصول'
            )
        ));
    }

    /**
     * فعال‌سازی افزونه
     */
    public function activate() {
        $this->create_tables();
        $this->set_default_options();

        // آپدیت جدول محصولات اگر نیاز باشد
        $this->update_products_table();
        
        // آپدیت جدول locations اگر نیاز باشد
        $this->update_locations_table();
        
        // اضافه کردن فیلدهای completion
        $this->add_completion_fields();
        
        // Migration وضعیت‌های قدیمی
        $this->migrate_old_statuses();
        
        // flush rewrite rules
        flush_rewrite_rules();

        // Create tracking table and blocked devices table
        if (class_exists('Market_Google_User_Tracking')) {
            $tracking_instance = new Market_Google_User_Tracking();
            $tracking_instance->maybe_create_table();
        }
        if (class_exists('Market_Google_Device_Blocker')) {
            Market_Google_Device_Blocker::create_blocked_devices_table();
        }

        // === [1] ساخت جدول whitelist هنگام فعال‌سازی افزونه ===
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_tracking_whitelist';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT PRIMARY KEY,
            type ENUM('ip','fingerprint') NOT NULL,
            value VARCHAR(255) NOT NULL,
            reason VARCHAR(255) DEFAULT NULL,
            added_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * آپدیت جدول محصولات برای سازگاری با ورژن جدید
     */
    private function update_products_table() {
        global $wpdb;
        $products_table = $wpdb->prefix . 'market_google_products';
        
        // بررسی وجود جدول
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$products_table}'") == $products_table;
        
        if (!$table_exists) {
            return; // اگر جدول وجود نداره، create_tables انجامش میده
        }
        
        // بررسی فیلدهای موجود
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$products_table}");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // اضافه کردن ستون subtitle اگر وجود نداره
        if (!in_array('subtitle', $column_names)) {
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN subtitle varchar(500) DEFAULT '' AFTER name");
        }
        
        // اضافه کردن ستون icon اگر وجود نداره  
        if (!in_array('icon', $column_names)) {
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN icon varchar(10) DEFAULT '🏪' AFTER description");
        }
        
        // اضافه کردن ستون image_url اگر وجود نداره
        if (!in_array('image_url', $column_names)) {
            $wpdb->query("ALTER TABLE {$products_table} ADD COLUMN image_url varchar(500) DEFAULT '' AFTER icon");
        }
    }

    /**
     * آپدیت جدول locations برای سازگاری با ورژن جدید
     */
    private function update_locations_table() {
        global $wpdb;
        $locations_table = $wpdb->prefix . 'market_google_locations';
        
        // بررسی وجود جدول
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$locations_table}'") == $locations_table;
        
        if (!$table_exists) {
            return; // اگر جدول وجود نداره، create_tables انجامش میده
        }
        
        // بررسی فیلدهای موجود
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$locations_table}");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        // لیست فیلدهای ضروری که باید وجود داشته باشند
        $required_fields = array(
            'session_id' => "varchar(100) DEFAULT NULL",
            'user_id' => "bigint(20) UNSIGNED DEFAULT NULL",
            'full_name' => "varchar(255) NOT NULL DEFAULT ''",
            'phone' => "varchar(20) NOT NULL DEFAULT ''",
            'business_name' => "varchar(255) NOT NULL DEFAULT ''",
            'business_phone' => "varchar(20) DEFAULT NULL",
            'website' => "varchar(255) DEFAULT NULL",
            'province' => "varchar(100) NOT NULL DEFAULT ''",
            'city' => "varchar(100) NOT NULL DEFAULT ''",
            'latitude' => "decimal(10, 8) DEFAULT NULL",
            'longitude' => "decimal(11, 8) DEFAULT NULL",
            'auto_address' => "text DEFAULT NULL",
            'manual_address' => "text DEFAULT NULL",
            'address' => "text DEFAULT NULL",
            'description' => "text DEFAULT NULL",
            'working_hours_preset' => "varchar(50) DEFAULT NULL",
            'working_hours' => "longtext DEFAULT NULL",
            'selected_products' => "longtext DEFAULT NULL",
            'price' => "decimal(10, 2) DEFAULT NULL",
            'payment_status' => "varchar(20) DEFAULT 'pending'",
            'payment_method' => "varchar(20) DEFAULT 'bmi'",
            'payment_authority' => "varchar(100) DEFAULT NULL",
            'payment_ref_id' => "varchar(100) DEFAULT NULL",
            'payment_transaction_id' => "varchar(100) DEFAULT NULL",
            'payment_amount' => "decimal(10, 2) DEFAULT NULL",
            'payment_verified_at' => "datetime DEFAULT NULL",
            'status' => "varchar(20) DEFAULT 'pending'",
            'ip_address' => "varchar(45) DEFAULT NULL",
            'user_agent' => "text DEFAULT NULL",
            'created_at' => "datetime DEFAULT CURRENT_TIMESTAMP",
            'paid_at' => "datetime DEFAULT NULL",
            'info_sent_at' => "datetime DEFAULT NULL",
            'completion_date' => "datetime DEFAULT NULL",
            'completed_by' => "int(11) DEFAULT NULL"
        );
        
        // اضافه کردن فیلدهای گمشده
        foreach ($required_fields as $field_name => $field_definition) {
            if (!in_array($field_name, $column_names)) {
                $alter_sql = "ALTER TABLE {$locations_table} ADD COLUMN {$field_name} {$field_definition}";
                $wpdb->query($alter_sql);
                error_log("Added missing field: {$field_name} to {$locations_table}");
            }
        }
        
        // بررسی و اضافه کردن indexes
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$locations_table}");
        $index_names = array_map(function($idx) { return $idx->Key_name; }, $indexes);
        
        if (!in_array('payment_transaction_id', $index_names)) {
            $wpdb->query("ALTER TABLE {$locations_table} ADD INDEX payment_transaction_id (payment_transaction_id)");
        }
        
        if (!in_array('status', $index_names)) {
            $wpdb->query("ALTER TABLE {$locations_table} ADD INDEX status (status)");
        }
        
        if (!in_array('payment_status', $index_names)) {
            $wpdb->query("ALTER TABLE {$locations_table} ADD INDEX payment_status (payment_status)");
        }
    }

    /**
     * اضافه کردن فیلدهای completion به جدول locations
     */
    private function add_completion_fields() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بررسی وجود فیلد completion_date
        $completion_date_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completion_date'");
        if (empty($completion_date_exists)) {
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completion_date DATETIME NULL");
            if ($result !== false) {
                error_log('Market Google: completion_date field added successfully');
            } else {
                error_log('Market Google: Failed to add completion_date field - ' . $wpdb->last_error);
            }
        }
        
        // بررسی وجود فیلد completed_by
        $completed_by_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completed_by'");
        if (empty($completed_by_exists)) {
            $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completed_by INT(11) NULL");
            if ($result !== false) {
                error_log('Market Google: completed_by field added successfully');
            } else {
                error_log('Market Google: Failed to add completed_by field - ' . $wpdb->last_error);
            }
        }
    }

    /**
     * Migration وضعیت‌های قدیمی به سیستم جدید
     */
    private function migrate_old_statuses() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بررسی اینکه آیا migration قبلاً انجام شده یا نه
        $migration_done = get_option('market_google_status_migration_done', false);
        
        if (!$migration_done) {
            // تبدیل وضعیت‌های active, inactive, rejected به pending
            $result1 = $wpdb->query("UPDATE {$table_name} SET status = 'pending' WHERE status IN ('active', 'inactive', 'rejected')");
            
            // اطمینان از اینکه سفارشات بدون وضعیت، pending میشوند
            $result2 = $wpdb->query("UPDATE {$table_name} SET status = 'pending' WHERE status IS NULL OR status = ''");
            
            // نشان‌دادن که migration انجام شده
            update_option('market_google_status_migration_done', true);
            
            error_log("Market Google Status Migration completed. Updated rows: " . ($result1 + $result2));
        }
    }

    /**
     * غیرفعال‌سازی افزونه
     */
    public function deactivate() {
        flush_rewrite_rules();

        // === [2] حذف جدول whitelist هنگام حذف افزونه (در صورت فعال بودن گزینه) ===
        global $wpdb;
        $delete_tables = get_option('market_google_delete_tables_on_uninstall', false);
        if ($delete_tables) {
            $table_name = $wpdb->prefix . 'market_google_tracking_whitelist';
            $wpdb->query("DROP TABLE IF EXISTS $table_name");
            // اینجا اگر جدول‌های دیگری هم داری اضافه کن
        }
    }

    /**
     * ایجاد جداول دیتابیس
     */
    private function create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
        // جدول اصلی موقعیت‌ها
        $table_name = $wpdb->prefix . 'market_google_locations';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100),
            user_id bigint(20) UNSIGNED,
            full_name varchar(255) NOT NULL,
            phone varchar(20) NOT NULL,
            province varchar(100) NOT NULL,
            city varchar(100) NOT NULL,
            latitude decimal(10, 8),
            longitude decimal(11, 8),
            auto_address text,
            manual_address text,
            address text,
            business_name varchar(255) NOT NULL,
            business_phone varchar(20),
            website varchar(255),
            description text,
            working_hours_preset varchar(50),
            working_hours longtext,
            selected_products longtext,
            price decimal(10, 2),
            payment_status varchar(20) DEFAULT 'pending',
            payment_method varchar(20) DEFAULT 'bmi',
            payment_authority varchar(100),
            payment_ref_id varchar(100),
            payment_transaction_id varchar(100),
            payment_amount decimal(10, 2),
            payment_verified_at datetime,
            status varchar(20) DEFAULT 'pending',
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            paid_at datetime,
            info_sent_at datetime DEFAULT NULL,
            completion_date datetime DEFAULT NULL,
            completed_by int(11) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY payment_status (payment_status),
            KEY city (city),
            KEY province (province),
            KEY created_at (created_at),
            KEY payment_transaction_id (payment_transaction_id)
        ) $charset_collate;";

        // جدول داده‌های موقت
        $temp_table = $wpdb->prefix . 'market_google_temp_data';
        $sql2 = "CREATE TABLE $temp_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            step int(2) NOT NULL,
            form_data longtext NOT NULL,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id),
            KEY step (step),
            KEY created_at (created_at)
        ) $charset_collate;";

        // جدول ردیابی مراحل
        $tracking_table = $wpdb->prefix . 'market_google_step_tracking';
        $sql3 = "CREATE TABLE $tracking_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            step int(2) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY step (step),
            KEY created_at (created_at)
    ) $charset_collate;";

        // جدول محصولات
        $products_table = $wpdb->prefix . 'market_google_products';
        $sql4 = "CREATE TABLE $products_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            subtitle varchar(500) DEFAULT '',
            description text,
            icon varchar(10) DEFAULT '🏪',
            image_url varchar(500) DEFAULT '',
            type enum('normal','featured','package') DEFAULT 'normal',
            original_price decimal(10,0) NOT NULL,
            sale_price decimal(10,0) NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            is_featured tinyint(1) DEFAULT 0,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type_active (type, is_active),
            KEY sort_order (sort_order)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);

        // اضافه کردن محصولات پیش‌فرض - غیرفعال شده برای تست
        // $this->add_default_products();
    }

    
    /**
     * تنظیم گزینه‌های پیش‌فرض
     */
    private function set_default_options() {
        $default_options = array(
            // بانک ملی (درگاه اصلی - همیشه فعال)
            'bmi_terminal_id' => '',
            'bmi_merchant_id' => '',
            'bmi_secret_key' => '',
            
            // زرین‌پال (درگاه پشتیبان)
            'zarinpal_enabled' => true,
            'zarinpal_merchant_id' => '',
            
            // تنظیمات عمومی
            'auto_approve' => false,
            'max_products' => 5,
            'sms_enabled' => false,
            'sms_api_key' => '',
            'sms_template' => 'کسب و کار شما با موفقیت ثبت شد.'
        );
        
        // تنظیمات پیش‌فرض SMS
        $default_sms_options = array(
            'provider' => 'melipayamak',
            'sending_method' => 'service',
            'username' => '',
            'password' => '',
            'api_key' => '',
            'line_number' => '',
            'connection_status' => false,
            'sms_count' => 0,
            
            // رویدادهای SMS با فرمت جدید
            'events' => array(
                'form_submitted' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، اطلاعات شما با شماره سفارش {order_number} دریافت شد. مارکت گوگل'
                ),
                'payment_pending' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، سفارش شما {payment_status} است، سفارش خود را تکمیل نمایید. مارکت گوگل'
                ),
                'payment_success' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، پرداخت شما به شماره پیگیری {ref_id} {payment_status} بود. مارکت گوگل'
                ),
                'payment_failure' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، پرداخت شما {payment_status} بود، لطفا مجدد تلاش کنید. مارکت گوگل'
                ),
                'payment_cancelled' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، فرایند پرداخت شما {payment_status} گردید. جهت کسب اطلاعات بیشتر تماس بگیرید. 02191552080 مارکت گوگل'
                ),
                'payment_error' => array(
                    'enabled' => false,
                    'value' => '{full_name} عزیز، پرداخت شما با خطای {error} مواجه شد، لطفا مجدد اقدام به پرداخت نمایید. مارکت گوگل'
                ),
                'order_completed' => array(
                    'enabled' => false,
                    'value' => 'سفارش شما تکمیل شد. کد پیگیری: {order_number}'
                ),
                'info_delivery' => array(
                    'enabled' => true,
                    'value' => '{full_name} عزیز، شماره و آیدی تلگرام خدمت شما، 09355158614 @MarketGoogle_ir مارکت گوگل'
                ),
                'login_code' => array(
                    'enabled' => false,
                    'value' => 'کد ورود شما: {login_code}'
                )
            )
        );
    
        add_option('market_google_settings', $default_options);
        add_option('market_google_sms_settings', $default_sms_options);
    }



    /**
     * اضافه کردن محصولات پیش‌فرض
     */
    private function add_default_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        // بررسی وجود محصولات
        $existing_products = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if ($existing_products == 0) {
            $default_products = array(
                array(
                    'name' => 'تمامی نقشه‌های آنلاین',
                    'subtitle' => 'پکیج کامل و اقتصادی',
                    'description' => 'شامل تمامی نقشه‌ها - پکیج ویژه با ۳۶٪ تخفیف',
                    'icon' => '🗺️',
                    'type' => 'package',
                    'original_price' => 1397000,
                    'sale_price' => 889000,
                    'is_active' => 1,
                    'is_featured' => 1,
                    'sort_order' => 1
                ),
                array(
                    'name' => 'نقشه گوگل‌مپ',
                    'subtitle' => 'محبوب‌ترین نقشه جهان',
                    'description' => 'ثبت در گوگل مپ',
                    'icon' => 'G',
                    'type' => 'normal',
                    'original_price' => 510000,
                    'sale_price' => 459000,
                    'is_active' => 1,
                    'is_featured' => 0,
                    'sort_order' => 2
                ),
                array(
                    'name' => 'اپن‌استریت',
                    'subtitle' => 'نقشه متن‌باز جهانی',
                    'description' => 'ثبت در OpenStreetMap',
                    'icon' => 'O',
                    'type' => 'normal',
                    'original_price' => 326000,
                    'sale_price' => 293000,
                    'is_active' => 1,
                    'is_featured' => 0,
                    'sort_order' => 3
                ),
                array(
                    'name' => 'نقشه نشان',
                    'subtitle' => 'نقشه محلی ایران',
                    'description' => 'ثبت در نشان',
                    'icon' => 'ن',
                    'type' => 'normal',
                    'original_price' => 294000,
                    'sale_price' => 264000,
                    'is_active' => 1,
                    'is_featured' => 0,
                    'sort_order' => 4
                ),
                array(
                    'name' => 'نقشه بلد',
                    'subtitle' => 'نقشه و ترافیک هوشمند',
                    'description' => 'ثبت در بلد',
                    'icon' => 'ب',
                    'type' => 'normal',
                    'original_price' => 283000,
                    'sale_price' => 254000,
                    'is_active' => 1,
                    'is_featured' => 0,
                    'sort_order' => 5
                ),
                array(
                    'name' => 'کارت ویزیت آنلاین',
                    'subtitle' => 'کارت ویزیت دیجیتال حرفه‌ای',
                    'description' => 'کارت ویزیت دیجیتال و سایت اختصاصی',
                    'icon' => '💼',
                    'type' => 'featured',
                    'original_price' => 1234000,
                    'sale_price' => 1109000,
                    'is_active' => 1,
                    'is_featured' => 1,
                    'sort_order' => 6
                )
            );
            
            foreach ($default_products as $product) {
                $wpdb->insert($table_name, $product);
            }
        }
    }
}

// راه‌اندازی افزونه
new Market_Google_Location();
// کد محصولات پیش‌فرض را حذف کنید
// function market_google_add_default_products() و register_activation_hook مربوطه

/**
 * فعال‌سازی افزونه
 */
function activate_market_google_location() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-market-google-activator.php';
    Market_Google_Activator::activate();
    
    // اضافه کردن قوانین rewrite برای صفحات پرداخت
    add_rewrite_rule(
        '^payment-result/?$',
        'index.php?payment_result_page=1',
        'top'
    );
    flush_rewrite_rules();
}

/**
 * غیرفعال‌سازی افزونه
 */
function deactivate_market_google_location() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-market-google-deactivator.php';
    Market_Google_Deactivator::deactivate();
    
    // حذف قوانین rewrite
    flush_rewrite_rules();
}

/**
 * اضافه کردن query vars برای صفحات پرداخت
 */
function market_google_add_query_vars($vars) {
    $vars[] = 'payment_result_page';
    return $vars;
}
add_filter('query_vars', 'market_google_add_query_vars');

/**
 * مدیریت template برای صفحات پرداخت
 */
function market_google_template_redirect() {
    // بررسی پارامتر payment_result_page
    if (get_query_var('payment_result_page')) {
        require_once plugin_dir_path(__FILE__) . 'public/payment-pages.php';
        Market_Google_Payment_Pages::display_payment_result();
        exit;
    }
    
    // بررسی پارامتر payment برای callback ها
    if (isset($_GET['payment'])) {
        $payment_status = sanitize_text_field($_GET['payment']);
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
        $gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
        $ref_id = isset($_GET['ref_id']) ? sanitize_text_field($_GET['ref_id']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        // دریافت اطلاعات کاربر از دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        $location = null;
        $user_name = '';
        $business_name = '';
        $amount = 0;
        
        if ($location_id > 0) {
            $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $location_id));
            if ($location) {
                $user_name = $location->user_name;
                $business_name = $location->business_name;
                $amount = $location->amount;
            }
        }
        
        // بررسی URL های سفارشی
        $custom_success_url = get_option('market_google_payment_success_url', '');
        $custom_failed_url = get_option('market_google_payment_failed_url', '');
        $custom_canceled_url = get_option('market_google_payment_canceled_url', '');
        
        switch ($payment_status) {
            case 'success':
                if (!empty($custom_success_url)) {
                    // جایگزینی متغیرها در URL سفارشی
                    $redirect_url = str_replace(
                        array('{user_name}', '{business_name}', '{amount}', '{ref_id}', '{transaction_id}'),
                        array($user_name, $business_name, $amount, $ref_id, $transaction_id),
                        $custom_success_url
                    );
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    // استفاده از صفحه پیش‌فرض
                    require_once plugin_dir_path(__FILE__) . 'public/payment-pages.php';
                    $_GET['payment_result'] = 'success';
                    Market_Google_Payment_Pages::display_payment_result();
                    exit;
                }
                break;
                
            case 'failed':
                if (!empty($custom_failed_url)) {
                    $redirect_url = str_replace(
                        array('{user_name}', '{business_name}', '{amount}', '{error}', '{transaction_id}'),
                        array($user_name, $business_name, $amount, $error, $transaction_id),
                        $custom_failed_url
                    );
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    require_once plugin_dir_path(__FILE__) . 'public/payment-pages.php';
                    $_GET['payment_result'] = 'failed';
                    Market_Google_Payment_Pages::display_payment_result();
                    exit;
                }
                break;
                
            case 'canceled':
                if (!empty($custom_canceled_url)) {
                    $redirect_url = str_replace(
                        array('{user_name}', '{business_name}', '{amount}', '{transaction_id}'),
                        array($user_name, $business_name, $amount, $transaction_id),
                        $custom_canceled_url
                    );
                    wp_redirect($redirect_url);
                    exit;
                } else {
                    require_once plugin_dir_path(__FILE__) . 'public/payment-pages.php';
                    $_GET['payment_result'] = 'canceled';
                    Market_Google_Payment_Pages::display_payment_result();
                    exit;
                }
                break;
        }
    }
}
add_action('template_redirect', 'market_google_template_redirect');

// اضافه کردن فایل functions.php
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

/**
 * اضافه کردن اسکریپت‌ها و استایل‌های تقویم جلالی
 */
function market_google_enqueue_jalali_calendar() {
    // CSS
    wp_enqueue_style(
        'jalali-calendar-css',
        plugin_dir_url(__FILE__) . 'admin/css/jalali-calendar.css',
        array(),
        '1.0.0'
    );
    
    // JavaScript
    wp_enqueue_script(
        'jalali-calendar-js',
        plugin_dir_url(__FILE__) . 'admin/js/jalali-calendar.js',
        array('jquery'),
        '1.0.0',
        true
    );
}

// اضافه کردن به admin و frontend
add_action('admin_enqueue_scripts', 'market_google_enqueue_jalali_calendar');
add_action('wp_enqueue_scripts', 'market_google_enqueue_jalali_calendar');

// === [3] افزودن چک‌باکس به تنظیمات عمومی افزونه ===
// در تابع نمایش تنظیمات (مثلاً settings_page یا مشابه آن)
// فرض: تابع Market_Google_Admin::settings_page وجود دارد
add_action('admin_init', function() {
    register_setting('market_google_settings', 'market_google_delete_tables_on_uninstall');
});