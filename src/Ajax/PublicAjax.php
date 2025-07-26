<?php
namespace MarketGoogle\Ajax;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PublicAjax
 *
 * مدیریت درخواست‌های Ajax بخش عمومی
 *
 * @package MarketGoogle\Ajax
 */
class PublicAjax {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_submit_location_form', [$this, 'submit_location_form']);
        add_action('wp_ajax_submit_location_form', [$this, 'submit_location_form']);
    }

    /**
     * مدیریت ثبت نهایی فرم
     */
    public function submit_location_form() {
        // بررسی امنیتی
        check_ajax_referer('market_google_nonce', 'nonce');

        // اعتبارسنجی داده‌ها
        $form_data = $_POST;
        if (empty($form_data['business_name']) || empty($form_data['phone'])) {
            wp_send_json_error(['message' => 'لطفاً فیلدهای ضروری را پر کنید.']);
        }

        // ذخیره اطلاعات در دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        $location_data = [
            'full_name' => sanitize_text_field($form_data['full_name']),
            'phone' => sanitize_text_field($form_data['phone']),
            'business_name' => sanitize_text_field($form_data['business_name']),
            // ... سایر فیلدها
            'price' => 10000, // مبلغ تستی
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => current_time('mysql')
        ];

        $wpdb->insert($table_name, $location_data);
        $location_id = $wpdb->insert_id;

        if (!$location_id) {
            wp_send_json_error(['message' => 'خطا در ثبت اطلاعات.']);
        }

        // شروع فرآیند پرداخت
        $payment = new \MarketGoogle\Gateway\Payment();
        $payment_result = $payment->process_payment($location_id, $location_data);

        if ($payment_result['success']) {
            wp_send_json_success([
                'message' => 'در حال انتقال به درگاه پرداخت...',
                'redirect_url' => $payment_result['redirect_url']
            ]);
        } else {
            wp_send_json_error(['message' => $payment_result['message']]);
        }
    }
}
