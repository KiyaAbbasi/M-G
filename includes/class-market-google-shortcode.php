<?php
/**
 * کلاس شورت کدهای افزونه Market Google Location
 */
class Market_Google_Shortcode {

    /**
     * راه‌اندازی hook ها
     */
    public static function init() {
        add_shortcode('market_location_form', array(__CLASS__, 'location_form_shortcode'));
        add_shortcode('market_location_list', array(__CLASS__, 'location_list_shortcode'));
        add_shortcode('market_location_map', array(__CLASS__, 'location_map_shortcode'));
        add_shortcode('market_location_search', array(__CLASS__, 'location_search_shortcode'));
        add_shortcode('market_location_stats', array(__CLASS__, 'location_stats_shortcode'));
        add_shortcode('market_payment_result', array(__CLASS__, 'payment_result_shortcode'));
    }

    /**
     * نمایش فرم ثبت موقعیت
     */
    public static function location_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'theme' => 'default',
            'show_map' => 'yes',
            'default_city' => 'تهران',
            'price' => '50000'
        ), $atts);

        ob_start();
        ?>
        <div class="container">
            <div class="logo">
                <h1>ثبت کسب‌وکار در نقشه‌ها</h1>
                <p>راهکاری ساده برای نمایش کسب‌وکار شما در نقشه‌های معتبر</p>
            </div>
            
            <div class="market-location-container">
                <div class="form-header">
                    <h1>ثبت اطلاعات کسب‌وکار</h1>
                    <div class="step-indicator">
                        <div class="step-dot active" id="dot-1"></div>
                        <div class="step-dot" id="dot-2"></div>
                        <div class="step-dot" id="dot-3"></div>
                        <div class="step-dot" id="dot-4"></div>
                    </div>
                </div>
                
                <div class="form-body">
                    <form id="market-location-form" method="post" enctype="multipart/form-data">
                        
                        <!-- مرحله 1: اطلاعات شخصی -->
                        <div class="form-step active" data-step="1">
                            <div class="form-group">
                                <label for="full_name" class="required">نام و نام خانوادگی</label>
                                <input type="text" id="full_name" name="full_name" class="form-input" placeholder="نام کامل خود را وارد کنید" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone" class="required">شماره تلفن همراه</label>
                                <input type="tel" id="phone" name="phone" class="form-input" placeholder="09123456789" required>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary btn-next">مرحله بعد</button>
                                <div></div>
                            </div>
                        </div>

                        <!-- مرحله 2: کسب‌وکار و موقعیت -->
                        <div class="form-step" data-step="2">
                            <div class="form-group">
                                <label for="business_name" class="required">نام کسب‌وکار</label>
                                <input type="text" id="business_name" name="business_name" class="form-input" placeholder="مثال: رستوران شهرزاد" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="business_phone" class="required">شماره تماس کسب‌وکار</label>
                                <input type="tel" id="business_phone" name="business_phone" class="form-input" placeholder="مثال: 02123456789" required>
                                <p class="subtitle-form">تذکر: این شماره در نقشه نمایش داده می‌شود.</p>
                            </div>
                            
                            <div class="map-container">
                                <div id="map"></div>
                            </div>
                            
                            <div class="address-display">
                                موقعیت انتخاب شده: <strong id="selected-location">روی نقشه کلیک کنید</strong>
                                <div>مختصات: <strong id="coordinates">-</strong></div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="province" class="required">استان</label>
                                    <select id="province" name="province" class="form-select searchable-select" required>
                                        <option value="">انتخاب کنید</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="city" class="required">شهر</label>
                                    <select id="city" name="city" class="form-select searchable-select" required>
                                        <option value="">انتخاب کنید</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="manual_address">آدرس دقیق <span class="optional-label">(اختیاری)</span></label>
                                <input type="text" id="manual_address" name="manual_address" class="form-input" placeholder="مثال: خیابان ولیعصر، پلاک 100">
                            </div>
                            
                            <div class="form-group">
                                <label for="website">وب سایت <span class="optional-label">(اختیاری)</span></label>
                                <input type="text" id="website" name="website" class="form-input" placeholder="مثال: mywebsite.com" value="">
                            </div>
                            
                            <div class="form-group">
                                <label for="working_hours_text">ساعت کاری</label>
                                <input type="text" id="working_hours_text" name="working_hours_text" class="form-input" placeholder="مثال: شنبه تا چهارشنبه 9 تا 18 - پنج‌شنبه 9 تا 14" value="24 ساعته">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary btn-next">مرحله بعد</button>
                                <button type="button" class="btn btn-outline btn-prev">مرحله قبل</button>
                            </div>
                        </div>

                        <!-- مرحله 3: انتخاب نقشه‌ها -->
                        <div class="form-step" data-step="3">
                            <div class="packages-container" id="packages-container">
                                <!-- محصولات از دیتابیس لود می‌شوند -->
                                <div class="loading-packages">
                                    <div class="loading-spinner">⏳</div>
                                    <p>در حال بارگذاری محصولات...</p>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn btn-primary btn-next">مرحله بعد</button>
                                <button type="button" class="btn btn-outline btn-prev">مرحله قبل</button>
                            </div>
                        </div>

                        <!-- مرحله 4: بازبینی و پرداخت -->
                        <div class="form-step" data-step="4">
                            <div class="summary-container">
                                <div class="summary-section">
                                    <h3 class="summary-title">اطلاعات فردی</h3>
                                    <p id="summary-personal">-</p>
                                </div>
                                
                                <div class="summary-section">
                                    <h3 class="summary-title">اطلاعات کسب‌وکار</h3>
                                    <p id="summary-business">-</p>
                                    <div id="summary-working-hours"></div>
                                </div>                                
                                
                                <div class="summary-section">
                                    <h3 class="summary-title">محصولات انتخابی</h3>
                                    <div id="summary-packages-list"></div>
                                </div>
                                
                                <div class="summary-calculation">
                                    <div class="summary-row">
                                        <span class="label">جمع محصولات:</span>
                                        <span class="value" id="subtotal-price">0 تومان</span>
                                    </div>
                                    <div class="summary-row">
                                        <span class="label">مالیات بر ارزش افزوده (۱۰٪):</span>
                                        <span class="value" id="tax-amount">0 تومان</span>
                                    </div>
                                    <div class="summary-row total">
                                        <span class="label">مبلغ قابل پرداخت:</span>
                                        <span class="value" id="total-price">0 تومان</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <span>قوانین و مقررات را مطالعه کرده و پذیرفته‌ام</span>
                                </label>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-submit">پرداخت و ثبت نهایی</button>
                                <button type="button" class="btn btn-outline btn-prev">مرحله قبل</button>
                            </div>
                        </div>

                        <!-- فیلدهای مخفی -->
                        <input type="hidden" name="action" value="submit_location_form">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('market_location_nonce'); ?>">
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="selected_packages" id="selected_packages">
                        <input type="hidden" name="working_hours" id="working_hours" value="24 ساعته">
                        <input type="hidden" name="price" value="<?php echo esc_attr($atts['price']); ?>">
                    </form>
                </div>
            </div>
        </div>

        <script>
        // متغیرهای JavaScript برای استفاده در اسکریپت
        var marketLocationVars = {
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('market_location_nonce'); ?>',
            mapDefaultLat: 35.6892,
            mapDefaultLng: 51.3890,
            currency: 'تومان'
        };
        </script>

        <?php
        return ob_get_clean();
    }

    /**
     * نمایش لیست موقعیت‌ها
     */
    public static function location_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'city' => '',
            'province' => '',
            'show_map' => 'no',
            'layout' => 'list'
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $where_conditions = array("status = 'active'");
        
        if (!empty($atts['city'])) {
            $where_conditions[] = $wpdb->prepare("city = %s", $atts['city']);
        }
        
        if (!empty($atts['province'])) {
            $where_conditions[] = $wpdb->prepare("province = %s", $atts['province']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d",
            $atts['limit']
        ));

        ob_start();
        ?>
        <div class="market-location-list">
            <?php if (!empty($locations)): ?>
                <?php foreach ($locations as $location): ?>
                    <div class="location-item">
                        <h4><?php echo esc_html($location->business_name); ?></h4>
                        <p><strong>شهر:</strong> <?php echo esc_html($location->city); ?></p>
                        <p><strong>آدرس:</strong> <?php echo esc_html($location->address); ?></p>
                        <?php if ($location->phone): ?>
                            <p><strong>تلفن:</strong> <?php echo esc_html($location->phone); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>موقعیتی یافت نشد.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * نمایش نقشه موقعیت‌ها
     */
    public static function location_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'height' => '400px',
            'zoom' => 10,
            'center_lat' => 35.6892,
            'center_lng' => 51.3890,
            'city' => '',
            'province' => ''
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $where_conditions = array("status = 'active'", "latitude IS NOT NULL", "longitude IS NOT NULL");
        
        if (!empty($atts['city'])) {
            $where_conditions[] = $wpdb->prepare("city = %s", $atts['city']);
        }
        
        if (!empty($atts['province'])) {
            $where_conditions[] = $wpdb->prepare("province = %s", $atts['province']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $locations = $wpdb->get_results("SELECT * FROM {$table_name} WHERE {$where_clause}");

        ob_start();
        ?>
        <div class="market-location-map-display">
            <div id="locations-map" style="height: <?php echo esc_attr($atts['height']); ?>; width: 100%;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // راه‌اندازی نقشه
            var map = L.map('locations-map').setView([<?php echo $atts['center_lat']; ?>, <?php echo $atts['center_lng']; ?>], <?php echo $atts['zoom']; ?>);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            // اضافه کردن marker ها
            <?php foreach ($locations as $location): ?>
                L.marker([<?php echo $location->latitude; ?>, <?php echo $location->longitude; ?>])
                    .addTo(map)
                    .bindPopup('<strong><?php echo esc_js($location->business_name); ?></strong><br><?php echo esc_js($location->address); ?>');
            <?php endforeach; ?>
        });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * فرم جستجو
     */
    public static function location_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_filters' => 'yes',
            'ajax' => 'yes'
        ), $atts);

        ob_start();
        ?>
        <div class="market-location-search">
            <form id="location-search-form" method="get">
                <div class="search-fields">
                    <input type="text" name="s" placeholder="جستجو در نام کسب و کار..." value="<?php echo esc_attr(get_query_var('s')); ?>">
                    
                    <?php if ($atts['show_filters'] === 'yes'): ?>
                        <select name="province">
                            <option value="">همه استان‌ها</option>
                            <!-- استان‌ها اینجا اضافه می‌شود -->
                        </select>
                        
                        <select name="city">
                            <option value="">همه شهرها</option>
                            <!-- شهرها اینجا اضافه می‌شود -->
                        </select>
                    <?php endif; ?>
                    
                    <button type="submit">جستجو</button>
                </div>
            </form>
            
            <div id="search-results"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * نمایش آمار
     */
    public static function location_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'total,cities,recent',
            'period' => '30'
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $stats = array();
        $show_items = explode(',', $atts['show']);

        if (in_array('total', $show_items)) {
            $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'");
        }

        if (in_array('cities', $show_items)) {
            $stats['cities'] = $wpdb->get_var("SELECT COUNT(DISTINCT city) FROM {$table_name} WHERE status = 'active'");
        }

        if (in_array('recent', $show_items)) {
            $stats['recent'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
                $atts['period']
            ));
        }

        ob_start();
        ?>
        <div class="market-location-stats">
            <?php if (isset($stats['total'])): ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['total']); ?></span>
                    <span class="stat-label">کل کسب و کارها</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats['cities'])): ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['cities']); ?></span>
                    <span class="stat-label">شهرهای فعال</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($stats['recent'])): ?>
                <div class="stat-item">
                    <span class="stat-number"><?php echo number_format($stats['recent']); ?></span>
                    <span class="stat-label">ثبت شده در <?php echo $atts['period']; ?> روز اخیر</span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * صفحه نتیجه پرداخت - نسخه بهبود یافته
     */
    public static function payment_result_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect_success' => '',
            'redirect_failed' => ''
        ), $atts);

        // بررسی نتیجه پرداخت از URL
        $payment_result = isset($_GET['payment_result']) ? sanitize_text_field($_GET['payment_result']) : '';
        $location_id = isset($_GET['location_id']) ? intval($_GET['location_id']) : 0;
        $transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';
        $gateway = isset($_GET['gateway']) ? sanitize_text_field($_GET['gateway']) : '';
        $ref_id = isset($_GET['ref_id']) ? sanitize_text_field($_GET['ref_id']) : '';
        $amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
        $user_name = isset($_GET['user_name']) ? sanitize_text_field($_GET['user_name']) : '';
        $business_name = isset($_GET['business_name']) ? sanitize_text_field($_GET['business_name']) : '';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';

        if (empty($payment_result)) {
            return '<div class="payment-result-container"><p>نتیجه پرداخت مشخص نیست.</p></div>';
        }

        // استفاده از کلاس جدید صفحات پرداخت
        ob_start();
        
        // اضافه کردن فایل صفحات پرداخت
        require_once plugin_dir_path(__FILE__) . '../public/payment-pages.php';
        
        // نمایش صفحه مناسب
        Market_Google_Payment_Pages::display_payment_result();
        
        return ob_get_clean();
    }
}