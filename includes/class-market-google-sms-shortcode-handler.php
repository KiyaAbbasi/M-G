<?php
/**
 * Market Google SMS Shortcode Handler
 * 
 * مدیریت کدهای کوتاه پیامک
 * 
 * @package Market_Google_Location
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Market_Google_SMS_Shortcode_Handler')) {

class Market_Google_SMS_Shortcode_Handler {

    /**
     * Instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the handler
     */
    public static function init() {
        $instance = self::get_instance();
        // هیچ hook خاصی نیاز نیست
    }

    /**
     * دریافت لیست کدهای کوتاه
     */
    public function get_shortcodes() {
        return array(
            '{full_name}' => 'نام کامل',
            '{phone}' => 'شماره تماس',
            '{business_name}' => 'نام کسب‌وکار',
            '{business_phone}' => 'تلفن کسب‌وکار',
            '{address}' => 'آدرس',
            '{selected_products}' => 'محصولات انتخابی',
            '{price}' => 'مبلغ',
            '{payment_amount}' => 'مبلغ پرداخت', // مشابه price
            '{order_id}' => 'شماره سفارش', // مشابه order_number
            '{order_number}' => 'شماره سفارش',
            '{payment_authority}' => 'کد پیگیری پرداخت',
            '{transaction_id}' => 'شناسه تراکنش',
            '{payment_date}' => 'تاریخ پرداخت',
            '{ref_id}' => 'شماره مرجع',
            '{login_code}' => 'کد ورود',
            '{payment_status}' => 'وضعیت پرداخت',
            '{failure_reason}' => 'دلیل عدم موفقیت',
            '{amount}' => 'مبلغ', // مشابه price
            '{user_name}' => 'نام کاربر', // مشابه full_name
            '{error}' => 'پیام خطا' // مشابه failure_reason
        );
    }
    
    public function replace_shortcodes($message, $data = array()) {
        // نقشه‌گذاری کدهای کوتاه مشابه به کلیدهای اصلی
        $shortcode_mapping = array(
            'payment_amount' => 'amount',
            'order_id' => 'order_number',
            'user_name' => 'full_name',
            'error' => 'failure_reason'
        );
        
        // لیست کامل کدهای کوتاه قابل استفاده
        $all_shortcodes = array(
            'full_name',
            'phone', 
            'business_name',
            'business_phone',
            'address',
            'selected_products',
            'price',
            'amount',
            'payment_amount',
            'order_id',
            'order_number',
            'payment_authority',
            'transaction_id',
            'payment_date',
            'ref_id',
            'login_code',
            'payment_status',
            'failure_reason',
            'user_name',
            'error'
        );
        
        // جایگزینی کدهای کوتاه
        foreach ($all_shortcodes as $key) {
            $value = null;
            
            // ابتدا بررسی می‌کنیم که آیا مستقیماً در data موجود است
            if (isset($data[$key])) {
                $value = $data[$key];
            }
            // سپس بررسی می‌کنیم که آیا کلید mapping شده‌ای دارد
            elseif (isset($shortcode_mapping[$key]) && isset($data[$shortcode_mapping[$key]])) {
                $value = $data[$shortcode_mapping[$key]];
            }
            // یا بالعکس، mapping معکوس
            else {
                foreach ($shortcode_mapping as $mapped_key => $original_key) {
                    if ($original_key === $key && isset($data[$mapped_key])) {
                        $value = $data[$mapped_key];
                        break;
                    }
                }
            }
            
            // اگر مقدار پیدا نشد
            if ($value === null || $value === '') {
                // فقط full_name و user_name مقدار پیش‌فرض "کاربر" دارند (فقط اگر واقعاً خالی باشند)
                if (($key === 'full_name' || $key === 'user_name')) {
                    $value = 'کاربر';
                } else {
                    // سایر کدهای کوتاه اگر مقدار نداشته باشند، نادیده گرفته می‌شوند
                    continue;
                }
            }
            
            // جایگزینی کد کوتاه با مقدار
            $message = str_replace('{' . $key . '}', $value, $message);
        }
        
        return $message;
    }
}
}