<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Settings
 *
 * مدیریت صفحه تنظیمات افزونه با تب‌های مختلف
 *
 * @package MarketGoogle\Admin
 */
class Settings {

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * رندر کردن صفحه تنظیمات
     */
    public function render_page() {
        // استفاده از فایل قالب برای نمایش
        include_once MARKET_GOOGLE_LOCATION_PATH . 'templates/admin/settings-page.php';
    }

    /**
     * ثبت تنظیمات، بخش‌ها و فیلدها
     */
    public function register_settings() {
        // ثبت گروه اصلی تنظیمات
        register_setting(
            'market_google_settings_group',
            'market_google_settings',
            [$this, 'sanitize_general_settings']
        );

        register_setting(
            'market_google_settings_group',
            'market_google_payment_settings',
            [$this, 'sanitize_payment_settings']
        );

        register_setting(
            'market_google_settings_group',
            'market_google_sms_settings',
            [$this, 'sanitize_sms_settings']
        );

        // بخش تنظیمات عمومی
        add_settings_section(
            'market_google_general_section',
            'تنظیمات عمومی',
            null,
            'market-google-settings-general'
        );

        add_settings_field(
            'api_key', 'Google Maps API Key',
            [$this, 'render_text_field'],
            'market-google-settings-general', 'market_google_general_section',
            ['option_name' => 'market_google_settings', 'id' => 'api_key', 'label_for' => 'api_key']
        );

        // بخش تنظیمات پرداخت
        add_settings_section(
            'market_google_payment_section',
            'تنظیمات درگاه پرداخت',
            null,
            'market-google-settings-payment'
        );

        add_settings_field(
            'bmi_merchant_id', 'مرچنت کد بانک ملی',
            [$this, 'render_text_field'],
            'market-google-settings-payment', 'market_google_payment_section',
            ['option_name' => 'market_google_payment_settings', 'id' => 'bmi_merchant_id', 'label_for' => 'bmi_merchant_id']
        );

        // ... سایر فیلدهای پرداخت

        // بخش تنظیمات پیامک
        add_settings_section(
            'market_google_sms_section',
            'تنظیمات سیستم پیامک',
            null,
            'market-google-settings-sms'
        );

        add_settings_field(
            'sms_username', 'نام کاربری پنل پیامک',
            [$this, 'render_text_field'],
            'market-google-settings-sms', 'market_google_sms_section',
            ['option_name' => 'market_google_sms_settings', 'id' => 'username', 'label_for' => 'sms_username']
        );

        // ... سایر فیلدهای پیامک
    }

    /**
     * رندر کردن یک فیلد متنی عمومی
     */
    public function render_text_field($args) {
        $option_name = $args['option_name'];
        $id = $args['id'];
        $options = get_option($option_name);
        $value = isset($options[$id]) ? $options[$id] : '';
        echo "<input type='text' id='$id' name='{$option_name}[{$id}]' value='" . esc_attr($value) . "' class='regular-text'>";
    }

    /**
     * اعتبارسنجی تنظیمات عمومی
     */
    public function sanitize_general_settings($input) {
        $sanitized_input = [];
        // ...
        return $sanitized_input;
    }

    /**
     * اعتبارسنجی تنظیمات پرداخت
     */
    public function sanitize_payment_settings($input) {
        $sanitized_input = [];
        // ...
        return $sanitized_input;
    }

    /**
     * اعتبارسنجی تنظیمات پیامک
     */
    public function sanitize_sms_settings($input) {
        $sanitized_input = [];
        // ...
        return $sanitized_input;
    }
}
