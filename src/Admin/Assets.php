<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Assets
 *
 * مدیریت فایل‌های CSS و JavaScript بخش مدیریت
 *
 * @package MarketGoogle\Admin
 */
class Assets {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * بارگذاری فایل‌های CSS و JavaScript
     *
     * @param string $hook
     */
    public function enqueue_assets($hook) {
        // بررسی اینکه آیا در صفحات مربوط به افزونه هستیم یا خیر
        if (strpos($hook, 'market-google') === false) {
            return;
        }

        // بارگذاری استایل اصلی ادمین
        wp_enqueue_style(
            'market-google-admin-style',
            MARKET_GOOGLE_LOCATION_ASSETS_URL . 'css/admin.css',
            [],
            MARKET_GOOGLE_LOCATION_VERSION
        );

        // بارگذاری اسکریپت اصلی ادمین
        wp_enqueue_script(
            'market-google-admin-script',
            MARKET_GOOGLE_LOCATION_ASSETS_URL . 'js/admin.js',
            ['jquery'],
            MARKET_GOOGLE_LOCATION_VERSION,
            true
        );

        // ارسال داده به جاوا اسکریپت
        wp_localize_script('market-google-admin-script', 'marketAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('market_google_admin_nonce'),
        ]);
    }
}
