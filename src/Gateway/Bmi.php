<?php
namespace MarketGoogle\Gateway;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Bmi
 *
 * مدیریت درگاه پرداخت بانک ملی (سداد)
 *
 * @package MarketGoogle\Gateway
 */
class Bmi {

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
        $terminal_id = $options['bmi_terminal_id'] ?? '';
        $merchant_id = $options['bmi_merchant_id'] ?? '';
        $secret_key = $options['bmi_secret_key'] ?? '';

        if (empty($terminal_id) || empty($merchant_id) || empty($secret_key)) {
            return ['success' => false, 'message' => 'اطلاعات درگاه بانک ملی کامل نیست.'];
        }

        $order_id = $location_id; // استفاده از شناسه سفارش
        $callback_url = add_query_arg(['gateway' => 'bmi', 'location_id' => $location_id], home_url('/'));

        $sign_data_string = "{$terminal_id};{$order_id};{$amount}";
        $sign_data = $this->encrypt($sign_data_string, $secret_key);

        $request_data = [
            'MerchantID' => $merchant_id,
            'TerminalId' => $terminal_id,
            'Amount' => $amount,
            'OrderId' => $order_id,
            'LocalDateTime' => date('Ymdhis'),
            'ReturnUrl' => $callback_url,
            'SignData' => $sign_data,
        ];

        $response = wp_remote_post('https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest', [
            'body' => json_encode($request_data),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'message' => 'خطا در اتصال به درگاه: ' . $response->get_error_message()];
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($result['ResCode']) && $result['ResCode'] === 0) {
            // ذخیره توکن
            update_post_meta($location_id, '_payment_token', $result['Token']);
            return [
                'success' => true,
                'redirect_url' => 'https://sadad.shaparak.ir/VPG/Purchase?Token=' . $result['Token']
            ];
        } else {
            return ['success' => false, 'message' => 'خطا از درگاه: ' . ($result['Description'] ?? 'نامشخص')];
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

    /**
     * رمزنگاری داده‌ها برای سداد
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    private function encrypt($data, $key) {
        $key = base64_decode($key);
        $encrypted = openssl_encrypt($data, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }
}
