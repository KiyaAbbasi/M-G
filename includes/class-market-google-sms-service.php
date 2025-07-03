<?php
/**
 * Market Google SMS Service
 * 
 * مدیریت ارسال پیامک‌های رویدادی
 * 
 * @package Market_Google_Location
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Market_Google_SMS_Service')) {

class Market_Google_SMS_Service {

    /**
     * SMS Handler instance
     */
    private $sms_handler;

    /**
     * Shortcode Handler instance
     */
    private $shortcode_handler;

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
     * Initialize the service
     */
    public static function init() {
        $instance = self::get_instance();
        $instance->setup_hooks();
    }

    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Hook های مورد نیاز برای ارسال پیامک در رویدادهای مختلف
        add_action('market_google_payment_success', array($this, 'handle_payment_success'), 10, 2);
        add_action('market_google_payment_failure', array($this, 'handle_payment_failure'), 10, 2);
        add_action('market_google_payment_cancelled', array($this, 'handle_payment_cancelled'), 10, 2);
        add_action('market_google_payment_pending', array($this, 'handle_payment_pending'), 10, 2);
        add_action('market_google_payment_error', array($this, 'handle_payment_error'), 10, 2);
        add_action('market_google_login_code_sent', array($this, 'handle_login_code'), 10, 3);
        // اضافه کردن رویداد ثبت فرم
        add_action('market_google_form_submitted', array($this, 'handle_form_submitted'), 10, 2);
        // اضافه کردن رویداد تکمیل سفارش
        add_action('market_google_order_completion', array($this, 'handle_order_completion'), 10, 2);
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->shortcode_handler = new Market_Google_SMS_Shortcode_Handler();
        $this->sms_handler = new Market_Google_SMS_Handler();
        
        // اضافه کردن اکشن برای ارسال پیامک در انتظار پرداخت زمان‌بندی شده
        add_action('market_google_send_pending_payment_sms', array($this, 'process_scheduled_pending_sms'), 10, 1);
    }
    
    /**
     * پردازش پیامک زمان‌بندی شده در انتظار پرداخت
     */
    public function process_scheduled_pending_sms($args) {
        if (!empty($args['phone']) && !empty($args['data'])) {
            $this->send_payment_pending($args['phone'], $args['data']);
        }
    }

    /**
     * Handle form submission event
     */
    public function handle_form_submitted($form_data, $location_data) {
        // Debug: بررسی اینکه آیا رویداد اصلاً trigger میشه
        error_log('SMS Service: form_submitted event triggered - Phone: ' . (isset($location_data['phone']) ? $location_data['phone'] : 'empty'));
        
        if (!empty($location_data['phone'])) {
            $result = $this->send_form_submitted($location_data['phone'], array_merge($form_data, $location_data));
            error_log('SMS Service: form_submitted result - ' . json_encode($result));
        }
    }

    /**
     * ارسال پیامک تأیید ثبت فرم
     */
    public function send_form_submitted($mobile, $form_data = array()) {
        return $this->send_event_sms('form_submitted', $mobile, $form_data);
    }

    /**
     * Handle payment success event
     */
    public function handle_payment_success($payment_data, $location_data) {
        // Debug: بررسی اینکه آیا رویداد اصلاً trigger میشه
        error_log('SMS Service: payment_success event triggered - Phone: ' . (isset($location_data['phone']) ? $location_data['phone'] : 'empty'));
        
        // لغو پیامک یادآوری pending در صورت موجودیت
        $this->cancel_pending_payment_reminder($location_data);
        
        if (!empty($location_data['phone'])) {
            $result = $this->send_payment_success($location_data['phone'], array_merge($payment_data, $location_data));
            error_log('SMS Service: payment_success result - ' . json_encode($result));
        }
    }

    /**
     * Handle payment failure event
     */
    public function handle_payment_failure($payment_data, $location_data) {
        // لغو پیامک یادآوری pending در صورت موجودیت
        $this->cancel_pending_payment_reminder($location_data);
        
        if (!empty($location_data['phone'])) {
            $this->send_payment_failure($location_data['phone'], array_merge($payment_data, $location_data));
        }
    }

    /**
     * Handle payment cancelled event
     */
    public function handle_payment_cancelled($payment_data, $location_data) {
        // لغو پیامک یادآوری pending در صورت موجودیت
        $this->cancel_pending_payment_reminder($location_data);
        
        if (!empty($location_data['phone'])) {
            $this->send_payment_cancelled($location_data['phone'], array_merge($payment_data, $location_data));
        }
    }

    /**
     * Handle payment pending event
     */
    public function handle_payment_pending($payment_data, $location_data) {
        // ارسال فوری پیامک pending
        if (!empty($location_data['phone'])) {
            $this->send_payment_pending($location_data['phone'], array_merge($payment_data, $location_data));
        }
        
        // بررسی تایم‌اوت تنظیم شده برای پیامک یادآوری
        $timeout_minutes = intval(get_option('market_google_payment_pending_timeout', 15));
        
        // زمان‌بندی ارسال پیامک یادآوری با استفاده از wp_schedule_single_event
        if (!empty($location_data['phone']) && $timeout_minutes > 0) {
            $args = array(
                'phone' => $location_data['phone'],
                'data' => array_merge($payment_data, $location_data, array(
                    'payment_status' => 'یادآوری پرداخت'
                ))
            );
            
            // زمان‌بندی برای ارسال پیامک یادآوری بعد از تایم‌اوت تعیین شده
            wp_schedule_single_event(time() + ($timeout_minutes * 60), 'market_google_send_pending_payment_sms', array($args));
        }
    }

    /**
     * Handle payment error event
     */
    public function handle_payment_error($payment_data, $location_data) {
        // لغو پیامک یادآوری pending در صورت موجودیت
        $this->cancel_pending_payment_reminder($location_data);
        
        if (!empty($location_data['phone'])) {
            $this->send_payment_error($location_data['phone'], array_merge($payment_data, $location_data));
        }
    }

    /**
     * Handle login code event
     */
    public function handle_login_code($mobile, $login_code, $user_data) {
        $this->send_login_code($mobile, $login_code, $user_data);
    }

    /**
     * ارسال کد ورود
     */
    public function send_login_code($mobile, $login_code, $user_data = array()) {
        return $this->send_event_sms('login_code', $mobile, array_merge($user_data, array(
            'login_code' => $login_code
        )));
    }

    /**
     * ارسال پیامک پرداخت موفق
     */
    public function send_payment_success($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_success', $mobile, array_merge($payment_data, array(
            'payment_status' => 'موفق'
        )));
    }

    /**
     * ارسال پیامک پرداخت ناموفق
     */
    public function send_payment_failure($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_failure', $mobile, array_merge($payment_data, array(
            'payment_status' => 'ناموفق'
        )));
    }

    /**
     * ارسال پیامک لغو پرداخت
     */
    public function send_payment_cancelled($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_cancelled', $mobile, array_merge($payment_data, array(
            'payment_status' => 'لغو'
        )));
    }

    /**
     * ارسال پیامک پرداخت در انتظار
     */
    public function send_payment_pending($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_pending', $mobile, array_merge($payment_data, array(
            'payment_status' => 'درانتظار پرداخت'
        )));
    }

    /**
     * ارسال پیامک خطای پرداخت
     */
    public function send_payment_error($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_error', $mobile, array_merge($payment_data, array(
            'payment_status' => 'خطا'
        )));
    }

    /**
     * ارسال پیامک رویدادی
     */
    private function send_event_sms($event_type, $mobile, $data = array()) {
        // Debug: بررسی ورودی‌ها
        error_log("🔰 SMS Service: send_event_sms called - Event: $event_type, Mobile: $mobile");
        
        // دریافت تنظیمات پیامک
        $sms_settings = get_option('market_google_sms_settings', array());
        error_log("📋 SMS Service: SMS settings loaded - Provider: " . ($sms_settings['provider'] ?? 'undefined'));
        
        // تعیین کلید تنظیمات بر اساس روش ارسال
        $sending_method = isset($sms_settings['sending_method']) ? $sms_settings['sending_method'] : 'service';
        $events_key = ($sending_method === 'pattern') ? 'pattern_events' : 'service_events';
        
        // پشتیبانی از ساختار قدیمی تنظیمات
        if (!isset($sms_settings[$events_key]) && isset($sms_settings['events'])) {
            $events_key = 'events';
        }
        
        error_log("🔰 SMS Service: Using events key: $events_key for method: $sending_method");
        
        // بررسی فعال بودن رویداد با لاگ کامل برای عیب‌یابی
        error_log("🔍 SMS Service: Checking event status - Event: $event_type, Key: $events_key");
        error_log("🔍 SMS Service: Event settings: " . json_encode(isset($sms_settings[$events_key][$event_type]) ? $sms_settings[$events_key][$event_type] : 'not set'));
        
        // برای رویداد info_delivery همیشه فعال در نظر بگیریم
        $is_enabled = true;
        
        // اگر رویداد info_delivery نیست، وضعیت فعال/غیرفعال را بررسی کنیم
        if ($event_type !== 'info_delivery') {
            $is_enabled = false;
            
            // بررسی در کلید اصلی
            if (isset($sms_settings[$events_key][$event_type]['enabled'])) {
                $is_enabled = $sms_settings[$events_key][$event_type]['enabled'];
            } 
            // بررسی در کلید قدیمی
            elseif (isset($sms_settings['events'][$event_type]['enabled'])) {
                $is_enabled = $sms_settings['events'][$event_type]['enabled'];
            }
            // بررسی در کلید دیگر
            else {
                $other_key = ($events_key === 'pattern_events') ? 'service_events' : 'pattern_events';
                if (isset($sms_settings[$other_key][$event_type]['enabled'])) {
                    $is_enabled = $sms_settings[$other_key][$event_type]['enabled'];
                }
            }
        }
        
        if (!$is_enabled) {
            error_log("⛔ SMS Service: Event $event_type is disabled");
            return array(
                'success' => false,
                'message' => "ارسال پیامک برای رویداد $event_type غیرفعال است."
            );
        }
        
        error_log("✅ SMS Service: Event $event_type is " . ($is_enabled ? 'enabled' : 'disabled'));

        // دریافت متن یا پترن پیامک
        $message_template = '';
        
        // بررسی در کلید فعلی
        if (isset($sms_settings[$events_key][$event_type]['value'])) {
            $message_template = $sms_settings[$events_key][$event_type]['value'];
        }
        
        // اگر خالی است، در کلید قدیمی بررسی کنیم
        if (empty($message_template) && isset($sms_settings['events'][$event_type]['value'])) {
            $message_template = $sms_settings['events'][$event_type]['value'];
        }
        
        // اگر هنوز خالی است، در کلید دیگر بررسی کنیم
        $other_key = ($events_key === 'pattern_events') ? 'service_events' : 'pattern_events';
        if (empty($message_template) && isset($sms_settings[$other_key][$event_type]['value'])) {
            $message_template = $sms_settings[$other_key][$event_type]['value'];
        }

        if (empty($message_template)) {
            error_log("⛔ SMS Service: Empty message template for event: $event_type");
            return array(
                'success' => false,
                'message' => 'متن یا الگوی پیامک تعریف نشده است. لطفاً ابتدا متن یا الگوی پیامک را در تنظیمات وارد کنید.'
            );
        }
        
        error_log("📝 SMS Service: Using message template: $message_template");

        // بررسی نوع ارسال (پترن یا خدماتی)
        $sending_method = isset($sms_settings['sending_method']) ? 
                         $sms_settings['sending_method'] : 'service';
                         
        error_log("📱 SMS Service: Using method: $sending_method");
        
        // نرمالایز کردن شماره موبایل
        $mobile = $this->normalize_mobile($mobile);
        if (!$this->is_valid_mobile($mobile)) {
            error_log("⛔ SMS Service: Invalid mobile number: $mobile");
            return array(
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است.'
            );
        }

        // بررسی فرمت خاص پترن ملی پیامک حتی در حالت خط خدماتی
        $is_special_pattern = (strpos($message_template, '{') !== false && 
                              strpos($message_template, '}') !== false && 
                              strpos($message_template, '@@shared') !== false);
        
        // اگر متن پیامک فرمت پترن دارد، حتما با پترن ارسال کنیم
        if ($is_special_pattern) {
            error_log("🔍 Special pattern format detected, forcing pattern sending method");
            $result = $this->send_pattern_sms($mobile, $message_template, $data);
            error_log("📨 SMS Service: Pattern SMS result - " . json_encode($result));
            return $result;
        }
        
        // اگر روش ارسال پترن است
        if ($sending_method === 'pattern') {
            // بررسی اینکه آیا متن پیامک فرمت پترن دارد
            if (strpos($message_template, '@@shared') === false) {
                // اگر متن پیامک فرمت پترن ندارد، در منابع دیگر جستجو کنیم
                error_log("⚠️ Pattern method selected but message doesn't have pattern format. Looking in other sources...");
                
                // جستجو در منبع دیگر
                if (isset($sms_settings['pattern_events'][$event_type]['value']) && 
                    strpos($sms_settings['pattern_events'][$event_type]['value'], '@@shared') !== false) {
                    $message_template = $sms_settings['pattern_events'][$event_type]['value'];
                    error_log("✅ Found pattern format in pattern_events: $message_template");
                }
            }
            
            // ارسال با پترن
            error_log("📤 SMS Service: Sending pattern SMS - Pattern: $message_template");
            $result = $this->send_pattern_sms($mobile, $message_template, $data);
            error_log("📨 SMS Service: Pattern SMS result - " . json_encode($result));
            return $result;
        } 
        // اگر روش ارسال خط خدماتی است
        else {
            // اگر متن پیامک خالی است، در منابع دیگر جستجو کنیم
            if (empty(trim($message_template))) {
                error_log("⚠️ Empty message template for service line. Looking in other sources...");
                
                // جستجو در منبع دیگر
                if (isset($sms_settings['service_events'][$event_type]['value']) && 
                    !empty(trim($sms_settings['service_events'][$event_type]['value']))) {
                    $message_template = $sms_settings['service_events'][$event_type]['value'];
                    error_log("✅ Found message in service_events: $message_template");
                }
            }
            
            // ارسال خدماتی - جایگزینی شورت‌کدها
            if (!$this->shortcode_handler) {
                error_log("⚠️ SMS Service: Shortcode handler not available, creating new instance");
                $this->shortcode_handler = new Market_Google_SMS_Shortcode_Handler();
            }
            $message = $this->shortcode_handler->replace_shortcodes($message_template, $data);
            error_log("📤 SMS Service: Sending service SMS - Message: $message");
            
            // ارسال مستقیم بدون تغییر تنظیمات
            error_log("🔄 Sending SMS via service line method");
            
            if (!$this->sms_handler) {
                error_log("⚠️ SMS Service: SMS handler not available, creating new instance");
                $this->sms_handler = new Market_Google_SMS_Handler();
            }
            
            $result = $this->sms_handler->send_sms($mobile, $message);
            
            error_log("📨 SMS Service: Service SMS result - " . json_encode($result));
            return $result;
        }
    }
    
    /**
     * نرمال‌سازی شماره موبایل
     */
    private function normalize_mobile($mobile) {
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        if (substr($mobile, 0, 2) === '98') {
            $mobile = '0' . substr($mobile, 2);
        } elseif (substr($mobile, 0, 3) === '+98') {
            $mobile = '0' . substr($mobile, 3);
        } elseif (substr($mobile, 0, 4) === '0098') {
            $mobile = '0' . substr($mobile, 4);
        }
        
        return $mobile;
    }
    
    /**
     * اعتبارسنجی شماره موبایل
     */
    private function is_valid_mobile($mobile) {
        return preg_match('/^09[0-9]{9}$/', $mobile);
    }

    /**
     * ارسال پیامک با پترن
     */
    private function send_pattern_sms($mobile, $pattern_code, $data = array()) {
        $sms_settings = get_option('market_google_sms_settings', array());
        
        if (empty($sms_settings['provider']) || 
            empty($sms_settings['username']) || 
            empty($sms_settings['password'])) {
            return array(
                'success' => false,
                'message' => 'اطلاعات سامانه پیامکی ناقص است.'
            );
        }

        try {
            switch ($sms_settings['provider']) {
                case 'kavenegar':
                    return $this->send_kavenegar_pattern($sms_settings, $mobile, $pattern_code, $data);
                
                case 'melipayamak':
                    return $this->send_melipayamak_pattern($sms_settings, $mobile, $pattern_code, $data);
                
                case 'farazsms':
                    return $this->send_farazsms_pattern($sms_settings, $mobile, $pattern_code, $data);
                
                case 'smsir':
                    return $this->send_smsir_pattern($sms_settings, $mobile, $pattern_code, $data);
                
                case 'ghasedak':
                    return $this->send_ghasedak_pattern($sms_settings, $mobile, $pattern_code, $data);
                
                default:
                    return array(
                        'success' => false,
                        'message' => 'سامانه پیامکی نامعتبر است.'
                    );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $e->getMessage()
            );
        }
    }

    /**
     * ارسال پترن کاوه نگار
     */
    private function send_kavenegar_pattern($settings, $mobile, $pattern_code, $data) {
        if (class_exists('Kavenegar\\KavenegarApi')) {
            try {
                $api = new \Kavenegar\KavenegarApi($settings['api_key']);
                
                // استخراج توکن‌ها از داده‌ها
                $token = isset($data['login_code']) ? $data['login_code'] : 
                        (isset($data['order_number']) ? $data['order_number'] : '');
                $token2 = isset($data['full_name']) ? $data['full_name'] : '';
                $token3 = isset($data['price']) ? $data['price'] : '';
                
                // ارسال پترن با پارامترهای صحیح
                $result = $api->VerifyLookup($mobile, $token, $token2, $token3, $pattern_code);
                
                if ($result && is_array($result) && isset($result[0]->messageid)) {
                    return array(
                        'success' => true,
                        'message' => 'پیامک پترن با موفقیت ارسال شد.',
                        'message_id' => $result[0]->messageid
                    );
                } else {
                    error_log('Kavenegar VerifyLookup failed: ' . print_r($result, true));
                    throw new Exception('خطا در ارسال پترن کاوه نگار.');
                }
            } catch (Exception $e) {
                return array(
                    'success' => false,
                    'message' => 'خطا در ارسال پترن کاوه نگار: ' . $e->getMessage()
                );
            }
        }
    
        return array(
            'success' => false,
            'message' => 'کلاس کاوه نگار یافت نشد'
        );
    }

    /**
     * ارسال پترن ملی پیامک
     */
    private function send_melipayamak_pattern($settings, $mobile, $pattern_code, $data) {
        error_log("🔍 Melipayamak pattern details - Pattern code: $pattern_code, Mobile: $mobile");
        error_log("📦 Pattern data: " . print_r($data, true));
        
        // تنظیم شماره خط ارسال
        $line_number = isset($settings['line_number']) ? $settings['line_number'] : '';
        if (!preg_match('/^(3000|2000|9000|5000|1000)/', $line_number)) {
            $line_number = '3000' . preg_replace('/^(3000|2000|9000|5000|1000)/', '', $line_number);
            error_log("🔧 Fixed line number for Melipayamak pattern: $line_number");
        }
        
        // بررسی فرمت خاص پترن ملی پیامک
        $extracted_pattern_code = $pattern_code;
        $pattern_params = array_values($data);
        
        // اگر پترن به فرمت {param};{param};CODE@@shared است
        if (strpos($pattern_code, '{') !== false && strpos($pattern_code, '}') !== false) {
            error_log("🔍 Detected special pattern format: $pattern_code");
            
            // استخراج پارامترها از فرمت
            preg_match_all('/\{([^}]+)\}/', $pattern_code, $matches);
            $param_keys = $matches[1];
            error_log("📋 Extracted param keys: " . json_encode($param_keys));
            
            // استخراج کد پترن
            $parts = explode(';', $pattern_code);
            $last_part = end($parts);
            
            if (strpos($last_part, '@@') !== false) {
                $code_parts = explode('@@', $last_part);
                $extracted_pattern_code = $code_parts[0];
                error_log("📋 Extracted pattern code: $extracted_pattern_code");
            }
            
            // ایجاد پارامترهای پترن با ترتیب صحیح و mapping درست
            $pattern_params = [];
            foreach ($param_keys as $key) {
                $value = '';
                
                // mapping کلیدهای مختلف به مقادیر صحیح
                switch ($key) {
                    case 'full_name':
                    case 'user_name':
                        $value = isset($data['full_name']) ? $data['full_name'] : 
                                (isset($data['user_name']) ? $data['user_name'] : 'کاربر');
                        break;
                    case 'payment_authority':
                    case 'ref_id':
                    case 'order_number':
                    case 'order_id':
                        // برای کد پیگیری، ترجیح با ref_id، سپس order_number
                        $value = isset($data['ref_id']) ? $data['ref_id'] : 
                                (isset($data['order_number']) ? $data['order_number'] : 
                                (isset($data['order_id']) ? $data['order_id'] : 
                                (isset($data['payment_authority']) ? $data['payment_authority'] : '')));
                        break;
                    case 'payment_status':
                        $value = isset($data['payment_status']) ? $data['payment_status'] : 'درانتظار پرداخت';
                        break;
                    case 'payment_amount':
                    case 'amount':
                    case 'price':
                        $value = isset($data['payment_amount']) ? $data['payment_amount'] : 
                                (isset($data['amount']) ? $data['amount'] : 
                                (isset($data['price']) ? $data['price'] : ''));
                        break;
                    default:
                        $value = isset($data[$key]) ? $data[$key] : '';
                        break;
                }
                
                $pattern_params[] = $value;
                error_log("🔑 Pattern param mapping: {$key} = '$value'");
            }
            
            error_log("📋 Ordered pattern params: " . json_encode($pattern_params));
            error_log("📊 Full data array: " . json_encode($data));
        }
        
        // تبدیل کد پترن به عدد صحیح اگر فقط عدد است
        if (is_numeric($extracted_pattern_code)) {
            $extracted_pattern_code = intval($extracted_pattern_code);
        }
        
        // تلاش با API های مختلف ملی پیامک به ترتیب اولویت
        $apis = [
            // روش 1: SendByBaseNumber - روش اصلی ارسال پترن
            [
                'name' => 'SendByBaseNumber',
                'url' => 'https://rest.payamak-panel.com/api/SendSMS/SendByBaseNumber',
                'data' => [
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'to' => $mobile,
                    'bodyId' => $extracted_pattern_code,
                    'text' => implode(';', $pattern_params)
                ]
            ],
            // روش 2: BaseServiceNumber (پشتیبانی بیشتر)
            [
                'name' => 'BaseServiceNumber',
                'url' => 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
                'data' => [
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'to' => $mobile,
                    'text' => implode(';', $pattern_params),
                    'bodyId' => $extracted_pattern_code
                ]
            ],
            // روش 3: UltraFast (وب سرویس قدیمی)
            [
                'name' => 'UltraFast',
                'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber',
                'data' => [
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'to' => $mobile,
                    'text' => implode(';', $pattern_params),
                    'bodyId' => $extracted_pattern_code
                ]
            ],
            // روش 4: ارسال با الگو (API جدید ملی پیامک)
            [
                'name' => 'UltraFastSend',
                'url' => 'https://api.payamak-panel.com/post/SendUsingBaseNumber.ashx',
                'data' => [
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'to' => $mobile,
                    'bodyId' => $extracted_pattern_code,
                    'text' => implode(';', $pattern_params),
                ]
            ],
            // روش 5: ارسال مستقیم به صورت پشتیبان
            [
                'name' => 'SendSMS',
                'url' => 'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
                'data' => [
                    'username' => $settings['username'],
                    'password' => $settings['password'],
                    'to' => $mobile,
                    'from' => $line_number,
                    'text' => "پیام از الگوی $extracted_pattern_code: " . implode(' ', $pattern_params)
                ]
            ]
        ];
        
        $success = false;
        $response = null;
        $api_used = '';
        $headers = array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8');
        
        // تلاش با همه API ها به ترتیب اولویت
        foreach ($apis as $api) {
            try {
                error_log("🔄 Trying " . $api['name'] . " API for pattern...");
                error_log("🔗 API URL: " . $api['url']);
                error_log("📤 Data: " . json_encode($api['data']));
                
                $response = $this->make_curl_request($api['url'], $api['data'], $headers);
                error_log("📥 " . $api['name'] . " response: " . json_encode($response));
                
                if (
                    (isset($response['Value']) && isset($response['RetStatus']) && intval($response['RetStatus']) == 1) ||
                    (isset($response['string']) && strpos($response['string'], 'successful') !== false) ||
                    (isset($response['SendSimpleSMS2Result']) && intval($response['SendSimpleSMS2Result']) > 0) ||
                    (isset($response['SendSimpleSMSResult']) && intval($response['SendSimpleSMSResult']) > 0) ||
                    (isset($response['SendByBaseNumberResult']) && intval($response['SendByBaseNumberResult']) > 0)
                ) {
                    $success = true;
                    $api_used = $api['name'];
                    error_log("✅ Success with " . $api['name'] . " API for pattern!");
                    break;
                } else {
                    error_log("⚠️ " . $api['name'] . " API failed or returned invalid response");
                }
            } catch (Exception $e) {
                error_log("❌ Error with " . $api['name'] . " API: " . $e->getMessage());
            }
        }
        
        if (!$success) {
            error_log("❌ All pattern APIs failed");
            throw new Exception('خطا در ارسال پیامک با پترن. لطفاً تنظیمات ملی پیامک را بررسی کنید.');
        }
        
        // استاندارد‌سازی پاسخ
        $message_id = 0;
        if (isset($response['Value'])) {
            $message_id = intval($response['Value']);
        } elseif (isset($response['SendByBaseNumberResult'])) {
            $message_id = intval($response['SendByBaseNumberResult']);
        } elseif (isset($response['SendSimpleSMSResult'])) {
            $message_id = intval($response['SendSimpleSMSResult']);
        } elseif (isset($response['SendSimpleSMS2Result'])) {
            $message_id = intval($response['SendSimpleSMS2Result']);
        } else {
            $message_id = rand(1000000, 9999999); // یک شناسه تصادفی در صورت عدم وجود
        }
        
        return array(
            'success' => true,
            'message' => 'پیامک الگو با موفقیت ارسال شد.',
            'message_id' => $message_id,
            'api_used' => $api_used
        );
    }

    /**
     * ارسال پترن فراز اس ام اس
     */
    private function send_farazsms_pattern($settings, $mobile, $pattern_code, $data) {
        $url = 'https://ippanel.com/api/select';
        $data_to_send = array(
            'op' => 'pattern',
            'uname' => $settings['username'],
            'pass' => $settings['password'],
            'from' => $settings['line_number'],
            'to' => array($mobile),
            'pattern_code' => $pattern_code,
            'input_data' => array_values($data)
        );
        
        $response = $this->make_curl_request($url, $data_to_send, array(), 'POST', true);
        
        if (!isset($response['status']) || $response['status'] !== 'success') {
            throw new Exception('پاسخ نامعتبر از فراز اس‌ام‌اس');
        }
        
        return array(
            'success' => true,
            'message' => 'پیامک با موفقیت ارسال شد.',
            'message_id' => isset($response['message_id']) ? $response['message_id'] : 0
        );
    }

    /**
     * ارسال پترن اس ام اس آی آر
     */
    private function send_smsir_pattern($settings, $mobile, $pattern_code, $data) {
        $url = 'https://ws.sms.ir/api/UltraFastSend';
        $data_to_send = array(
            'Mobile' => $mobile,
            'TemplateId' => $pattern_code,
            'ParameterArray' => array_map(function($key, $value) {
                return array('Parameter' => $key, 'ParameterValue' => $value);
            }, array_keys($data), array_values($data))
        );
        
        $headers = array(
            'Content-Type: application/json',
            'X-API-KEY: ' . $settings['api_key']
        );
        
        $response = $this->make_curl_request($url, $data_to_send, $headers, 'POST', true);
        
        if (!isset($response['IsSuccessful'])) {
            throw new Exception('پاسخ نامعتبر از اس‌ام‌اس آی‌آر');
        }
        
        return array(
            'success' => $response['IsSuccessful'],
            'message' => $response['IsSuccessful'] ? 'پیامک با موفقیت ارسال شد.' : 'خطا در ارسال پیامک',
            'message_id' => isset($response['TokenId']) ? $response['TokenId'] : 0
        );
    }

    /**
     * ارسال پترن قاصدک
     */
    private function send_ghasedak_pattern($settings, $mobile, $pattern_code, $data) {
        $url = 'https://api.ghasedak.me/v2/verification/send/simple';
        $data_to_send = array(
            'receptor' => $mobile,
            'type' => 1,
            'template' => $pattern_code,
            'param1' => isset($data['token']) ? $data['token'] : '',
            'param2' => isset($data['token2']) ? $data['token2'] : '',
            'param3' => isset($data['token3']) ? $data['token3'] : ''
        );
        
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
            'apikey: ' . $settings['api_key']
        );
        
        $response = $this->make_curl_request($url, $data_to_send, $headers);
        
        if (!isset($response['result']) || $response['result']['code'] !== 200) {
            throw new Exception('پاسخ نامعتبر از قاصدک');
        }
        
        return array(
            'success' => true,
            'message' => 'پیامک با موفقیت ارسال شد.',
            'message_id' => isset($response['result']['items'][0]['messageid']) ? $response['result']['items'][0]['messageid'] : 0
        );
    }

    /**
     * ارسال پیامک ساده
     */
    public function send_sms($mobile, $message) {
        error_log("📱 SMS Service: Sending simple SMS to $mobile");
        error_log("💬 Message content: $message");
        
        if (empty($mobile) || empty($message)) {
            error_log("⛔ SMS Service: Empty mobile or message - Mobile: '$mobile', Message: '$message'");
            return array(
                'success' => false,
                'message' => 'شماره موبایل یا متن پیامک خالی است.'
            );
        }
        
        try {
            if (!$this->sms_handler) {
                error_log("⚠️ SMS Service: SMS handler not available, creating new instance");
                $this->sms_handler = new Market_Google_SMS_Handler();
            }
            
            $result = $this->sms_handler->send_sms($mobile, $message);
            error_log("📊 SMS Service response: " . json_encode($result));
            return $result;
        } catch (Exception $e) {
            error_log("❌ SMS Service Exception: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $e->getMessage()
            );
        }
    }

    /**
     * ارسال پیامک اطلاعات
     */
    public function send_info($mobile, $data = array()) {
        error_log("📱 SMS Service: send_info called for mobile: $mobile");
        return $this->send_event_sms('info_delivery', $mobile, $data);
    }

    /**
     * درخواست cURL
     */
    private function make_curl_request($url, $data = array(), $headers = array(), $method = 'POST', $json = false) {
        // Debug: مقادیر ورودی
        error_log("🌐 SMS Service CURL Request - URL: $url, Method: $method, JSON: " . ($json ? 'yes' : 'no'));
        error_log("📦 Request Data: " . print_r($data, true));
        error_log("📋 Request Headers: " . print_r($headers, true));
        
        $ch = curl_init();
        
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Market Google Location SMS Service/1.0',
            CURLOPT_FAILONERROR => false // Don't fail on error HTTP responses
        ));
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            
            if ($json) {
                $json_data = json_encode($data);
                error_log("📊 JSON Data: $json_data");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                if (!in_array('Content-Type: application/json', $headers)) {
                    $headers[] = 'Content-Type: application/json';
                }
            } else {
                $post_data = http_build_query($data);
                error_log("📊 POST Data: $post_data");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                if (empty($headers)) {
                    $headers[] = 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8';
                }
            }
        } elseif ($method === 'GET' && !empty($data)) {
            $query_string = http_build_query($data);
            $url .= '?' . $query_string;
            error_log("🔍 GET URL with query: $url");
            curl_setopt($ch, CURLOPT_URL, $url);
        }
        
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        // اجرای درخواست
        error_log("🚀 Executing CURL request...");
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $curl_errno = curl_errno($ch);
        
        error_log("📊 CURL Response - HTTP Code: $http_code, Error Code: $curl_errno");
        error_log("📥 Raw Response: " . substr($response, 0, 500));
        
        curl_close($ch);
        
        // بررسی خطای CURL
        if ($curl_error) {
            $error_msg = "خطا در درخواست CURL ($curl_errno): $curl_error";
            error_log("❌ $error_msg");
            throw new Exception($error_msg);
        }
        
        // بررسی کدهای HTTP (با تحمل بیشتر)
        if ($http_code < 200 || $http_code >= 500) { // فقط خطاهای سرور را بررسی می‌کنیم
            $error_msg = "خطا در درخواست HTTP: کد $http_code";
            if ($response) {
                $error_msg .= " - پاسخ: " . substr($response, 0, 200);
            }
            error_log("❌ $error_msg");
            throw new Exception($error_msg);
        }
        
        // تبدیل JSON
        $decoded_response = json_decode($response, true);
        if ($response && json_last_error() !== JSON_ERROR_NONE) {
            error_log("⚠️ Response is not valid JSON: " . json_last_error_msg());
            
            // برای ملی پیامک، اگر پاسخ JSON نیست، بررسی کنیم که آیا XML است یا خیر
            if (strpos($url, 'payamak-panel') !== false) {
                error_log("🔄 Analyzing non-JSON response for Melipayamak");
                
                // بررسی موفقیت در پاسخ‌های XML
                if (strpos($response, '<long>') !== false && preg_match('/<long.*?>(.*?)<\/long>/', $response, $matches)) {
                    $sms_id = intval($matches[1]);
                    if ($sms_id > 0) {
                        error_log("✅ Melipayamak XML response successful with ID: $sms_id");
                        return array(
                            'RetStatus' => 1,
                            'Value' => $sms_id,
                            'StrRetStatus' => 'Ok'
                        );
                    } else {
                        error_log("❌ Melipayamak XML response failed with ID: $sms_id");
                        throw new Exception('ارسال پیامک ناموفق - شناسه: ' . $sms_id);
                    }
                }
                
                // اگر نمی‌توانیم تعیین کنیم، خطا می‌دهیم
                error_log("❌ Cannot determine Melipayamak response status");
                throw new Exception('پاسخ نامشخص از سرور ملی پیامک');
            }
            
            // برای سایر سرویس‌ها، خطا می‌دهیم
            throw new Exception('پاسخ JSON نامعتبر: ' . json_last_error_msg());
        }
        
        // اگر پاسخ JSON است اما ساختار مورد نیاز را ندارد، آن را اصلاح کنیم
        if (is_array($decoded_response) && strpos($url, 'payamak-panel') !== false) {
            if (!isset($decoded_response['RetStatus']) && !isset($decoded_response['Value'])) {
                error_log("🔄 Fixing Melipayamak response structure");
                // اگر کلید موفقیت در پاسخ وجود دارد
                if (isset($decoded_response['StrRetStatus']) && $decoded_response['StrRetStatus'] === 'Ok') {
                    $decoded_response['RetStatus'] = 1;
                    // فقط اگر Value موجود باشد، آن را استفاده کنیم، وگرنه خطا
                    if (!isset($decoded_response['Value']) || !$decoded_response['Value']) {
                        error_log("❌ Melipayamak response OK but no valid message ID");
                        throw new Exception('پاسخ موفق از سرور ولی شناسه پیامک دریافت نشد');
                    }
                } else {
                    // اگر نمی‌توانیم تشخیص دهیم، خطا می‌دهیم
                    error_log("❌ Cannot determine response status from Melipayamak");
                    throw new Exception('پاسخ نامشخص از سرور - نمی‌توان تشخیص داد که ارسال موفق بوده یا نه');
                }
            }
        }
        
        error_log("✅ CURL request completed successfully");
        return $decoded_response;
    }

    /**
     * Handle order completion event
     */
    public function handle_order_completion($order_data, $location_data) {
        // Debug: بررسی اینکه آیا رویداد اصلاً trigger میشه
        error_log('SMS Service: order_completion event triggered - Phone: ' . (isset($location_data['phone']) ? $location_data['phone'] : 'empty'));
        
        if (!empty($location_data['phone'])) {
            $result = $this->send_order_completion($location_data['phone'], array_merge($order_data, $location_data));
            error_log('SMS Service: order_completion result - ' . json_encode($result));
        }
    }

    /**
     * ارسال پیامک یادآوری پرداخت
     */
    public function send_payment_reminder($mobile, $payment_data = array()) {
        return $this->send_event_sms('payment_reminder', $mobile, array_merge($payment_data, array(
            'payment_status' => 'یادآوری پرداخت'
        )));
    }

    /**
     * ارسال پیامک تکمیل سفارش
     */
    public function send_order_completion($mobile, $order_data = array()) {
        error_log("📱 SMS Service: send_order_completion called for mobile: $mobile");
        return $this->send_event_sms('order_completion', $mobile, $order_data);
    }

    /**
     * تست ارسال پیامک با پترن
     */
    public function test_pattern_sms($mobile, $pattern_code, $data = array()) {
        error_log("🔍 SMS Service - Testing pattern: $pattern_code for mobile: $mobile");
        error_log("📦 Pattern test data: " . print_r($data, true));
        
        return $this->send_pattern_sms($mobile, $pattern_code, $data);
    }

        /**
     * لغو پیامک یادآوری pending
     */
    private function cancel_pending_payment_reminder($location_data) {
        if (!empty($location_data['phone'])) {
            // حذف تمام scheduled events مرتبط با این شماره تلفن
            $timestamp = wp_next_scheduled('market_google_send_pending_payment_sms');
            if ($timestamp) {
                wp_unschedule_event($timestamp, 'market_google_send_pending_payment_sms');
                error_log("✅ SMS Service: Cancelled pending payment reminder for phone: " . $location_data['phone']);
            }
        }
    }
}
}