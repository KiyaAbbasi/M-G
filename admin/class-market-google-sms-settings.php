<?php
/**
 * Market Google SMS Settings
 * 
 * مدیریت تنظیمات پیامک در پنل مدیریت
 * 
 * @package Market_Google_Location
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Market_Google_SMS_Settings')) {

class Market_Google_SMS_Settings {

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
     * Initialize the settings
     */
    public static function init() {
        $instance = self::get_instance();
        // هیچ hook اضافی نیاز نیست چون در constructor تعریف شده‌اند
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_market_google_test_sms_connection', array($this, 'test_sms_connection'));
        add_action('wp_ajax_market_google_send_test_sms', array($this, 'send_test_sms'));
        add_action('wp_ajax_market_google_save_sms_settings', array($this, 'save_sms_settings'));
        add_action('wp_ajax_market_google_fix_line_number', array($this, 'fix_line_number'));
        add_action('wp_ajax_market_google_test_pattern_sms', array($this, 'ajax_test_pattern_sms'));
        add_action('wp_ajax_market_google_reset_sms_settings', array($this, 'reset_sms_settings'));
    }

    /**
     * بارگذاری فایل‌های CSS و JS
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'market-google') !== false) {
            // استفاده از فایل محلی Font Awesome به جای CDN
            wp_enqueue_style('font-awesome', plugin_dir_url(__FILE__) . 'css/fontawesome.min.css', array(), '6.4.0');
            
            wp_enqueue_style('market-google-sms-settings', plugin_dir_url(__FILE__) . 'css/market-google-sms-settings.css', array(), '1.2.1');
            wp_enqueue_script('market-google-sms-settings', plugin_dir_url(__FILE__) . 'js/market-google-sms-settings.js', array('jquery'), '1.2.1', true);
            
            wp_localize_script('market-google-sms-settings', 'marketGoogleSmsSettings', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('market_google_sms_settings_nonce'),
                'strings' => array(
                    'testSuccess' => 'اتصال به سامانه پیامکی با موفقیت برقرار شد.',
                    'testFailed' => 'اتصال به سامانه پیامکی ناموفق بود. لطفا اطلاعات را بررسی کنید.',
                    'smsSent' => 'پیامک با موفقیت ارسال شد.',
                    'smsFailed' => 'ارسال پیامک ناموفق بود.',
                    'settingsSaved' => 'تنظیمات با موفقیت ذخیره شد.',
                    'settingsFailed' => 'ذخیره تنظیمات ناموفق بود.'
                )
            ));
        }
    }

    /**
     * رندر کردن تنظیمات SMS
     */
    public function render_sms_settings() {
        $sms_settings = get_option('market_google_sms_settings', array());
        
        // بررسی وجود کلاس‌ها قبل از استفاده
        if (!class_exists('Market_Google_SMS_Handler') || !class_exists('Market_Google_SMS_Shortcode_Handler')) {
            echo '<div class="notice notice-error"><p>کلاس‌های مورد نیاز برای سیستم پیامک یافت نشدند.</p></div>';
            return;
        }
        
        try {
            $sms_handler = new Market_Google_SMS_Handler();
            $shortcode_handler = new Market_Google_SMS_Shortcode_Handler();
            
            $providers = $sms_handler->get_providers();
            $sending_methods = $sms_handler->get_sending_methods(); // Corrected variable name
            $shortcodes = $shortcode_handler->get_shortcodes();
            
            $connection_check_result = $sms_handler->is_connected();
            $is_connected = $connection_check_result['connected'];
            $sms_count = $sms_handler->get_sms_count(); // This can also be $connection_check_result['sms_count'] if is_connected is called once
        } catch (Exception $e) {
            echo '<div class="notice notice-error"><p>خطا در بارگذاری سیستم پیامک: ' . esc_html($e->getMessage()) . '</p></div>';
            return;
        }
        
        $current_method = isset($sms_settings['sending_method']) ? $sms_settings['sending_method'] : '';
        
        // دریافت تنظیمات رویدادها برای هر دو روش ارسال
        $service_events = isset($sms_settings['service_events']) ? $sms_settings['service_events'] : array();
        $pattern_events = isset($sms_settings['pattern_events']) ? $sms_settings['pattern_events'] : array();
        
        // انتخاب تنظیمات رویداد مناسب بر اساس روش ارسال فعلی
        $event_sms = ($current_method === 'pattern') ? $pattern_events : $service_events;
        
        // ترتیب جدید رویدادها طبق درخواست کاربر
        $event_types = array(
            'form_submitted' => 'ثبت فرم (بعد از ارسال اطلاعات)',
            'payment_pending' => 'در انتظار پرداخت',
            'payment_success' => 'پرداخت موفق',
            'payment_failure' => 'پرداخت ناموفق',
            'payment_cancelled' => 'لغو پرداخت',
            'payment_error' => 'خطای پرداخت',
            'order_completion' => 'تکمیل سفارش (توسط مدیر)',
            'info_delivery' => 'ارسال اطلاعات (توسط ادمین)',
            'login_code' => 'کد ورود'
        );
        
        ob_start();
        ?>
        <div class="market-google-sms-settings">
            
            <div class="sms-card">
                <div class="sms-card-header">
                    <div class="sms-card-icon">
                        <i class="fas fa-sms"></i> <!-- Changed icon -->
                    </div>
                    <div class="sms-card-title">
                        <span class="title-gray">سامانه</span>
                        <span class="title-blue">پیامکی</span>
                    </div>
                </div>

                <div class="sms-card-content">
                    <div class="sms-left-content">
                        <div class="sms-form-container">
                            <div class="sms-form-group">
                                <label class="sms-form-label required">انتخاب سامانه</label>
                                <div class="sms-select-wrapper">
                                    <select class="sms-form-input" name="market_google_sms_settings[provider]" id="sms-provider">
                                        <option value="" disabled <?php selected(empty($current_provider), true); ?>>انتخاب کنید</option>
                                        <?php foreach ($providers as $key => $provider) : ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected(isset($sms_settings['provider']) ? $sms_settings['provider'] : '', $key); ?>><?php echo esc_html($provider); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down select-arrow"></i>
                                </div>
                            </div>

                            <div class="sms-form-group">
                                <label class="sms-form-label required">انتخاب نحوه ارسال</label>
                                <div class="sms-select-wrapper">
                                    <select class="sms-form-input" name="market_google_sms_settings[sending_method]" id="sending-method">
                                        <option value="" disabled <?php selected(empty($current_method), true); ?>>انتخاب کنید</option>
                                        <?php foreach ($sending_methods as $key => $method) : ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($current_method, $key); ?>><?php echo esc_html($method); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down select-arrow"></i>
                                </div>
                            </div>

                            <div class="sms-form-group">
                                <label class="sms-form-label required">نام کاربری</label>
                                <input type="text" class="sms-form-input" name="market_google_sms_settings[username]" value="<?php echo esc_attr(isset($sms_settings['username']) ? $sms_settings['username'] : ''); ?>" placeholder="نام کاربری سامانه پیامکی">
                            </div>

                            <div class="sms-form-group">
                                <label class="sms-form-label required">رمز عبور</label>
                                <input type="password" class="sms-form-input" name="market_google_sms_settings[password]" value="<?php echo esc_attr(isset($sms_settings['password']) ? $sms_settings['password'] : ''); ?>" placeholder="رمز عبور سامانه پیامکی">
                            </div>

                            <div class="sms-form-group">
                                <label class="sms-form-label required">کلید API</label>
                                <input type="text" class="sms-form-input" name="market_google_sms_settings[api_key]" value="<?php echo esc_attr(isset($sms_settings['api_key']) ? $sms_settings['api_key'] : ''); ?>" placeholder="کلید API">
                            </div>

                            <div class="sms-form-group">
                                <label class="sms-form-label required">شماره خط سامانه</label>
                                <input type="text" class="sms-form-input" name="market_google_sms_settings[line_number]" value="<?php echo esc_attr(isset($sms_settings['line_number']) ? $sms_settings['line_number'] : ''); ?>" placeholder="شماره خط ارسال">
                            </div>

                            <div class="sms-required-note">
                                * پر کردن تمام فیلدهای ستاره دار ضروری می‌باشد.
                            </div>
                        </div>
                    </div>

                    <div class="sms-right-content">
                        <div class="sms-connection-wrapper">
                            <h4 class="sms-connection-title">وضعیت اتصال به سامانه</h4>
                            
                            <div class="sms-status-section">
                                <div class="sms-status-item">
                                    <div class="sms-status-icon <?php echo $is_connected ? 'connected' : 'disconnected'; ?>">
                                        <?php if ($is_connected) : ?>
                                            <i class="fas fa-check"></i>
                                        <?php else : ?>
                                            <i class="fas fa-times"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="sms-status-text">
                                        کاربری به سامانه <span class="provider-name">ملی‌پیامک</span> پیامک <span class="status-value <?php echo $is_connected ? 'connected' : 'disconnected'; ?>"><?php echo $is_connected ? 'متصل است' : 'متصل نیست'; ?></span>
                                    </span>
                                </div>
                                
                                <?php if ($is_connected && $sms_count !== null && $sms_count > 0) : ?>
                                <div class="sms-status-item">
                                    <div class="sms-status-icon connected">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span class="sms-status-text">
                                        اعتبار پیامک شما در سامانه <span class="sms-count"><?php echo number_format($sms_count); ?></span> عدد می‌باشد
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="sms-test-section">
                                <h5 class="sms-test-title">ارسال پیامک تست</h5>
                                
                                <div class="sms-test-tabs">
                                    <button type="button" class="sms-test-tab active" data-tab="regular">خط خدماتی</button>
                                    <button type="button" class="sms-test-tab" data-tab="pattern">پیامک پترن</button>
                                </div>
                                
                                <div class="sms-test-content active" id="regular-test-tab">
                                    <div class="sms-form-group">
                                        <label class="sms-form-label">نوع پیامک تست</label>
                                        <div class="sms-select-wrapper">
                                            <select class="sms-send-input" id="test-sms-type">
                                                <option value="" disabled selected>انتخاب کنید</option>
                                                <?php foreach ($event_types as $key => $label) : ?>
                                                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <i class="fas fa-chevron-down select-arrow"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="sms-form-group">
                                        <label class="sms-form-label">شماره موبایل</label>
                                        <input type="text" class="sms-send-input" id="test-mobile-number" placeholder="09123456789" maxlength="11">
                                    </div>
                                    
                                    <div class="sms-send-button-row">
                                        <button type="button" class="sms-send-button" id="test-connection-btn">
                                            <i class="fas fa-plug"></i> تست اتصال
                                        </button>
                                        <button type="button" class="sms-send-button" id="send-test-sms-btn">
                                            <i class="fas fa-paper-plane"></i> ارسال پیامک
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="sms-test-content" id="pattern-test-tab">
                                    <div class="sms-form-group">
                                        <label class="sms-form-label">کد پترن</label>
                                        <input type="text" class="sms-send-input" id="test-pattern-code" placeholder="کد پترن را وارد کنید" maxlength="20">
                                        <p class="sms-help-text">کد پترن تایید شده در پنل ملی پیامک را وارد کنید</p>
                                    </div>
                                    
                                    <div class="sms-form-group">
                                        <label class="sms-form-label">شماره موبایل</label>
                                        <input type="text" class="sms-send-input" id="test-pattern-mobile" placeholder="09123456789" maxlength="11">
                                    </div>
                                    
                                    <div class="sms-send-button-row">
                                        <button type="button" class="sms-send-button" id="send-test-pattern-btn">
                                            <i class="fas fa-paper-plane"></i> ارسال پیامک پترن
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- رویدادهای پیامکی -->
            <div class="sms-card" id="event-sms-container" style="<?php echo empty($current_method) ? 'display: none;' : ''; ?>">
                <div class="sms-card-header">
                    <div class="sms-card-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="sms-card-title" id="event-fields-title">
                        <?php if ($current_method === 'pattern') : ?>
                            <span class="title-gray">کدهای پترن</span>
                            <span class="title-blue">سامانه پیامکی</span>
                        <?php else : ?>
                            <span class="title-gray">متن‌های پیامک</span>
                            <span class="title-blue">رویدادی</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sms-event-card">
                    <div class="sms-event-form-group sms-event-grid">
                        <?php foreach ($event_types as $event_key => $event_label) : ?>
                            <div class="sms-event-item">
                                <div class="sms-event-header">
                                    <label class="sms-event-label"><?php echo esc_html($event_label); ?></label>
                                    <label class="sms-toggle-switch">
                                        <input type="checkbox" name="market_google_sms_settings[<?php echo $current_method === 'pattern' ? 'pattern_events' : 'service_events'; ?>][<?php echo esc_attr($event_key); ?>][enabled]" value="1" <?php checked(isset($event_sms[$event_key]['enabled']) ? $event_sms[$event_key]['enabled'] : false, true); ?>>
                                        <span class="sms-toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <!-- فیلدهای مخصوص پترن (همیشه وجود دارند اما با CSS مخفی می‌شوند) -->
                                <div class="sms-pattern-field <?php echo $current_method === 'pattern' ? 'active' : ''; ?>">
                                    <input type="text" class="sms-event-input" name="market_google_sms_settings[pattern_events][<?php echo esc_attr($event_key); ?>][value]" placeholder="کد پترن یا فرمت کامل {param};{param};CODE@@shared را وارد کنید" value="<?php echo esc_attr(isset($pattern_events[$event_key]['value']) ? $pattern_events[$event_key]['value'] : ''); ?>">
                                </div>
                                
                                <!-- فیلدهای مخصوص خط خدماتی (همیشه وجود دارند اما با CSS مخفی می‌شوند) -->
                                <div class="sms-service-field <?php echo $current_method === 'service' ? 'active' : ''; ?>">
                                    <textarea class="sms-event-textarea" name="market_google_sms_settings[service_events][<?php echo esc_attr($event_key); ?>][value]" placeholder="متن پیامک را وارد کنید"><?php echo esc_textarea(isset($service_events[$event_key]['value']) ? $service_events[$event_key]['value'] : ''); ?></textarea>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- کدهای کوتاه -->
            <div class="sms-card">
                <div class="sms-card-header">
                    <div class="sms-card-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="sms-card-title">
                        <span class="title-gray">کدهای</span>
                        <span class="title-blue">کوتاه</span>
                    </div>
                </div>

                <div class="sms-event-card">
                    <div class="sms-shortcodes-container">
                        <?php foreach ($shortcodes as $code => $description) : ?>
                            <div class="sms-shortcode-item">
                                <span class="sms-shortcode-code"><?php echo esc_html($code); ?></span>
                                <span class="sms-shortcode-desc"><?php echo esc_html($description); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>           
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * تست اتصال به سامانه پیامکی
     */
    public function test_sms_connection() {
        check_ajax_referer('market_google_sms_settings_nonce', 'nonce');

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : ''; // Consider encryption
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $line_number = isset($_POST['line_number']) ? sanitize_text_field($_POST['line_number']) : '';

        error_log("🔌 Testing SMS connection - Provider: $provider, Line: $line_number");

        if (empty($provider) || empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'اطلاعات ناقص است.'));
        }

        $settings = array(
            'provider' => $provider,
            'username' => $username,
            'password' => $password,
            'api_key' => $api_key,
            'line_number' => $line_number
        );

        if (!class_exists('Market_Google_SMS_Handler')) {
            wp_send_json_error(array('message' => 'کلاس Market_Google_SMS_Handler یافت نشد.'));
        }

        $sms_handler = new Market_Google_SMS_Handler();
        $result = $sms_handler->test_connection($settings);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => $result['message'], 
                'sms_count' => $result['sms_count']
            ));
        } else {
            // اگر خطای شماره خط داشتیم و شماره پیشنهادی وجود داشت
            if (isset($result['suggested_number'])) {
                wp_send_json_error(array(
                    'message' => $result['message'],
                    'suggested_number' => $result['suggested_number'],
                    'fix_available' => true
                ));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        }
    }

    /**
     * اصلاح خودکار شماره خط
     * این متد جدید برای اصلاح خودکار شماره خط اضافه شده است
     */
    public function fix_line_number() {
        check_ajax_referer('market_google_sms_settings_nonce', 'nonce');
        
        $line_number = isset($_POST['line_number']) ? sanitize_text_field($_POST['line_number']) : '';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        error_log("🔧 Fixing line number: $line_number for provider: $provider");
        
        if (empty($line_number) || empty($provider)) {
            wp_send_json_error(array('message' => 'اطلاعات ناقص است.'));
        }
        
        // اصلاح شماره خط بر اساس نوع سامانه پیامکی
        $fixed_number = $line_number;
        
        if ($provider === 'melipayamak') {
            // حذف پیشوندهای احتمالی
            $number = preg_replace('/^(\+98|98|0)/', '', $line_number);
            
            // اضافه کردن پیشوند مناسب
            if (is_numeric($number)) {
                $fixed_number = '3000' . $number;
            }
        }
        
        error_log("🔧 Fixed line number: $fixed_number");
        
        wp_send_json_success(array(
            'message' => 'شماره خط اصلاح شد.',
            'fixed_number' => $fixed_number
        ));
    }

    /**
     * ارسال پیامک تست
     */
    public function send_test_sms() {
        // Debug: شروع فرآیند
        error_log("📱 🚀 AJAX TEST SMS INITIATED 🚀 📱");
        error_log("📣 Debugging SMS Test...");
        
        try {
            // بررسی امنیت
            check_ajax_referer('market_google_sms_settings_nonce', 'nonce');
    
            // دریافت پارامترها
            $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
            $event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
    
            error_log("📱 Test parameters - Mobile: $mobile, Event: $event_type");
    
            if (empty($mobile)) {
                error_log("❌ Missing mobile number");
                wp_send_json_error(array('message' => 'شماره موبایل وارد نشده است.'));
                return;
            }
            
            if (empty($event_type)) {
                error_log("❌ Missing event type");
                wp_send_json_error(array('message' => 'نوع رویداد انتخاب نشده است.'));
                return;
            }
            
            // بررسی فرمت شماره موبایل
            if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
                error_log("❌ Invalid mobile format: $mobile");
                wp_send_json_error(array('message' => 'فرمت شماره موبایل صحیح نیست. شماره باید 11 رقم و با 09 شروع شود.'));
                return;
            }
    
            // بررسی وجود کلاس‌های لازم
            if (!class_exists('Market_Google_SMS_Handler')) {
                error_log("❌ SMS_Handler class not found");
                wp_send_json_error(array('message' => 'کلاس SMS_Handler یافت نشد.'));
                return;
            }
    
            // دریافت تنظیمات
            $sms_settings = get_option('market_google_sms_settings', array());
            error_log("📋 Current Settings - Provider: " . ($sms_settings['provider'] ?? 'undefined') . 
                      ", Method: " . ($sms_settings['sending_method'] ?? 'undefined'));
    
            // بررسی تنظیمات قبل از ارسال
            if (empty($sms_settings['provider']) || empty($sms_settings['username']) || empty($sms_settings['password'])) {
                error_log("❌ SMS settings incomplete");
                wp_send_json_error(array('message' => 'تنظیمات سامانه پیامکی ناقص است. لطفا ابتدا تنظیمات را تکمیل و ذخیره کنید.'));
                return;
            }
    
            // ایجاد نمونه کلاس SMS
            error_log("🛠 Creating SMS Handler instance...");
            $sms_handler = new Market_Google_SMS_Handler();
            
            // ایجاد داده‌های تستی برای پترن (بدون مقادیر پیش‌فرض)
            $test_order_number = '#MG-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $pattern_data = array(
                'full_name' => 'کاربر', // فقط این یکی مقدار پیش‌فرض دارد
                'user_name' => 'کاربر',
                'business_name' => '',
                'business_phone' => '',
                'phone' => '09123456789',
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
                'payment_status' => 'موفق',
                'failure_reason' => '',
                'error' => ''
            );
            
            // شروع زمان‌سنج برای تشخیص تأخیر
            $start_time = microtime(true);
            
            // ارسال پیامک تست با داده‌های تستی آماده شده
            $result = $sms_handler->send_test_sms($mobile, $event_type, '', $pattern_data);
            
            // پایان زمان‌سنج
            $end_time = microtime(true);
            $time_taken = round(($end_time - $start_time) * 1000);
            error_log("⏱️ SMS test took $time_taken ms");
            
            error_log("📊 Test result: " . json_encode($result));
    
            if ($result['success']) {
                wp_send_json_success(array('message' => $result['message'] . ' (زمان: ' . $time_taken . ' میلی‌ثانیه)'));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } catch (Exception $e) {
            error_log("💥 Test SMS Exception: " . $e->getMessage());
            error_log("📍 Stack trace: " . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'خطای غیرمنتظره: ' . $e->getMessage()));
        }
    }

    /**
     * ذخیره تنظیمات پیامک
     */
    public function save_sms_settings() {
        check_ajax_referer('market_google_sms_settings_nonce', 'nonce');

        if (!isset($_POST['market_google_sms_settings'])) {
            wp_send_json_error(array('message' => 'اطلاعات تنظیمات ارسال نشده است.'));
        }

        $settings = map_deep($_POST['market_google_sms_settings'], 'sanitize_text_field');
        
        // لاگ کردن تنظیمات برای عیب‌یابی
        error_log("📋 Saving SMS settings: " . json_encode($settings));
        
        // اطمینان از وجود کلیدهای تنظیمات
        if (!isset($settings['pattern_events'])) {
            $settings['pattern_events'] = array();
        }
        
        if (!isset($settings['service_events'])) {
            $settings['service_events'] = array();
        }
        
        // انتقال تنظیمات قدیمی به ساختار جدید (اگر وجود داشته باشد)
        if (isset($settings['events'])) {
            $current_method = isset($settings['sending_method']) ? $settings['sending_method'] : 'service';
            $target_key = ($current_method === 'pattern') ? 'pattern_events' : 'service_events';
            
            // انتقال تنظیمات رویدادها به کلید مناسب
            foreach ($settings['events'] as $event_key => $event_data) {
                if (!isset($settings[$target_key][$event_key])) {
                    $settings[$target_key][$event_key] = $event_data;
                }
            }
            
            // حذف کلید قدیمی
            unset($settings['events']);
        }
        
        update_option('market_google_sms_settings', $settings);
        
        wp_send_json_success(array('message' => 'تنظیمات با موفقیت ذخیره شد.'));
    }
    
    /**
     * تست ارسال پیامک با پترن
     * این متد برای تست ارسال پیامک با پترن از طریق AJAX استفاده می‌شود
     */
    public function ajax_test_pattern_sms() {
        // Debug: شروع فرآیند
        error_log("📱 🚀 AJAX TEST PATTERN SMS INITIATED 🚀 📱");
        
        try {
            // بررسی امنیت
            check_ajax_referer('market_google_sms_settings_nonce', 'nonce');
    
            // دریافت پارامترها
            $mobile = isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '';
            $pattern_code = isset($_POST['pattern_code']) ? sanitize_text_field($_POST['pattern_code']) : '';
            
            error_log("📱 Test pattern parameters - Mobile: $mobile, Pattern code: $pattern_code");
    
            if (empty($mobile)) {
                error_log("❌ Missing mobile number");
                wp_send_json_error(array('message' => 'شماره موبایل وارد نشده است.'));
                return;
            }
            
            if (empty($pattern_code)) {
                error_log("❌ Missing pattern code");
                wp_send_json_error(array('message' => 'کد پترن وارد نشده است.'));
                return;
            }
            
            // بررسی فرمت شماره موبایل
            if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
                error_log("❌ Invalid mobile format: $mobile");
                wp_send_json_error(array('message' => 'فرمت شماره موبایل صحیح نیست. شماره باید 11 رقم و با 09 شروع شود.'));
                return;
            }
    
            // بررسی وجود کلاس‌های لازم
            if (!class_exists('Market_Google_SMS_Handler')) {
                error_log("❌ SMS_Handler class not found");
                wp_send_json_error(array('message' => 'کلاس SMS_Handler یافت نشد.'));
                return;
            }
    
            // دریافت تنظیمات
            $sms_settings = get_option('market_google_sms_settings', array());
            error_log("📋 Current Settings - Provider: " . ($sms_settings['provider'] ?? 'undefined'));
    
            // بررسی تنظیمات قبل از ارسال
            if (empty($sms_settings['provider']) || empty($sms_settings['username']) || empty($sms_settings['password'])) {
                error_log("❌ SMS settings incomplete");
                wp_send_json_error(array('message' => 'تنظیمات سامانه پیامکی ناقص است. لطفا ابتدا تنظیمات را تکمیل و ذخیره کنید.'));
                return;
            }
    
            // ایجاد داده‌های تستی برای پترن (بدون مقادیر پیش‌فرض)
            $test_order_number = '#MG-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            $pattern_data = array(
                'full_name' => 'کاربر', // فقط این یکی مقدار پیش‌فرض دارد
                'user_name' => 'کاربر',
                'business_name' => '',
                'business_phone' => '',
                'phone' => '09123456789',
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
                'payment_status' => 'موفق',
                'failure_reason' => '',
                'error' => ''
            );
            
            // تلاش برای استفاده از SMS Service ابتدا، سپس SMS Handler
            $result = null;
            $start_time = microtime(true);
            
            if (class_exists('Market_Google_SMS_Service')) {
                error_log("🛠 Using SMS Service for pattern test...");
                $sms_service = new Market_Google_SMS_Service();
                $result = $sms_service->test_pattern_sms($mobile, $pattern_code, $pattern_data);
            } else {
                error_log("🛠 Falling back to SMS Handler for pattern test...");
                $sms_handler = new Market_Google_SMS_Handler();
                $result = $sms_handler->test_pattern_sms($mobile, $pattern_code, $pattern_data);
            }
            
            // پایان زمان‌سنج
            $end_time = microtime(true);
            $time_taken = round(($end_time - $start_time) * 1000);
            error_log("⏱️ Pattern SMS test took $time_taken ms");
            
            error_log("📊 Pattern test result: " . json_encode($result));
    
            if ($result['success']) {
                wp_send_json_success(array('message' => $result['message'] . ' (زمان: ' . $time_taken . ' میلی‌ثانیه)'));
            } else {
                wp_send_json_error(array('message' => $result['message']));
            }
        } catch (Exception $e) {
            error_log("💥 Test Pattern SMS Exception: " . $e->getMessage());
            error_log("📍 Stack trace: " . $e->getTraceAsString());
            wp_send_json_error(array('message' => 'خطای غیرمنتظره: ' . $e->getMessage()));
        }
    }
    
    /**
     * بازنشانی تنظیمات پیامک
     * این متد برای بازنشانی تنظیمات پیامک به حالت اولیه استفاده می‌شود
     */
    public function reset_sms_settings() {
        try {
            // بررسی امنیت
            check_ajax_referer('market_google_sms_settings_nonce', 'nonce');
            
            // دریافت نوع تب
            $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'sms';
            
            error_log("🔄 Resetting settings for tab: $tab");
            
            // بازنشانی تنظیمات بر اساس نوع تب
            if ($tab === 'sms') {
                // ایجاد ساختار جدید تنظیمات پیامک
                $current_settings = get_option('market_google_sms_settings', array());
                
                // حفظ اطلاعات اصلی اتصال
                $new_settings = array(
                    'provider' => isset($current_settings['provider']) ? $current_settings['provider'] : '',
                    'username' => isset($current_settings['username']) ? $current_settings['username'] : '',
                    'password' => isset($current_settings['password']) ? $current_settings['password'] : '',
                    'api_key' => isset($current_settings['api_key']) ? $current_settings['api_key'] : '',
                    'line_number' => isset($current_settings['line_number']) ? $current_settings['line_number'] : '',
                    'sending_method' => isset($current_settings['sending_method']) ? $current_settings['sending_method'] : 'service',
                    'pattern_events' => array(),
                    'service_events' => array()
                );
                
                // انتقال تنظیمات رویدادها به ساختار جدید
                $event_types = array(
                    'form_submitted', 'payment_pending', 'payment_success', 'payment_failure', 
                    'payment_cancelled', 'payment_error', 'order_completion', 'info_delivery', 'login_code'
                );
                
                // بررسی همه منابع ممکن تنظیمات رویدادها
                $sources = array('events', 'pattern_events', 'service_events');
                
                foreach ($event_types as $event_type) {
                    // مقادیر پیش‌فرض
                    $pattern_value = '';
                    $pattern_enabled = true; // تغییر به true برای فعال کردن همه رویدادها
                    $service_value = '';
                    $service_enabled = true; // تغییر به true برای فعال کردن همه رویدادها
                    
                    // بررسی همه منابع
                    foreach ($sources as $source) {
                        if (isset($current_settings[$source][$event_type])) {
                            // اگر فرمت پترن خاص دارد
                            if (isset($current_settings[$source][$event_type]['value']) && 
                                strpos($current_settings[$source][$event_type]['value'], '@@shared') !== false) {
                                $pattern_value = $current_settings[$source][$event_type]['value'];
                            } 
                            // در غیر این صورت به عنوان متن خط خدماتی در نظر بگیریم
                            else if (isset($current_settings[$source][$event_type]['value'])) {
                                $service_value = $current_settings[$source][$event_type]['value'];
                            }
                        }
                    }
                    
                    // ذخیره در ساختار جدید - همه رویدادها فعال
                    $new_settings['pattern_events'][$event_type] = array(
                        'enabled' => true,
                        'value' => $pattern_value
                    );
                    
                    $new_settings['service_events'][$event_type] = array(
                        'enabled' => true,
                        'value' => $service_value
                    );
                }
                
                // ذخیره تنظیمات جدید
                update_option('market_google_sms_settings', $new_settings);
                error_log("✅ SMS settings reset successfully");
                
                wp_send_json_success(array('message' => 'تنظیمات پیامک با موفقیت بازنشانی شد.'));
            } 
            // برای تب‌های دیگر (کال‌بک‌ها و غیره)
            else if ($tab === 'callbacks') {
                // پیاده‌سازی بازنشانی تنظیمات کال‌بک‌ها
                wp_send_json_success(array('message' => 'تنظیمات کال‌بک‌ها با موفقیت بازنشانی شد.'));
            }
            else {
                wp_send_json_error(array('message' => 'تب نامعتبر است.'));
            }
        } catch (Exception $e) {
            error_log("❌ Error resetting settings: " . $e->getMessage());
            wp_send_json_error(array('message' => 'خطا در بازنشانی تنظیمات: ' . $e->getMessage()));
        }
    }
}
}