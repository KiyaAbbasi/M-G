<?php
namespace MarketGoogle\Ajax;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AdminAjax
 *
 * مدیریت درخواست‌های Ajax بخش مدیریت
 *
 * @package MarketGoogle\Ajax
 */
class AdminAjax {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('wp_ajax_market_google_save_settings', [$this, 'save_settings']);
        add_action('wp_ajax_market_google_search_orders', [$this, 'search_orders']);
    }

    /**
     * ذخیره تنظیمات
     */
    public function save_settings() {
        // بررسی امنیتی
        check_ajax_referer('market_google_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        // دریافت و ذخیره تنظیمات
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        update_option('market_google_settings', $settings);

        wp_send_json_success(['message' => 'تنظیمات با موفقیت ذخیره شد.']);
    }

    /**
     * جستجوی سفارشات
     */
    public function search_orders() {
        // بررسی امنیتی
        check_ajax_referer('market_google_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز.']);
        }

        // منطق جستجو در آینده اینجا پیاده‌سازی خواهد شد

        wp_send_json_success(['orders' => []]);
    }
}
