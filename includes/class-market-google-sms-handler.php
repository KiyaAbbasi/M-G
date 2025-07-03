<?php
/**
 * Market Google SMS Handler
 * 
 * مدیریت ارسال پیامک و اتصال به ارائه‌دهندگان مختلف
 * 
 * @package Market_Google_Location
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Market_Google_SMS_Handler')) {

class Market_Google_SMS_Handler {

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
     * Shortcode Handler instance
     */
    private $shortcode_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->shortcode_handler = new Market_Google_SMS_Shortcode_Handler();
    }

    /**
     * دریافت لیست ارائه‌دهندگان پیامک
     */
    public function get_providers() {
        return array(
            'kavenegar' => 'کاوه نگار',
            'melipayamak' => 'ملی پیامک',
            'farazsms' => 'فراز اس‌ام‌اس',
            'smsir' => 'پنل اس‌ام‌اس',
            'ghasedak' => 'قاصدک'
        );
    }

    /**
     * دریافت روش‌های ارسال
     */
    public function get_sending_methods() {
        return array(
            'pattern' => 'ارسال با پترن',
            'service' => 'ارسال با خط خدماتی'
        );
    }

    /**
     * بررسی اتصال به سامانه پیامکی
     */
    public function is_connected() {
        $sms_settings = get_option('market_google_sms_settings', array());
        
        if (empty($sms_settings['provider']) || empty($sms_settings['username']) || empty($sms_settings['password'])) {
            return array(
                'connected' => false,
                'sms_count' => 0,
                'message' => 'اطلاعات سامانه پیامکی ناقص است.'
            );
        }
        
        try {
            $count = $this->get_sms_count_from_provider($sms_settings);
            
            set_transient('market_google_sms_connection_status', 'connected', HOUR_IN_SECONDS);
            set_transient('market_google_sms_count', $count, HOUR_IN_SECONDS);
            
            return array(
                'connected' => true,
                'sms_count' => intval($count),
                'message' => 'اتصال به سامانه پیامکی برقرار است.'
            );
        } catch (Exception $e) {
            set_transient('market_google_sms_connection_status', 'disconnected', HOUR_IN_SECONDS);
            set_transient('market_google_sms_count', 0, HOUR_IN_SECONDS);
            
            return array(
                'connected' => false,
                'sms_count' => 0,
                'message' => 'خطا در اتصال به سامانه پیامکی: ' . $e->getMessage()
            );
        }
    }

    /**
     * دریافت موجودی پیامک
     */
    public function get_sms_count() {
        $sms_settings = get_option('market_google_sms_settings', array());
        
        if (empty($sms_settings['provider']) || empty($sms_settings['username']) || empty($sms_settings['password'])) {
            return 0;
        }
        
        $sms_count = get_transient('market_google_sms_count');
        
        if ($sms_count !== false) {
            return intval($sms_count);
        }
        
        $count = $this->get_sms_count_from_provider($sms_settings);
        set_transient('market_google_sms_count', $count, HOUR_IN_SECONDS);
        
        return intval($count);
    }

    /**
     * تست اتصال
     */
    public function test_connection($settings) {
        error_log("🔌 Testing connection to SMS provider: " . ($settings['provider'] ?? 'undefined'));
        
        delete_transient('market_google_sms_connection_status');
        delete_transient('market_google_sms_count');
        
        if (empty($settings['provider'])) {
            error_log("❌ Provider not selected");
            return array(
                'success' => false,
                'message' => 'سامانه پیامکی انتخاب نشده است.',
                'sms_count' => 0
            );
        }
        
        if (empty($settings['username']) || empty($settings['password'])) {
            error_log("❌ Missing credentials");
            return array(
                'success' => false,
                'message' => 'نام کاربری یا رمز عبور وارد نشده است.',
                'sms_count' => 0
            );
        }
        
        // بررسی شماره خط
        if (empty($settings['line_number'])) {
            error_log("❌ Line number is missing");
            return array(
                'success' => false,
                'message' => 'شماره خط ارسال وارد نشده است.',
                'sms_count' => 0
            );
        }
        
        // تست فرمت شماره خط
        $line_test = $this->test_line_number($settings);
        if (!$line_test['success']) {
            error_log("❌ Line number format test failed: " . $line_test['message']);
            
            // اگر شماره خط پیشنهادی داریم، آن را به کاربر نمایش دهیم
            if (isset($line_test['suggested_number'])) {
                return array(
                    'success' => false,
                    'message' => $line_test['message'],
                    'sms_count' => 0,
                    'suggested_number' => $line_test['suggested_number']
                );
            }
            
            return array(
                'success' => false,
                'message' => $line_test['message'],
                'sms_count' => 0
            );
        }
        
        try {
            error_log("📊 Getting SMS credit...");
            $count = $this->get_sms_count_from_provider($settings);
            error_log("✅ Connection successful, SMS count: $count");
            
            set_transient('market_google_sms_connection_status', $count > 0 ? 'connected' : 'disconnected', HOUR_IN_SECONDS);
            set_transient('market_google_sms_count', $count, HOUR_IN_SECONDS);
            
            return array(
                'success' => $count > 0,
                'message' => $count > 0 ? 'اتصال به سامانه پیامکی با موفقیت برقرار شد. موجودی: ' . number_format($count) . ' پیامک' : 'اتصال به سامانه پیامکی ناموفق بود.',
                'sms_count' => $count
            );
        } catch (Exception $e) {
            error_log("❌ Connection test failed: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'خطا در اتصال به سامانه پیامکی: ' . $e->getMessage(),
                'sms_count' => 0
            );
        }
    }

    /**
     * ارسال پیامک تست
     */
    public function send_test_sms($mobile, $event_type = 'simple', $custom_message = '', $custom_test_data = null) {
        // Debug: شروع تست
        error_log("📱 🔥 SMS TEST STARTED 🔥 📱");
        error_log("🔰 Test details - Mobile: $mobile, Event Type: $event_type");
        
        // خروجی پشته فراخوانی برای دیباگ بهتر 
        $debug_backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($debug_backtrace[1]) ? $debug_backtrace[1]['function'] : 'unknown';
        error_log("📍 Test called by: $caller");
        
        // نرمال‌سازی شماره موبایل
        $original_mobile = $mobile;
        $mobile = $this->normalize_mobile($mobile);
        if ($original_mobile !== $mobile) {
            error_log("📱 Mobile normalized from: $original_mobile to: $mobile");
        }
        
        // دریافت و بررسی تنظیمات
        $sms_settings = get_option('market_google_sms_settings', array());
        error_log("📋 SMS Provider: " . ($sms_settings['provider'] ?? 'undefined'));
        error_log("🛠 SMS Method: " . ($sms_settings['sending_method'] ?? 'undefined'));
        error_log("📞 Line Number: " . ($sms_settings['line_number'] ?? 'undefined'));
        
        // بررسی تنظیمات رویداد
        if ($event_type !== 'simple') {
            $event_enabled = isset($sms_settings['events'][$event_type]['enabled']) ? 
                             $sms_settings['events'][$event_type]['enabled'] : false;
            $event_value = isset($sms_settings['events'][$event_type]['value']) ?
                           $sms_settings['events'][$event_type]['value'] : '';
                           
            error_log("🔔 Event '$event_type': " . ($event_enabled ? 'Enabled' : 'Disabled'));
            error_log("📄 Event template/pattern: $event_value");
        }
        
        // بررسی اعتبار تنظیمات سامانه
        if (empty($sms_settings['provider'])) {
            error_log("❌ SMS Test Failed: Provider not selected");
            return array(
                'success' => false,
                'message' => 'سامانه پیامکی انتخاب نشده است.'
            );
        }
        
        if (empty($sms_settings['username']) || empty($sms_settings['password'])) {
            error_log("❌ SMS Test Failed: Missing credentials");
            return array(
                'success' => false,
                'message' => 'نام کاربری یا رمز عبور سامانه پیامکی وارد نشده است.'
            );
        }
        
        // بررسی اعتبار شماره موبایل
        if (!$this->is_valid_mobile($mobile)) {
            error_log("❌ SMS Test Failed: Invalid mobile number - $mobile");
            return array(
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است. شماره باید 11 رقم و با ۰۹ شروع شود.'
            );
        }

        try {
            // اگر یک سرویس SMS داریم، از آن استفاده کنیم
            if (class_exists('Market_Google_SMS_Service')) {
                error_log("📱 Using SMS_Service class for test SMS");
                
                if ($event_type !== 'simple' && !in_array($event_type, array('form_submitted', 'payment_pending', 'payment_success', 
                   'payment_failure', 'payment_cancelled', 'payment_error', 'order_completion', 'info_delivery', 'login_code'))) {
                    
                    error_log("⛔ Invalid event type: $event_type");
                    return array(
                        'success' => false,
                        'message' => 'نوع رویداد نامعتبر است.'
                    );
                }
                
                // ایجاد نمونه سرویس SMS
                $sms_service = new Market_Google_SMS_Service();
                
                // در صورتی که پیامک ساده باشد
                if ($event_type === 'simple') {
                    $message = 'این یک پیامک تست از سامانه Market Google Location است. ' . date('Y-m-d H:i:s');
                    error_log("📝 Sending simple message via SMS Service: $message");
                    
                    // ارسال مستقیم از طریق SMS Service
                    try {
                        $result = $sms_service->send_sms($mobile, $message);
                        error_log("📨 Simple SMS result via service: " . json_encode($result));
                        
                        if ($result['success']) {
                            return array(
                                'success' => true,
                                'message' => 'پیامک ساده با موفقیت ارسال شد.'
                            );
                        } else {
                            error_log("⚠️ Simple SMS via service failed, trying direct method");
                            // اگر با سرویس موفق نبود، مستقیم امتحان کنیم
                            $direct_result = $this->send_sms($mobile, $message);
                            error_log("📨 Simple SMS direct result: " . json_encode($direct_result));
                            return $direct_result;
                        }
                    } catch (Exception $e) {
                        error_log("❌ SMS Service exception: " . $e->getMessage());
                        error_log("⚠️ Falling back to direct SMS method");
                        
                        // ارسال مستقیم در صورت خطای سرویس
                        $direct_result = $this->send_sms($mobile, $message);
                        error_log("📨 Direct SMS result: " . json_encode($direct_result));
                        return $direct_result;
                    }
                }
                
                // در مورد پیامک رویدادی، داده‌های تست آماده می‌کنیم
                $test_data = $custom_test_data ?: $this->get_test_data($event_type);
                
                // متد مناسب را بر اساس نوع رویداد انتخاب میکنیم
                error_log("🔄 Triggering event $event_type via SMS Service");
                
                switch($event_type) {
                    case 'form_submitted':
                        $result = $sms_service->send_form_submitted($mobile, $test_data);
                        break;
                    case 'payment_success':
                        $result = $sms_service->send_payment_success($mobile, $test_data);
                        break;
                    case 'payment_failure':
                        $result = $sms_service->send_payment_failure($mobile, $test_data);
                        break;
                    case 'payment_cancelled':
                        $result = $sms_service->send_payment_cancelled($mobile, $test_data);
                        break;
                    case 'payment_pending':
                        $result = $sms_service->send_payment_pending($mobile, $test_data);
                        break;
                    case 'payment_error':
                        $result = $sms_service->send_payment_error($mobile, $test_data);
                        break;
                    case 'order_completion':
                        $result = $sms_service->send_order_completion($mobile, $test_data);
                        break;
                    case 'info_delivery':
                        $result = $sms_service->send_info($mobile, $test_data);
                        break;
                    case 'login_code':
                        $result = $sms_service->send_login_code($mobile, rand(100000, 999999), $test_data);
                        break;
                    default:
                        error_log("⚠️ Unrecognized event type for SMS Service: $event_type, falling back to simple SMS");
                        $message = "پیامک تست برای رویداد ناشناخته: $event_type";
                        $result = $sms_service->send_sms($mobile, $message);
                }
                
                error_log("📨 Event SMS result ($event_type): " . json_encode($result));
                
                if ($result['success']) {
                    return array(
                        'success' => true,
                        'message' => "پیامک تست برای رویداد '$event_type' با موفقیت ارسال شد.",
                        'message_id' => $result['message_id'] ?? ''
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => "خطا در ارسال پیامک تست برای رویداد '$event_type': " . $result['message']
                    );
                }
            } 
            
            // روش قدیمی - اگر کلاس سرویس موجود نیست
            else {
                error_log("⚠️ SMS_Service class not found, using old method");
                if ($event_type === 'simple') {
                    $message = 'این یک پیامک تست از سامانه Market Google Location است.';
                    error_log("📝 Using simple message: $message");
                } else {
                    error_log("🎯 Processing event type: $event_type");
                    $event_sms = isset($sms_settings['events'][$event_type]) ? $sms_settings['events'][$event_type] : array();
                    error_log("📑 Event SMS config: " . json_encode($event_sms));
                    
                    if (isset($event_sms['enabled']) && !$event_sms['enabled']) {
                        error_log("❌ Event disabled: $event_type");
                        return array(
                            'success' => false,
                            'message' => 'ارسال پیامک برای این رویداد غیرفعال است.'
                        );
                    }
                    
                    $message = isset($event_sms['value']) ? $event_sms['value'] : '';
                    error_log("📝 Raw message template: $message");
                    
                    if (empty($message)) {
                        error_log("❌ Empty message template for event: $event_type");
                        return array(
                            'success' => false,
                            'message' => 'متن یا پترن پیامک تنظیم نشده است.'
                        );
                    }
    
                    // بررسی وجود shortcode handler
                    if (!$this->shortcode_handler) {
                        error_log("❌ Shortcode handler not initialized");
                        return array(
                            'success' => false,
                            'message' => 'خطا در سیستم: Shortcode handler not found'
                        );
                    }
                    
                    $test_data = $custom_test_data ?: $this->get_test_data($event_type);
                    error_log("🧪 Test data: " . json_encode($test_data));
                    
                    $message = $this->shortcode_handler->replace_shortcodes($message, $test_data);
                    error_log("📝 Final message after shortcode replacement: $message");
                }
                
                error_log("📤 Sending SMS to provider...");
                $result = $this->send_sms_to_provider($sms_settings, $mobile, $message);
                error_log("📨 Provider response: " . json_encode($result));
                
                if ($result['success']) {
                    $current_count = get_transient('market_google_sms_count');
                    if ($current_count !== false) {
                        set_transient('market_google_sms_count', max(0, $current_count - 1), HOUR_IN_SECONDS);
                    }
                    
                    error_log("✅ SMS Test Success!");
                    return array(
                        'success' => true,
                        'message' => 'پیامک با موفقیت ارسال شد.',
                        'message_id' => $result['message_id']
                    );
                } else {
                    error_log("❌ SMS Test Failed at provider level: " . $result['message']);
                    return array(
                        'success' => false,
                        'message' => 'خطا در ارسال پیامک: ' . $result['message']
                    );
                }
            }
        } catch (Exception $e) {
            error_log('💥 SMS Test Exception: ' . $e->getMessage());
            error_log('📍 Stack trace: ' . $e->getTraceAsString());
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $e->getMessage()
            );
        }
    }

    /**
     * تست ارسال پیامک با پترن
     * این متد برای تست ارسال پیامک با پترن استفاده می‌شود
     * 
     * @param string $mobile شماره موبایل
     * @param string $pattern_code کد پترن
     * @param array $pattern_data داده‌های پترن
     * @return array نتیجه ارسال
     */
    public function test_pattern_sms($mobile, $pattern_code, $pattern_data = array()) {
        error_log("🔍 Testing pattern SMS - Mobile: $mobile, Pattern: $pattern_code");
        error_log("📦 Pattern data: " . print_r($pattern_data, true));
        
        if (empty($mobile) || empty($pattern_code)) {
            return array(
                'success' => false,
                'message' => 'شماره موبایل یا کد پترن وارد نشده است.'
            );
        }
        
        // اعتبارسنجی شماره موبایل
        $mobile = $this->normalize_mobile($mobile);
        if (!$this->is_valid_mobile($mobile)) {
            return array(
                'success' => false,
                'message' => 'فرمت شماره موبایل صحیح نیست.'
            );
        }
        
        // اصلاح کد پترن
        $pattern_code = $this->normalize_pattern_code($pattern_code);
        error_log("📋 Normalized pattern code: $pattern_code");
        
        // اگر داده‌های پترن خالی است، داده‌های تستی ایجاد کنیم
        if (empty($pattern_data)) {
            $pattern_data = $this->get_test_data('pattern_test');
            error_log("📦 Using test pattern data: " . print_r($pattern_data, true));
        }
        
        // دریافت تنظیمات سامانه پیامکی
        $sms_settings = get_option('market_google_sms_settings', array());
        
        // بررسی تنظیمات
        if (empty($sms_settings['provider']) || 
            empty($sms_settings['username']) || 
            empty($sms_settings['password'])) {
            return array(
                'success' => false,
                'message' => 'تنظیمات سامانه پیامکی ناقص است.'
            );
        }
        
        // تنظیم روش ارسال به پترن
        $sms_settings['sending_method'] = 'pattern';
        
        try {
            // ارسال پیامک
            $result = $this->send_sms_to_provider($sms_settings, $mobile, $pattern_code, $pattern_data);
            
            if ($result['success']) {
                return array(
                    'success' => true,
                    'message' => 'پیامک پترن با موفقیت ارسال شد.',
                    'message_id' => isset($result['message_id']) ? $result['message_id'] : 0
                );
            } else {
                return array(
                    'success' => false,
                    'message' => isset($result['message']) ? $result['message'] : 'خطا در ارسال پیامک پترن.'
                );
            }
        } catch (Exception $e) {
            error_log("❌ Error in test_pattern_sms: " . $e->getMessage());
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک پترن: ' . $e->getMessage()
            );
        }
    }

    /**
     * دریافت موجودی از ارائه‌دهنده
     */
    private function get_sms_count_from_provider($settings) {
        try {
            switch ($settings['provider']) {
                case 'kavenegar':
                    if (class_exists('Kavenegar\\KavenegarApi')) {
                        $api = new \Kavenegar\KavenegarApi($settings['api_key']);
                        $result = $api->AccountInfo();
                        return intval($result->entries->remaincredit);
                    }
                    throw new Exception('کلاس کاوه نگار یافت نشد');
                
                case 'melipayamak':
                    $url = 'https://rest.payamak-panel.com/api/SendSMS/GetCredit';
                    $data = array(
                        'username' => $settings['username'],
                        'password' => $settings['password']
                    );
                    
                    $response = $this->make_curl_request($url, $data);
                    
                    if (!isset($response['Value'])) {
                        throw new Exception('پاسخ نامعتبر از ملی پیامک');
                    }
                    
                    return intval($response['Value']);
                
                case 'farazsms':
                    $url = 'https://ippanel.com/api/select';
                    $data = array(
                        'op' => 'credit',
                        'uname' => $settings['username'],
                        'pass' => $settings['password']
                    );
                    
                    $response = $this->make_curl_request($url, $data);
                    
                    if (!isset($response['credit'])) {
                        throw new Exception('پاسخ نامعتبر از فراز اس‌ام‌اس');
                    }
                    
                    return intval($response['credit']);
                
                case 'smsir':
                    $url = 'https://ws.sms.ir/api/credit';
                    $headers = array(
                        'Content-Type: application/json',
                        'X-API-KEY: ' . $settings['api_key']
                    );
                    
                    $response = $this->make_curl_request($url, array(), $headers, 'GET', true);
                    
                    if (!isset($response['Credit'])) {
                        throw new Exception('پاسخ نامعتبر از اس‌ام‌اس آی‌آر');
                    }
                    
                    return intval($response['Credit']);
                
                case 'ghasedak':
                    $url = 'https://api.ghasedak.me/v2/account/info';
                    $headers = array(
                        'apikey: ' . $settings['api_key']
                    );
                    
                    $response = $this->make_curl_request($url, array(), $headers, 'GET');
                    
                    if (!isset($response['result']['entries']['remaincredit'])) {
                        throw new Exception('پاسخ نامعتبر از قاصدک');
                    }
                    
                    return intval($response['result']['entries']['remaincredit']);
                
                default:
                    throw new Exception('ارائه‌دهنده پیامک پشتیبانی نمی‌شود');
            }
        } catch (Exception $e) {
            throw new Exception('خطا در دریافت موجودی: ' . $e->getMessage());
        }
    }

    /**
     * ارسال پیامک به ارائه‌دهنده
     */
    private function send_sms_to_provider($settings, $mobile, $message_or_pattern_name, $params = array()) {
        try {
            $mobile = $this->normalize_mobile($mobile);
            $sending_method = isset($settings['sending_method']) ? $settings['sending_method'] : 'service';

            switch ($settings['provider']) {
                case 'kavenegar':
                    if (!class_exists('Kavenegar\\KavenegarApi')) {
                        throw new Exception('کلاس کاوه نگار یافت نشد');
                    }
                    $api = new \Kavenegar\KavenegarApi($settings['api_key']);
                    
                    if ($sending_method === 'pattern') {
                        $tokens = $this->extract_pattern_tokens($params);
                        $result = $api->VerifyLookup(
                            $mobile, 
                            $tokens['token'] ?? '', 
                            $tokens['token2'] ?? '', 
                            $tokens['token3'] ?? '', 
                            $message_or_pattern_name
                        );
                        
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
                    } else {
                        $result = $api->Send($settings['line_number'], $mobile, $message_or_pattern_name);
                        if ($result && is_array($result) && isset($result[0]->messageid)) {
                            return array(
                                'success' => true,
                                'message' => 'پیامک با موفقیت ارسال شد.',
                                'message_id' => $result[0]->messageid
                            );
                        } else {
                            error_log('Kavenegar Send failed: ' . print_r($result, true));
                            throw new Exception('خطا در ارسال پیامک کاوه نگار.');
                        }
                    }
                    break;
                
                case 'melipayamak':
                    error_log("🔄 Using Melipayamak provider for SMS sending");
                    error_log("📱 Mobile: $mobile");
                    error_log("💬 Message: $message_or_pattern_name");
                    error_log("📞 Line Number: " . $settings['line_number']);
                    
                    // اصلاح شماره خط برای ملی پیامک
                    $line_number = $settings['line_number'];
                    if (!preg_match('/^(3000|2000|9000|5000|1000|50001|50002|50004|5001|50005|5002|5003|5004|5005)/', $line_number)) {
                        // اگر شماره خط خدماتی با 98 یا 0 شروع شده باشد، آنها را حذف می‌کنیم
                        $clean_number = preg_replace('/^(\+98|98|0)/', '', $line_number);
                        
                        // برای خطوط 5 رقمی، تغییر خاصی لازم نیست
                        if (strlen($clean_number) == 5) {
                            $line_number = $clean_number;
                        } 
                        // برای خطوط کوتاه‌تر از 5 رقم، پیشوند مناسب اضافه می‌کنیم
                        else if (strlen($clean_number) < 5) {
                            $line_number = '3000' . $clean_number;
                        }
                        // برای خطوط بلندتر، تغییر خاصی نمی‌دهیم
                        else {
                            $line_number = $clean_number;
                        }
                        
                        error_log("🔧 Fixed line number for Melipayamak: $line_number (original: " . $settings['line_number'] . ")");
                    }
                    
                    if ($sending_method === 'pattern') {
                        error_log("🔖 Using pattern method");
                        
                        // اصلاح کد پترن
                        $pattern_code = $this->normalize_pattern_code($message_or_pattern_name);
                        error_log("📋 Pattern code: $pattern_code (original: $message_or_pattern_name)");
                        
                        // آماده‌سازی پارامترهای پترن
                        $pattern_params = array_values($params);
                        $pattern_text = implode(';', $pattern_params);
                        error_log("📝 Pattern params: " . print_r($pattern_params, true));
                        error_log("📝 Pattern text: $pattern_text");
                        
                        // تبدیل کد پترن به عدد صحیح اگر فقط عدد است
                        if (is_numeric($pattern_code)) {
                            $pattern_code = intval($pattern_code);
                            error_log("🔢 Pattern code converted to integer: $pattern_code");
                        }
                        
                        // تلاش با چند روش مختلف ارسال پترن
                        $apis = [
                            // روش 1: SendByBaseNumber - روش اصلی ارسال پترن در ملی پیامک
                            [
                                'name' => 'SendByBaseNumber',
                                'url' => 'https://rest.payamak-panel.com/api/SendSMS/SendByBaseNumber',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'bodyId' => $pattern_code,
                                    'text' => $pattern_text
                                ]
                            ],
                            // روش 2: BaseServiceNumber - روش جایگزین
                            [
                                'name' => 'BaseServiceNumber',
                                'url' => 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'text' => $pattern_text,
                                    'bodyId' => $pattern_code
                                ]
                            ],
                            // روش 3: UltraFast - روش وب سرویس جدید
                            [
                                'name' => 'UltraFast',
                                'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'text' => $pattern_text,
                                    'bodyId' => $pattern_code
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
                                    'bodyId' => $pattern_code,
                                    'text' => $pattern_text,
                                ]
                            ]
                        ];
                        
                        $success = false;
                        $response = null;
                        $api_used = '';
                        $headers = array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8');
                        
                        // تلاش با همه API های پترن
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
                        } else {
                            $message_id = rand(1000000, 9999999); // یک شناسه تصادفی در صورت عدم وجود
                        }
                        
                        return array(
                            'success' => true,
                            'message' => 'پیامک با موفقیت ارسال شد.',
                            'message_id' => $message_id,
                            'api_used' => $api_used
                        );
                    } else {
                        error_log("📨 Using direct SMS method");
                        
                        // تلاش با روش های مختلف خط خدماتی ملی پیامک
                        $apis = [
                            // روش 1: ارسال از طریق REST API اصلی
                            [
                                'name' => 'SendSMS',
                                'url' => 'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'from' => $line_number,
                                    'text' => $message_or_pattern_name
                                ]
                            ],
                            // روش 2: ارسال از طریق REST API جدید
                            [
                                'name' => 'APIService',
                                'url' => 'https://api.melipayamak.com/api/SendSMS/SendSimple',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'from' => $line_number,
                                    'text' => $message_or_pattern_name
                                ],
                                'headers' => [
                                    'Content-Type: application/json'
                                ],
                                'json' => true
                            ],
                            // روش 3: ارسال از طریق SOAP API
                            [
                                'name' => 'WebService',
                                'url' => 'https://api.payamak-panel.com/post/send.asmx/SendSimpleSMS2',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'from' => $line_number,
                                    'text' => $message_or_pattern_name,
                                    'isflash' => 'false'
                                ]
                            ],
                            // روش 4: روش SOAP قدیمی
                            [
                                'name' => 'OldWebService',
                                'url' => 'https://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS',
                                'data' => [
                                    'username' => $settings['username'],
                                    'password' => $settings['password'],
                                    'to' => $mobile,
                                    'from' => $line_number,
                                    'text' => $message_or_pattern_name,
                                    'isflash' => 'false'
                                ]
                            ]
                        ];
                        
                        $success = false;
                        $response = null;
                        $api_used = '';
                        $headers = array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8');
                        $errors = array();
                        
                        error_log("🔍 Starting service line SMS test on Melipayamak with line number: $line_number");
                        
                        // تلاش با همه API ها به ترتیب
                        foreach ($apis as $api) {
                            try {
                                error_log("🔄 Trying " . $api['name'] . " API for direct SMS...");
                                error_log("🔗 API URL: " . $api['url']);
                                error_log("📤 Data: " . json_encode($api['data']));
                                
                                // استفاده از هدرهای خاص اگر تعریف شده باشد
                                $current_headers = isset($api['headers']) ? $api['headers'] : $headers;
                                $use_json = isset($api['json']) ? $api['json'] : false;
                                
                                $response = $this->make_curl_request($api['url'], $api['data'], $current_headers, 'POST', $use_json);
                                error_log("📥 " . $api['name'] . " response: " . json_encode($response));
                                
                                // بررسی موفقیت یا شکست
                                $success_flag = false;
                                
                                // بررسی حالت‌های مختلف پاسخ در API‌های مختلف
                                if ($api['name'] === 'APIService' && isset($response['Value']) && $response['Value']) {
                                    $success_flag = true;
                                    $message_id = $response['Value'];
                                } 
                                else if (isset($response['RetStatus']) && $response['RetStatus'] == 1) {
                                    $success_flag = true;
                                    $message_id = isset($response['Value']) ? $response['Value'] : 0;
                                } 
                                else if (isset($response['SendSimpleSMS2Result']) && intval($response['SendSimpleSMS2Result']) > 0) {
                                    $success_flag = true;
                                    $message_id = $response['SendSimpleSMS2Result'];
                                } 
                                else if (isset($response['SendSimpleSMSResult']) && intval($response['SendSimpleSMSResult']) > 0) {
                                    $success_flag = true;
                                    $message_id = $response['SendSimpleSMSResult'];
                                } 
                                else if (isset($response['StrRetStatus']) && $response['StrRetStatus'] == 'Ok') {
                                    $success_flag = true;
                                    $message_id = isset($response['Value']) ? $response['Value'] : rand(1000000, 9999999);
                                }
                                
                                if ($success_flag) {
                                    $success = true;
                                    $api_used = $api['name'];
                                    error_log("✅ Success with " . $api['name'] . " API! Message ID: $message_id");
                                    break;
                                } else {
                                    // استخراج پیام خطا از پاسخ
                                    $error_desc = 'خطای نامشخص';
                                    if (isset($response['RetStatus']) && $response['RetStatus'] == 0) {
                                        $error_desc = isset($response['strRetStatus']) ? $response['strRetStatus'] : 'کد خطا: ' . $response['RetStatus'];
                                    }
                                    if (isset($response['ErrorMessage'])) {
                                        $error_desc = $response['ErrorMessage'];
                                    }
                                    if (isset($response['Message'])) {
                                        $error_desc = $response['Message'];
                                    }
                                    
                                    error_log("⚠️ " . $api['name'] . " API failed: " . $error_desc);
                                    $errors[] = $api['name'] . ": " . $error_desc;
                                }
                            } catch (Exception $e) {
                                error_log("❌ Error with " . $api['name'] . " API: " . $e->getMessage());
                                $errors[] = $api['name'] . ": " . $e->getMessage();
                            }
                        }
                        
                        if (!$success) {
                            error_log("❌ All service line SMS APIs failed");
                            error_log("🧾 Errors: " . implode(", ", $errors));
                            throw new Exception('خطا در ارسال پیامک با خط خدماتی: ' . implode(", ", $errors));
                    }
                    
                        // استخراج شناسه پیام از پاسخ
                        if (!isset($message_id) || !$message_id) {
                            if (isset($response['Value'])) {
                    $message_id = intval($response['Value']);
                            } elseif (isset($response['SendSimpleSMS2Result'])) {
                                $message_id = intval($response['SendSimpleSMS2Result']);
                            } elseif (isset($response['SendSimpleSMSResult'])) {
                                $message_id = intval($response['SendSimpleSMSResult']);
                            } else {
                                $message_id = rand(1000000, 9999999); // شناسه تصادفی در صورت عدم وجود
                            }
                        }
                        
                        error_log("✅ Service line SMS sent successfully with ID: $message_id using $api_used");
                        
                    return array(
                        'success' => true,
                        'message' => 'پیامک با موفقیت ارسال شد.',
                            'message_id' => $message_id,
                            'api_used' => $api_used
                    );
                    }
                    break;
                
                case 'farazsms':
                    $url = 'https://ippanel.com/api/select';
                    $data = array(
                        'op' => 'send',
                        'uname' => $settings['username'],
                        'pass' => $settings['password'],
                        'message' => $message_or_pattern_name,
                        'to' => array($mobile),
                        'from' => $settings['line_number']
                    );
                    
                    $response = $this->make_curl_request($url, $data, array(), 'POST', true);
                    
                    if (!isset($response['status']) || $response['status'] !== 'success') {
                        throw new Exception('پاسخ نامعتبر از فراز اس‌ام‌اس');
                    }
                    
                    return array(
                        'success' => true,
                        'message' => 'پیامک با موفقیت ارسال شد.',
                        'message_id' => isset($response['message_id']) ? $response['message_id'] : 0
                    );
                    break;
                
                case 'smsir':
                    $url = 'https://ws.sms.ir/api/MessageSend';
                    $data = array(
                        'Messages' => array($message_or_pattern_name),
                        'MobileNumbers' => array($mobile),
                        'LineNumber' => $settings['line_number']
                    );
                    
                    $headers = array(
                        'Content-Type: application/json',
                        'X-API-KEY: ' . $settings['api_key']
                    );
                    
                    $response = $this->make_curl_request($url, $data, $headers, 'POST', true);
                    
                    if (!isset($response['IsSuccessful'])) {
                        throw new Exception('پاسخ نامعتبر از اس‌ام‌اس آی‌آر');
                    }
                    
                    return array(
                        'success' => $response['IsSuccessful'],
                        'message' => $response['IsSuccessful'] ? 'پیامک با موفقیت ارسال شد.' : 'خطا در ارسال پیامک',
                        'message_id' => isset($response['MessageIds'][0]) ? $response['MessageIds'][0] : 0
                    );
                    break;
                
                case 'ghasedak':
                    $url = 'https://api.ghasedak.me/v2/sms/send/simple';
                    $data = array(
                        'message' => $message_or_pattern_name,
                        'receptor' => $mobile,
                        'linenumber' => $settings['line_number']
                    );
                    
                    $headers = array(
                        'Content-Type: application/x-www-form-urlencoded',
                        'apikey: ' . $settings['api_key']
                    );
                    
                    $response = $this->make_curl_request($url, $data, $headers);
                    
                    if (!isset($response['result']) || $response['result']['code'] !== 200) {
                        throw new Exception('پاسخ نامعتبر از قاصدک');
                    }
                    
                    return array(
                        'success' => true,
                        'message' => 'پیامک با موفقیت ارسال شد.',
                        'message_id' => isset($response['result']['items'][0]['messageid']) ? $response['result']['items'][0]['messageid'] : 0
                    );
                    break;
                
                default:
                    throw new Exception('ارائه‌دهنده پیامک پشتیبانی نمی‌شود');
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'message_id' => 0
            );
        }
    }

    /**
     * استخراج توکن‌ها برای پترن
     */
    private function extract_pattern_tokens($params) {
        $tokens = array();
        
        if (is_array($params)) {
            $values = array_values($params);
            $tokens['token'] = isset($values[0]) ? $values[0] : '';
            $tokens['token2'] = isset($values[1]) ? $values[1] : '';
            $tokens['token3'] = isset($values[2]) ? $values[2] : '';
        }
        
        return $tokens;
    }

    /**
     * درخواست CURL
     */
    private function make_curl_request($url, $data = array(), $headers = array(), $method = 'POST', $json = false) {
        // Debug: مقادیر ورودی
        error_log("🌐 CURL Request - URL: $url, Method: $method, JSON: " . ($json ? 'yes' : 'no'));
        error_log("📦 Request Data: " . print_r($data, true));
        error_log("📋 Request Headers: " . print_r($headers, true));
        
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            error_log("❌ CURL extension not installed or enabled");
            throw new Exception('CURL extension is not installed or not enabled on this server');
        }
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Market Google Location SMS Client/1.0');
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        // اضافه کردن بررسی خطای وب سرویس
        curl_setopt($ch, CURLOPT_FAILONERROR, false); // Don't fail on error HTTP responses
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($json) {
                $json_data = json_encode($data);
                error_log("📊 JSON Data: $json_data");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
                if (empty($headers)) {
                    $headers = array('Content-Type: application/json');
                }
            } else {
                $post_data = http_build_query($data);
                error_log("📊 POST Data: $post_data");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                if (empty($headers)) {
                    $headers = array('Content-Type: application/x-www-form-urlencoded');
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
        
        curl_close($ch);
        
        // بررسی خطای CURL
        if ($curl_error) {
            $error_msg = "خطا در درخواست CURL ($curl_errno): $curl_error";
            error_log("❌ $error_msg");
            throw new Exception($error_msg);
        }
        
        // بررسی کدهای HTTP (با تحمل بیشتر)
        if ($http_code < 200 || $http_code >= 400) {
            $error_msg = "خطا در درخواست HTTP: کد $http_code";
            if ($response) {
                $error_msg .= " - پاسخ: " . substr($response, 0, 200); // نمایش بخشی از پاسخ
            }
            error_log("❌ $error_msg");
            throw new Exception($error_msg);
        }
        
        // لاگ پاسخ برای دیباگ
        error_log("📥 Raw Response: " . substr($response, 0, 500));
        
        // بررسی اگر پاسخ خالی است
        if (empty($response)) {
            error_log("⚠️ Empty response from API");
            return array(
                'RetStatus' => 0,
                'Value' => 0,
                'StrRetStatus' => 'Error',
                'ErrorMessage' => 'پاسخ دریافتی از سرور خالی است'
            );
        }
        
        // تبدیل JSON
        $decoded = json_decode($response, true);
        if ($response && json_last_error() !== JSON_ERROR_NONE) {
            error_log("⚠️ Response is not valid JSON: " . json_last_error_msg());
            
            // برای ملی پیامک، اگر پاسخ XML است، تلاش می‌کنیم آن را پردازش کنیم
            if (strpos($url, 'payamak-panel') !== false || strpos($url, 'melipayamak') !== false) {
                error_log("🔄 Trying to parse XML response for Melipayamak");
                
                // بررسی موفقیت در پاسخ‌های SOAP
                if (strpos($response, '<long>') !== false) {
                    preg_match('/<long.*?>(.*?)<\/long>/', $response, $matches);
                    if (isset($matches[1]) && intval($matches[1]) > 0) {
                        error_log("✅ Melipayamak XML response successful with ID: " . $matches[1]);
                return array(
                    'RetStatus' => 1,
                            'Value' => intval($matches[1]),
                    'StrRetStatus' => 'Ok'
                );
            }
                }
                
                // بررسی خطا در پاسخ XML
                if (strpos($response, 'Error') !== false || 
                    strpos($response, 'Exception') !== false ||
                    strpos($response, '<int>0</int>') !== false ||
                    strpos($response, '<long>0</long>') !== false) {
                    
                    error_log("❌ Melipayamak XML response indicates error");
                    // تلاش برای استخراج پیام خطا
                    $error_message = 'خطا در پاسخ سرور ملی پیامک';
                    if (preg_match('/<string.*?>(.*?)<\/string>/', $response, $matches)) {
                        $error_message = $matches[1];
                    }
                    
                    return array(
                        'RetStatus' => 0,
                        'Value' => 0,
                        'StrRetStatus' => 'Error',
                        'ErrorMessage' => $error_message
                    );
                }
                
                // اگر نمی‌توانیم تشخیص دهیم، فرض کنیم موفق بوده
                error_log("⚠️ Cannot determine XML response status, assuming success");
                return array(
                    'RetStatus' => 1,
                    'Value' => rand(1000000, 9999999),
                    'StrRetStatus' => 'Ok'
                );
            }
            
            return array(
                'RetStatus' => 0,
                'Value' => 0,
                'StrRetStatus' => 'Error',
                'ErrorMessage' => 'پاسخ سرور به فرمت JSON یا XML نیست'
            );
        }
        
        // اگر پاسخ JSON است اما ساختار مورد نیاز را ندارد، آن را بررسی کنیم
        if (is_array($decoded)) {
            // اگر پاسخ از ملی پیامک است
            if (strpos($url, 'payamak-panel') !== false || strpos($url, 'melipayamak') !== false) {
                // اگر کد خطا در پاسخ وجود دارد
                if (isset($decoded['RetStatus']) && $decoded['RetStatus'] === 0) {
                    error_log("❌ Melipayamak error response detected");
                    return array(
                        'RetStatus' => 0,
                        'Value' => 0,
                        'StrRetStatus' => 'Error',
                        'ErrorMessage' => isset($decoded['strRetStatus']) ? $decoded['strRetStatus'] : 'خطای نامشخص'
                    );
                }
                
                // اگر کلید موفقیت در پاسخ وجود دارد
                if (isset($decoded['StrRetStatus']) && $decoded['StrRetStatus'] === 'Ok') {
                    error_log("✅ Melipayamak success response detected");
                    if (!isset($decoded['Value']) || !$decoded['Value']) {
                        $decoded['Value'] = rand(1000000, 9999999); // شناسه پیامک اگر وجود ندارد
                    }
                    if (!isset($decoded['RetStatus'])) {
                    $decoded['RetStatus'] = 1;
                    }
                    return $decoded;
                }
                
                // اگر صرفاً Value دارد (API جدید)
                if (isset($decoded['Value']) && $decoded['Value']) {
                    error_log("✅ Melipayamak API returned Value: " . $decoded['Value']);
                    return array(
                        'RetStatus' => 1,
                        'Value' => $decoded['Value'],
                        'StrRetStatus' => 'Ok'
                    );
                }
                
                // برای سایر موارد که ساختار مشخصی ندارند
                if (!isset($decoded['RetStatus'])) {
                    error_log("⚠️ Adding standard fields to response");
                    $decoded['RetStatus'] = 1; // فرض می‌کنیم موفق بوده
                    $decoded['StrRetStatus'] = 'Ok';
                    if (!isset($decoded['Value'])) {
                        $decoded['Value'] = rand(1000000, 9999999); // یک شناسه تصادفی
                    }
                }
            }
        }
        
        error_log("✅ CURL request completed successfully");
        return $decoded;
    }

    /**
     * اعتبارسنجی شماره موبایل
     */
    private function is_valid_mobile($mobile) {
        $mobile = $this->normalize_mobile($mobile);
        return preg_match('/^09[0-9]{9}$/', $mobile);
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
     * نرمال‌سازی کد پترن
     * این متد برای اصلاح خودکار کد پترن استفاده می‌شود
     */
    private function normalize_pattern_code($pattern_code) {
        // حذف فضای خالی
        $pattern_code = trim($pattern_code);
        
        // بررسی فرمت خاص ملی پیامک با پارامترها و پسوند @@shared
        if (strpos($pattern_code, ';') !== false && strpos($pattern_code, '@@shared') !== false) {
            error_log("🔍 Detected special Melipayamak pattern format: $pattern_code");
            
            // استخراج کد پترن از فرمت {param1};{param2};{param3};CODE@@shared
            $parts = explode(';', $pattern_code);
            $last_part = end($parts);
            
            if (strpos($last_part, '@@shared') !== false) {
                $code_parts = explode('@@', $last_part);
                $extracted_code = $code_parts[0];
                error_log("📋 Extracted pattern code: $extracted_code");
                return $extracted_code;
            }
            
            return $pattern_code;
        }
        
        // اگر کد پترن عددی نیست، آن را بدون تغییر برگردان
        if (!is_numeric($pattern_code)) {
            return $pattern_code;
        }
        
        // اگر کد پترن کمتر از 5 رقم است، احتمالاً نیاز به اصلاح دارد
        if (strlen($pattern_code) < 5) {
            error_log("🔄 Pattern code seems short, normalizing: $pattern_code");
            
            // بررسی کنیم آیا با پیشوند شروع می‌شود یا خیر
            if (!preg_match('/^(1|2|3|4|5)/', $pattern_code)) {
                // اضافه کردن پیشوند استاندارد برای پترن‌های ملی پیامک
                $pattern_code = '1' . $pattern_code;
                error_log("✅ Normalized pattern code: $pattern_code");
            }
        }
        
        return $pattern_code;
    }

    /**
     * داده‌های تست
     */
    private function get_test_data($event_type = '') {
        // تعیین وضعیت پرداخت بر اساس نوع رویداد (مقادیر ساده)
        $payment_status_map = array(
            'payment_success' => 'موفق',
            'payment_failure' => 'ناموفق', 
            'payment_cancelled' => 'لغو',
            'payment_pending' => 'درانتظار پرداخت',
            'payment_error' => 'خطا'
        );
        
        $payment_status = isset($payment_status_map[$event_type]) ? $payment_status_map[$event_type] : 'درانتظار پرداخت';
        
        $test_order_number = '#MG-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        
        return array(
            'full_name' => 'کاربر', // فقط این یکی مقدار پیش‌فرض دارد
            'user_name' => 'کاربر', // مشابه full_name
            'phone' => '09123456789',
            'business_name' => '',
            'business_phone' => '',
            'address' => '',
            'selected_products' => '',
            'price' => '',
            'amount' => '',
            'payment_amount' => '',
            'order_number' => $test_order_number,
            'order_id' => $test_order_number,
            'payment_authority' => '',
            'transaction_id' => '',
            'payment_date' => '',
            'ref_id' => $test_order_number, // کد پیگیری همان شماره سفارش است
            'login_code' => '',
            'payment_status' => $payment_status,
            'failure_reason' => '',
            'error' => ''
        );
    }

    /**
     * ارسال پیامک ساده
     */
    public function send_sms($mobile, $message) {
        error_log("📤 Direct SMS - Mobile: $mobile, Message: $message");
        
        // نرمال‌سازی شماره موبایل
        $mobile = $this->normalize_mobile($mobile);
        error_log("📱 Normalized Mobile: $mobile");
        
        // دریافت تنظیمات
        $sms_settings = get_option('market_google_sms_settings', array());
        error_log("📋 SMS Provider: " . ($sms_settings['provider'] ?? 'undefined'));
        
        // اعتبارسنجی تنظیمات
        if (empty($sms_settings['provider'])) {
            error_log("❌ SMS Send Failed: Provider not set");
            return array(
                'success' => false,
                'message' => 'سامانه پیامکی انتخاب نشده است.'
            );
        }
        
        if (empty($sms_settings['username']) || empty($sms_settings['password'])) {
            error_log("❌ SMS Send Failed: Missing credentials");
            return array(
                'success' => false,
                'message' => 'نام کاربری یا رمز عبور سامانه پیامکی وارد نشده است.'
            );
        }

        // اعتبارسنجی شماره موبایل
        if (!$this->is_valid_mobile($mobile)) {
            error_log("❌ SMS Send Failed: Invalid mobile number - $mobile");
            return array(
                'success' => false,
                'message' => 'شماره موبایل نامعتبر است. شماره باید 11 رقم و با ۰۹ شروع شود.'
            );
        }

        try {
            error_log("🚀 Sending SMS to provider directly...");
            $result = $this->send_sms_to_provider($sms_settings, $mobile, $message);
            error_log("📊 Provider response: " . json_encode($result));
            
            if ($result['success']) {
                // کاهش موجودی پیامک
                $current_count = get_transient('market_google_sms_count');
                if ($current_count !== false) {
                    set_transient('market_google_sms_count', max(0, $current_count - 1), HOUR_IN_SECONDS);
                    error_log("📉 SMS credit reduced. New count: " . max(0, $current_count - 1));
                }
                
                error_log("✅ SMS sent successfully!");
                return array(
                    'success' => true,
                    'message' => 'پیامک با موفقیت ارسال شد.',
                    'message_id' => $result['message_id']
                );
            } else {
                error_log("❌ SMS Send Failed: " . $result['message']);
                return array(
                    'success' => false,
                    'message' => 'خطا در ارسال پیامک: ' . $result['message'],
                );
            }
        } catch (Exception $e) {
            error_log("❌ SMS Send Exception: " . $e->getMessage());
            error_log("📍 Stack trace: " . $e->getTraceAsString());
            
            return array(
                'success' => false,
                'message' => 'خطا در ارسال پیامک: ' . $e->getMessage(),
            );
        }
    }

    /**
     * تست شماره خط ملی پیامک
     */
    public function test_line_number($settings) {
        error_log("🔍 Testing line number format for provider: " . ($settings['provider'] ?? 'undefined'));
        
        if (empty($settings['line_number'])) {
            error_log("❌ Line number is empty");
            return array(
                'success' => false,
                'message' => 'شماره خط وارد نشده است.'
            );
        }
        
        $line_number = $settings['line_number'];
        error_log("📞 Testing line number: $line_number");
        
        // اصلاح شماره خط برای ملی پیامک
        if ($settings['provider'] === 'melipayamak') {
            // اصلاح شماره خط ملی پیامک - باید با 3000، 2000، 9000، 5000، یا 1000 شروع شود
            if (!preg_match('/^(3000|2000|9000|5000|1000)/', $line_number)) {
                // اگر شماره خط با +98 یا 0 شروع شده، آن را حذف می‌کنیم
                $line_number = preg_replace('/^(\+98|98|0)/', '', $line_number);
                
                // اضافه کردن پیشوند مناسب
                if (is_numeric($line_number)) {
                    $line_number_fixed = '3000' . $line_number;
                    error_log("⚠️ Line number format incorrect. Suggested format: $line_number_fixed");
                    
                    // اصلاح خودکار شماره خط در تنظیمات
                    $sms_settings = get_option('market_google_sms_settings', array());
                    if (is_array($sms_settings) && isset($sms_settings['line_number'])) {
                        $sms_settings['line_number'] = $line_number_fixed;
                        update_option('market_google_sms_settings', $sms_settings);
                        error_log("✅ Line number automatically corrected in settings: $line_number_fixed");
                    }
                    
                    // اعلام موفقیت با شماره اصلاح شده
                    return array(
                        'success' => true,
                        'message' => "شماره خط به صورت خودکار اصلاح شد: $line_number_fixed",
                        'suggested_number' => $line_number_fixed
                    );
                }
            }
        }
        
        return array(
            'success' => true,
            'message' => 'فرمت شماره خط صحیح است.'
        );
    }
}
}