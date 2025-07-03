<?php
/**
 * کلاس مدیریت درخواست‌های Ajax
 */
class Market_Google_Ajax {

    /**
     * راه‌اندازی hook ها
     */
    public static function init() {
        // ثبت handlers
        add_action('wp_ajax_save_temp_location', array(__CLASS__, 'save_temp_location'));
        add_action('wp_ajax_nopriv_save_temp_location', array(__CLASS__, 'save_temp_location'));
        
        add_action('wp_ajax_track_form_step', array(__CLASS__, 'track_form_step'));
        add_action('wp_ajax_nopriv_track_form_step', array(__CLASS__, 'track_form_step'));
        
        // AJAX handler برای tracking به کلاس User_Tracking منتقل شد
        
        add_action('wp_ajax_submit_location_form', array(__CLASS__, 'submit_location_form'));
        add_action('wp_ajax_nopriv_submit_location_form', array(__CLASS__, 'submit_location_form'));
        
        add_action('wp_ajax_payment_callback', array(__CLASS__, 'payment_callback'));
        add_action('wp_ajax_nopriv_payment_callback', array(__CLASS__, 'payment_callback'));
        
        add_action('wp_ajax_bmi_callback', array(__CLASS__, 'bmi_payment_callback'));
        add_action('wp_ajax_nopriv_bmi_callback', array(__CLASS__, 'bmi_payment_callback'));
        
        add_action('wp_ajax_search_locations', array(__CLASS__, 'search_locations'));
        add_action('wp_ajax_nopriv_search_locations', array(__CLASS__, 'search_locations'));
        
        add_action('wp_ajax_get_active_products', array(__CLASS__, 'get_active_products'));
        add_action('wp_ajax_nopriv_get_active_products', array(__CLASS__, 'get_active_products'));

        // AJAX handlers برای admin
        add_action('wp_ajax_market_google_search_orders', array('Market_Google_Admin', 'ajax_search_orders'));
        add_action('wp_ajax_market_google_autocomplete_orders', array('Market_Google_Admin', 'ajax_autocomplete_orders'));
        add_action('wp_ajax_market_google_toggle_read_status', array('Market_Google_Admin', 'ajax_toggle_read_status'));
        add_action('wp_ajax_market_google_complete_order', array('Market_Google_Admin', 'ajax_complete_order'));
        add_action('wp_ajax_market_google_uncomplete_order', array('Market_Google_Admin', 'ajax_uncomplete_order'));
        add_action('wp_ajax_market_google_send_location_info_sms', array('Market_Google_Admin', 'send_location_info_sms'));
        add_action('wp_ajax_market_google_delete_order', array('Market_Google_Admin', 'ajax_delete_order'));
        add_action('wp_ajax_get_order_details', array('Market_Google_Admin', 'ajax_get_order_details'));
        add_action('wp_ajax_get_order_edit_form', array('Market_Google_Admin', 'ajax_get_order_edit_form'));
        add_action('wp_ajax_update_order', array('Market_Google_Admin', 'ajax_update_order'));
        add_action('wp_ajax_market_google_check_sms', array('Market_Google_Admin', 'ajax_check_sms'));
        add_action('wp_ajax_change_payment_status', array('Market_Google_Admin', 'ajax_change_payment_status'));

        // ایجاد جداول در صورت عدم وجود
        self::create_temp_table();
        self::create_tracking_table();
    }

    /**
     * ذخیره موقت اطلاعات فرم (Auto-save)
     */
    public static function save_temp_location() {
        // بررسی nonce
        if (!wp_verify_nonce($_POST['nonce'], 'market_location_nonce')) {
            wp_die('Security check failed');
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        $step = intval($_POST['step']);
        $form_data = $_POST['form_data'];

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_temp_data';

        // بررسی وجود جدول و ایجاد در صورت عدم وجود
        self::create_temp_table();

        // ذخیره/به‌روزرسانی اطلاعات موقت
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE session_id = %s",
            $session_id
        ));

        $data = array(
            'session_id' => $session_id,
            'step' => $step,
            'form_data' => json_encode($form_data),
            'ip_address' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            $wpdb->update($table_name, $data, array('session_id' => $session_id));
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_name, $data);
        }

        wp_send_json_success(array(
            'message' => 'Form data saved temporarily',
            'step' => $step
        ));
    }

    /**
     * ردیابی مراحل فرم
     */
    public static function track_form_step() {
        if (!wp_verify_nonce($_POST['nonce'], 'market_location_nonce')) {
            wp_die('Security check failed');
        }

        $session_id = sanitize_text_field($_POST['session_id']);
        $step = intval($_POST['step']);
        $user_agent = sanitize_text_field($_POST['user_agent']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_step_tracking';

        // بررسی وجود جدول و ایجاد در صورت عدم وجود
        self::create_tracking_table();

        $wpdb->insert($table_name, array(
            'session_id' => $session_id,
            'step' => $step,
            'ip_address' => self::get_client_ip(),
            'user_agent' => $user_agent,
            'created_at' => current_time('mysql')
        ));

        wp_send_json_success();
    }

    // متد track_user_progress به کلاس Market_Google_User_Tracking منتقل شد

    /**
     * ثبت فرم نهایی
     */
    public static function submit_location_form() {
        // DEBUG: لاگ کامل درخواست
        error_log("🔥 SUBMIT FORM DEBUG - START");
        error_log("📊 POST Data: " . print_r($_POST, true));
        error_log("🌐 Request Method: " . $_SERVER['REQUEST_METHOD']);
        error_log("🔗 Request URI: " . $_SERVER['REQUEST_URI']);
        
        // بررسی امنیتی - استفاده از nonce همون که در فرم استفاده می‌شه
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_location_nonce')) {
            error_log("❌ Nonce verification failed");
            wp_send_json_error('امنیت درخواست تأیید نشد. لطفاً صفحه را رفرش کنید.');
            return;
        }
        
        error_log("✅ Nonce verification passed");
        $form_data = $_POST;
        
        // اعتبارسنجی داده‌های پایه
        $validation = self::validate_form_data($form_data);
        if (!$validation['valid']) {
            error_log("❌ Form validation failed: " . $validation['message']);
            wp_send_json_error($validation['message']);
            return;
        }
        
        error_log("✅ Form validation passed");
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بررسی و آماده‌سازی محصولات انتخابی
        $selected_products = array();
        if (isset($form_data['selected_packages'])) {
            if (is_string($form_data['selected_packages'])) {
                $selected_products = json_decode(stripslashes($form_data['selected_packages']), true);
            } else {
                $selected_products = $form_data['selected_packages'];
            }
        }
        
        if (!is_array($selected_products)) {
            $selected_products = array();
        }
        
        // محاسبه مبلغ کل
        $total_amount = self::calculate_total_amount($selected_products);
        
        // اگر مبلغ صفر باشه، خطا برگردان
        if ($total_amount <= 0) {
            wp_send_json_error(array(
                'message' => 'لطفاً حداقل یک محصول انتخاب کنید یا قیمت محصولات را بررسی کنید.'
            ));
            return;
        }
        
        // آماده‌سازی ساعات کاری
        $working_hours = isset($form_data['working_hours']) ? $form_data['working_hours'] : '24/7';
        if (is_string($working_hours) && strpos($working_hours, '{') !== false) {
            // اگر JSON است، دیکود کن
            $hours_data = json_decode(stripslashes($working_hours), true);
            if ($hours_data) {
                $working_hours = $hours_data;
            }
        }
        
        // آماده‌سازی داده‌ها برای ثبت
        $manual_address = isset($form_data['manual_address']) ? sanitize_text_field($form_data['manual_address']) : '';
        $auto_address = isset($form_data['auto_address']) ? sanitize_text_field($form_data['auto_address']) : '';
        $final_address = !empty($manual_address) ? $manual_address : $auto_address;
        
        // محاسبه مبلغ‌ها برای ذخیره در دیتابیس (بر حسب تومان)
        $subtotal_toman = intval($total_amount / 10); // تبدیل از ریال به تومان
        $tax_toman = round($subtotal_toman * 0.1 / 1.1); // محاسبه مالیات از کل مبلغ
        $subtotal_without_tax = $subtotal_toman - $tax_toman;
        
        $location_data = array(
            'full_name' => sanitize_text_field($form_data['full_name']),
            'phone' => self::sanitize_phone_number($form_data['phone']),
            'business_name' => sanitize_text_field($form_data['business_name']),
            'business_phone' => self::sanitize_phone_number($form_data['business_phone']),
            'website' => sanitize_text_field($form_data['website']),
            'province' => sanitize_text_field($form_data['province']),
            'city' => sanitize_text_field($form_data['city']),
            'manual_address' => $manual_address,
            'auto_address' => $auto_address,
            'address' => $final_address,
            'latitude' => floatval($form_data['latitude']),
            'longitude' => floatval($form_data['longitude']),
            'working_hours' => is_array($working_hours) ? json_encode($working_hours) : $working_hours,
            'selected_products' => json_encode($selected_products),
            'price' => $total_amount, // مبلغ کل شامل مالیات (ریال)
            'payment_amount' => $subtotal_toman, // مبلغ کل شامل مالیات (تومان)
            'status' => 'pending',
            'payment_status' => 'pending',
            'created_at' => current_time('mysql')
        );
        
        // ثبت در دیتابیس
        $result = $wpdb->insert($table_name, $location_data);
        
        if (!$result) {
            wp_send_json_error('خطا در ثبت اطلاعات در دیتابیس');
            return;
        }
        
        $location_id = $wpdb->insert_id;
        
        // اضافه کردن شناسه تراکنش
        $transaction_id = 'MGL-' . time() . '-' . $location_id;
        
        // ارسال SMS تأیید ثبت فرم
        do_action('market_google_form_submitted', array(
            'location_id' => $location_id,
            'transaction_id' => $transaction_id
        ), $location_data);
        $wpdb->update(
            $table_name,
            array('payment_transaction_id' => $transaction_id),
            array('id' => $location_id)
        );
        
        error_log("🚀 Starting payment processing for Location ID: $location_id, Amount: $total_amount");
        
        // پردازش پرداخت با سوییچ هوشمند
        $payment_result = self::smart_payment_gateway($location_id, $location_data, $transaction_id, $total_amount);
        
        error_log("🎯 Payment gateway result: " . json_encode($payment_result));
        
        if ($payment_result['success']) {
            error_log("✅ Payment gateway successful, sending success response");
            wp_send_json_success(array(
                'message' => 'فرم با موفقیت ثبت شد',
                'location_id' => $location_id,
                'redirect_url' => $payment_result['redirect_url'],
                'gateway_used' => $payment_result['gateway_used']
            ));
        } else {
            error_log("❌ Payment gateway failed: " . $payment_result['message']);
            // در صورت خطا، رکورد ثبت شده را حذف کن
            $wpdb->delete($table_name, array('id' => $location_id));
            wp_send_json_error($payment_result['message']);
        }
    }
    
    /**
     * سوییچ هوشمند درگاه پرداخت - ساده شده
     */
    private static function smart_payment_gateway($location_id, $location_data, $transaction_id, $total_amount) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        error_log("🔥 PAYMENT GATEWAY DEBUG - START");
        error_log("🏦 Location ID: $location_id");
        error_log("💰 Total Amount: $total_amount");
        error_log("🆔 Transaction ID: $transaction_id");
        error_log("📊 Location Data: " . print_r($location_data, true));
        
        // بررسی تنظیمات درگاه‌ها
        error_log("🔍 Checking gateway settings...");
        
        // BMI Settings
        $bmi_terminal = get_option('market_google_bmi_terminal_id', '');
        $bmi_merchant = get_option('market_google_bmi_merchant_id', '');
        $bmi_secret = get_option('market_google_bmi_secret_key', '');
        error_log("🏛️ BMI Settings - Terminal: '$bmi_terminal', Merchant: '$bmi_merchant', Secret: " . (!empty($bmi_secret) ? 'SET' : 'EMPTY'));
        
        // ZarinPal Settings
        $zp_merchant = get_option('market_google_zarinpal_merchant_id', '');
        $zp_enabled = get_option('market_google_zarinpal_enabled', false);
        error_log("💳 ZarinPal Settings - Merchant: '$zp_merchant', Enabled: " . ($zp_enabled ? 'YES' : 'NO'));
        
        // اولویت 1: بانک ملی (درگاه اصلی)
        error_log("💳 تلاش اتصال به بانک ملی...");
        $bmi_result = self::try_bmi_payment($location_id, $location_data, $transaction_id, $total_amount);
        
        if ($bmi_result['success']) {
            $wpdb->update(
                $table_name,
                array('payment_method' => 'bmi'),
                array('id' => $location_id)
            );
            
            error_log("✅ بانک ملی موفق: " . $bmi_result['redirect_url']);
            
            // اضافه کردن رویداد اتصال به درگاه پرداخت
            do_action('market_google_payment_gateway_connected', array(
                'location_id' => $location_id,
                'transaction_id' => $transaction_id,
                'gateway' => 'bmi',
                'redirect_url' => $bmi_result['redirect_url']
            ), $location_data);
            
            return array(
                'success' => true,
                'redirect_url' => $bmi_result['redirect_url'],
                'gateway_used' => 'bmi',
                'message' => 'در حال هدایت به درگاه بانک ملی...'
            );
        }
        
        error_log("❌ بانک ملی ناموفق: " . $bmi_result['message']);
        
        // اولویت 2: زرین‌پال (درگاه پشتیبان)
        error_log("🔄 سوییچ به زرین‌پال...");
        $zarinpal_result = self::try_zarinpal_payment($location_id, $location_data, $transaction_id, $total_amount);
        
        if ($zarinpal_result['success']) {
            $wpdb->update(
                $table_name,
                array('payment_method' => 'zarinpal'),
                array('id' => $location_id)
            );
            
            error_log("✅ زرین‌پال موفق: " . $zarinpal_result['redirect_url']);
            
            // اضافه کردن رویداد اتصال به درگاه پرداخت
            do_action('market_google_payment_gateway_connected', array(
                'location_id' => $location_id,
                'transaction_id' => $transaction_id,
                'gateway' => 'zarinpal',
                'redirect_url' => $zarinpal_result['redirect_url']
            ), $location_data);
            
            return array(
                'success' => true,
                'redirect_url' => $zarinpal_result['redirect_url'],
                'gateway_used' => 'zarinpal',
                'message' => 'بانک ملی در دسترس نبود، هدایت به زرین‌پال...'
            );
        }
        
        error_log("❌ زرین‌پال هم ناموفق: " . $zarinpal_result['message']);
        
        // اولویت 3: حالت تست (در صورت عدم دسترسی به درگاه‌ها)
        error_log("🧪 Trying test/demo mode...");
        
        // در حالت تست یا محیط development
        $current_url = home_url();
        $is_dev_env = (
            strpos($current_url, 'localhost') !== false || 
            strpos($current_url, '127.0.0.1') !== false ||
            strpos($current_url, '.test') !== false ||
            strpos($current_url, '.local') !== false ||
            defined('WP_DEBUG') && WP_DEBUG
        );
        
        if ($is_dev_env) {
            error_log("🏠 Development environment detected - Using test mode");
            
            $wpdb->update(
                $table_name,
                array('payment_method' => 'test'),
                array('id' => $location_id)
            );
            
            // اضافه کردن رویداد اتصال به درگاه پرداخت
            do_action('market_google_payment_gateway_connected', array(
                'location_id' => $location_id,
                'transaction_id' => $transaction_id,
                'gateway' => 'test',
                'redirect_url' => add_query_arg(array(
                    'payment' => 'test_success',
                    'gateway' => 'test',
                    'location_id' => $location_id
                ), home_url())
            ), $location_data);
            
            return array(
                'success' => true,
                'redirect_url' => add_query_arg(array(
                    'payment' => 'test_success',
                    'gateway' => 'test',
                    'location_id' => $location_id
                ), home_url()),
                'gateway_used' => 'test',
                'message' => 'حالت تست - پرداخت شبیه‌سازی شد'
            );
        }
        
        // اگر هر سه ناموفق بودند
        return array(
            'success' => false,
            'message' => 'متأسفانه هیچ یک از درگاه‌های پرداخت در دسترس نیستند. لطفاً ابتدا تنظیمات درگاه‌ها را در پنل ادمین تکمیل کنید.',
            'gateway_used' => 'none'
        );
    }
    
    /**
     * تلاش اتصال به بانک ملی (درگاه اصلی)
     */
    private static function try_bmi_payment($location_id, $location_data, $transaction_id, $total_amount) {
        try {
            // دریافت تنظیمات درگاه از options جداگانه
            $terminal_id = trim(get_option('market_google_bmi_terminal_id', ''));
            $merchant_id = trim(get_option('market_google_bmi_merchant_id', ''));
            $secret_key = trim(get_option('market_google_bmi_secret_key', ''));
            
            error_log("🔍 BMI Settings Check - Terminal: '$terminal_id', Merchant: '$merchant_id', Secret: " . (!empty($secret_key) ? 'SET' : 'EMPTY'));
            
            if (empty($terminal_id) || empty($merchant_id) || empty($secret_key)) {
                return array('success' => false, 'message' => 'اطلاعات درگاه پرداخت بانک ملی به درستی تنظیم نشده است. لطفاً از پنل ادمین تنظیمات درگاه را کامل کنید.');
            }
            
            // ⭐️ اصلاحیه مهم: callback URL درست
            $callback_url = add_query_arg(array(
                'action' => 'bmi_callback',
                'transaction_id' => $transaction_id,
                'location_id' => $location_id
            ), admin_url('admin-ajax.php'));
            
            // اطمینان از HTTPS اگر سایت روی HTTPS است
            if (is_ssl()) {
                $callback_url = set_url_scheme($callback_url, 'https');
            }
            
            // OrderId باید عدد باشد، نه رشته - طبق مستندات سداد
            $order_id = intval($location_id . substr(time(), -6));
            
            // استفاده از PHP date به جای jdate برای جلوگیری از خطا
            $current_date = date('Ymd');
            $current_datetime = date('YmdHis');
            $order_number = 'ORD-' . $current_date . '-' . rand(1000, 9999);
            
            error_log("🔍 BMI Debug - Order ID: $order_id, Date: $current_date, DateTime: $current_datetime");
            
            // ایجاد SignData با الگوریتم صحیح
            $sign_data_string = $terminal_id . ';' . $order_id . ';' . $total_amount;
            $sign_data = self::sadad_encrypt($sign_data_string, $secret_key);
            
            error_log("🔐 BMI SignData - Input: '$sign_data_string', Output: " . substr($sign_data, 0, 20) . "...");
            
            $request_data = array(
                'MerchantID' => $merchant_id,
                'TerminalId' => $terminal_id,
                'Amount' => intval($total_amount),
                'OrderId' => intval($order_id),
                'LocalDateTime' => $current_datetime,
                'ReturnUrl' => $callback_url,
                'SignData' => $sign_data
            );
            
            $response = wp_remote_post('https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest', array(
                'body' => json_encode($request_data),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'WordPress-Market-Google-Plugin/1.0'
                ),
                'timeout' => 20,
                'sslverify' => true,
                'redirection' => 5,
                'blocking' => true,
                'httpversion' => '1.1'
            ));
            
            if (is_wp_error($response)) {
                return array('success' => false, 'message' => 'خطا در اتصال به بانک ملی: ' . $response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            error_log("🏦 BMI Response Body: " . $body);
            error_log("🏦 BMI Parsed Result: " . print_r($result, true));
            
            // بررسی صحیح پاسخ بانک ملی - سداد ResCode برمی‌گرداند نه Status
            if (isset($result['ResCode']) && ($result['ResCode'] === 0 || $result['ResCode'] === "0")) {
                $token = $result['Token'];
                
                error_log("✅ BMI Token received: " . substr($token, 0, 20) . "...");
                
                // ذخیره token در دیتابیس
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'market_google_locations',
                    array('payment_authority' => $token),
                    array('id' => $location_id)
                );
                
                $gateway_url = 'https://sadad.shaparak.ir/VPG/Purchase?Token=' . $token;
                
                return array(
                    'success' => true,
                    'redirect_url' => $gateway_url
                );
            } else {
                $error_code = isset($result['ResCode']) ? $result['ResCode'] : 'N/A';
                $error_msg = isset($result['Description']) ? $result['Description'] : 'خطای نامشخص';
                
                error_log("❌ BMI Error - ResCode: $error_code, Description: $error_msg");
                
                // اگر خطای -1 است، اطلاعات کاملی برای دیباگ لاگ کن
                if ($error_code == -1 || $error_code == "-1") {
                    error_log("🔍 BMI Debug Info - Terminal: $terminal_id, Merchant: $merchant_id, Secret: " . substr($secret_key, 0, 10) . "..., Amount: $total_amount");
                }
                
                return array('success' => false, 'message' => "بانک ملی (کد: $error_code): $error_msg");
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'خطای سیستم بانک ملی: ' . $e->getMessage());
        }
    }
    
    /**
     * تلاش اتصال به زرین‌پال (درگاه پشتیبان)
     */
    private static function try_zarinpal_payment($location_id, $location_data, $transaction_id, $total_amount) {
        try {
            // دریافت تنظیمات درگاه از options جداگانه
            $merchant_id = trim(get_option('market_google_zarinpal_merchant_id', ''));
            $is_enabled = get_option('market_google_zarinpal_enabled', false);
            $sandbox_mode = get_option('market_google_zarinpal_sandbox', false);
            
            error_log("💳 ZarinPal Config Check - Enabled: " . ($is_enabled ? 'Yes' : 'No') . ", Merchant: '$merchant_id'");
            
            if (!$is_enabled) {
                return array('success' => false, 'message' => 'درگاه زرین‌پال غیرفعال است');
            }
            
            if (empty($merchant_id) || $merchant_id === 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx') {
                return array('success' => false, 'message' => 'Merchant ID زرین‌پال تنظیم نشده است. لطفاً از پنل ادمین تنظیمات درگاه را کامل کنید.');
            }
            
            // تصحیح callback URL
            $callback_url = add_query_arg(array(
                'action' => 'payment_callback',
                'gateway' => 'zarinpal',
                'location_id' => $location_id,
                'transaction_id' => $transaction_id
            ), admin_url('admin-ajax.php'));
            
            // اطمینان از HTTPS اگر سایت روی HTTPS است
            if (is_ssl()) {
                $callback_url = set_url_scheme($callback_url, 'https');
            }
            
            error_log("🔗 ZarinPal Callback URL: " . $callback_url);
            
            $request_data = array(
                'merchant_id' => $merchant_id,
                'amount' => $total_amount,
                'callback_url' => $callback_url,
                'description' => 'ثبت کسب‌وکار - ' . $location_data['business_name']
            );
            
            $response = wp_remote_post('https://api.zarinpal.com/pg/v4/payment/request.json', array(
                'body' => json_encode($request_data),
                'headers' => array(
                    'Content-Type' => 'application/json'
                ),
                'timeout' => 15,
                'sslverify' => true
            ));
            
            if (is_wp_error($response)) {
                return array('success' => false, 'message' => 'خطا در اتصال به زرین‌پال: ' . $response->get_error_message());
            }
            
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            
            if (isset($result['data']['code']) && $result['data']['code'] == 100) {
                $authority = $result['data']['authority'];
                
                // ذخیره authority در دیتابیس
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix . 'market_google_locations',
                    array('payment_authority' => $authority),
                    array('id' => $location_id)
                );
                
                $gateway_url = 'https://www.zarinpal.com/pg/StartPay/' . $authority;
                
                return array(
                    'success' => true,
                    'redirect_url' => $gateway_url
                );
            } else {
                $error_msg = isset($result['errors']) ? implode(', ', $result['errors']) : 'خطای نامشخص';
                return array('success' => false, 'message' => 'زرین‌پال: ' . $error_msg);
            }
            
        } catch (Exception $e) {
            return array('success' => false, 'message' => 'خطای سیستم زرین‌پال: ' . $e->getMessage());
        }
    }

    /**
     * محاسبه مبلغ کل بر اساس محصولات انتخابی (شامل مالیات)
     */
    private static function calculate_total_amount($selected_products) {
        error_log("💰 CALCULATE AMOUNT DEBUG");
        error_log("📦 Selected Products: " . print_r($selected_products, true));
        
        if (empty($selected_products) || !is_array($selected_products)) {
            error_log("❌ No products selected or not array!");
            return 0;
        }
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'market_google_products';
        $subtotal = 0;
        
        foreach ($selected_products as $product_id) {
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT sale_price, original_price FROM $products_table WHERE id = %d AND is_active = 1",
                intval($product_id)
            ));
            
            if ($product) {
                $price = !empty($product->sale_price) ? $product->sale_price : $product->original_price;
                // قیمت در دیتابیس به تومان است
                $subtotal += intval($price);
            }
        }
        
        // محاسبه مالیات ارزش افزوده 10%
        $tax_amount = round($subtotal * 0.1);
        $total_with_tax = $subtotal + $tax_amount;
        
        // تبدیل به ریال برای درگاه‌های ایرانی
        $total_in_rials = $total_with_tax * 10;
        
        error_log("💰 Price Calculation - Subtotal: {$subtotal} تومان, Tax: {$tax_amount} تومان, Total: {$total_with_tax} تومان, Final (Rials): {$total_in_rials}");
        
        return $total_in_rials;
    }

    /**
     * ایجاد درخواست پرداخت
     */
    private static function create_payment_request($location_id, $location_data) {
        $payment_method = $location_data['payment_method'];
        
        // انتخاب درگاه بر اساس روش پرداخت انتخابی کاربر
        switch ($payment_method) {
            case 'bmi':
                return self::process_bmi_payment($location_id, $location_data);
            case 'zarinpal':
                return self::process_zarinpal_payment($location_id, $location_data);
            default:
                return array(
                    'success' => false,
                    'message' => 'روش پرداخت انتخابی نامعتبر است.'
                );
        }
    }

    /**
     * پردازش پرداخت بانک ملی (سداد)
     */
    private static function process_bmi_payment($location_id, $location_data) {
        $options = get_option('market_google_settings', array());
        
        // اصلاح نام فیلدها
        $merchant_code = isset($options['bmi_merchant_id']) ? $options['bmi_merchant_id'] : '';
        $terminal_code = isset($options['bmi_terminal_id']) ? $options['bmi_terminal_id'] : '';
        $secret_key = isset($options['bmi_secret_key']) ? $options['bmi_secret_key'] : '';

        if (empty($merchant_code) || empty($terminal_code) || empty($secret_key)) {
            return array(
                'success' => false,
                'message' => 'تنظیمات درگاه بانک ملی ناقص است. لطفاً شماره پذیرنده، شماره ترمینال و کلید امنیتی را در تنظیمات وارد کنید.'
            );
        }

        $transaction_id = 'MGL-' . time() . '-' . $location_id;
        $amount = intval($location_data['price']);
        
        // اصلاح URL callback
        $callback_url = add_query_arg(array(
            'action' => 'market_google_payment_return',
            'gateway' => 'bmi',
            'location_id' => $location_id,
            'transaction_id' => $transaction_id
        ), home_url('/'));

        // OrderId باید عدد باشد
        $order_id = intval(substr(str_replace(['MGL-', '-'], '', $transaction_id), -10)) ?: time();
        $order_number = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
        
        // ایجاد SignData با الگوریتم صحیح
        $sign_data_string = $terminal_code . ';' . $order_id . ';' . $amount;
        $sign_data = self::sadad_encrypt($sign_data_string, $secret_key);
        
        // آماده‌سازی داده‌های درخواست
        $data = array(
            'MerchantID' => $merchant_code,
            'TerminalId' => $terminal_code,
            'Amount' => $amount,
            'OrderId' => intval($order_id),
            'LocalDateTime' => date('Ymdhis'),
            'ReturnUrl' => $callback_url,
            'SignData' => $sign_data
        );

        // ارسال درخواست به سداد
        $response = wp_remote_post('https://sadad.shaparak.ir/VPG/api/v0/Request/PaymentRequest', array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در اتصال به درگاه بانک ملی: ' . $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['Token'])) {
            // ذخیره token و transaction_id در دیتابیس
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'market_google_locations',
                array(
                    'payment_authority' => $result['Token'],
                    'payment_transaction_id' => $transaction_id
                ),
                array('id' => $location_id)
            );

            return array(
                'success' => true,
                'payment_url' => 'https://sadad.shaparak.ir/VPG/Purchase?Token=' . $result['Token']
            );
        } else {
            $error_message = isset($result['Description']) ? $result['Description'] : 'خطای نامشخص در درگاه بانک ملی';
            return array(
                'success' => false,
                'message' => 'خطا در ایجاد درخواست پرداخت: ' . $error_message
            );
        }
    }

    /**
     * پردازش پرداخت زرین‌پال
     */
    private static function process_zarinpal_payment($location_id, $location_data) {
        $options = get_option('market_google_settings', array());
        
        $merchant_id = $options['zarinpal_merchant_id'] ?? '';
        
        // بررسی معتبر بودن merchant_id
        if (empty($merchant_id) || $merchant_id === 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX') {
            return array(
                'success' => false,
                'message' => 'مرچند کد زرین‌پال تنظیم نشده است. لطفاً در تنظیمات پلاگین مرچند کد معتبر وارد کنید.'
            );
        }
        
        $sandbox = isset($options['zarinpal_sandbox']) ? $options['zarinpal_sandbox'] : true;

        $base_url = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com';
        
        $data = array(
            'merchant_id' => $merchant_id,
            'amount' => intval($location_data['price']),
            'description' => 'ثبت موقعیت کسب و کار: ' . $location_data['business_name'],
            'callback_url' => add_query_arg(array(
                'action' => 'market_google_payment_return',
                'gateway' => 'zarinpal',
                'location_id' => $location_id,
                'transaction_id' => $transaction_id
            ), home_url('/')),
            'metadata' => array(
                'mobile' => $location_data['phone'],
                'email' => ''
            )
        );

        $response = wp_remote_post($base_url . '/pg/rest/WebGate/PaymentRequest.json', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در اتصال به درگاه پرداخت: ' . $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['data']['code']) && $result['data']['code'] == 100) {
            // ذخیره authority در دیتابیس
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'market_google_locations',
                array('payment_authority' => $result['data']['authority']),
                array('id' => $location_id)
            );

            return array(
                'success' => true,
                'payment_url' => $base_url . '/pg/StartPay/' . $result['data']['authority']
            );
        } else {
            // Extract Zarinpal error message from array if present
            $error_message = 'خطای نامشخص';
            if (!empty($result['errors'])) {
                if (is_array($result['errors'])) {
                    // If errors is a list of error objects
                    $first_error = reset($result['errors']);
                    if (is_array($first_error) && !empty($first_error['message'])) {
                        $error_message = $first_error['message'];
                    } elseif (!empty($result['errors']['message'])) {
                        $error_message = $result['errors']['message'];
                    }
                } elseif (!empty($result['errors']['message'])) {
                    $error_message = $result['errors']['message'];
                }
            }
            return array(
                'success' => false,
                'message' => 'خطا در ایجاد درخواست پرداخت: ' . $error_message
            );
        }
    }

    /**
     * Callback برای پرداخت زرین‌پال
     */
    public static function payment_callback() {
        error_log('ZarinPal Callback triggered with data: ' . json_encode($_GET));
        
        $authority = sanitize_text_field($_GET['Authority'] ?? '');
        $status = sanitize_text_field($_GET['Status'] ?? '');
        $location_id = intval($_GET['location_id'] ?? 0);
        $transaction_id = sanitize_text_field($_GET['transaction_id'] ?? '');

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // بررسی وجود location با ID و transaction_id
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND payment_transaction_id = %s",
            $location_id,
            $transaction_id
        ));

        if (!$location) {
            error_log('ZarinPal Callback: Location not found for ID ' . $location_id . ' and Transaction ' . $transaction_id);
            wp_redirect(home_url('/?payment=failed&error=location_not_found'));
            exit;
        }

        if ($status == 'OK' && !empty($authority)) {
            // تایید پرداخت
            $verification_result = self::verify_zarinpal_payment($authority, $location->price);
            
            if ($verification_result['success']) {
                // به‌روزرسانی وضعیت پرداخت
                $options = get_option('market_google_settings', array());
                $auto_approve = isset($options['auto_approve']) && $options['auto_approve'];
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'completed',
                    'payment_ref_id' => $verification_result['ref_id'],
                    'payment_authority' => $authority,
                    'status' => $auto_approve ? 'approved' : 'pending',
                    'paid_at' => current_time('mysql')
                ), array('id' => $location_id));

                // ارسال SMS موفقیت (اختیاری)
                self::send_success_sms($location);

                // اضافه کردن trigger برای SMS
                $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
                $payment_data = array(
                    'amount' => $location->price,
                    'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                    'gateway' => 'zarinpal',
                    'transaction_id' => $transaction_id,
                    'order_number' => $order_number,
                    'payment_amount' => number_format($location->price) . ' تومان'
                );
                
                $location_data = array(
                    'phone' => $location->phone,
                    'user_name' => $location->full_name,
                    'full_name' => $location->full_name,
                    'business_name' => $location->business_name
                );
                
                // فراخوانی action برای ارسال SMS
                do_action('market_google_payment_success', $payment_data, $location_data);

                // هدایت به صفحه موفقیت
                wp_redirect(add_query_arg(array(
                    'payment' => 'success',
                    'gateway' => 'zarinpal',
                    'ref_id' => $verification_result['ref_id'],
                    'location_id' => $location_id,
                    'transaction_id' => $transaction_id
                ), home_url()));
                exit;
            } else {
                // پرداخت ناموفق
                $wpdb->update($table_name, array(
                    'payment_status' => 'failed',
                    'payment_authority' => $authority
                ), array('id' => $location_id));

                // اضافه کردن trigger برای SMS شکست
                $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
                $payment_data = array(
                    'amount' => $location->price,
                    'gateway' => 'zarinpal',
                    'transaction_id' => $transaction_id,
                    'error' => $verification_result['message'],
                    'failure_reason' => $verification_result['message'],
                    'order_number' => $order_number,
                    'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                    'payment_amount' => number_format($location->price) . ' تومان'
                );
                
                $location_data = array(
                    'phone' => $location->phone,
                    'user_name' => $location->full_name,
                    'full_name' => $location->full_name,
                    'business_name' => $location->business_name
                );
                
                do_action('market_google_payment_failure', $payment_data, $location_data);

                wp_redirect(add_query_arg(array(
                    'payment' => 'failed',
                    'gateway' => 'zarinpal',
                    'error' => urlencode($verification_result['message']),
                    'location_id' => $location_id,
                    'transaction_id' => $transaction_id
                ), home_url()));
                exit;
            }
        } else {
            // کاربر پرداخت را لغو کرد
            $wpdb->update($table_name, array(
                'payment_status' => 'cancelled',
                'payment_authority' => $authority
            ), array('id' => $location_id));

            $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
            $payment_data = array(
                'amount' => $location->price,
                'gateway' => 'zarinpal',
                'transaction_id' => $transaction_id,
                'order_number' => $order_number,
                'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                'payment_amount' => number_format($location->price) . ' تومان'
            );
            
            $location_data = array(
                'phone' => $location->phone,
                'user_name' => $location->full_name,
                'full_name' => $location->full_name,
                'business_name' => $location->business_name
            );
            
            do_action('market_google_payment_cancelled', $payment_data, $location_data);

            wp_redirect(add_query_arg(array(
                'payment' => 'canceled',
                'gateway' => 'zarinpal',
                'location_id' => $location_id,
                'transaction_id' => $transaction_id
            ), home_url()));
            exit;
        }
    }

    /**
     * تایید پرداخت زرین‌پال
     */
    private static function verify_zarinpal_payment($authority, $amount) {
        $options = get_option('market_google_settings', array());
        
        $merchant_id = $options['zarinpal_merchant_id'] ?? 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX';
        $sandbox = isset($options['zarinpal_sandbox']) ? $options['zarinpal_sandbox'] : true;

        $base_url = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com';

        $data = array(
            'merchant_id' => $merchant_id,
            'authority' => $authority,
            'amount' => intval($amount)
        );

        $response = wp_remote_post($base_url . '/pg/rest/WebGate/PaymentVerification.json', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در تایید پرداخت: ' . $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['data']['code']) && $result['data']['code'] == 100) {
            return array(
                'success' => true,
                'ref_id' => $result['data']['ref_id']
            );
        } else {
            return array(
                'success' => false,
                'message' => 'تایید پرداخت ناموفق: ' . ($result['errors']['message'] ?? 'خطای نامشخص')
            );
        }
    }

    /**
     * جستجو در موقعیت‌ها
     */
    public static function search_locations() {
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $province = sanitize_text_field($_POST['province'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        $where_conditions = array("status = 'active'", "payment_status = 'completed'");
        
        if (!empty($search_term)) {
            $where_conditions[] = $wpdb->prepare(
                "(business_name LIKE %s OR description LIKE %s OR auto_address LIKE %s)",
                '%' . $search_term . '%',
                '%' . $search_term . '%',
                '%' . $search_term . '%'
            );
        }

        if (!empty($province)) {
            $where_conditions[] = $wpdb->prepare("province = %s", $province);
        }

        if (!empty($city)) {
            $where_conditions[] = $wpdb->prepare("city = %s", $city);
        }

        $where_clause = implode(' AND ', $where_conditions);
        
        $locations = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT 20"
        );

        ob_start();
        if (!empty($locations)) {
            foreach ($locations as $location) {
                ?>
                <div class="search-result-item">
                    <h4><?php echo esc_html($location->business_name); ?></h4>
                    <p><strong>آدرس:</strong> <?php echo esc_html($location->auto_address); ?></p>
                    <p><strong>شهر:</strong> <?php echo esc_html($location->city); ?></p>
                    <?php if ($location->business_phone): ?>
                        <p><strong>تلفن:</strong> <?php echo esc_html($location->business_phone); ?></p>
                    <?php endif; ?>
                </div>
                <?php
            }
        } else {
            echo '<p>نتیجه‌ای یافت نشد.</p>';
        }
        
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'count' => count($locations)
        ));
    }

    /**
     * اعتبارسنجی داده‌های فرم
     */
    private static function validate_form_data($form_data) {
        error_log("🔍 VALIDATION DEBUG - Form Data Keys: " . implode(', ', array_keys($form_data)));
        
        $required_fields = array(
            'full_name' => 'نام و نام خانوادگی',
            'phone' => 'شماره موبایل',
            'province' => 'استان',
            'city' => 'شهر',
            'business_name' => 'نام کسب و کار'
        );

        foreach ($required_fields as $field => $label) {
            $value = isset($form_data[$field]) ? trim($form_data[$field]) : '';
            error_log("🔍 Validating field '$field' (label: $label): '$value'");
            
            if (empty($value)) {
                error_log("❌ Validation failed for field: $field");
                return array(
                    'valid' => false,
                    'message' => "فیلد {$label} الزامی است."
                );
            }
        }
        
        // Latitude و Longitude اختیاری کن
        $lat = isset($form_data['latitude']) ? floatval($form_data['latitude']) : 0;
        $lng = isset($form_data['longitude']) ? floatval($form_data['longitude']) : 0;
        
        if ($lat == 0 || $lng == 0) {
            error_log("⚠️ Warning: Missing coordinates, but continuing...");
        }

        // بررسی شماره موبایل
        if (!preg_match('/^(09\d{9}|9\d{9})$/', $form_data['phone'])) {
            return array(
                'valid' => false,
                'message' => 'شماره موبایل نامعتبر است.'
            );
        }

        return array('valid' => true);
    }

    /**
     * ایجاد جدول داده‌های موقت
     */
    private static function create_temp_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_temp_data';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            step int(2) NOT NULL,
            form_data longtext NOT NULL,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * ایجاد جدول ردیابی مراحل
     */
    private static function create_tracking_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_step_tracking';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            step int(2) NOT NULL,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY step (step)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * دریافت IP کاربر
     */
    private static function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * ارسال SMS موفقیت
     */
    private static function send_success_sms($location) {
        // پیاده‌سازی سیستم SMS در ادامه
        // فعلاً placeholder
        
        $message = "کسب و کار شما با موفقیت در نقشه ثبت شد. نام: {$location->business_name}";
        
        // ارسال SMS با API موردنظر
        // ...
    }

    /**
     * دریافت محصولات فعال برای نمایش در فرم
     */
    public static function get_active_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        $products = $wpdb->get_results("
            SELECT * FROM {$table_name} 
            WHERE is_active = 1 
            ORDER BY sort_order ASC, id ASC
        ");
        
        if (empty($products)) {
            // اگر محصولی وجود ندارد، خطا برگردان تا از JavaScript fallback جلوگیری شود
            wp_send_json_error('هیچ محصول فعالی در دیتابیس یافت نشد - برای تست منطق');
            return;
        }
        
        $formatted_products = array();
        $has_package = false;
        
        foreach ($products as $product) {
            $formatted_product = array(
                'id' => $product->id,
                'name' => $product->name,
                'subtitle' => $product->subtitle ?? '',
                'description' => $product->description,
                'icon' => $product->icon,
                'image_url' => $product->image_url,
                'type' => $product->type,
                'original_price' => intval($product->original_price), // قیمت به تومان
                'sale_price' => intval($product->sale_price), // قیمت به تومان
                'is_featured' => $product->is_featured == 1,
                'has_discount' => $product->original_price != $product->sale_price
            );
            
            if ($product->type === 'package') {
                $has_package = true;
            }
            
            $formatted_products[] = $formatted_product;
        }
        
        // گروه‌بندی محصولات بر اساس نوع
        $packages = array();
        $normal_products = array();
        $featured_products = array();
        
        foreach ($formatted_products as $fp) {
            if ($fp['type'] === 'package') {
                $packages[] = $fp;
            } elseif ($fp['type'] === 'featured') {
                $featured_products[] = $fp;
            } else {
                $normal_products[] = $fp;
            }
        }
        
        wp_send_json_success(array(
            'packages' => $packages,
            'normal_products' => $normal_products,
            'featured_products' => $featured_products,
            'special_products' => array_merge($normal_products, $featured_products) // برای سازگاری با کد قدیمی
        ));
    }

    /**
     * callback پرداخت بانک ملی
     */
    public static function bmi_payment_callback() {
        $location_id = intval($_GET['location_id'] ?? 0);
        $transaction_id = sanitize_text_field($_GET['transaction_id'] ?? '');
        
        // فقط از POST استفاده شود و هر دو حالت 'token' و 'Token' بررسی شود
        $raw_post = $_POST;
        $token = sanitize_text_field($raw_post['token'] ?? $raw_post['Token'] ?? '');
        $order_id = sanitize_text_field($raw_post['OrderId'] ?? '');
        $res_code = sanitize_text_field($raw_post['ResCode'] ?? '');
        
        error_log("BMI Callback - Data: " . json_encode($raw_post) . ", LocationId: $location_id, TransactionId: $transaction_id");

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND payment_transaction_id = %s",
            $location_id,
            $transaction_id
        ));

        if (!$location) {
            error_log('BMI Callback: Location not found for ID ' . $location_id);
            wp_redirect(home_url('/?payment_result=failed&error=location_not_found'));
            exit;
        }

        // بررسی وضعیت پرداخت - اگر ResCode = 0 یعنی موفق
        if (!empty($token) && $res_code === '0') {
            // تایید پرداخت
            $verification_result = self::verify_bmi_payment($token, $transaction_id);
            
            if ($verification_result['success']) {
                // به‌روزرسانی وضعیت پرداخت
                $options = get_option('market_google_settings', array());
                $auto_approve = isset($options['auto_approve']) && $options['auto_approve'];
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'completed',
                    'payment_ref_id' => $verification_result['ref_id'] ?? $token,
                    'status' => $auto_approve ? 'approved' : 'pending',
                    'paid_at' => current_time('mysql')
                ), array('id' => $location_id));

                // ارسال SMS موفقیت (اختیاری)
                self::send_success_sms($location);

                // اضافه کردن trigger برای SMS
                $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
                $payment_data = array(
                    'amount' => $location->price,
                    'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                    'gateway' => 'bmi',
                    'transaction_id' => $transaction_id,
                    'order_number' => $order_number,
                    'payment_amount' => number_format($location->price) . ' تومان'
                );
                
                $location_data = array(
                    'phone' => $location->phone,
                    'user_name' => $location->full_name,
                    'full_name' => $location->full_name,
                    'business_name' => $location->business_name
                );
                
                // فراخوانی action برای ارسال SMS
                do_action('market_google_payment_success', $payment_data, $location_data);

                // هدایت به صفحه موفقیت
                wp_redirect(add_query_arg(array(
                    'payment' => 'success',
                    'gateway' => 'bmi',
                    'ref_id' => $verification_result['ref_id'] ?? $token,
                    'location_id' => $location_id
                ), home_url()));
                exit;
            } else {
                // پرداخت ناموفق
                $wpdb->update($table_name, array(
                    'payment_status' => 'failed'
                ), array('id' => $location_id));

                // اضافه کردن trigger برای SMS شکست
                $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
                $payment_data = array(
                    'amount' => $location->price,
                    'gateway' => 'bmi',
                    'transaction_id' => $transaction_id,
                    'error' => $verification_result['message'],
                    'failure_reason' => $verification_result['message'],
                    'order_number' => $order_number,
                    'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                    'payment_amount' => number_format($location->price) . ' تومان'
                );
                
                $location_data = array(
                    'phone' => $location->phone,
                    'user_name' => $location->full_name,
                    'full_name' => $location->full_name,
                    'business_name' => $location->business_name
                );
                
                do_action('market_google_payment_failure', $payment_data, $location_data);

                wp_redirect(add_query_arg(array(
                    'payment' => 'failed',
                    'gateway' => 'bmi',
                    'error' => urlencode($verification_result['message']),
                    'location_id' => $location_id
                ), home_url()));
                exit;
            }
        } else {
            // کاربر پرداخت را لغو کرد یا داده‌های نادرست
            $wpdb->update($table_name, array(
                'payment_status' => 'cancelled'
            ), array('id' => $location_id));

            $order_number = '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT);
            $payment_data = array(
                'amount' => $location->price,
                'gateway' => 'bmi',
                'transaction_id' => $transaction_id,
                'order_number' => $order_number,
                'ref_id' => $order_number, // کد پیگیری همان شماره سفارش است
                'payment_amount' => number_format($location->price) . ' تومان'
            );
            
            $location_data = array(
                'phone' => $location->phone,
                'user_name' => $location->full_name,
                'full_name' => $location->full_name,
                'business_name' => $location->business_name
            );
            
            do_action('market_google_payment_cancelled', $payment_data, $location_data);

            wp_redirect(add_query_arg(array(
                'payment' => 'canceled',
                'gateway' => 'bmi',
                'location_id' => $location_id
            ), home_url()));
            exit;
        }
    }

    /**
     * تایید پرداخت بانک ملی
     */
    private static function verify_bmi_payment($token, $transaction_id) {
        $options = get_option('market_google_settings', array());
        
        $secret_key = isset($options['bmi_secret_key']) ? $options['bmi_secret_key'] : '';
        
        if (empty($secret_key)) {
            return array(
                'success' => false,
                'message' => 'کلید امنیتی درگاه تنظیم نشده است.'
            );
        }

        // رمزنگاری SignData برای verify (فقط توکن) با الگوریتم صحیح
        $sign_data = self::sadad_encrypt($token, $secret_key);
        
        error_log("🔐 BMI Verify - Token: " . substr($token, 0, 20) . "..., SignData: " . substr($sign_data, 0, 20) . "...");
        
        // ارسال درخواست تأیید به سداد
        $data = array(
            'Token' => $token,
            'SignData' => $sign_data
        );

        $response = wp_remote_post('https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify', array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress-Market-Google-Plugin/1.0'
            ),
            'timeout' => 30,
            'sslverify' => true,
            'redirection' => 5,
            'blocking' => true,
            'httpversion' => '1.1'
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'خطا در تایید پرداخت: ' . $response->get_error_message()
            );
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (isset($result['ResCode']) && ($result['ResCode'] === 0 || $result['ResCode'] === "0")) {
            return array(
                'success' => true,
                'ref_id' => $result['SystemTraceAuditNumber'] ?? $token
            );
        } else {
            $error_code = isset($result['ResCode']) ? $result['ResCode'] : 'N/A';
            $error_msg = isset($result['Description']) ? $result['Description'] : 'خطای نامشخص';
            return array(
                'success' => false,
                'message' => "تایید پرداخت ناموفق (کد: $error_code): $error_msg"
            );
        }
    }

    /**
     * تابع کمکی برای رمزنگاری سداد با الگوریتم صحیح
     */
    private static function sadad_encrypt($data, $key) {
        $decoded_key = base64_decode($key);
        if (!$decoded_key) {
            return false; // این می‌تواند باعث خطا شود
        }
        $key = $decoded_key;
        $encrypted = openssl_encrypt($data, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($encrypted);
    }

    /**
     * تبدیل اعداد فارسی به انگلیسی
     */
    private static function convert_persian_to_english($string) {
        $persian_numbers = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        return str_replace($persian_numbers, $english_numbers, $string);
    }

    /**
     * پاکسازی و تبدیل شماره‌ها
     */
    private static function sanitize_phone_number($phone) {
        // تبدیل اعداد فارسی به انگلیسی
        $phone = self::convert_persian_to_english($phone);
        // حذف همه کاراکترهای غیر عددی
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return $phone;
    }
}
