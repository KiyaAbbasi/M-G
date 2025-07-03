<?php

/**
 * کلاس فعال‌سازی افزونه
 */
class Market_Google_Activator {

    /**
     * فعال‌سازی افزونه
     */
    public static function activate() {
        global $wpdb;
        
        // ایجاد جدول لوکیشن‌ها
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_name varchar(100) NOT NULL,
            user_phone varchar(20) NOT NULL,
            business_name varchar(200) NOT NULL,
            business_type varchar(50) DEFAULT '',
            business_phone varchar(20) DEFAULT '',
            business_hours text DEFAULT '',
            province varchar(50) DEFAULT '',
            city varchar(50) DEFAULT '',
            address text NOT NULL,
            auto_address text DEFAULT '',
            latitude decimal(10, 8) NOT NULL,
            longitude decimal(11, 8) NOT NULL,
            website varchar(255) DEFAULT '',
            payment_status varchar(20) DEFAULT 'pending',
            transaction_id varchar(100) DEFAULT '',
            ref_id varchar(100) DEFAULT '',
            gateway varchar(20) DEFAULT '',
            amount decimal(10, 2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_payment_status (payment_status),
            KEY idx_created_at (created_at),
            KEY idx_user_phone (user_phone)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // ایجاد جدول تراکنش‌ها
        $transactions_table = $wpdb->prefix . 'market_google_transactions';
        
        $sql_transactions = "CREATE TABLE $transactions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            location_id mediumint(9) NOT NULL,
            transaction_id varchar(100) NOT NULL,
            gateway varchar(20) NOT NULL,
            amount decimal(10, 2) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            ref_id varchar(100) DEFAULT '',
            gateway_response text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_location_id (location_id),
            KEY idx_transaction_id (transaction_id),
            KEY idx_status (status)
        ) $charset_collate;";
        
        dbDelta($sql_transactions);
        
        // تنظیمات پیش‌فرض
        add_option('market_google_version', MARKET_GOOGLE_LOCATION_VERSION);
        add_option('market_google_db_version', '1.0');
    }
}