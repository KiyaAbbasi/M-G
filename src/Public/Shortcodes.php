<?php
namespace MarketGoogle\Public;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Shortcodes
 *
 * مدیریت شورت‌کدهای افزونه
 *
 * @package MarketGoogle\Public
 */
class Shortcodes {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('init', [$this, 'register_shortcodes']);
    }

    /**
     * ثبت شورت‌کدهای افزونه
     */
    public function register_shortcodes() {
        add_shortcode('market_location_form', [$this, 'render_location_form']);
    }

    /**
     * رندر کردن شورت‌کد فرم ثبت موقعیت
     *
     * @param array $atts
     * @return string
     */
    public function render_location_form($atts) {
        // پارامترهای پیش‌فرض شورت‌کد
        $args = shortcode_atts([
            'height' => '500',
        ], $atts);

        ob_start();

        // بارگذاری فایل قالب و ارسال پارامترها به آن
        include_once MARKET_GOOGLE_LOCATION_PATH . 'templates/public/location-form.php';

        return ob_get_clean();
    }
}
