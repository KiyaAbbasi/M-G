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
        $atts = shortcode_atts([
            'height' => '500',
            'theme' => 'default',
        ], $atts);

        // در آینده، این بخش از یک فایل template برای نمایش محتوا استفاده خواهد کرد
        ob_start();

        echo "<h2>فرم ثبت موقعیت مکانی</h2>";
        echo "<p>این فرم به زودی با ظاهر جدید و امکانات کامل در اینجا نمایش داده خواهد شد.</p>";
        echo "<div id='market-google-map' style='height: " . esc_attr($atts['height']) . "px; background: #eee;'></div>";

        return ob_get_clean();
    }
}
