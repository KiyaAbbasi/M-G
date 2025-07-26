<?php
namespace MarketGoogle\Public;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Assets
 *
 * مدیریت فایل‌های CSS و JavaScript بخش عمومی
 *
 * @package MarketGoogle\Public
 */
class Assets {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * بارگذاری فایل‌های CSS و JavaScript
     */
    public function enqueue_assets() {
        global $post;

        // فقط در صورتی که شورت‌کد در صفحه وجود داشته باشد، فایل‌ها را بارگذاری کن
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'market_location_form')) {

            // بارگذاری Leaflet (نقشه)
            wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
            wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);

            // بارگذاری استایل اصلی بخش عمومی
            wp_enqueue_style(
                'market-google-public-style',
                MARKET_GOOGLE_LOCATION_ASSETS_URL . 'css/public.css',
                ['leaflet-css'],
                MARKET_GOOGLE_LOCATION_VERSION
            );

            // بارگذاری اسکریپت اصلی بخش عمومی
            wp_enqueue_script(
                'market-google-public-script',
                MARKET_GOOGLE_LOCATION_ASSETS_URL . 'js/public.js',
                ['jquery', 'leaflet-js'],
                MARKET_GOOGLE_LOCATION_VERSION,
                true
            );

            // ارسال داده به جاوا اسکریپت
            wp_localize_script('market-google-public-script', 'marketPublic', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('market_google_nonce'),
            ]);
        }
    }
}
