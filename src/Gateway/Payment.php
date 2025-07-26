<?php
namespace MarketGoogle\Gateway;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Payment
 *
 * مدیریت کلی فرآیند پرداخت و ارتباط با درگاه‌ها
 *
 * @package MarketGoogle\Gateway
 */
class Payment {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        // این هوک در آینده برای مدیریت بازگشت از درگاه استفاده خواهد شد
        add_action('init', [$this, 'handle_payment_return']);
    }

    /**
     * شروع فرآیند پرداخت برای یک سفارش
     *
     * @param int $location_id شناسه سفارش
     * @param array $location_data اطلاعات سفارش
     * @return array نتیجه عملیات (شامل URL درگاه)
     */
    public function process_payment($location_id, $location_data) {
        $total_amount = (int) $location_data['price'];
        $gateway = 'bmi'; // درگاه پیش‌فرض

        // در آینده، انتخاب درگاه هوشمند خواهد شد
        if ($gateway === 'bmi') {
            $bmi_gateway = new Bmi();
            return $bmi_gateway->create_payment_request($location_id, $total_amount, $location_data);
        } elseif ($gateway === 'zarinpal') {
            $zarinpal_gateway = new Zarinpal();
            return $zarinpal_gateway->create_payment_request($location_id, $total_amount, $location_data);
        }

        return [
            'success' => false,
            'message' => 'هیچ درگاه پرداختی فعال نیست.'
        ];
    }

    /**
     * مدیریت بازگشت از درگاه پرداخت
     */
    public function handle_payment_return() {
        if (!isset($_GET['gateway']) || !isset($_GET['location_id'])) {
            return;
        }

        $gateway_name = sanitize_text_field($_GET['gateway']);
        $location_id = intval($_GET['location_id']);

        if ($gateway_name === 'bmi') {
            $bmi_gateway = new Bmi();
            $bmi_gateway->verify_payment($_GET);
        } elseif ($gateway_name === 'zarinpal') {
            $zarinpal_gateway = new Zarinpal();
            $zarinpal_gateway->verify_payment($_GET);
        }

        // پس از تایید پرداخت، کاربر به صفحه نتیجه هدایت می‌شود
    }
}
