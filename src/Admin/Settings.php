<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * مدیریت صفحه تنظیمات افزونه
 *
 * @package MarketGoogle\Admin
 */
class Settings {

    /**
     * رندر کردن صفحه تنظیمات
     */
    public function render_page() {
        // در آینده، این بخش شامل فرم تنظیمات با تب‌های مختلف خواهد بود
        // و از فایل template برای نمایش استفاده خواهد کرد.

        echo '<div class="wrap"><h1>تنظیمات افزونه</h1>';

        // نمونه‌ای از فرم تنظیمات
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('market_google_settings_group');
            do_settings_sections('market-google-settings');
            submit_button();
            ?>
        </form>
        <?php

        echo '</div>';
    }

    /**
     * ثبت تنظیمات
     * این متد در سازنده کلاس فراخوانی می‌شود تا تنظیمات را در وردپرس ثبت کند.
     */
    public function register_settings() {
        register_setting(
            'market_google_settings_group', // نام گروه تنظیمات
            'market_google_settings',      // نام option در دیتابیس
            [$this, 'sanitize_settings']   // تابع اعتبارسنجی
        );

        // اضافه کردن بخش‌های مختلف تنظیمات
        add_settings_section(
            'market_google_general_section',
            'تنظیمات عمومی',
            null,
            'market-google-settings'
        );

        // اضافه کردن فیلدهای تنظیمات
        add_settings_field(
            'api_key',
            'Google Maps API Key',
            [$this, 'render_api_key_field'],
            'market-google-settings',
            'market_google_general_section'
        );
    }

    /**
     * اعتبارسنجی مقادیر تنظیمات قبل از ذخیره
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized_input = [];
        if (isset($input['api_key'])) {
            $sanitized_input['api_key'] = sanitize_text_field($input['api_key']);
        }
        return $sanitized_input;
    }

    /**
     * رندر کردن فیلد API Key
     */
    public function render_api_key_field() {
        $options = get_option('market_google_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        echo "<input type='text' name='market_google_settings[api_key]' value='" . esc_attr($api_key) . "' class='regular-text'>";
    }
}
