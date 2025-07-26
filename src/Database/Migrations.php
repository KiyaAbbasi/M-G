<?php
namespace MarketGoogle\Database;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Migrations
 *
 * مدیریت ساختار دیتابیس، ایجاد و به‌روزرسانی جداول
 *
 * @package MarketGoogle\Database
 */
class Migrations {

    /**
     * اجرای مایگریشن‌ها
     * این متد تمام جداول مورد نیاز را ایجاد یا به‌روزرسانی می‌کند.
     */
    public static function run() {
        self::create_locations_table();
        self::create_products_table();
        self::create_user_tracking_table();
    }

    /**
     * ایجاد جدول اصلی موقعیت‌ها (locations)
     */
    private static function create_locations_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

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
    }

    /**
     * ایجاد جدول محصولات (products)
     */
    private static function create_products_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
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
        dbDelta($sql);
    }

    /**
     * ایجاد جدول ردیابی کاربران (user_tracking)
     */
    private static function create_user_tracking_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql = "CREATE TABLE $table_name (
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
        dbDelta($sql);
    }
}
