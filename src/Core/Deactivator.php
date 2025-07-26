<?php
namespace MarketGoogle\Core;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Deactivator
 *
 * این کلاس وظیفه اجرای عملیات مورد نیاز هنگام غیرفعال‌سازی افزونه را بر عهده دارد.
 *
 * @package MarketGoogle\Core
 */
class Deactivator {

    /**
     * متد اصلی غیرفعال‌سازی
     * این متد استاتیک هنگام غیرفعال‌سازی افزونه توسط وردپرس فراخوانی می‌شود.
     */
    public static function deactivate() {
        // پاک کردن قوانین بازنویسی URL
        flush_rewrite_rules();

        // حذف جداول در صورت فعال بودن گزینه (اختیاری)
        // self::remove_tables();
    }

    /**
     * (اختیاری) حذف جداول افزونه از دیتابیس
     * این متد به صورت پیش‌فرض فراخوانی نمی‌شود تا از حذف ناخواسته داده‌ها جلوگیری شود.
     */
    private static function remove_tables() {
        $options = get_option('market_google_settings', []);
        if (isset($options['delete_tables_on_uninstall']) && $options['delete_tables_on_uninstall']) {
            global $wpdb;
            $tables_to_drop = [
                $wpdb->prefix . 'market_google_locations',
                $wpdb->prefix . 'market_google_products',
                $wpdb->prefix . 'market_google_user_tracking',
            ];

            foreach ($tables_to_drop as $table) {
                $wpdb->query("DROP TABLE IF EXISTS {$table}");
            }
        }
    }
}
