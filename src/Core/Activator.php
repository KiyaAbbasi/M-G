<?php
namespace MarketGoogle\Core;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Activator
 *
 * این کلاس وظیفه اجرای عملیات مورد نیاز هنگام فعال‌سازی افزونه را بر عهده دارد.
 *
 * @package MarketGoogle\Core
 */
class Activator {

    /**
     * متد اصلی فعال‌سازی
     * این متد استاتیک هنگام فعال‌سازی افزونه توسط وردپرس فراخوانی می‌شود.
     */
    public static function activate() {
        // ایجاد جداول دیتابیس
        self::create_tables();

        // تنظیم گزینه‌های پیش‌فرض
        self::set_default_options();

        // پاک کردن قوانین بازنویسی URL
        flush_rewrite_rules();
    }

    /**
     * ایجاد جداول مورد نیاز افزونه در دیتابیس
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // جدول اصلی موقعیت‌ها (locations)
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
        dbDelta($sql);

        // جدول محصولات (products)
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
        dbDelta($sql4);

        // جدول ردیابی کاربران (user_tracking)
        $tracking_table = $wpdb->prefix . 'market_google_user_tracking';
        $sql_tracking = "CREATE TABLE $tracking_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            user_ip varchar(45) DEFAULT NULL,
            device_fingerprint varchar(100) DEFAULT NULL,
            event_type varchar(50) NOT NULL DEFAULT 'page_load',
            element_id varchar(100) DEFAULT NULL,
            page_url text,
            user_agent text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY user_ip (user_ip)
        ) $charset_collate;";
        dbDelta($sql_tracking);
    }

    /**
     * تنظیم گزینه‌های پیش‌فرض افزونه
     */
    private static function set_default_options() {
        // تنظیمات عمومی
        $general_options = [
            'auto_approve' => false,
            'max_products' => 5,
        ];
        add_option('market_google_settings', $general_options, '', 'yes');

        // تنظیمات پرداخت
        $payment_options = [
            'bmi_terminal_id' => '',
            'bmi_merchant_id' => '',
            'bmi_secret_key' => '',
            'zarinpal_enabled' => true,
            'zarinpal_merchant_id' => '',
        ];
        add_option('market_google_payment_settings', $payment_options, '', 'yes');

        // تنظیمات پیامک
        $sms_options = [
            'provider' => 'melipayamak',
            'sending_method' => 'service',
            'username' => '',
            'password' => '',
            'api_key' => '',
            'line_number' => '',
            'events' => [
                'payment_success' => [
                    'enabled' => true,
                    'value' => '{full_name} عزیز، پرداخت شما به شماره پیگیری {ref_id} با موفقیت انجام شد. مارکت گوگل'
                ],
                'info_delivery' => [
                    'enabled' => true,
                    'value' => '{full_name} عزیز، شماره و آیدی تلگرام خدمت شما، 09355158614 @MarketGoogle_ir مارکت گوگل'
                ],
            ]
        ];
        add_option('market_google_sms_settings', $sms_options, '', 'yes');
    }
}
