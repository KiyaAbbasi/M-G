<?php

/**
 * کلاس غیرفعال‌سازی افزونه
 */
class Market_Google_Deactivator {

    /**
     * غیرفعال‌سازی افزونه
     */
    public static function deactivate() {
        // حذف rewrite rules
        flush_rewrite_rules();
        
        // حذف cron jobs اگر وجود دارد
        wp_clear_scheduled_hook('market_google_cleanup_sessions');
        wp_clear_scheduled_hook('market_google_send_reports');
        
        // پاک کردن cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // حذف transients موقت
        delete_transient('market_google_stats');
        delete_transient('market_google_cities');
    }
}