<?php
namespace MarketGoogle\Services\Sms;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Melipayamak
 *
 * مدیریت ارتباط با وب‌سرویس ملی پیامک
 *
 * @package MarketGoogle\Services\Sms
 */
class Melipayamak {

    /**
     * ارسال پیامک
     *
     * @param string $mobile
     * @param string $message
     * @return bool
     */
    public function send($mobile, $message) {
        $sms_settings = get_option('market_google_sms_settings', []);
        $username = $sms_settings['username'] ?? '';
        $password = $sms_settings['password'] ?? '';
        $line_number = $sms_settings['line_number'] ?? '';

        if (empty($username) || empty($password) || empty($line_number)) {
            return false;
        }

        $data = [
            'username' => $username,
            'password' => $password,
            'to' => $mobile,
            'from' => $line_number,
            'text' => $message,
            'isflash' => false,
        ];

        $response = wp_remote_post('https://rest.payamak-panel.com/api/SendSMS/SendSMS', [
            'body' => $data,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            // ثبت خطا در لاگ
            error_log('Melipayamak SMS Error: ' . $response->get_error_message());
            return false;
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        return isset($result['RetStatus']) && $result['RetStatus'] === 1;
    }
}
