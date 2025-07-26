<?php
namespace MarketGoogle\Core;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Activator
 *
 * این کلاس وظیفه اجرای عملیات مورد نیاز هنگام فعال‌سازی افزونه را بر عهده دارد.
 *
 * @package MarketGoogle\Core
 */
class Activator {

    /**
     * متد اصلی فعال‌سازی
     */
    public static function activate() {
        // فایل Migrations را به صورت دستی بارگذاری می‌کنیم تا در دسترس باشد
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Database/Migrations.php';

        // اجرای مایگریشن‌ها برای ایجاد یا به‌روزرسانی جداول
        \MarketGoogle\Database\Migrations::run();

        // تنظیم گزینه‌های پیش‌فرض
        self::set_default_options();

        // پاک کردن قوانین بازنویسی URL
        flush_rewrite_rules();
    }

    /**
     * تنظیم گزینه‌های پیش‌فرض افزونه
     */
    private static function set_default_options() {
        // تنظیمات عمومی
        add_option('market_google_settings', [
            'auto_approve' => false,
            'max_products' => 5,
        ], '', 'yes');

        // تنظیمات پرداخت
        add_option('market_google_payment_settings', [
            'bmi_terminal_id' => '',
            'bmi_merchant_id' => '',
            'bmi_secret_key' => '',
            'zarinpal_enabled' => true,
            'zarinpal_merchant_id' => '',
        ], '', 'yes');

        // تنظیمات پیامک
        add_option('market_google_sms_settings', [
            'provider' => 'melipayamak',
            'sending_method' => 'service',
            'username' => '',
            'password' => '',
            'api_key' => '',
            'line_number' => '',
            'events' => [
                'payment_success' => [
                    'enabled' => true,
                    'value' => '{full_name} عزیز، پرداخت شما به شماره پیگیری {ref_id} با موفقیت انجام شد. مارکت گوگل'
                ],
                'info_delivery' => [
                    'enabled' => true,
                    'value' => '{full_name} عزیز، شماره و آیدی تلگرام خدمت شما، 09355158614 @MarketGoogle_ir مارکت گوگل'
                ],
            ]
        ], '', 'yes');
    }
}
