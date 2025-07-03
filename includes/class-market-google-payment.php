<?php
/**
 * کلاس مدیریت پرداخت‌ها
 */
class Market_Google_Payment {
    
    /**
     * راه‌اندازی کلاس
     */
    public function init() {
        // اضافه کردن اکشن‌های مربوط به بازگشت از درگاه پرداخت
        add_action('init', array($this, 'handle_payment_return'));
        add_action('wp_ajax_nopriv_bmi_callback', array($this, 'handle_bmi_callback'));
        add_action('wp_ajax_bmi_callback', array($this, 'handle_bmi_callback'));
        add_action( 'woocommerce_api_market-google', [ $this, 'check_transaction_status' ] );
    }
    
    /**
     * دریافت URL مناسب برای redirect بر اساس تنظیمات
     */
    private function get_redirect_url($result_type, $gateway = '', $data = array()) {
        $use_custom_callbacks = get_option('market_google_use_custom_callbacks', 0);
        
        if (!$use_custom_callbacks) {
            // استفاده از صفحات جدید داخلی
            $base_url = home_url('payment-result/');
            
            $params = array_merge(array(
                'payment_result' => $result_type,
                'gateway' => $gateway
            ), $data);
            
            return add_query_arg($params, $base_url);
        }
        
        // تعیین URL بر اساس نوع نتیجه و درگاه
        $url = '';
        
        switch ($result_type) {
            case 'success':
                if ($gateway === 'zarinpal') {
                    $url = get_option('market_google_zarinpal_success_url', '');
                } elseif ($gateway === 'bmi') {
                    $url = get_option('market_google_bmi_success_url', '');
                }
                
                if (empty($url)) {
                    $url = get_option('market_google_payment_success_url', '');
                }
                break;
                
            case 'failed':
                if ($gateway === 'zarinpal') {
                    $url = get_option('market_google_zarinpal_failed_url', '');
                } elseif ($gateway === 'bmi') {
                    $url = get_option('market_google_bmi_failed_url', '');
                }
                
                if (empty($url)) {
                    $url = get_option('market_google_payment_failed_url', '');
                }
                break;
                
            case 'canceled':
                $url = get_option('market_google_payment_canceled_url', '');
                break;
                
            case 'pending':
                $url = get_option('market_google_payment_pending_url', '');
                break;
                
            case 'error':
                $url = get_option('market_google_payment_error_url', '');
                break;
        }
        
        // اگر URL سفارشی وجود نداشت، از صفحات داخلی استفاده کن
        if (empty($url)) {
            $base_url = home_url('payment-result/');
            $params = array_merge(array(
                'payment_result' => $result_type,
                'gateway' => $gateway
            ), $data);
            return add_query_arg($params, $base_url);
        }
        
        // جایگزینی پارامترها در URL سفارشی
        $params = array_merge(array(
            'payment_result' => $result_type,
            'gateway' => $gateway
        ), $data);
        
        return add_query_arg($params, $url);
    }
    
    /**
     * پردازش بازگشت از درگاه پرداخت
     */
    public function handle_payment_return() {
        if (isset($_GET['action']) && $_GET['action'] == 'market_google_payment_return') {
            error_log('Payment return handler triggered');
            
            $gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
            $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
            $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
            
            error_log("Gateway: $gateway, Transaction: $transaction_id, Location: $location_id");
            
            if (empty($transaction_id) || empty($location_id)) {
                error_log('Missing transaction_id or location_id');
                wp_redirect($this->get_redirect_url('error', $gateway, array(
                    'error' => 'invalid_params',
                    'message' => 'پارامترهای مورد نیاز یافت نشد'
                )));
                exit;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_locations';
            
            $location = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d AND payment_transaction_id = %s",
                $location_id,
                $transaction_id
            ));
            
            if (!$location) {
                error_log('Location not found in database');
                wp_redirect($this->get_redirect_url('error', $gateway, array(
                    'error' => 'location_not_found',
                    'message' => 'اطلاعات تراکنش یافت نشد'
                )));
                exit;
            }
            
            // پردازش بر اساس نوع درگاه
            if ($gateway === 'zarinpal') {
                $this->handle_zarinpal_return($location);
            } elseif ($gateway === 'bmi') {
                $this->handle_bmi_return($location);
            } else {
                error_log('Unknown gateway: ' . $gateway);
                wp_redirect($this->get_redirect_url('error', $gateway, array(
                    'error' => 'unknown_gateway',
                    'message' => 'درگاه پرداخت نامشخص'
                )));
                exit;
            }
        }
    }
    
    /**
     * پردازش بازگشت از زرین‌پال
     */
    private function handle_zarinpal_return($location) {
        $authority = sanitize_text_field($_GET['Authority'] ?? '');
        $status = sanitize_text_field($_GET['Status'] ?? '');
        
        error_log("ZarinPal return - Authority: $authority, Status: $status");
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        if ($status == 'OK' && !empty($authority)) {
            // تایید پرداخت
            $verification_result = $this->verify_zarinpal_payment($authority, $location->price);
            
            if ($verification_result['success']) {
                // به‌روزرسانی وضعیت پرداخت
                $options = get_option('market_google_settings', array());
                $auto_approve = isset($options['auto_approve']) && $options['auto_approve'];
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'completed',
                    'payment_ref_id' => $verification_result['ref_id'],
                    'status' => $auto_approve ? 'active' : 'pending',
                    'paid_at' => current_time('mysql')
                ), array('id' => $location->id));
                
                error_log('ZarinPal payment verified successfully');
                
                // فراخوانی رویداد پرداخت موفق
                $payment_data = array(
                    'ref_id' => $verification_result['ref_id'],
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'gateway' => 'zarinpal'
                );
                $this->trigger_payment_success($payment_data, $location);
                
                wp_redirect($this->get_redirect_url('success', 'zarinpal', array(
                    'ref_id' => $verification_result['ref_id'],
                    'location_id' => $location->id,
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'business_name' => $location->business_name
                )));
                exit;
            } else {
                error_log('ZarinPal verification failed: ' . $verification_result['message']);
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'failed'
                ), array('id' => $location->id));
                
                // فراخوانی رویداد پرداخت ناموفق
                $payment_data = array(
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'gateway' => 'zarinpal',
                    'error' => $verification_result['message']
                );
                $this->trigger_payment_failure($payment_data, $location);
                
                wp_redirect($this->get_redirect_url('failed', 'zarinpal', array(
                    'error' => urlencode($verification_result['message']),
                    'location_id' => $location->id,
                    'transaction_id' => $location->payment_transaction_id
                )));
                exit;
            }
        } else {
            error_log('ZarinPal payment canceled or failed');
            
            $wpdb->update($table_name, array(
                'payment_status' => 'cancelled'
            ), array('id' => $location->id));
            
            // فراخوانی رویداد لغو پرداخت
            $payment_data = array(
                'transaction_id' => $location->payment_transaction_id,
                'amount' => $location->price,
                'gateway' => 'zarinpal'
            );
            $this->trigger_payment_cancelled($payment_data, $location);
            
            wp_redirect($this->get_redirect_url('canceled', 'zarinpal', array(
                'error' => 'payment_canceled',
                'location_id' => $location->id,
                'message' => 'پرداخت لغو شد یا ناموفق بود'
            )));
            exit;
        }
    }
    
    /**
     * پردازش بازگشت از بانک ملی
     */
    private function handle_bmi_return($location) {
        // فقط از POST استفاده شود و هر دو حالت 'token' و 'Token' بررسی شود
        $raw_post = $_POST;
        $token = sanitize_text_field($raw_post['token'] ?? $raw_post['Token'] ?? '');
        $order_id = sanitize_text_field($raw_post['OrderId'] ?? '');
        $res_code = sanitize_text_field($raw_post['ResCode'] ?? '');
        
        error_log("BMI return - Data: " . json_encode($raw_post));
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        if (!empty($token) && $res_code === '0') {
            // تایید پرداخت
            $verification_result = $this->verify_bmi_payment($token, $order_id);
            
            if ($verification_result['success']) {
                $options = get_option('market_google_settings', array());
                $auto_approve = isset($options['auto_approve']) && $options['auto_approve'];
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'completed',
                    'payment_ref_id' => $verification_result['ref_id'] ?? $token,
                    'status' => $auto_approve ? 'active' : 'pending',
                    'paid_at' => current_time('mysql')
                ), array('id' => $location->id));
                
                error_log('BMI payment verified successfully');
                
                wp_redirect($this->get_redirect_url('success', 'bmi', array(
                    'ref_id' => $verification_result['ref_id'] ?? $token,
                    'location_id' => $location->id,
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'business_name' => $location->business_name
                )));
                exit;
            } else {
                error_log('BMI verification failed: ' . $verification_result['message']);
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'failed'
                ), array('id' => $location->id));
                
                wp_redirect($this->get_redirect_url('failed', 'bmi', array(
                    'error' => urlencode($verification_result['message']),
                    'location_id' => $location->id,
                    'transaction_id' => $location->payment_transaction_id
                )));
                exit;
            }
        } else {
            error_log('BMI payment canceled or invalid data');
            
            $wpdb->update($table_name, array(
                'payment_status' => 'cancelled'
            ), array('id' => $location->id));
            
            wp_redirect($this->get_redirect_url('canceled', 'bmi', array(
                'error' => 'payment_canceled',
                'location_id' => $location->id,
                'message' => 'پرداخت لغو شد یا اطلاعات نامعتبر است'
            )));
            exit;
        }
    }
    
    /**
     * تایید پرداخت زرین‌پال
     */
    private function verify_zarinpal_payment($authority, $amount) {
        $options = get_option('market_google_settings', array());
        $merchant_id = $options['zarinpal_merchant_id'] ?? '';
        
        // بررسی معتبر بودن merchant_id
        if (empty($merchant_id) || $merchant_id === 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX') {
            return array(
                'success' => false,
                'message' => 'مرچند کد زرین‌پال تنظیم نشده است.'
            );
        }
        
        $sandbox = isset($options['zarinpal_sandbox']) ? $options['zarinpal_sandbox'] : true;
        $base_url = $sandbox ? 'https://sandbox.zarinpal.com' : 'https://www.zarinpal.com';
        
        $data = array(
            'merchant_id' => $merchant_id,
            'authority' => $authority,
            'amount' => intval($amount)
        );
        
        $response = wp_remote_post($base_url . '/pg/v4/payment/verify.json', array(
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
                'message' => 'تایید پرداخت ناموفق'
            );
        }
    }
    
    /**
     * تایید پرداخت بانک ملی
     */
    private function verify_bmi_payment($token, $transaction_id) {
        $options = get_option('market_google_settings', array());
        // اصلاح نام فیلدها
        $terminal_id = $options['bmi_terminal_id'] ?? '';
        $secret_key = $options['bmi_secret_key'] ?? '';
    
        if (empty($terminal_id) || empty($secret_key)) {
            error_log('BMI settings are missing');
            return array('success' => false, 'message' => 'تنظیمات درگاه بانک ملی یافت نشد.');
        }
    
        // SignData برای تایید تراکنش فقط خود توکن است
        $sign_data = $this->sadad_encrypt($token, $secret_key);
    
        $data = array(
            'Token' => $token,
            'SignData' => $sign_data
        );
        
        error_log("BMI Verify Request Data: " . print_r($data, true));
    
        $response = wp_remote_post('https://sadad.shaparak.ir/VPG/api/v0/Advice/Verify', array(
            'body' => json_encode($data),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 45, // افزایش زمان انتظار
            'sslverify' => true // همیشه فعال باشد
        ));
        
        if (is_wp_error($response)) {
            error_log("BMI Verify WP_Error: " . $response->get_error_message());
            return array(
                'success' => false,
                'message' => 'خطا در ارتباط با بانک برای تایید پرداخت: ' . $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        error_log("BMI Verify Response Body: " . $body);
        
        if (isset($result['ResCode']) && ($result['ResCode'] === 0 || $result['ResCode'] === "0")) {
            error_log("BMI Verify Success: " . print_r($result, true));
            return array(
                'success' => true,
                'ref_id' => $result['SystemTraceAuditNumber'] ?? $token
            );
        } else {
            $error_code = isset($result['ResCode']) ? $result['ResCode'] : 'N/A';
            $error_msg = isset($result['Description']) ? $result['Description'] : 'خطای نامشخص از سمت بانک';
            error_log("BMI Verify Failed - Code: $error_code, Message: $error_msg");
            return array(
                'success' => false,
                'message' => "تایید پرداخت از سوی بانک ناموفق بود (کد خطا: $error_code): " . $this->get_error_message($error_code)
            );
        }
    }
    
    /**
     * هندل کردن callback از بانک ملی
     */
    public function handle_bmi_callback() {
        error_log('BMI Callback triggered');
        
        // فقط از POST استفاده شود و هر دو حالت 'token' و 'Token' بررسی شود
        $raw_post = $_POST;
        $token = sanitize_text_field($raw_post['token'] ?? $raw_post['Token'] ?? '');
        $order_id = sanitize_text_field($raw_post['OrderId'] ?? '');
        $res_code = sanitize_text_field($raw_post['ResCode'] ?? '');
        
        // location_id و transaction_id از GET خوانده می‌شوند چون در آدرس بازگشتی هستند
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
        
        error_log("BMI Callback - Data: " . json_encode($raw_post) . ", LocationId: $location_id");
        
        if (empty($location_id) || empty($transaction_id)) {
            wp_redirect(home_url('/?payment_result=failed&error=invalid_params_callback'));
            exit;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d AND payment_transaction_id = %s",
            $location_id,
            $transaction_id
        ));
        
        if (!$location) {
            wp_redirect(home_url('/?payment_result=failed&error=location_not_found'));
            exit;
        }
        
        if (!empty($token) && $res_code === '0') {
            // پرداخت موفق، تایید پرداخت
            $verification_result = $this->verify_bmi_payment($token, $order_id);
            
            if ($verification_result['success']) {
                $options = get_option('market_google_settings', array());
                $auto_approve = isset($options['auto_approve']) && $options['auto_approve'];
                
                $wpdb->update($table_name, array(
                    'payment_status' => 'completed',
                    'payment_ref_id' => $verification_result['ref_id'] ?? $token,
                    'status' => $auto_approve ? 'active' : 'pending',
                    'paid_at' => current_time('mysql')
                ), array('id' => $location->id));
                
                // فراخوانی رویداد پرداخت موفق
                $payment_data = array(
                    'ref_id' => $verification_result['ref_id'] ?? $token,
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'gateway' => 'bmi'
                );
                $this->trigger_payment_success($payment_data, $location);
                
                wp_redirect(add_query_arg(array(
                    'payment_result' => 'success',
                    'gateway' => 'bmi',
                    'ref_id' => $verification_result['ref_id'] ?? $token,
                    'location_id' => $location->id,
                    'transaction_id' => $location->payment_transaction_id
                ), home_url()));
                exit;
            } else {
                // تایید ناموفق
                $wpdb->update($table_name, array(
                    'payment_status' => 'failed'
                ), array('id' => $location->id));
                
                // فراخوانی رویداد پرداخت ناموفق
                $payment_data = array(
                    'transaction_id' => $location->payment_transaction_id,
                    'amount' => $location->price,
                    'gateway' => 'bmi',
                    'error' => $verification_result['message']
                );
                $this->trigger_payment_failure($payment_data, $location);
                
                wp_redirect(add_query_arg(array(
                    'payment_result' => 'failed',
                    'gateway' => 'bmi',
                    'error' => urlencode($verification_result['message']),
                    'location_id' => $location->id
                ), home_url()));
                exit;
            }
        } else {
            // پرداخت لغو شده یا ناموفق
            $wpdb->update($table_name, array(
                'payment_status' => 'cancelled'
            ), array('id' => $location->id));
            
            // فراخوانی رویداد لغو پرداخت
            $payment_data = array(
                'transaction_id' => $location->payment_transaction_id,
                'amount' => $location->price,
                'gateway' => 'bmi'
            );
            $this->trigger_payment_cancelled($payment_data, $location);
            
            wp_redirect(add_query_arg(array(
                'payment_result' => 'failed',
                'gateway' => 'bmi',
                'error' => 'payment_canceled',
                'location_id' => $location->id
            ), home_url()));
            exit;
        }
    }

    // تغییر نام تابع دوم برای جلوگیری از تکرار (خط 470)
    private function get_payment_redirect_url($status, $gateway, $location_id, $transaction_id, $error = '', $user_data = array()) {
        $use_custom_callbacks = get_option('market_google_use_custom_callbacks', 0);
        
        if (!$use_custom_callbacks) {
            $base_url = home_url();
            
            switch($status) {
                case 'success':
                    $url = get_option('market_google_payment_success_url', $base_url);
                    break;
                case 'failed':
                    $url = get_option('market_google_payment_failed_url', $base_url);
                    break;
                case 'pending':
                    $url = get_option('market_google_payment_pending_url', $base_url);
                    break;
                default:
                    $url = $base_url;
            }
        } else {
            // استفاده از تابع اصلی get_redirect_url
            return $this->get_redirect_url($status, $gateway, array(
                'location_id' => $location_id,
                'transaction_id' => $transaction_id,
                'error' => $error
            ));
        }
        
        // جایگزینی پارامترها در URL
        $params = array(
            'payment_result' => $status,
            'gateway' => $gateway,
            'location_id' => $location_id,
            'transaction_id' => $transaction_id,
            'error' => $error
        );
        
        // اضافه کردن اطلاعات کاربر
        if (!empty($user_data)) {
            $params = array_merge($params, $user_data);
        }
        
        // جایگزینی پارامترها در URL
        $url = $this->replace_url_parameters($url, $params);
        
        return add_query_arg($params, $url);
    }

    // تابع جدید برای جایگزینی پارامترها
    private function replace_url_parameters($url, $params) {
        $replacements = array(
            '{location_id}' => $params['location_id'] ?? '',
            '{transaction_id}' => $params['transaction_id'] ?? '',
            '{ref_id}' => $params['ref_id'] ?? '',
            '{gateway}' => $params['gateway'] ?? '',
            '{amount}' => $params['amount'] ?? '',
            '{user_name}' => $params['user_name'] ?? '',
            '{user_family}' => $params['user_family'] ?? '',
            '{user_mobile}' => $params['user_mobile'] ?? '',
            '{business_name}' => $params['business_name'] ?? '',
            '{business_category}' => $params['business_category'] ?? '',
            '{business_address}' => $params['business_address'] ?? '',
            '{business_phone}' => $params['business_phone'] ?? '',
            '{payment_date}' => date('Y/m/d'),
            '{payment_time}' => date('H:i:s')
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $url);
    }

    private function sadad_encrypt( $data, $key ) {
        // تغییر این خط:
        // $key = base64_decode( $key );
        // به:
        $key = base64_decode($key);
        if (!$key) {
            throw new Exception('Invalid encryption key');
        }
        // ⭐️ استفاده از الگوریتم و حالت صحیح: DES-EDE3-ECB
        $encrypted = openssl_encrypt( $data, 'DES-EDE3-ECB', $key, OPENSSL_RAW_DATA );
        return base64_encode( $encrypted );
    }

    public function check_transaction_status() {
        
        // فقط از POST استفاده شود و هر دو حالت 'token' و 'Token' بررسی شود
        $raw_post = $_POST;
        $ResCode = sanitize_text_field($raw_post['ResCode'] ?? '-1');
        $Token   = sanitize_text_field($raw_post['token'] ?? $raw_post['Token'] ?? '');
        $OrderId = sanitize_text_field($raw_post['OrderId'] ?? '0');
    
        $order = wc_get_order( $OrderId );
        if ( ! $order ) {
            $this->set_message( $this->get_error_message( 1000 ) ); // خطای سفارش یافت نشد
            wp_safe_redirect( wc_get_checkout_url() );
            exit();
        }
    
        // اگر پرداخت موفقیت آمیز نبود
        if ( $ResCode != 0 ) {
            $order->update_status( 'failed' );
            $order->add_order_note( 'پرداخت ناموفق بود. کد خطا: ' . $ResCode . ' - ' . $this->get_error_message( $ResCode ) );
            $this->set_message( $this->get_error_message( $ResCode ) );
            
            // فراخوانی رویداد پرداخت ناموفق
            $payment_data = array(
                'transaction_id' => $OrderId,
                'amount' => $order->get_total(),
                'gateway' => 'bmi',
                'error' => $this->get_error_message( $ResCode )
            );
            $location_data = array(
                'id' => $OrderId,
                'phone' => $order->get_billing_phone(),
                'business_name' => $order->get_billing_company(),
                'payment_transaction_id' => $OrderId
            );
            $this->trigger_payment_failure($payment_data, $location_data);
            
            wp_safe_redirect( $order->get_checkout_payment_url( false ) );
            exit();
        }
    
        // اگر پرداخت موفق بود، باید تایید (Verify) شود
        $sign_data  = $this->sadad_encrypt( $Token, $this->settings['secret_key'] );
        $data       = [
            'Token'    => $Token,
            'SignData' => $sign_data
        ];
    
        $result = $this->call_api( $this->get_verify_url(), $data );
    
        if ( ! empty( $result['ResCode'] ) && $result['ResCode'] == 0 ) {
            // پرداخت با موفقیت تایید شد
            $order->update_status( 'completed' );
            $order->add_order_note( 'پرداخت با موفقیت انجام شد. شماره پیگیری: ' . $result['RetrivalRefNo'] );
            $this->set_message( 'پرداخت شما با موفقیت انجام شد. شماره پیگیری: ' . $result['RetrivalRefNo'], 'success' );
            
            // فراخوانی رویداد پرداخت موفق
            $payment_data = array(
                'ref_id' => $result['RetrivalRefNo'],
                'transaction_id' => $OrderId,
                'amount' => $order->get_total(),
                'gateway' => 'bmi'
            );
            $location_data = array(
                'id' => $OrderId,
                'phone' => $order->get_billing_phone(),
                'business_name' => $order->get_billing_company(),
                'payment_transaction_id' => $OrderId
            );
            $this->trigger_payment_success($payment_data, $location_data);
            
            wp_safe_redirect( $this->get_return_url( $order ) );
            exit();
        } else {
            // خطا در تایید تراکنش
            $error_code = $result['ResCode'] ?? 'unknown';
            $order->update_status( 'failed' );
            $order->add_order_note( 'خطا در تایید تراکنش. بانک پرداخت را تایید نکرد. کد خطا: ' . $error_code );
            $this->set_message( 'خطا در تایید تراکنش. کد خطا: ' . $this->get_error_message( $error_code ) );
            
            // فراخوانی رویداد پرداخت ناموفق
            $payment_data = array(
                'transaction_id' => $OrderId,
                'amount' => $order->get_total(),
                'gateway' => 'bmi',
                'error' => $this->get_error_message( $error_code )
            );
            $location_data = array(
                'id' => $OrderId,
                'phone' => $order->get_billing_phone(),
                'business_name' => $order->get_billing_company(),
                'payment_transaction_id' => $OrderId
            );
            $this->trigger_payment_failure($payment_data, $location_data);
            
            wp_safe_redirect( $order->get_checkout_payment_url( false ) );
            exit();
        }
    }

    public function get_error_message( $code ) {
        $messages = [
            0 => 'موفق',
            -1 => 'برای توکن مقدار وارد نمایید',
            1000 => 'سفارش یافت نشد',
            1025 => 'SignData ارسالی اشتباه است',
            // ... سایر کدها
        ];
        return $messages[$code] ?? "خطای تعریف نشده (کد: $code)";
    }

    /**
     * فراخوانی رویداد پرداخت موفق
     */
    private function trigger_payment_success($payment_data, $location_data) {
        do_action('market_google_payment_success', $payment_data, $location_data);
    }

    /**
     * فراخوانی رویداد پرداخت ناموفق
     */
    private function trigger_payment_failure($payment_data, $location_data) {
        do_action('market_google_payment_failure', $payment_data, $location_data);
    }

    /**
     * فراخوانی رویداد لغو پرداخت
     */
    private function trigger_payment_cancelled($payment_data, $location_data) {
        do_action('market_google_payment_cancelled', $payment_data, $location_data);
    }

    /**
     * فراخوانی رویداد پرداخت در انتظار
     */
    private function trigger_payment_pending($payment_data, $location_data) {
        do_action('market_google_payment_pending', $payment_data, $location_data);
    }

    /**
     * فراخوانی رویداد خطای پرداخت
     */
    private function trigger_payment_error($location_id, $error_message) {
        do_action('market_google_payment_error', $location_id, $error_message);
    }
}

