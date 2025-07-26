<?php
namespace MarketGoogle\Services\Sms;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class SmsService
 *
 * سرویس مدیریت و ارسال پیامک‌های رویدادی
 *
 * @package MarketGoogle\Services\Sms
 */
class SmsService {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // ثبت هوک‌های مربوط به رویدادها
        add_action('market_google_payment_success', [$this, 'handle_payment_success'], 10, 2);
        add_action('market_google_payment_failure', [$this, 'handle_payment_failure'], 10, 2);
        // ... سایر هوک‌ها
    }

    /**
     * ارسال پیامک بر اساس رویداد
     *
     * @param string $event_type نوع رویداد
     * @param string $mobile شماره موبایل
     * @param array $data داده‌های مورد نیاز برای متن پیامک
     * @return bool|array
     */
    public function send_event_sms($event_type, $mobile, $data = []) {
        $sms_settings = get_option('market_google_sms_settings', []);

        // بررسی فعال بودن رویداد
        if (!isset($sms_settings['events'][$event_type]['enabled']) || !$sms_settings['events'][$event_type]['enabled']) {
            return false;
        }

        $message_template = $sms_settings['events'][$event_type]['value'] ?? '';
        if (empty($message_template)) {
            return false;
        }

        // جایگزینی shortcode ها
        $message = $this->replace_shortcodes($message_template, $data);

        // انتخاب ارائه‌دهنده و ارسال
        $provider = $sms_settings['provider'] ?? 'melipayamak';

        if ($provider === 'melipayamak') {
            $melipayamak = new Melipayamak();
            return $melipayamak->send($mobile, $message);
        }
        // ... سایر ارائه‌دهندگان

        return false;
    }

    /**
     * جایگزینی کدهای کوتاه در متن پیامک
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    private function replace_shortcodes($template, $data) {
        $replacements = [
            '{full_name}' => $data['full_name'] ?? '',
            '{order_number}' => $data['order_number'] ?? '',
            '{ref_id}' => $data['ref_id'] ?? '',
        ];
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * مدیریت رویداد پرداخت موفق
     */
    public function handle_payment_success($payment_data, $location_data) {
        $this->send_event_sms('payment_success', $location_data['phone'], array_merge($payment_data, $location_data));
    }

    /**
     * مدیریت رویداد پرداخت ناموفق
     */
    public function handle_payment_failure($payment_data, $location_data) {
        $this->send_event_sms('payment_failure', $location_data['phone'], array_merge($payment_data, $location_data));
    }
}
