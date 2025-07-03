<?php
/**
 * کلاس بخش عمومی افزونه
 */
class Market_Google_Public {
    
    /**
     * راه‌اندازی کلاس
     */
    public function init() {
        // اضافه کردن شورت‌کد
        add_shortcode('market_location_form', array($this, 'location_form_shortcode'));
        
        // اضافه کردن اسکریپت‌ها و استایل‌ها
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // اضافه کردن اکشن برای ذخیره اطلاعات لوکیشن
        add_action('wp_ajax_save_location', array($this, 'save_location'));
        add_action('wp_ajax_nopriv_save_location', array($this, 'save_location'));
    }
    
    /**
     * اضافه کردن اسکریپت‌ها و استایل‌ها
     */
    public function enqueue_scripts() {
        error_log('Market_Google_Public: enqueue_scripts called!');
        // بارگذاری tracking script در تمام صفحات برای ردیابی کامل کاربران - مهم!
        wp_enqueue_script('market-google-user-tracking', MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/js/market-google-user-tracking.js', array('jquery'), MARKET_GOOGLE_LOCATION_VERSION, true);
        
        // انتقال متغیرها به جاوااسکریپت برای tracking script (در همه صفحات) - مهم!
        wp_localize_script('market-google-user-tracking', 'marketGoogleTracking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'ajaxUrl' => admin_url('admin-ajax.php'), // fallback
            'nonce' => wp_create_nonce('market_google_tracking_nonce'),
            'enabled' => true,
            'form_id' => 'market-location-form',
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
        
        // اضافه کردن inline script برای اطمینان از بارگذاری متغیرها
        wp_add_inline_script('market-google-user-tracking', '
            console.log("🔧 Market Google Tracking: Script loaded on page: " + window.location.href);
            if (typeof marketGoogleTracking === "undefined") {
                console.log("🚨 Creating fallback marketGoogleTracking");
                window.marketGoogleTracking = {
                    ajax_url: "' . admin_url('admin-ajax.php') . '",
                    ajaxUrl: "' . admin_url('admin-ajax.php') . '",
                    nonce: "' . wp_create_nonce('market_google_tracking_nonce') . '",
                    enabled: true,
                    form_id: "market-location-form",
                    debug: true
                };
            } else {
                console.log("✅ marketGoogleTracking loaded correctly:", marketGoogleTracking);
            }
        ', 'before');
        
        // فقط در صفحات با فرم، استایل و scripts اضافی لود کن
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'market_location_form')) {
            // اضافه کردن استایل‌ها
            wp_enqueue_style('market-google-public', MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/css/market-google-public.css', array(), MARKET_GOOGLE_LOCATION_VERSION, 'all');
            // اضافه کردن Leaflet (OpenStreetMap)
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css', array(), '1.7.1');
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js', array(), '1.7.1', true);
            
            // اضافه کردن Leaflet Geocoder
            wp_enqueue_style('leaflet-geocoder', 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css', array(), '1.13.0');
            wp_enqueue_script('leaflet-geocoder', 'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js', array('leaflet'), '1.13.0', true);
            
            // اضافه کردن فونت وزیر
            wp_enqueue_style('vazir-font', 'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css', array(), '30.1.0');
            
            // اضافه کردن اسکریپت افزونه
            wp_enqueue_script('market-google-public', MARKET_GOOGLE_LOCATION_PLUGIN_URL . 'public/js/market-google-public.js', array('jquery', 'leaflet', 'leaflet-geocoder'), MARKET_GOOGLE_LOCATION_VERSION, true);
            
            // انتقال متغیرها به جاوااسکریپت برای اسکریپت اصلی
            wp_localize_script('market-google-public', 'marketLocationVars', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('market_google_nonce'),
                'i18n' => array(
                    'searchPlaceholder' => __('جستجوی آدرس...', 'market-google-location'),
                    'latitude' => __('عرض جغرافیایی', 'market-google-location'),
                    'longitude' => __('طول جغرافیایی', 'market-google-location'),
                    'dragMarker' => __('انتخاب موقعیت روی نقشه', 'market-google-location'),
                    'confirmLocation' => __('تأیید موقعیت', 'market-google-location'),
                    'submitForm' => __('ثبت اطلاعات', 'market-google-location'),
                    'paymentProcessing' => __('در حال پردازش پرداخت...', 'market-google-location'),
                    'paymentSuccess' => __('پرداخت با موفقیت انجام شد.', 'market-google-location'),
                    'paymentFailed' => __('پرداخت ناموفق بود. لطفاً دوباره تلاش کنید.', 'market-google-location'),
                    'requiredField' => __('این فیلد الزامی است', 'market-google-location'),
                    'selectLocation' => __('لطفاً موقعیت را روی نقشه انتخاب کنید', 'market-google-location'),
                )
            ));
        }
    }
    
    /**
     * شورت‌کد فرم ثبت لوکیشن
     */
    public function location_form_shortcode($atts) {
        // پارامترهای پیش‌فرض
        $atts = shortcode_atts(array(
            'height' => '500',
            'default_lat' => get_option('market_google_default_lat', '35.6892'),
            'default_lng' => get_option('market_google_default_lng', '51.3890'),
            'default_zoom' => get_option('market_google_default_zoom', '12'),
            'show_search' => 'true',
            'show_coordinates' => 'true',
            'show_address' => 'true',
            'show_business_fields' => 'true',
            'show_maps_selection' => 'true'
        ), $atts);
        
        // ایجاد شناسه یکتا
        $unique_id = uniqid('map_');
        
        // شروع خروجی
        ob_start();
        
        // بررسی نتیجه پرداخت
        if (isset($_GET['payment_result'])) {
            $payment_result = sanitize_text_field($_GET['payment_result']);
            $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
            
            if ($payment_result == 'success') {
                echo '<div class="market-location-message success"><i class="material-icons">check_circle</i>' . __('پرداخت با موفقیت انجام شد. اطلاعات کسب و کار شما ثبت شد.', 'market-google-location') . '</div>';
                
                // نمایش اطلاعات ثبت شده
                if ($location_id > 0) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'market_google_locations';
                    $location = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $location_id));
                    
                    if ($location) {
                        echo '<div class="market-location-details">';
                        echo '<h3>' . __('اطلاعات ثبت شده', 'market-google-location') . '</h3>';
                        echo '<p><strong>' . __('نام کسب و کار:', 'market-google-location') . '</strong> ' . esc_html($location->business_name) . '</p>';
                        echo '<p><strong>' . __('شماره تماس:', 'market-google-location') . '</strong> ' . esc_html($location->business_phone) . '</p>';
                        echo '<p><strong>' . __('آدرس:', 'market-google-location') . '</strong> ' . esc_html($location->address) . '</p>';
                        echo '</div>';
                    }
                }
                
                // پایان خروجی
                return ob_get_clean();
            } elseif ($payment_result == 'failed') {
                echo '<div class="market-location-message error"><i class="material-icons">error</i>' . __('پرداخت ناموفق بود. لطفاً دوباره تلاش کنید.', 'market-google-location') . '</div>';
            }
        }
        
        // ایجاد فرم
        ?>
        <div class="market-location-container">
            <form id="market-location-form" class="market-location-form" method="post">
                <div class="market-location-map-wrapper">
                    <!-- نقشه -->
                    <div class="market-location-map-container" data-id="<?php echo $unique_id; ?>">
                        <div class="market-location-map" id="<?php echo $unique_id; ?>" 
                             style="height:<?php echo $atts['height']; ?>px;" 
                             data-default-lat="<?php echo $atts['default_lat']; ?>" 
                             data-default-lng="<?php echo $atts['default_lng']; ?>" 
                             data-default-zoom="<?php echo $atts['default_zoom']; ?>">
                        </div>
                        
                        <?php if ($atts['show_search'] === 'true') : ?>
                        <!-- جستجو -->
                        <div class="market-location-map-search">
                            <i class="material-icons">search</i>
                            <input type="text" class="market-location-map-search-input" 
                                   placeholder="<?php _e('جستجوی آدرس...', 'market-google-location'); ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- فرم اطلاعات -->
                    <div class="market-location-form-container">
                        <div class="market-location-form-content">
                            <?php if ($atts['show_coordinates'] === 'true') : ?>
                            <!-- مختصات -->
                            <div class="market-location-map-coordinates">
                                <div class="market-location-map-lat">
                                    <i class="material-icons">place</i>
                                    <label><?php _e('عرض جغرافیایی', 'market-google-location'); ?>:</label>
                                    <span></span>
                                </div>
                                <div class="market-location-map-lng">
                                    <i class="material-icons">place</i>
                                    <label><?php _e('طول جغرافیایی', 'market-google-location'); ?>:</label>
                                    <span></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_address'] === 'true') : ?>
                            <!-- آدرس -->
                            <div class="market-location-map-address">
                                <i class="material-icons">location_on</i>
                                <label><?php _e('آدرس', 'market-google-location'); ?>:</label>
                                <span></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_business_fields'] === 'true') : ?>
                            <!-- اطلاعات کسب و کار -->
                            <div class="market-location-business-fields">
                                <h3><i class="material-icons">business</i> <?php _e('اطلاعات کسب و کار', 'market-google-location'); ?></h3>
                                
                                <div class="market-location-field">
                                    <label for="business_name" class="required"><?php _e('نام کسب و کار', 'market-google-location'); ?></label>
                                    <input type="text" name="business_name" id="business_name" required>
                                    <span class="field-description"><?php _e('نام کسب و کار شما که در نقشه نمایش داده می‌شود', 'market-google-location'); ?></span>
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="business_type"><?php _e('نوع کسب و کار', 'market-google-location'); ?></label>
                                    <select name="business_type" id="business_type">
                                        <option value=""><?php _e('انتخاب کنید', 'market-google-location'); ?></option>
                                        <option value="restaurant"><?php _e('رستوران', 'market-google-location'); ?></option>
                                        <option value="cafe"><?php _e('کافه', 'market-google-location'); ?></option>
                                        <option value="shop"><?php _e('فروشگاه', 'market-google-location'); ?></option>
                                        <option value="hotel"><?php _e('هتل', 'market-google-location'); ?></option>
                                        <option value="office"><?php _e('دفتر کار', 'market-google-location'); ?></option>
                                        <option value="medical"><?php _e('مرکز درمانی', 'market-google-location'); ?></option>
                                        <option value="education"><?php _e('مرکز آموزشی', 'market-google-location'); ?></option>
                                        <option value="entertainment"><?php _e('مرکز تفریحی', 'market-google-location'); ?></option>
                                        <option value="other"><?php _e('سایر', 'market-google-location'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="business_phone" class="required"><?php _e('شماره تماس', 'market-google-location'); ?></label>
                                    <input type="tel" name="business_phone" id="business_phone" required>
                                    <span class="field-description"><?php _e('شماره تماس کسب و کار شما', 'market-google-location'); ?></span>
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="business_hours"><?php _e('ساعات کاری', 'market-google-location'); ?></label>
                                    <input type="text" name="business_hours" id="business_hours" placeholder="<?php _e('مثال: شنبه تا پنجشنبه 9 الی 18', 'market-google-location'); ?>">
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="province"><?php _e('استان', 'market-google-location'); ?></label>
                                    <select name="province" id="province">
                                        <option value=""><?php _e('انتخاب کنید', 'market-google-location'); ?></option>
                                        <option value="تهران"><?php _e('تهران', 'market-google-location'); ?></option>
                                        <option value="اصفهان"><?php _e('اصفهان', 'market-google-location'); ?></option>
                                        <option value="فارس"><?php _e('فارس', 'market-google-location'); ?></option>
                                        <option value="خراسان رضوی"><?php _e('خراسان رضوی', 'market-google-location'); ?></option>
                                        <option value="آذربایجان شرقی"><?php _e('آذربایجان شرقی', 'market-google-location'); ?></option>
                                        <option value="آذربایجان غربی"><?php _e('آذربایجان غربی', 'market-google-location'); ?></option>
                                        <option value="اردبیل"><?php _e('اردبیل', 'market-google-location'); ?></option>
                                        <option value="البرز"><?php _e('البرز', 'market-google-location'); ?></option>
                                        <option value="ایلام"><?php _e('ایلام', 'market-google-location'); ?></option>
                                        <option value="بوشهر"><?php _e('بوشهر', 'market-google-location'); ?></option>
                                        <option value="چهارمحال و بختیاری"><?php _e('چهارمحال و بختیاری', 'market-google-location'); ?></option>
                                        <option value="خراسان جنوبی"><?php _e('خراسان جنوبی', 'market-google-location'); ?></option>
                                        <option value="خراسان شمالی"><?php _e('خراسان شمالی', 'market-google-location'); ?></option>
                                        <option value="خوزستان"><?php _e('خوزستان', 'market-google-location'); ?></option>
                                        <option value="زنجان"><?php _e('زنجان', 'market-google-location'); ?></option>
                                        <option value="سمنان"><?php _e('سمنان', 'market-google-location'); ?></option>
                                        <option value="سیستان و بلوچستان"><?php _e('سیستان و بلوچستان', 'market-google-location'); ?></option>
                                        <option value="قزوین"><?php _e('قزوین', 'market-google-location'); ?></option>
                                        <option value="قم"><?php _e('قم', 'market-google-location'); ?></option>
                                        <option value="کردستان"><?php _e('کردستان', 'market-google-location'); ?></option>
                                        <option value="کرمان"><?php _e('کرمان', 'market-google-location'); ?></option>
                                        <option value="کرمانشاه"><?php _e('کرمانشاه', 'market-google-location'); ?></option>
                                        <option value="کهگیلویه و بویراحمد"><?php _e('کهگیلویه و بویراحمد', 'market-google-location'); ?></option>
                                        <option value="گلستان"><?php _e('گلستان', 'market-google-location'); ?></option>
                                        <option value="گیلان"><?php _e('گیلان', 'market-google-location'); ?></option>
                                        <option value="لرستان"><?php _e('لرستان', 'market-google-location'); ?></option>
                                        <option value="مازندران"><?php _e('مازندران', 'market-google-location'); ?></option>
                                        <option value="مرکزی"><?php _e('مرکزی', 'market-google-location'); ?></option>
                                        <option value="هرمزگان"><?php _e('هرمزگان', 'market-google-location'); ?></option>
                                        <option value="همدان"><?php _e('همدان', 'market-google-location'); ?></option>
                                        <option value="یزد"><?php _e('یزد', 'market-google-location'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="city"><?php _e('شهر', 'market-google-location'); ?></label>
                                    <input type="text" name="city" id="city">
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="address" class="required"><?php _e('آدرس دقیق', 'market-google-location'); ?></label>
                                    <textarea name="address" id="address" rows="3" required></textarea>
                                    <span class="field-description"><?php _e('آدرس دقیق محل کسب و کار شما', 'market-google-location'); ?></span>
                                </div>
                                
                                <div class="market-location-field">
                                    <label for="website"><?php _e('وب‌سایت', 'market-google-location'); ?></label>
                                    <input type="text" name="website" id="website" placeholder="مثال: mywebsite.com" value="">
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($atts['show_maps_selection'] === 'true') : ?>
                            <!-- انتخاب نقشه‌ها -->
                            <div class="market-location-maps-selection">
                                <h3><i class="material-icons">map</i> <?php _e('ثبت در نقشه‌های', 'market-google-location'); ?></h3>
                                
                                <div class="market-location-maps-options">
                                    <label>
                                        <input type="checkbox" name="selected_maps[]" value="openstreetmap" checked>
                                        <?php _e('اپن استریت مپ', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_maps[]" value="google">
                                        <?php _e('گوگل مپ', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_maps[]" value="balad">
                                        <?php _e('بلد', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_maps[]" value="neshan">
                                        <?php _e('نشان', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="checkbox" name="selected_maps[]" value="waze">
                                        <?php _e('ویز', 'market-google-location'); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- انتخاب روش پرداخت -->
                            <div class="market-location-payment-method">
                                <h3><i class="material-icons">payment</i> <?php _e('روش پرداخت', 'market-google-location'); ?></h3>
                                
                                <div class="market-location-payment-options">
                                    <label>
                                        <input type="radio" name="payment_method" value="bmi" checked>
                                        <?php _e('درگاه بانک ملی', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="radio" name="payment_method" value="zarinpal">
                                        <?php _e('درگاه زرین‌پال', 'market-google-location'); ?>
                                    </label>
                                    
                                    <label>
                                        <input type="radio" name="payment_method" value="idpay">
                                        <?php _e('درگاه آیدی پی', 'market-google-location'); ?>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- فیلدهای مخفی -->
                            <input type="hidden" name="latitude" id="<?php echo $unique_id; ?>_lat" value="">
                            <input type="hidden" name="longitude" id="<?php echo $unique_id; ?>_lng" value="">
                            <input type="hidden" name="formatted_address" id="<?php echo $unique_id; ?>_address" value="">
                            <?php wp_nonce_field('market_google_location_nonce', 'market_google_nonce'); ?>
                            
                            <!-- دکمه ثبت -->
                            <div class="market-location-submit">
                                <button type="submit" class="market-location-submit-button"><i class="material-icons">send</i> <?php _e('ثبت اطلاعات', 'market-google-location'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * ذخیره اطلاعات لوکیشن
     */
    public function save_location() {
        // بررسی امنیتی
        check_ajax_referer('market_google_nonce', 'nonce');
        
        $response = array('success' => false);
        
        // دریافت اطلاعات
        $latitude = isset($_POST['latitude']) ? sanitize_text_field($_POST['latitude']) : '';
        $longitude = isset($_POST['longitude']) ? sanitize_text_field($_POST['longitude']) : '';
        $business_name = isset($_POST['business_name']) ? sanitize_text_field($_POST['business_name']) : '';
        $business_type = isset($_POST['business_type']) ? sanitize_text_field($_POST['business_type']) : '';
        $business_phone = isset($_POST['business_phone']) ? sanitize_text_field($_POST['business_phone']) : '';
        $business_hours = isset($_POST['business_hours']) ? sanitize_text_field($_POST['business_hours']) : '';
        $province = isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $address = isset($_POST['address']) ? sanitize_textarea_field($_POST['address']) : '';
        $website = isset($_POST['website']) ? sanitize_text_field($_POST['website']) : '';
        $selected_maps = isset($_POST['selected_maps']) ? $_POST['selected_maps'] : array();
        $payment_method = isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'bmi';
        
        // بررسی اطلاعات ضروری
        if (empty($latitude) || empty($longitude) || empty($business_name) || empty($business_phone) || empty($address)) {
            $response['message'] = __('لطفاً اطلاعات ضروری را وارد کنید.', 'market-google-location');
            wp_send_json($response);
        }
        
        // ذخیره اطلاعات در دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            $user_id = 1; // کاربر مهمان
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'business_name' => $business_name,
                'business_type' => $business_type,
                'business_phone' => $business_phone,
                'business_hours' => $business_hours,
                'province' => $province,
                'city' => $city,
                'address' => $address,
                'website' => $website,
                'selected_maps' => is_array($selected_maps) ? json_encode($selected_maps) : '',
                'payment_method' => $payment_method,
                'payment_status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result) {
            $response['success'] = true;
            $response['message'] = __('اطلاعات کسب و کار شما با موفقیت ثبت شد. در حال انتقال به درگاه پرداخت...', 'market-google-location');
            $response['location_id'] = $wpdb->insert_id;
            
            // فراخوانی رویداد pending برای ارسال پیامک
            $location_data = array(
                'id' => $wpdb->insert_id,
                'business_name' => $business_name,
                'full_name' => $full_name,
                'phone' => $phone,
                'email' => $email,
                'price' => $price
            );
            
            $payment_data = array(
                'amount' => $price,
                'payment_status' => 'pending'
            );
            
            do_action('market_google_payment_pending', $payment_data, $location_data);
        } else {
            $response['message'] = __('خطا در ثبت اطلاعات. لطفاً دوباره تلاش کنید.', 'market-google-location');
        }
        
        wp_send_json($response);
    }
}