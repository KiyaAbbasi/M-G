<?php
namespace MarketGoogle\Gateway;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Zarinpal
 *
 * مدیریت درگاه پرداخت زرین‌پال
 *
 * @package MarketGoogle\Gateway
 */
class Zarinpal {

    /**
     * ایجاد درخواست پرداخت
     *
     * @param int $location_id
     * @param int $amount
     * @param array $location_data
     * @return array
     */
    public function create_payment_request($location_id, $amount, $location_data) {
        $options = get_option('market_google_payment_settings', []);
        $merchant_id = $options['zarinpal_merchant_id'] ?? '';

        if (empty($merchant_id)) {
            return ['success' => false, 'message' => 'اطلاعات درگاه زرین‌پال کامل نیست.'];
        }

        $callback_url = add_query_arg(['gateway' => 'zarinpal', 'location_id' => $location_id], home_url('/'));
        $description = 'پرداخت سفارش شماره ' . $location_id;

        $request_data = [
            'merchant_id' => $merchant_id,
            'amount' => $amount,
            'callback_url' => $callback_url,
            'description' => $description,
        ];

        $response = wp_remote_post('https://api.zarinpal.com/pg/v4/payment/request.json', [
            'body' => json_encode($request_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'خطا در اتصال به درگاه: ' . $response->get_error_message()];
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['data']['authority'])) {
            // ذخیره توکن
            update_post_meta($location_id, '_payment_token', $result['data']['authority']);
            return [
                'success' => true,
                'redirect_url' => 'https://www.zarinpal.com/pg/StartPay/' . $result['data']['authority']
            ];
        } else {
            return ['success' => false, 'message' => 'خطا از درگاه: ' . ($result['errors']['message'] ?? 'نامشخص')];
        }
    }

    /**
     * تایید پرداخت
     *
     * @param array $request_data
     */
    public function verify_payment($request_data) {
        // منطق تایید پرداخت در این بخش پیاده‌سازی خواهد شد
    }
}
