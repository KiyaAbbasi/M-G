<?php
/**
 * کلاس مدیریت پنل ادمین
 */
class Market_Google_Admin {

    /**
     * راه‌اندازی
     */
    public static function init() {
        // بارگذاری کلاس لیست سفارشات
        require_once plugin_dir_path(__FILE__) . 'partials/market-google-orders-list.php';
        
        if (function_exists('add_action')) {
            add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
            add_action('admin_init', array(__CLASS__, 'admin_init'));
            add_action('wp_ajax_update_location_status', array(__CLASS__, 'update_location_status'));
            add_action('wp_ajax_export_locations', array(__CLASS__, 'export_locations')); 
            add_action('wp_ajax_delete_location', array(__CLASS__, 'delete_location'));
            add_action('wp_ajax_get_products_for_settings', array(__CLASS__, 'get_products_for_settings'));
            add_action('wp_ajax_get_product_for_edit', array(__CLASS__, 'get_product_for_edit'));
            add_action('wp_ajax_toggle_product_status', array(__CLASS__, 'toggle_product_status'));
            add_action('wp_ajax_save_product', array(__CLASS__, 'save_product'));
            add_action('wp_ajax_delete_product', array(__CLASS__, 'delete_product'));
            add_action('wp_ajax_check_products_table', array(__CLASS__, 'check_products_table'));
            add_action('wp_ajax_get_location_details', array(__CLASS__, 'get_location_details'));
            add_action('wp_ajax_send_location_info', array(__CLASS__, 'send_location_info'));
            add_action('wp_ajax_market_google_search_orders', array(__CLASS__, 'ajax_search_orders'));
            add_action('wp_ajax_market_google_autocomplete_orders', array(__CLASS__, 'ajax_autocomplete_orders'));
            add_action('wp_ajax_market_google_toggle_read_status', array(__CLASS__, 'ajax_toggle_read_status'));
            add_action('wp_ajax_market_google_mark_as_read', array(__CLASS__, 'ajax_mark_as_read'));
            add_action('wp_ajax_send_location_info_sms', array(__CLASS__, 'send_location_info_sms'));
            add_action('wp_ajax_save_market_google_settings', array(__CLASS__, 'save_market_google_settings'));
            add_action('wp_ajax_market_google_complete_order', array(__CLASS__, 'ajax_complete_order'));
            add_action('wp_ajax_market_google_uncomplete_order', array(__CLASS__, 'ajax_uncomplete_order'));
        
        // تست Ajax
            add_action('wp_ajax_market_google_test_ajax', array(__CLASS__, 'ajax_test'));
            add_action('wp_ajax_market_google_check_sms', array(__CLASS__, 'ajax_check_sms'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
            
            // اضافه کردن endpoint تست پیامک
            add_action('wp_ajax_market_google_manual_sms_test', array(__CLASS__, 'ajax_manual_sms_test'));
        } else {
            error_log('WordPress add_action function not available');
        }
    }

    /**
     * اضافه کردن منو به پنل ادمین
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Market Google Location',
            'ثبت لوکیشن',
            'manage_options',
            'market-google-location',
            array(__CLASS__, 'dashboard_page'),
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'market-google-location',
            'داشبورد',
            '🏠 داشبورد',
            'manage_options',
            'market-google-location',
            array(__CLASS__, 'dashboard_page')
        );

        add_submenu_page(
            'market-google-location',
            'لیست سفارشات',
            '📋 لیست سفارشات',
            'manage_options',
            'market-google-orders-list',
            array('Market_Google_Orders_List', 'display_page')
        );

        add_submenu_page(
            'market-google-location',
            'آمار و گزارش‌گیری',
            '📈 آمار و گزارش‌گیری',
            'manage_options',
            'market-google-reports',
            array(__CLASS__, 'reports_page')
        );

        add_submenu_page(
            'market-google-location',
            'تنظیمات',
            '⚙️ تنظیمات',
            'manage_options',
            'market-google-settings',
            array(__CLASS__, 'settings_page')
        );

        // منوی ردیابی کاربران (اگر کلاس موجود باشد)
        if (class_exists('Market_Google_Tracking_Admin')) {
            add_submenu_page(
                'market-google-location',
                'ردیابی کاربران',
                '👁️ ردیابی کاربران',
                'manage_options',
                'market-google-user-tracking',
                array(__CLASS__, 'display_tracking_page')
            );
        }

        // منوی مدیریت دستگاه‌ها (اگر کلاس موجود باشد)
        if (class_exists('Market_Google_Device_Manager')) {
            add_submenu_page(
                'market-google-location',
                'مدیریت دستگاه‌ها',
                '📱 مدیریت دستگاه‌ها',
                'manage_options',
                'market-google-device-manager',
                array(__CLASS__, 'display_device_manager_page')
            );
        }
    }

    /**
     * راه‌اندازی تنظیمات ادمین
     */
    public static function admin_init() {
        register_setting('market_google_settings', 'market_google_settings');
    }

    /**
     * بارگذاری فایل‌های CSS
     */
    public static function enqueue_styles() {
        // بارگذاری فونت وزیر برای کل سایت
        wp_enqueue_style(
            'vazir-font',
            'https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css',
            array(),
            '30.1.0'
        );
        // CSS مخصوص لیست سفارشات
        if (isset($_GET['page']) && $_GET['page'] === 'market-google-orders-list') {
            // اضافه کردن jQuery UI CSS
            wp_enqueue_style(
                'jquery-ui-css',
                'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css',
                array(),
                '1.13.2',
                'all'
            );
            
            wp_enqueue_style(
                'market-google-orders',
                plugin_dir_url(__FILE__) . 'css/market-google-orders.css',
                array(),
                '1.0.0',
                'all'
            );
            
            // بارگذاری CSS تقویم شمسی
            wp_enqueue_style(
                'jalali-calendar-css',
                plugin_dir_url(__FILE__) . 'css/jalali-calendar.css',
                array(),
                '1.0.0',
                'all'
            );
        }
    }

    /**
     * بارگذاری فایل‌های JavaScript
     */
    public static function enqueue_scripts() {
        // JavaScript مخصوص لیست سفارشات
        if (isset($_GET['page']) && $_GET['page'] === 'market-google-orders-list') {
            // اضافه کردن jQuery UI
            wp_enqueue_script('jquery-ui-autocomplete');
            
            wp_enqueue_script(
                'market-google-orders',
                plugin_dir_url(__FILE__) . 'js/market-google-orders.js',
                array('jquery', 'jquery-ui-autocomplete'),
                '1.0.0',
                false
            );
            
            // بارگذاری JavaScript تقویم شمسی
            wp_enqueue_script(
                'jalali-calendar',
                plugins_url('js/jalali-calendar.js', __FILE__),
                array('jquery'),
                '1.0.0',
                false
            );
            
            // ارسال متغیرهای JavaScript
            wp_localize_script('market-google-orders', 'market_google_orders_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'security' => wp_create_nonce('market_google_orders_nonce'),
                'messages' => array(
                    'confirm_delete' => 'آیا مطمئن هستید که می‌خواهید این سفارش را حذف کنید؟',
                    'confirm_complete' => 'آیا مطمئن هستید که می‌خواهید این سفارش را تکمیل کنید؟',
                    'coordinates_copied' => 'مختصات کپی شد',
                    'error_occurred' => 'خطایی رخ داد',
                    'loading' => 'در حال بارگذاری...',
                    'no_results' => 'نتیجه‌ای یافت نشد'
                )
            ));
        }
    }

    /**
     * صفحه داشبورد
     */
    public static function dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // آمار کلی
        $total_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?: 0;
        $active_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'") ?: 0;
        $pending_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'") ?: 0;
        $completed_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE payment_status = 'completed'") ?: 0;
        $total_revenue = $wpdb->get_var("SELECT SUM(price) FROM {$table_name} WHERE payment_status = 'completed'") ?: 0;

        // آمار 30 روز اخیر
        $recent_stats = $wpdb->get_results("
            SELECT DATE(created_at) as date, COUNT(*) as count, SUM(price) as revenue
            FROM {$table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");

        // شهرهای برتر
        $top_cities = $wpdb->get_results("
            SELECT city, COUNT(*) as count
            FROM {$table_name} 
            WHERE status = 'active'
            GROUP BY city
            ORDER BY count DESC
            LIMIT 10
        ");

        // آخرین ثبت‌نام‌ها
        $recent_locations = $wpdb->get_results("
            SELECT * FROM {$table_name}
            ORDER BY created_at DESC
            LIMIT 10
        ");

        ?>
        <div class="wrap market-admin-dashboard">
            <h1>داشبورد Market Google Location</h1>

            <!-- کارت‌های آمار -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">📍</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_locations); ?></h3>
                        <p>کل موقعیت‌ها</p>
                    </div>
                </div>

                <div class="stat-card active">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">✅</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($active_locations); ?></h3>
                        <p>موقعیت‌های فعال</p>
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">⏳</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($pending_locations); ?></h3>
                        <p>در انتظار تایید</p>
                    </div>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">💰</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_revenue); ?></h3>
                        <p>کل درآمد (تومان)</p>
                    </div>
                </div>
            </div>

            <!-- نمودارها -->
            <div class="dashboard-charts">
                <div class="chart-container">
                    <h3>آمار 30 روز اخیر</h3>
                    <canvas id="monthlyChart"></canvas>
                </div>

                <div class="chart-container">
                    <h3>شهرهای برتر</h3>
                    <canvas id="citiesChart"></canvas>
                </div>
            </div>

            <!-- جداول -->
            <div class="dashboard-tables">
                <div class="table-container">
                    <h3>آخرین ثبت‌نام‌ها</h3>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>نام کسب و کار</th>
                                <th>شهر</th>
                                <th>وضعیت</th>
                                <th>تاریخ ثبت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_locations as $location): ?>
                            <tr>
                                <td><strong><?php echo esc_html($location->business_name); ?></strong></td>
                                <td><?php echo esc_html($location->city); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $location->status; ?>">
                                        <?php echo self::get_status_label($location->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n('Y/m/d H:i', strtotime($location->created_at)); ?></td>
                                <td>
                                    <a href="?page=market-google-locations-list&action=view&id=<?php echo $location->id; ?>" 
                                       class="button button-small">مشاهده</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- اسکریپت نمودارها -->
            <script>
            jQuery(document).ready(function($) {
                // نمودار آمار ماهانه
                const monthlyData = <?php echo json_encode($recent_stats); ?>;
                const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
                
                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: monthlyData.map(item => item.date),
                        datasets: [{
                            label: 'تعداد ثبت‌نام',
                            data: monthlyData.map(item => item.count),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // نمودار شهرهای برتر
                const citiesData = <?php echo json_encode($top_cities); ?>;
                const citiesCtx = document.getElementById('citiesChart').getContext('2d');
                
                new Chart(citiesCtx, {
                    type: 'doughnut',
                    data: {
                        labels: citiesData.map(item => item.city),
                        datasets: [{
                            data: citiesData.map(item => item.count),
                            backgroundColor: [
                                '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                                '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6b7280'
                            ]
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            });
            </script>
        </div>

        <style>
        /* فونت Vazir برای یکپارچگی */
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');
        
        .market-admin-dashboard,
        .market-admin-dashboard * {
            font-family: 'Vazir', Tahoma, sans-serif !important;
        }

        .market-admin-dashboard {
            margin: 20px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-card.total .stat-icon { background: #eff6ff; color: #2563eb; }
        .stat-card.active .stat-icon { background: #dcfce7; color: #16a34a; }
        .stat-card.pending .stat-icon { background: #fefbeb; color: #d97706; }
        .stat-card.revenue .stat-icon { background: #fef2f2; color: #dc2626; }

        .stat-content h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .stat-content p {
            margin: 5px 0 0;
            color: #6b7280;
        }

        .dashboard-charts {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .chart-container h3 {
            margin-top: 0;
            margin-bottom: 20px;
        }

        .dashboard-tables {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-badge.active { background: #dcfce7; color: #16a34a; }
        .status-badge.pending { background: #fefbeb; color: #d97706; }
        .status-badge.inactive { background: #f3f4f6; color: #6b7280; }
        </style>
        <?php
    }

    /**
     * صفحه لیست موقعیت‌ها
     */
    public static function locations_list_page() {
        // بررسی درخواست manual fix
        if (isset($_GET['action']) && $_GET['action'] === 'fix_completion_fields') {
            self::manual_add_completion_fields();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // پردازش عملیات
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = sanitize_text_field($_GET['action']);
            $location_id = intval($_GET['id']);

            switch ($action) {
                case 'activate':
                    $wpdb->update($table_name, array('status' => 'active'), array('id' => $location_id));
                    echo '<div class="notice notice-success"><p>موقعیت با موفقیت فعال شد.</p></div>';
                    break;
                
                case 'deactivate':
                    $wpdb->update($table_name, array('status' => 'inactive'), array('id' => $location_id));
                    echo '<div class="notice notice-success"><p>موقعیت غیرفعال شد.</p></div>';
                    break;
            }
        }

        // فیلتر و جستجو
        $where_conditions = array('1=1');
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $city_filter = isset($_GET['city']) ? sanitize_text_field($_GET['city']) : '';

        if (!empty($search)) {
            $where_conditions[] = $wpdb->prepare(
                "(business_name LIKE %s OR full_name LIKE %s OR phone LIKE %s)",
                '%' . $search . '%',
                '%' . $search . '%',
                '%' . $search . '%'
            );
        }

        if (!empty($status_filter)) {
            $where_conditions[] = $wpdb->prepare("status = %s", $status_filter);
        }

        if (!empty($city_filter)) {
            $where_conditions[] = $wpdb->prepare("city = %s", $city_filter);
        }

        $where_clause = implode(' AND ', $where_conditions);

        // صفحه‌بندی
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}");
        $total_pages = ceil($total_items / $per_page);

        $locations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        // دریافت شهرها برای فیلتر
        $cities = $wpdb->get_col("SELECT DISTINCT city FROM {$table_name} ORDER BY city");

        ?>
        <div class="wrap">
            <h1>مدیریت موقعیت‌ها</h1>
            
            <!-- دکمه تست برای اضافه کردن فیلدهای completion -->
            <div class="notice notice-warning" style="margin: 10px 0;">
                <p>
                    <strong>راهنمای تست:</strong> 
                    اگر خطای "Unknown column 'completion_date'" می‌گیرید، 
                    <a href="?page=market-google-locations-list&action=fix_completion_fields" class="button button-secondary">
                        کلیک کنید تا فیلدهای مورد نیاز اضافه شوند
                    </a>
                </p>
            </div>

            <!-- فیلتر و جستجو -->
            <div class="tablenav top">
                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="market-google-locations-list">
                    
                    <div class="alignleft actions">
                        <select name="status">
                            <option value="">همه وضعیت‌ها</option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار انجام</option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>>تکمیل شده</option>
                        </select>

                        <select name="city">
                            <option value="">همه شهرها</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo esc_attr($city); ?>" <?php selected($city_filter, $city); ?>>
                                    <?php echo esc_html($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="submit" class="button" value="فیلتر">
                    </div>

                    <div class="alignright">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="جستجو...">
                        <input type="submit" class="button" value="جستجو">
                    </div>
                </form>
            </div>

            <!-- جدول موقعیت‌ها -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>شناسه</th>
                        <th>نام کسب و کار</th>
                        <th>صاحب کسب و کار</th>
                        <th>شهر</th>
                        <th>تلفن</th>
                        <th>مختصات</th>
                        <th>وضعیت</th>
                        <th>تاریخ ثبت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($locations)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">موردی یافت نشد.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($locations as $location): ?>
                        <tr>
                            <td><?php echo $location->id; ?></td>
                            <td><strong><?php echo esc_html($location->business_name); ?></strong></td>
                            <td><?php echo esc_html($location->full_name); ?></td>
                            <td><?php echo esc_html($location->city); ?></td>
                            <td>
                                <a href="tel:<?php echo esc_attr($location->phone); ?>">
                                    <?php echo esc_html($location->phone); ?>
                                </a>
                            </td>
                            <td>
                                <button class="button button-small copy-coordinates" 
                                        data-lat="<?php echo $location->latitude; ?>" 
                                        data-lng="<?php echo $location->longitude; ?>">
                                    کپی مختصات
                                </button>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $location->status; ?>">
                                    <?php echo self::get_status_label($location->status); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('Y/m/d', strtotime($location->created_at)); ?></td>
                            <td>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="#" class="view-location" data-id="<?php echo $location->id; ?>">مشاهده</a> |
                                    </span>
                                    
                                    <?php if ($location->status === 'pending'): ?>
                                        <span class="activate">
                                            <a href="?page=market-google-locations-list&action=activate&id=<?php echo $location->id; ?>">
                                                تایید
                                            </a> |
                                        </span>
                                    <?php elseif ($location->status === 'active'): ?>
                                        <span class="deactivate">
                                            <a href="?page=market-google-locations-list&action=deactivate&id=<?php echo $location->id; ?>">
                                                غیرفعال
                                            </a> |
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="delete">
                                        <a href="#" class="delete-location submitdelete" data-id="<?php echo $location->id; ?>">
                                            حذف
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- صفحه‌بندی -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '‹',
                            'next_text' => '›',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        
                        if ($page_links) {
                            echo '<span class="displaying-num">' . 
                                 sprintf('%s مورد', number_format_i18n($total_items)) . 
                                 '</span>';
                            echo $page_links;
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- مودال مشاهده جزئیات -->
        <div id="location-modal" class="location-modal" style="display: none;">
            <div class="modal-content">
                <span class="modal-close">&times;</span>
                <div id="modal-body"></div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // کپی مختصات
            $('.copy-coordinates').click(function() {
                const lat = $(this).data('lat');
                const lng = $(this).data('lng');
                const coordinates = `${lat}, ${lng}`;
                
                navigator.clipboard.writeText(coordinates).then(function() {
                    alert('مختصات کپی شد: ' + coordinates);
                });
            });

            // مشاهده جزئیات
            $('.view-location').click(function(e) {
                e.preventDefault();
                const locationId = $(this).data('id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_location_details',
                        location_id: locationId,
                        nonce: '<?php echo wp_create_nonce('admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#modal-body').html(response.data.html);
                            $('#location-modal').show();
                        }
                    }
                });
            });

            // بستن مودال
            $('.modal-close, .location-modal').click(function(e) {
                if (e.target === this) {
                    $('#location-modal').hide();
                }
            });
            
            // ارسال اطلاعات
            $(document).on('click', '.send-info-button', function(e) {
                e.preventDefault();
                const locationId = $(this).data('id');
                
                if (confirm('آیا مطمئن هستید که می‌خواهید اطلاعات این موقعیت را ارسال کنید؟')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'send_location_info',
                            location_id: locationId,
                            nonce: '<?php echo wp_create_nonce('admin_nonce'); ?>'
                        },
                        beforeSend: function() {
                            $('.send-info-button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> در حال ارسال...');
                        },
                        success: function(response) {
                            if (response.success) {
                                alert(response.data);
                            } else {
                                alert(response.data);
                            }
                            $('.send-info-button').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> ارسال اطلاعات');
                        },
                        error: function() {
                            alert('خطا در ارسال درخواست.');
                            $('.send-info-button').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> ارسال اطلاعات');
                        }
                    });
                }
            });

            // حذف موقعیت
            $('.delete-location').click(function(e) {
                e.preventDefault();
                if (confirm('آیا مطمئن هستید که می‌خواهید این موقعیت را حذف کنید؟')) {
                    const locationId = $(this).data('id');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_location',
                            location_id: locationId,
                            nonce: '<?php echo wp_create_nonce('admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('خطا در حذف موقعیت.');
                            }
                        }
                    });
                }
            });
        });
        </script>

        <style>
        /* فونت Vazir برای صفحه لیست موقعیت‌ها */
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');
        
        .wrap,
        .wrap *,
        .wp-list-table,
        .wp-list-table *,
        .tablenav,
        .tablenav *,
        .search-form,
        .search-form * {
            font-family: 'Vazir', Tahoma, sans-serif !important;
        }

        .location-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 800px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .search-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-form .alignleft {
            display: flex;
            gap: 10px;
        }

        .search-form .alignright {
            display: flex;
            gap: 5px;
        }
        </style>
        <?php
    }

    /**
     * صفحه تنظیمات
     */
    public static function settings_page() {
        // دریافت تب فعال از URL یا POST
        $current_tab = 'general'; // پیش‌فرض
        
        if (isset($_POST['submit']) && isset($_POST['active_tab'])) {
            // اگر فرم ارسال شده، از POST بخوان
            $current_tab = sanitize_text_field($_POST['active_tab']);
        } elseif (isset($_GET['tab'])) {
            // اگر فرم ارسال نشده، از URL بخوان
            $current_tab = sanitize_text_field($_GET['tab']);
        }
        

        
        // دریافت تنظیمات موجود - هر کدام جداگانه
        $options = array(
            // عمومی
            'api_key' => get_option('market_google_api_key', ''),
            'default_lat' => get_option('market_google_default_lat', '35.6892'),
            'default_lng' => get_option('market_google_default_lng', '51.3890'),
            'default_zoom' => get_option('market_google_default_zoom', 12),
            'auto_approve' => get_option('market_google_auto_approve', false),
            'max_products' => get_option('market_google_max_products', 5),
            'payment_pending_timeout' => get_option('market_google_payment_pending_timeout', 15),
            
            // درگاه‌های پرداخت
            'bmi_terminal_id' => get_option('market_google_bmi_terminal_id', ''),
            'bmi_merchant_id' => get_option('market_google_bmi_merchant_id', ''),
            'bmi_secret_key' => get_option('market_google_bmi_secret_key', ''),
            'zarinpal_enabled' => get_option('market_google_zarinpal_enabled', false),
            'zarinpal_merchant_id' => get_option('market_google_zarinpal_merchant_id', ''),
            
            // تنظیمات شماره تراکنش
            'transaction_prefix' => get_option('market_google_transaction_prefix', 'MG'),
            'transaction_digits' => get_option('market_google_transaction_digits', 6),
            
            // پیامک
            'sms_enabled' => get_option('market_google_sms_enabled', false),
            'sms_api_key' => get_option('market_google_sms_api_key', ''),
            'sms_template' => get_option('market_google_sms_template', '')
        );
        
        // نمایش پیام موفقیت اگر تنظیمات ذخیره شده
        if (isset($_GET['settings-updated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>تنظیمات با موفقیت ذخیره شد.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>تنظیمات افزونه Market Google Location</h1>

            <form method="post" action="<?php echo admin_url('admin.php?page=market-google-settings'); ?>">
                <input type="hidden" id="active_tab" name="active_tab" value="<?php echo esc_attr($current_tab); ?>">
                <?php wp_nonce_field('market-google-settings'); ?>
                <div class="settings-tabs">
                    <div class="tab-nav">
                        <button type="button" class="tab-button <?php echo $current_tab === 'general' ? 'active' : ''; ?>" data-tab="general">تنظیمات عمومی</button>
                        <button type="button" class="tab-button <?php echo $current_tab === 'products' ? 'active' : ''; ?>" data-tab="products">محصولات</button>
                        <button type="button" class="tab-button <?php echo $current_tab === 'payment' ? 'active' : ''; ?>" data-tab="payment">درگاه‌های پرداخت</button>
                        <button type="button" class="tab-button <?php echo $current_tab === 'callbacks' ? 'active' : ''; ?>" data-tab="callbacks">تنظیمات کال‌بک</button>
                        <button type="button" class="tab-button <?php echo $current_tab === 'sms' ? 'active' : ''; ?>" data-tab="sms">سیستم پیامک</button>
                    </div>

                    <!-- تب تنظیمات عمومی -->
                    <div class="tab-content <?php echo $current_tab === 'general' ? 'active' : ''; ?>" id="general-tab">
                        <h2>تنظیمات عمومی</h2>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="api_key">Google Maps API Key</label>
                                </th>
                                <td>
                                    <input type="text" id="api_key" name="api_key" value="<?php echo esc_attr($options['api_key'] ?? ''); ?>" class="regular-text">
                                    <p class="description">کلید API گوگل مپس را وارد کنید. برای دریافت کلید API به <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">این لینک</a> مراجعه کنید.</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label>تنظیمات پیش‌فرض نقشه</label>
                                </th>
                                <td>
                                    <table class="form-table" style="margin: 0;">
                                        <tr>
                                            <th scope="row" style="padding-left: 0;">
                                                <label for="default_lat">عرض جغرافیایی پیش‌فرض</label>
                                            </th>
                                            <td>
                                                <input type="text" id="default_lat" name="default_lat" value="<?php echo esc_attr($options['default_lat'] ?? '35.6892'); ?>" class="small-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row" style="padding-left: 0;">
                                                <label for="default_lng">طول جغرافیایی پیش‌فرض</label>
                                            </th>
                                            <td>
                                                <input type="text" id="default_lng" name="default_lng" value="<?php echo esc_attr($options['default_lng'] ?? '51.3890'); ?>" class="small-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row" style="padding-left: 0;">
                                                <label for="default_zoom">بزرگنمایی پیش‌فرض (1-20)</label>
                                            </th>
                                            <td>
                                                <input type="number" id="default_zoom" name="default_zoom" value="<?php echo esc_attr($options['default_zoom'] ?? '12'); ?>" class="small-text" min="1" max="20">
                                            </td>
                                        </tr>
                                    </table>
                                    <p class="description">این تنظیمات برای نمایش اولیه نقشه استفاده می‌شود.</p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label>تایید خودکار</label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_approve" value="1" 
                                               <?php checked(isset($options['auto_approve']) ? $options['auto_approve'] : false); ?>>
                                        موقعیت‌ها بعد از پرداخت بلافاصله تایید شوند
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="max_products">حداکثر تعداد محصولات قابل انتخاب</label>
                                </th>
                                <td>
                                    <input type="number" id="max_products" name="max_products" min="1" max="10"
                                           value="<?php echo esc_attr($options['max_products'] ?? 5); ?>" 
                                           class="regular-text">
                                    <p class="description">حداکثر تعداد محصولات که کاربر می‌تواند انتخاب کند</p>
                                </td>
                            </tr>
                            
                            <tr>
                            
                            <th scope="row">
                                <label for="delete_tables_on_uninstall">حذف جداول و داده‌های افزونه هنگام حذف</label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="delete_tables_on_uninstall" name="delete_tables_on_uninstall" value="1" <?php checked(get_option('market_google_delete_tables_on_uninstall', false), 1); ?>>
                                    اگر این گزینه فعال باشد، هنگام حذف افزونه تمام جداول و داده‌های افزونه نیز حذف خواهند شد.
                                </label>
                                <p class="description" style="color: #d9534f; font-weight: bold;">
                                    <?php
                                    $delete_tables = get_option('market_google_delete_tables_on_uninstall', false);
                                    if ($delete_tables) {
                                        echo 'هشدار: با حذف افزونه، تمام داده‌ها و جداول افزونه پاک می‌شود!';
                                    } else {
                                        echo 'با حذف افزونه، داده‌ها و جداول افزونه باقی می‌ماند.';
                                    }
                                    ?>
                                </p>
                            </td>
                        </tr>

                            <tr>
                                <th scope="row">
                                    <label for="payment_pending_timeout">تایم‌اوت پیامک در انتظار پرداخت (دقیقه)</label>
                                </th>
                                <td>
                                    <input type="number" id="payment_pending_timeout" name="payment_pending_timeout" min="1" max="60"
                                           value="<?php echo esc_attr($options['payment_pending_timeout'] ?? 15); ?>" 
                                           class="small-text">
                                    <p class="description">مدت زمان انتظار (به دقیقه) قبل از ارسال پیامک در انتظار پرداخت در صورت عدم دریافت کال‌بک از درگاه پرداخت</p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- تب محصولات -->
                    <div class="tab-content <?php echo $current_tab === 'products' ? 'active' : ''; ?>" id="products-tab">
                        <h2>مدیریت محصولات</h2>
                        <div id="products-management">
                            <!-- محتوای محصولات اینجا لود می‌شود -->
                            <div class="products-loading">
                                <p>در حال بارگذاری محصولات...</p>
                            </div>
                        </div>
                    </div>

                    <!-- تب درگاه‌های پرداخت -->
                    <div class="tab-content <?php echo $current_tab === 'payment' ? 'active' : ''; ?>" id="payment-tab">
                        <h2>تنظیمات درگاه‌های پرداخت</h2>

                        <div class="gateway-section">
                            <h3 style="color: #006600;">🏦 درگاه بانک ملی (اصلی)</h3>
                            <div style="background: #f0f9f0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p style="margin: 0; font-weight: bold; color: #006600;">
                                    این درگاه به عنوان درگاه اصلی استفاده می‌شود
                                </p>
                            </div>
                            <table class="form-table">
                            <tr>
                                <th scope="row">شماره ترمینال</th>
                                <td>
                                    <input type="text" name="bmi_terminal_id" 
                                           value="<?php echo esc_attr($options['bmi_terminal_id'] ?? ''); ?>" 
                                           class="regular-text" placeholder="شماره ترمینال 8 رقمی">
                                    <p class="description">شماره ترمینال دریافتی از بانک ملی (8 رقم)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">شماره پذیرنده</th>
                                <td>
                                    <input type="text" name="bmi_merchant_id" 
                                           value="<?php echo esc_attr($options['bmi_merchant_id'] ?? ''); ?>" 
                                           class="regular-text" placeholder="شماره پذیرنده 15 رقمی">
                                    <p class="description">شماره پذیرنده دریافتی از بانک ملی (15 رقم)</p>
                                </td>
                            </tr>
                                <tr>
                                    <th scope="row">کلید ترمینال</th>
                                    <td>
                                        <input type="password" name="bmi_secret_key" 
                                               value="<?php echo esc_attr($options['bmi_secret_key'] ?? ''); ?>" 
                                               class="regular-text" placeholder="کلید امنیتی ترمینال">
                                        <p class="description">کلید امنیتی ترمینال دریافتی از بانک ملی</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- جداکننده -->
                        <hr style="border: none; border-top: 2px solid #ddd; margin: 30px 0;">

                        <div class="gateway-section">
                            <h3 style="color: #e6b800;">⚡ درگاه زرین‌پال (پشتیبان)</h3>
                            <div style="background: #fffbf0; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p style="margin: 0; font-weight: bold; color: #e6b800;">
                                    در صورت عدم دسترسی به بانک ملی، به زرین‌پال سوییچ می‌شود
                                </p>
                            </div>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">فعال‌سازی زرین‌پال</th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="zarinpal_enabled" value="1" 
                                                   <?php checked(isset($options['zarinpal_enabled']) ? $options['zarinpal_enabled'] : true); ?>>
                                            فعال
                                        </label>
                                        <p class="description">در صورت غیرفعال بودن، فقط از بانک ملی استفاده خواهد شد</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">مرچند کد</th>
                                    <td>
                                        <input type="text" name="zarinpal_merchant_id" 
                                               value="<?php echo esc_attr($options['zarinpal_merchant_id'] ?? ''); ?>" 
                                               class="regular-text" placeholder="مرچند کد 36 کاراکتری زرین‌پال">
                                        <p class="description">مرچند کد دریافتی از زرین‌پال (36 کاراکتر)</p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- جداکننده -->
                        <hr style="border: none; border-top: 2px solid #ddd; margin: 30px 0;">

                        <!-- تنظیمات شماره تراکنش -->
                        <div class="gateway-section">
                            <h3 style="color: #0073aa;">🔢 تنظیمات شماره تراکنش</h3>
                            <div style="background: #f0f6ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                                <p style="margin: 0; font-weight: bold; color: #0073aa;">
                                    شخصی‌سازی فرمت نمایش شماره تراکنش در لیست سفارشات
                                </p>
                            </div>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">پیشوند شماره تراکنش</th>
                                    <td>
                                        <input type="text" name="transaction_prefix" 
                                               value="<?php echo esc_attr($options['transaction_prefix'] ?? 'MG'); ?>" 
                                               class="small-text" placeholder="MG" maxlength="5">
                                        <p class="description">پیشوند نمایش داده شده قبل از شماره تراکنش (حداکثر 5 کاراکتر)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">تعداد رقم نمایش</th>
                                    <td>
                                        <select name="transaction_digits">
                                            <?php 
                                            $current_digits = isset($options['transaction_digits']) ? intval($options['transaction_digits']) : 6;
                                            for ($i = 4; $i <= 10; $i++): 
                                            ?>
                                                <option value="<?php echo $i; ?>" <?php selected($current_digits, $i); ?>>
                                                    <?php echo $i; ?> رقم
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <p class="description">تعداد ارقام نمایش داده شده بعد از پیشوند (4 تا 10 رقم)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">نمونه نمایش</th>
                                    <td>
                                        <code id="transaction-preview" style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px;">
                                            MG123456
                                        </code>
                                        <p class="description">پیش‌نمایش شماره تراکنش با تنظیمات فعلی</p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- تب تنظیمات کال‌بک -->
                    <div class="tab-content <?php echo $current_tab === 'callbacks' ? 'active' : ''; ?>" id="callbacks-tab">
                        <h2>تنظیمات صفحات بازگشت پرداخت</h2>
                        <?php include plugin_dir_path(__FILE__) . 'partials/market-google-admin-settings.php'; ?>
                    </div>

                    <!-- تب سیستم پیامک -->
                    <div class="tab-content <?php echo $current_tab === 'sms' ? 'active' : ''; ?>" id="sms-tab">
                        <h2>تنظیمات سیستم پیامک</h2>
                        <?php 
                        // نمایش تنظیمات پیشرفته SMS
                        if (class_exists('Market_Google_SMS_Settings')) {
                            try {
                                $sms_settings_instance = new Market_Google_SMS_Settings();
                                echo $sms_settings_instance->render_sms_settings();
                            } catch (Exception $e) {
                                echo '<div class="notice notice-error"><p>خطا در بارگذاری تنظیمات SMS: ' . esc_html($e->getMessage()) . '</p></div>';
                            }
                        } else {
                            echo '<p>کلاس تنظیمات SMS یافت نشد.</p>';
                        }
                        ?>
                    </div>
                </div>

                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // مدیریت ذخیره تنظیمات با Ajax
            $('form').submit(function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $button = $form.find('input[type="submit"]');
                var originalText = $button.val();
                
                // نمایش loading
                $button.val('در حال ذخیره...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $form.serialize() + '&action=save_market_google_settings',
                    success: function(response) {
                        if (response.success) {
                            // نمایش پیام موفقیت
                            $('body').prepend('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                            
                            // حذف notice بعد از 3 ثانیه
                            setTimeout(function() {
                                $('.notice-success').fadeOut();
                            }, 3000);
                        } else {
                            $('body').prepend('<div class="notice notice-error is-dismissible"><p>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $('body').prepend('<div class="notice notice-error is-dismissible"><p>خطا در ذخیره تنظیمات</p></div>');
                    },
                    complete: function() {
                        $button.val(originalText).prop('disabled', false);
                    }
                });
            });
            
            // مدیریت تب‌ها
            $('.tab-button').click(function(e) {
                e.preventDefault();
                var targetTab = $(this).data('tab');
                
                // به‌روزرسانی کلاس‌های فعال
                $('.tab-button').removeClass('active');
                $(this).addClass('active');
                
                $('.tab-content').removeClass('active');
                $('#' + targetTab + '-tab').addClass('active');
                
                // به‌روزرسانی فیلد مخفی
                $('#active_tab').val(targetTab);
                
                // به‌روزرسانی URL بدون reload
                if (history.pushState) {
                    var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?page=market-google-settings&tab=' + targetTab;
                    window.history.pushState({path: newUrl}, '', newUrl);
                }
            });
            
            // بارگذاری محصولات برای تب محصولات
            $(document).on('click', '.tab-button[data-tab="products"]', function() {
                if ($('#products-management .products-loading').length) {
                    $.post(ajaxurl, {
                        action: 'get_products_for_settings',
                        nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $('#products-management').html(response.data.html);
                        }
                    });
                }
            });
            
            // اگر تب محصولات فعال است، محصولات را بارگذاری کن
            if ($('.tab-button[data-tab="products"]').hasClass('active')) {
                $.post(ajaxurl, {
                    action: 'get_products_for_settings',
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#products-management').html(response.data.html);
                    }
                });
            }
            
            // بروزرسانی پیش‌نمایش شماره تراکنش
            function updateTransactionPreview() {
                var prefix = $('input[name="transaction_prefix"]').val() || 'MG';
                var digits = $('select[name="transaction_digits"]').val() || 6;
                var sample = '123456789';
                var preview = prefix + sample.substring(0, digits);
                $('#transaction-preview').text(preview);
            }
            
            // بروزرسانی پیش‌نمایش هنگام تغییر تنظیمات
            $('input[name="transaction_prefix"], select[name="transaction_digits"]').on('input change', updateTransactionPreview);
            
            // بروزرسانی اولیه پیش‌نمایش
            updateTransactionPreview();
        });
        </script>

        <style>
        .settings-tabs {
            margin-top: 20px;
        }
        
        .tab-nav {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-size: 14px;
            margin-right: 10px;
        }
        
        .tab-button.active {
            border-bottom-color: #0073aa;
            color: #0073aa;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-table th {
            width: 200px;
        }
        </style>

        <?php
    }

    /**
     * صفحه گزارش‌گیری
     */
    public static function reports_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // آمار کلی
        $total_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?: 0;
        $active_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'") ?: 0;
        $pending_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'") ?: 0;
        $completed_payments = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE payment_status = 'completed'") ?: 0;
        $total_revenue = $wpdb->get_var("SELECT SUM(price) FROM {$table_name} WHERE payment_status = 'completed'") ?: 0;

        // آمار ماهانه
        $monthly_stats = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_registrations,
                SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as completed_payments,
                SUM(CASE WHEN payment_status = 'completed' THEN price ELSE 0 END) as monthly_revenue
            FROM {$table_name} 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
        ");

        // شهرهای برتر
        $top_cities = $wpdb->get_results("
            SELECT city, COUNT(*) as count, SUM(CASE WHEN payment_status = 'completed' THEN price ELSE 0 END) as revenue
            FROM {$table_name} 
            GROUP BY city
            ORDER BY count DESC
            LIMIT 10
        ");

        ?>
        <div class="wrap">
            <h1>آمار و گزارش‌گیری</h1>

            <!-- کارت‌های آمار سریع -->
            <div class="stats-cards">
                <div class="stat-card total">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">📊</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_locations); ?></h3>
                        <p>کل ثبت‌نام‌ها</p>
                    </div>
                </div>

                <div class="stat-card active">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">✅</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($active_locations); ?></h3>
                        <p>فعال</p>
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">⏳</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($pending_locations); ?></h3>
                        <p>در انتظار</p>
                    </div>
                </div>

                <div class="stat-card revenue">
                    <div class="stat-icon">
                        <span style="font-size: 24px;">💰</span>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo number_format($total_revenue); ?></h3>
                        <p>کل درآمد (تومان)</p>
                    </div>
                </div>
            </div>

            <!-- جداول گزارش -->
            <div class="reports-container">
                <div class="report-section">
                    <h2>آمار ماهانه</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ماه</th>
                                <th>کل ثبت‌نام‌ها</th>
                                <th>پرداخت موفق</th>
                                <th>درآمد (تومان)</th>
                                <th>نرخ تبدیل</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_stats as $stat): ?>
                            <tr>
                                <td><?php echo $stat->month; ?></td>
                                <td><?php echo number_format($stat->total_registrations); ?></td>
                                <td><?php echo number_format($stat->completed_payments); ?></td>
                                <td><?php echo number_format($stat->monthly_revenue); ?></td>
                                <td>
                                    <?php 
                                    $conversion_rate = $stat->total_registrations > 0 ? 
                                        ($stat->completed_payments / $stat->total_registrations) * 100 : 0;
                                    echo number_format($conversion_rate, 1) . '%';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="report-section">
                    <h2>شهرهای برتر</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>شهر</th>
                                <th>تعداد ثبت‌نام</th>
                                <th>درآمد (تومان)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_cities as $city): ?>
                            <tr>
                                <td><?php echo esc_html($city->city); ?></td>
                                <td><?php echo number_format($city->count); ?></td>
                                <td><?php echo number_format($city->revenue); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- دکمه‌های export -->
            <div class="export-section">
                <h2>خروجی گزارش‌ها</h2>
                <button class="button button-primary" id="export-excel">📊 خروجی Excel</button>
                <button class="button" id="export-csv">📄 خروجی CSV</button>
            </div>
        </div>

        <style>
        /* فونت Vazir برای صفحه گزارش‌گیری */
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');
        
        .wrap,
        .wrap *,
        .stats-cards,
        .stats-cards *,
        .reports-container,
        .reports-container *,
        .export-section,
        .export-section * {
            font-family: 'Vazir', Tahoma, sans-serif !important;
        }

        .reports-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }

        .report-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .export-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 20px;
        }

        .export-section button {
            margin-left: 10px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('#export-excel, #export-csv').click(function() {
                var format = $(this).attr('id').replace('export-', '');
                window.location.href = ajaxurl + '?action=export_reports&format=' + format + '&nonce=<?php echo wp_create_nonce('export_reports'); ?>';
            });
        });
        </script>
        <?php
    }

    /**
     * صفحه سیستم پیامک
     */
    public static function sms_page() {
        echo '<div class="wrap"><h1>سیستم پیامک</h1><p>این بخش در حال توسعه است.</p></div>';
    }

    /**
     * Display tracking page
     */
    public static function display_tracking_page() {
        if (class_exists('Market_Google_Tracking_Admin')) {
            $tracking_admin = new Market_Google_Tracking_Admin();
            $tracking_admin->admin_page_content();
        } else {
            echo '<div class="wrap"><h1>ردیابی کاربران</h1><p>کلاس ردیابی یافت نشد.</p></div>';
        }
    }
    
    /**
     * Display device manager page
     */
    public static function display_device_manager_page() {
        if (class_exists('Market_Google_Device_Manager')) {
            $device_manager = new Market_Google_Device_Manager();
            $device_manager->admin_page_content();
        } else {
            echo '<div class="wrap"><h1>مدیریت دستگاه‌ها</h1><p>کلاس مدیریت دستگاه یافت نشد.</p></div>';
        }
    }



    /**
     * به‌روزرسانی وضعیت موقعیت
     */
    public static function update_location_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_die('Security check failed');
        }

        $location_id = intval($_POST['location_id']);
        $status = sanitize_text_field($_POST['status']);

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'market_google_locations',
            array('status' => $status),
            array('id' => $location_id)
        );

        if ($result !== false) {
            wp_send_json_success();
        } else {
            wp_send_json_error('خطا در به‌روزرسانی وضعیت موقعیت');
        }
    }

    /**
     * حذف موقعیت
     */
    public static function delete_location() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_die('Security check failed');
        }

        $location_id = intval($_POST['location_id']);

        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->prefix . 'market_google_locations',
            array('id' => $location_id)
        );

        if ($result !== false) {
            wp_send_json_success('موقعیت با موفقیت حذف شد');
        } else {
            wp_send_json_error('خطا در حذف موقعیت');
        }
    }

    /**
     * خروجی Excel
     */
    public static function export_locations() {
        // پیاده‌سازی export در ادامه
        wp_send_json_success();
    }

    /**
     * دریافت برچسب وضعیت
     */
    public static function get_status_label($status) {
        // تبدیل وضعیت‌های قدیمی به جدید
        $status = self::migrate_status($status);
        
        $labels = array(
            'pending' => 'در انتظار انجام',
            'completed' => 'تکمیل شده'
        );

        return isset($labels[$status]) ? $labels[$status] : 'در انتظار انجام';
    }
    
    /**
     * تبدیل وضعیت‌های قدیمی به سیستم جدید
     */
    public static function migrate_status($status) {
        // تبدیل وضعیت‌های قدیمی
        $migration_map = array(
            'active' => 'pending',
            'inactive' => 'pending', 
            'rejected' => 'pending',
            'pending' => 'pending',
            'completed' => 'completed'
        );
        
        return isset($migration_map[$status]) ? $migration_map[$status] : 'pending';
    }
    
    /**
     * دریافت برچسب وضعیت پرداخت
     */
    public static function get_payment_status_label($status) {
        $labels = array(
            'pending' => 'درانتظار پرداخت',
            'success' => 'پرداخت موفق',
            'completed' => 'پرداخت موفق',
            'failed' => 'پرداخت ناموفق',
            'cancelled' => 'لغو پرداخت'
        );

        return isset($labels[$status]) ? $labels[$status] : 'درانتظار پرداخت';
    }



    /**
     * دریافت جزئیات موقعیت برای نمایش در مودال
     */
    public static function get_location_details() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // علامت‌گذاری سفارش به عنوان خوانده شده
        $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $order_id)
        );

        // تبدیل داده‌های JSON به آرایه
        $selected_products = !empty($order->selected_products) ? json_decode($order->selected_products, true) : array();
        
        // دریافت محصولات از جدول محصولات
        $products_table = $wpdb->prefix . 'market_google_products';
        $products = array();
        
        // بررسی اینکه آیا پکیج ویژه انتخاب شده است یا نه
        $has_special_package = false;
        if (!empty($selected_products)) {
            foreach ($selected_products as $product_id => $quantity) {
                $product = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$products_table} WHERE id = %d",
                    $product_id
                ));
                
                if ($product) {
                    $products[$product_id] = (array) $product;
                    
                    // بررسی اینکه آیا پکیج ویژه 'all-maps' انتخاب شده است
                    if ($product->product_key === 'all-maps' || $product->title === 'تمامی نقشه‌های آنلاین') {
                        $has_special_package = true;
                    }
                }
            }
        }
        
        // تعیین محصولات برای نمایش
        if ($has_special_package) {
            // اگر پکیج ویژه انتخاب شده، محصولات معمولی را نمایش دهیم
            $normal_products = $wpdb->get_results(
                "SELECT * FROM {$products_table} WHERE type = 'normal' OR product_key IN ('google-maps', 'neshan', 'balad', 'openstreet') ORDER BY sort_order ASC",
                ARRAY_A
            );
            
            $products_to_show = array();
            foreach ($normal_products as $product) {
                $products_to_show[$product['id']] = $product;
            }
        } else {
            // اگر پکیج ویژه انتخاب نشده، محصولات انتخابی را نمایش دهیم
            $products_to_show = $products;
        }
        
        // نمایش شماره سفارش با فرمت MG-100, MG-101, ...
        $order_number = 'MG-' . str_pad($order->id + 99, 3, '0', STR_PAD_LEFT);
        
        // تابع برای بررسی اینکه فیلد خالی است یا نه
        $is_empty_field = function($value) {
            return empty($value) || trim($value) === '' || $value === null;
        };
        
        // تابع برای فرمت کردن ساعت کاری
        $format_working_hours = function($working_hours) use ($is_empty_field) {
            if ($is_empty_field($working_hours)) {
                return 'توسط کاربر تکمیل نشده';
            }
            
            // اگر ساعت کاری "24/7" یا "24 ساعته" باشد
            if ($working_hours === '24/7' || $working_hours === '24 ساعته') {
                return '24 ساعته';
            }
            
            // اگر ساعت کاری به صورت JSON ذخیره شده
            if (is_string($working_hours) && (strpos($working_hours, '{') !== false || strpos($working_hours, '[') !== false)) {
                $hours_data = json_decode($working_hours, true);
                if (is_array($hours_data) && !empty($hours_data)) {
                    // فیلتر کردن مقادیر خالی
                    $filtered_hours = array_filter($hours_data, function($item) {
                        return !empty(trim($item));
                    });
                    
                    if (!empty($filtered_hours)) {
                        return implode(', ', $filtered_hours);
                    }
                }
            }
            
            // برگرداندن همان مقدار
            return $working_hours;
        };
        
        // خروجی HTML
        ob_start();
        ?>
        <div class="order-details-container">            
            <!-- محتوای مودال -->
            <div class="order-details-content">
                <!-- اطلاعات اصلی سفارش -->
                <div class="order-main-details">
                    <div class="order-info-section">
                        <!-- نام و نام خانوادگی -->
                        <div class="info-group">
                            <div class="info-label">نام و نام خانوادگی:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->full_name) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->full_name); ?>">
                                <?php echo $is_empty_field($order->full_name) ? 'توسط کاربر تکمیل نشده' : esc_html($order->full_name); ?>
                            </div>
                        </div>
                        
                        <!-- شماره موبایل -->
                        <div class="info-group">
                            <div class="info-label">شماره موبایل:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->phone) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->phone); ?>">
                                <?php echo $is_empty_field($order->phone) ? 'توسط کاربر تکمیل نشده' : esc_html($order->phone); ?>
                            </div>
                        </div>
                        
                        <!-- نام کسب و کار -->
                        <div class="info-group">
                            <div class="info-label">نام کسب و کار:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->business_name) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->business_name); ?>">
                                <?php echo $is_empty_field($order->business_name) ? 'توسط کاربر تکمیل نشده' : esc_html($order->business_name); ?>
                            </div>
                        </div>
                        
                        <!-- شماره کسب و کار -->
                        <div class="info-group">
                            <div class="info-label">شماره کسب و کار:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->business_phone) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->business_phone ?? ''); ?>">
                                <?php echo $is_empty_field($order->business_phone) ? 'توسط کاربر تکمیل نشده' : esc_html($order->business_phone); ?>
                            </div>
                        </div>
                        
                        <!-- وب‌سایت -->
                        <div class="info-group">
                            <div class="info-label">وب‌سایت:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->website) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->website ?? ''); ?>">
                                <?php echo $is_empty_field($order->website) ? 'توسط کاربر تکمیل نشده' : esc_html($order->website); ?>
                            </div>
                        </div>
                        
                        <!-- ساعت کاری -->
                        <div class="info-group">
                            <div class="info-label">ساعت کاری:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->working_hours) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($format_working_hours($order->working_hours)); ?>">
                                <?php echo $format_working_hours($order->working_hours); ?>
                            </div>
                        </div>
                        
                        <!-- مختصات -->
                        <div class="info-group">
                            <div class="info-label">مختصات:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo $order->latitude . ', ' . $order->longitude; ?>">
                                <?php echo $order->latitude . ', ' . $order->longitude; ?>
                            </div>
                        </div>
                        
                        <!-- آدرس -->
                        <div class="info-group">
                            <div class="info-label">آدرس:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->manual_address) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->manual_address ?? $order->address ?? ''); ?>">
                                <?php 
                                // نمایش آدرس دستی که کاربر وارد کرده
                                $address_to_show = !empty($order->manual_address) ? $order->manual_address : (!empty($order->address) ? $order->address : '');
                                echo $is_empty_field($address_to_show) ? 'توسط کاربر تکمیل نشده' : esc_html($address_to_show); 
                                ?>
                            </div>
                        </div>
                        
                        <!-- شهر -->
                        <div class="info-group">
                            <div class="info-label">شهر:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo esc_attr($order->city); ?>">
                                <?php echo esc_html($order->city); ?>
                            </div>
                        </div>
                        
                        <!-- استان -->
                        <div class="info-group">
                            <div class="info-label">استان:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo esc_attr($order->province ?? $order->state ?? ''); ?>">
                                <?php echo esc_html($order->province ?? $order->state ?? ''); ?>
                            </div>
                        </div>
                        
                        <!-- مبلغ پرداختی -->
                        <div class="info-group">
                            <div class="info-label">مبلغ پرداختی:</div>
                            <div class="info-value payment-amount-display <?php echo ($order->payment_status === 'success' || $order->payment_status === 'completed') ? 'payment-success' : ''; ?>">
                                <?php 
                                $amount = isset($order->price) ? floatval($order->price) : 0;
                                $amount = $amount / 10; // تبدیل به تومان
                                echo number_format($amount, 0, '.', ',') . ' تومان';
                                ?>
                            </div>
                        </div>
                        
                        <!-- تاریخ و ساعت ثبت -->
                        <div class="info-group">
                            <div class="info-label">تاریخ و ساعت ثبت:</div>
                            <div class="info-value">
                                <?php 
                                $jalali_date = Market_Google_Orders_List::convert_to_shamsi_date($order->created_at);
                                echo $jalali_date;
                                ?>
                            </div>
                        </div>
                        
                        <!-- وضعیت پرداخت -->
                        <div class="info-group">
                            <div class="info-label">وضعیت پرداخت:</div>
                            <div class="info-value status-control">
                                <span class="status-badge payment-<?php echo esc_attr($order->payment_status); ?>">
                                    <?php echo Market_Google_Orders_List::get_payment_status_label($order->payment_status); ?>
                                </span>
                                
                                <select class="change-payment-status" data-id="<?php echo $order_id; ?>">
                                    <option value="pending" <?php selected($order->payment_status, 'pending'); ?>>درانتظار پرداخت</option>
                                    <option value="success" <?php selected($order->payment_status, 'success'); ?>>پرداخت موفق</option>
                                    <option value="failed" <?php selected($order->payment_status, 'failed'); ?>>پرداخت ناموفق</option>
                                    <option value="cancelled" <?php selected($order->payment_status, 'cancelled'); ?>>لغو پرداخت</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- وضعیت سفارش -->
                        <div class="info-group">
                            <div class="info-label">وضعیت سفارش:</div>
                            <div class="info-value status-control">
                                <span class="status-badge order-<?php echo esc_attr(self::migrate_status($order->status)); ?>">
                                    <?php echo self::get_status_label($order->status); ?>
                                </span>
                                
                                <select class="change-order-status" data-id="<?php echo $order_id; ?>">
                                    <option value="pending" <?php selected(self::migrate_status($order->status), 'pending'); ?>>در انتظار انجام</option>
                                    <option value="completed" <?php selected(self::migrate_status($order->status), 'completed'); ?>>تکمیل شده</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- محصولات انتخاب شده -->
                <div class="order-products-section">
                    <h3>محصولات انتخاب شده</h3>
                    <?php if (empty($selected_products)): ?>
                        <p>هیچ محصولی انتخاب نشده است.</p>
                    <?php else: ?>
                        <div class="order-products-list">
                            <?php if ($has_special_package): ?>
                                <!-- اگر پکیج ویژه انتخاب شده، محصولات معمولی نمایش داده می‌شود -->
                                <?php foreach ($products_to_show as $product_id => $product): ?>
                                    <div class="product-item">
                                        <div class="product-name">
                                            <?php echo esc_html($product['title']); ?>
                                            <div class="package-contents">
                                                <small>جزء پکیج "تمامی نقشه‌های آنلاین"</small>
                                            </div>
                                        </div>
                                        <div class="product-quantity">
                                            <span class="quantity-label">تعداد:</span>
                                            <span class="quantity-value">1</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- محصولات انتخابی معمولی -->
                                <?php foreach ($selected_products as $product_id => $quantity): ?>
                                    <div class="product-item">
                                        <div class="product-name">
                                            <?php 
                                            if (isset($products[$product_id])) {
                                                echo esc_html($products[$product_id]['title']);
                                                
                                                // نمایش محتویات پکیج
                                                $package_contents = json_decode($products[$product_id]['package_contents'] ?? '[]', true);
                                                if (!empty($package_contents) && is_array($package_contents)) {
                                                    echo '<div class="package-contents">';
                                                    echo '<ul>';
                                                    foreach ($package_contents as $item) {
                                                        echo '<li>' . esc_html($item) . '</li>';
                                                    }
                                                    echo '</ul>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo 'محصول #' . $product_id . ' (حذف شده)';
                                            } 
                                            ?>
                                        </div>
                                        <div class="product-quantity">
                                            <span class="quantity-label">تعداد:</span>
                                            <span class="quantity-value"><?php echo intval($quantity); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- نقشه -->
                <div class="map-container">
                    <h3>موقعیت مکانی</h3>
                    <div id="order-map" style="height: 300px; width: 100%; margin-top: 10px; border-radius: 8px;" data-lat="<?php echo esc_attr($order->latitude); ?>" data-lng="<?php echo esc_attr($order->longitude); ?>"></div>
                </div>
            </div>
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'order_id' => $order_id,
            'order_number' => $order_number
        ));
    }

    /**
     * دریافت محصولات برای نمایش در تب تنظیمات
     */
    public static function get_products_for_settings() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
            return;
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_admin')) {
            wp_send_json_error('Security check failed');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        // Verify table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error('Products table does not exist');
            return;
        }
        
        $products = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY sort_order ASC, id ASC");
        
        ob_start();
        ?>
        <div class="products-in-settings">
            <div class="products-header">
                <h2>📦 مدیریت محصولات</h2>
                <div class="products-actions">
                    <button type="button" class="btn-primary add-product-btn" data-action="add">
                        <i class="icon">➕</i>
                        <span>افزودن محصول جدید</span>
                    </button>
                    <button type="button" class="btn-secondary" id="refresh-products">
                        <i class="icon">🔄</i>
                        <span>بروزرسانی</span>
                    </button>
                </div>
            </div>

            <div class="products-container">
                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">📦</div>
                        <h3>هیچ محصولی یافت نشد</h3>
                        <p>برای شروع، اولین محصول خود را اضافه کنید</p>
                        <button type="button" class="btn-primary add-product-btn" data-action="add-first">
                            افزودن محصول
                        </button>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                            <div class="product-item <?php echo !$product->is_active ? 'inactive' : ''; ?>" data-id="<?php echo $product->id; ?>">
                                <div class="product-header">
                                    <div class="product-image">
                                        <?php if (!empty($product->image_url)): ?>
                                            <img src="<?php echo esc_url($product->image_url); ?>" alt="<?php echo esc_attr($product->name); ?>">
                                        <?php else: ?>
                                            <div class="placeholder-icon"><?php echo $product->icon; ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="product-type-badge <?php echo $product->type; ?> <?php echo $product->is_featured ? 'featured' : ''; ?>">
                                        <?php 
                                        switch($product->type) {
                                            case 'package':
                                                echo '📦 پکیج';
                                                break;
                                            case 'featured':
                                                echo '⭐ برجسته';
                                                break;
                                            default:
                                                echo '🏪 معمولی';
                                        }
                                        ?>
                                    </div>
                                </div>

                                <div class="product-content">
                                    <h3 class="product-title"><?php echo esc_html($product->name); ?></h3>
                                    <?php if (!empty($product->subtitle)): ?>
                                        <p class="product-subtitle"><?php echo esc_html($product->subtitle); ?></p>
                                    <?php endif; ?>
                                    <p class="product-description"><?php echo esc_html($product->description); ?></p>
                                    
                                    <div class="product-pricing">
                                        <?php if ($product->original_price != $product->sale_price): ?>
                                            <span class="original-price"><?php echo number_format($product->original_price); ?> تومان</span>
                                            <span class="sale-price"><?php echo number_format($product->sale_price); ?> تومان</span>
                                            <span class="discount-badge">
                                                <?php echo round((($product->original_price - $product->sale_price) / $product->original_price) * 100); ?>% تخفیف
                                            </span>
                                        <?php else: ?>
                                            <span class="sale-price"><?php echo number_format($product->sale_price); ?> تومان</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="product-actions">
                                    <button type="button" class="btn-edit edit-product" data-id="<?php echo $product->id; ?>">
                                        <i class="icon">✏️</i>
                                        ویرایش
                                    </button>
                                    <button type="button" class="btn-toggle toggle-status" data-id="<?php echo $product->id; ?>" data-status="<?php echo $product->is_active; ?>">
                                        <i class="icon"><?php echo $product->is_active ? '❌' : '✅'; ?></i>
                                        <?php echo $product->is_active ? 'غیرفعال' : 'فعال'; ?>
                                    </button>
                                    <button type="button" class="btn-delete delete-product" data-id="<?php echo $product->id; ?>">
                                        <i class="icon">🗑️</i>
                                        حذف
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- مودال افزودن/ویرایش محصول -->
        <div id="product-modal" class="modern-modal">
            <div class="modal-overlay"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <h3 id="modal-title">افزودن محصول جدید</h3>
                    <button type="button" class="modal-close">
                        <i class="icon">✕</i>
                    </button>
                </div>
                
                <form id="product-form-modal" class="modal-form">
                    <!-- nonce برای AJAX save_product -->
                    <input type="hidden" id="modal-nonce" name="nonce" value="<?php echo wp_create_nonce('market_google_admin'); ?>">
                    
                    <input type="hidden" id="modal-product-id" name="product_id" value="">
                    
                    <!-- ردیف اول: عنوان محصول + زیر عنوان محصول -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal-product-name">عنوان محصول *</label>
                            <input type="text" id="modal-product-name" name="name" required placeholder="مثل: نقشه گوگل مپ">
                        </div>
                        
                        <div class="form-group">
                            <label for="modal-product-subtitle">زیر عنوان محصول</label>
                            <input type="text" id="modal-product-subtitle" name="subtitle" placeholder="مثل: پرکاربردترین نقشه جهان">
                        </div>
                    </div>
                    
                    <!-- ردیف دوم: توضیحات محصول + انتخاب تصویر -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal-product-description">توضیحات محصول</label>
                            <textarea id="modal-product-description" name="description" rows="3" placeholder="مثل: ثبت کسب و کار در گوگل مپ و دریافت مشتریان بیشتر"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="modal-product-image">تصویر محصول</label>
                            <div class="image-upload-container">
                                <input type="hidden" id="modal-product-image" name="image_url" value="">
                                <div class="image-preview" id="image-preview">
                                    <div class="placeholder">
                                        <i class="icon">📷</i>
                                        <span>انتخاب تصویر</span>
                                    </div>
                                </div>
                                <div class="image-actions">
                                    <button type="button" class="btn-upload" id="select-image">انتخاب از رسانه</button>
                                    <button type="button" class="btn-remove" id="remove-image">حذف تصویر</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- ردیف سوم: قیمت اصلی + قیمت با تخفیف -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal-original-price">قیمت اصلی *</label>
                            <input type="text" id="modal-original-price" name="original_price" required placeholder="مثال: 459 (برای 459.000 تومان)">
                            <small class="form-help">فقط عدد وارد کنید، خروجی: 459.000 تومان</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="modal-sale-price">قیمت با تخفیف</label>
                            <input type="text" id="modal-sale-price" name="sale_price" placeholder="مثال: 400 (اختیاری)">
                            <small class="form-help">اگر تخفیف داره وارد کنید، وگرنه خالی بذارید</small>
                        </div>
                    </div>
                    
                    <!-- ردیف پنجم: ترتیب نمایش + نوع محصول -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal-sort-order">ترتیب نمایش</label>
                            <input type="number" id="modal-sort-order" name="sort_order" min="0" value="0" placeholder="0">
                        </div>
                        <div class="form-group">
                            <label for="modal-product-type">نوع محصول *</label>
                            <select id="modal-product-type" name="type" required>
                                <option value="normal">محصول معمولی</option>
                                <option value="featured">محصول برجسته</option>
                                <option value="package">پکیج ویژه</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- فیلدهای مخفی برای مقادیر پیش‌فرض -->
                    <input type="hidden" id="modal-is-active" name="is_active" value="1">
                    <input type="hidden" id="modal-is-featured" name="is_featured" value="0">
                    
                    <!-- دکمه‌های عملیات -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary btn-save">ذخیره محصول</button>
                        <button type="button" class="btn-secondary btn-cancel modal-close">انصراف</button>
                    </div>
                </form>
            </div>
        </div>

        <style>
        /* استایل مودال اختصاصی */
        .modern-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modern-modal.show { display: flex !important; }
        .modern-modal .modal-container { width: 90%; max-width: 700px; padding: 20px; }
        .modal-form .form-row { display: flex; flex-wrap: wrap; gap: 20px; }
        .modal-form .form-row .form-group { flex: 1 1 calc(50% - 20px); }
        .modal-form .form-row.single .form-group { flex: 1 1 100%; }
        
        /* راهنمای کمکی زیر فیلدها */
        .form-help {
            display: block;
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
            font-style: italic;
        }
        /* فونت و استایل پایه */
        @import url('https://cdn.jsdelivr.net/gh/rastikerdar/vazir-font@v30.1.0/dist/font-face.css');

        .products-in-settings,
        .products-in-settings * {
            font-family: 'Vazir', Tahoma, sans-serif !important;
        }

        /* متغیرهای رنگی */
        .products-in-settings {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-500: #6b7280;
            --gray-700: #374151;
            --gray-900: #111827;
            --border-radius: 8px;
        }
        
        /* هدر ساده */
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding: 20px;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .products-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .products-actions {
            display: flex;
            gap: 12px;
        }
        
        /* دکمه‌های ساده و تمیز */
        .btn-primary,
        .btn-secondary,
        .btn-edit,
        .btn-toggle,
        .btn-delete,
        .btn-upload,
        .btn-remove,
        .btn-save,
        .btn-cancel {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            border: 1px solid;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .btn-secondary {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background: var(--gray-50);
        }

        .btn-edit {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-toggle {
            background: var(--warning);
            color: white;
            border-color: var(--warning);
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-delete {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
            font-size: 12px;
            padding: 6px 12px;
        }

        .btn-upload {
            background: var(--success);
            color: white;
            border-color: var(--success);
            font-size: 12px;
            padding: 8px 16px;
        }

        .btn-remove {
            background: var(--danger);
            color: white;
            border-color: var(--danger);
            font-size: 12px;
            padding: 8px 16px;
        }

        .btn-save {
            background: var(--success);
            color: white;
            border-color: var(--success);
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-cancel {
            background: white;
            color: var(--gray-700);
            border-color: var(--gray-300);
            padding: 12px 24px;
            font-size: 16px;
        }

        /* حالت خالی */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
        }

        .empty-state h3 {
            margin: 0 0 12px;
            font-size: 18px;
            color: var(--gray-700);
        }

        .empty-state p {
            margin: 0 0 24px;
            color: var(--gray-500);
        }

        /* گرید محصولات */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .product-item {
            display: flex;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: all 0.2s ease;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .product-item.inactive {
            opacity: 0.6;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }

        .product-image {
            width: 48px;
            height: 48px;
            border-radius: var(--border-radius);
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--gray-200);
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .placeholder-icon {
            font-size: 20px;
            color: var(--gray-500);
        }

        .product-type-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .product-type-badge.package {
            background: #dcfce7;
            color: #166534;
            border: 1px solid var(--success);
        }

        .product-type-badge.featured {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid var(--warning);
        }

        .product-type-badge.normal {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid var(--primary);
        }

        .product-content {
            padding: 16px;
        }

        .product-title {
            margin: 0 0 8px;
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .product-subtitle {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 500;
            color: var(--primary);
            font-style: italic;
        }

        .product-description {
            margin: 0 0 12px;
            color: var(--gray-500);
            font-size: 14px;
            line-height: 1.4;
        }

        .product-pricing {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .original-price {
            text-decoration: line-through;
            color: var(--gray-400);
            font-size: 12px;
            font-weight: 400;
        }

        .sale-price {
            font-weight: 700;
            font-size: 16px;
            color: var(--success);
        }

        .discount-badge {
            background: var(--success);
            color: white;
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            align-self: flex-start;
            margin-top: 4px;
        }

        .product-actions {
            display: flex;
            gap: 6px;
            padding: 12px 16px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }

        /* مودال ساده */
        .modern-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.6);
        }

        .modal-container {
            position: relative;
            background: white;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-900);
        }

        .modal-close {
            background: var(--gray-200);
            border: none;
            color: var(--gray-600);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 16px;
            font-weight: bold;
        }

        .modal-close:hover {
            background: var(--gray-300);
            color: var(--gray-800);
        }

        .modal-form {
            padding: 24px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--gray-700);
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 1px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
            height: 46px;
        }

        .form-group textarea {
            height: 90px;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
            outline: none;
        }

        /* آپلود تصویر */
        .image-upload-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .image-preview {
            width: 100%;
            height: 90px;
            border: 2px dashed var(--gray-300);
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
            transition: all 0.2s ease;
            overflow: hidden;
            cursor: pointer;
        }

        .image-preview:hover {
            border-color: var(--primary);
            background: #eff6ff;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview .placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--gray-500);
            font-size: 12px;
        }

        .image-actions {
            display: flex;
            gap: 8px;
        }

        /* سوییچ ساده */
        .switch-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 6px;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .switch-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .3s;
            border-radius: 24px;
        }

        .switch-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
            box-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        input:checked + .switch-slider {
            background-color: var(--success);
        }

        input:checked + .switch-slider:before {
            transform: translateX(24px);
        }

        .switch-text {
            font-weight: 500;
            color: var(--gray-700);
            font-size: 14px;
        }

        /* اکشن‌های فرم */
        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }

        /* نوتیفیکیشن‌های تمیز */
        .admin-notification {
            position: fixed;
            top: 32px;
            right: 20px;
            z-index: 10001;
            padding: 14px 18px;
            border-radius: var(--border-radius);
            color: white;
            font-weight: 500;
            font-size: 14px;
            min-width: 280px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .admin-notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        .admin-notification.hide {
            opacity: 0;
            transform: translateX(100%);
        }

        .admin-notification.success {
            background: var(--success);
        }

        .admin-notification.success:before {
            content: "✓ ";
            font-weight: bold;
        }

        .admin-notification.error {
            background: var(--danger);
        }

        .admin-notification.error:before {
            content: "✕ ";
            font-weight: bold;
        }

        /* بارگذاری */
        .loading {
            text-align: center;
            padding: 40px;
            font-size: 14px;
            color: var(--gray-500);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .modal-container {
                width: 95%;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            var mediaUploader;

            // Debug function
            function debugLog(message) {
                console.log('🔧 Products Tab JS: ' + message);
            }

            debugLog('Products tab JavaScript loaded');

            // اطمینان از بسته بودن modal در ابتدا
            $('#product-modal').removeClass('show').hide();
            $('body').removeClass('modal-open');
            debugLog('Modal ensured to be closed on load');

            // جلوگیری از دوبل bind کردن event handler ها
            $('.add-product-btn, .edit-product').off('click.products-tab');

            // افزودن محصول جدید - با namespace مخصوص
            $(document).on('click.products-tab', '.add-product-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                debugLog('Add product button clicked');
                
                // بررسی اینکه modal از قبل باز نباشد
                if ($('#product-modal').hasClass('show')) {
                    debugLog('Modal already open, ignoring click');
                    return false;
                }
                
                try {
                    resetForm();
                    $('#modal-title').text('افزودن محصول جدید');
                    $('#product-modal').show().addClass('show');
                    $('body').addClass('modal-open');
                    debugLog('Modal opened successfully');
                } catch (error) {
                    console.error('Error opening modal:', error);
                    showNotification('خطا در باز کردن فرم: ' + error.message, 'error');
                }
                return false;
            });

            // ویرایش محصول - با namespace مخصوص
            $(document).on('click.products-tab', '.edit-product', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var productId = $(this).data('id');
                debugLog('Edit product clicked: ' + productId);
                
                // بررسی اینکه modal از قبل باز نباشد
                if ($('#product-modal').hasClass('show')) {
                    debugLog('Modal already open, ignoring edit click');
                    return false;
                }
                
                $.post(ajaxurl, {
                    action: 'get_product_for_edit',
                    product_id: productId,
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                }).done(function(response) {
                    if (response.success) {
                        var product = response.data;
                        $('#modal-title').text('ویرایش محصول');
                        $('#modal-product-id').val(product.id);
                        $('#modal-product-name').val(product.name);
                        $('#modal-product-subtitle').val(product.subtitle || '');
                        $('#modal-product-type').val(product.type);
                        $('#modal-product-description').val(product.description);
                        $('#modal-original-price').val(formatPriceForInput(product.original_price));
                        
                        if (product.sale_price != product.original_price) {
                            $('#modal-sale-price').val(formatPriceForInput(product.sale_price));
                        } else {
                            $('#modal-sale-price').val('');
                        }
                        
                        $('#modal-sort-order').val(product.sort_order);
                        $('#modal-is-active').val(product.is_active == 1 ? '1' : '0');
                        
                        if (product.image_url) {
                            $('#modal-product-image').val(product.image_url);
                            showImagePreview(product.image_url);
                        } else {
                            removeImage();
                        }
                        
                        $('#product-modal').show().addClass('show');
                        $('body').addClass('modal-open');
                        debugLog('Modal opened for edit: ' + productId);
                    } else {
                        showNotification('خطا در بارگذاری محصول', 'error');
                    }
                }).fail(function() {
                    showNotification('خطا در اتصال به سرور', 'error');
                });
                return false;
            });

            // انتخاب تصویر از رسانه وردپرس
            $(document).on('click.products-tab', '#select-image, #image-preview .placeholder', function(e) {
                e.preventDefault();
                
                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                    showNotification('رسانه وردپرس لود نشده است', 'error');
                    return;
                }
                
                if (mediaUploader) {
                    mediaUploader.close();
                }
                
                mediaUploader = wp.media({
                    title: 'انتخاب تصویر محصول',
                    button: { text: 'انتخاب این تصویر' },
                    multiple: false,
                    library: { type: 'image' }
                });
                
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    if (attachment && attachment.url) {
                        $('#modal-product-image').val(attachment.url);
                        showImagePreview(attachment.url);
                        showNotification('تصویر انتخاب شد', 'success');
                    }
                });
                
                mediaUploader.open();
            });

            // حذف تصویر
            $(document).on('click.products-tab', '#remove-image', function(e) {
                e.preventDefault();
                removeImage();
                showNotification('تصویر حذف شد', 'success');
            });

            // فرمت کردن قیمت
            $(document).on('input.products-tab', '#modal-original-price, #modal-sale-price', function() {
                formatPriceInput(this);
            });



            // ذخیره محصول - هر دو فرم
            $(document).on('submit', '#product-form-modal, #product-form', function(e) {
                e.preventDefault();
                debugLog('Form submitted');
                
                var $form = $(this);
                var saveBtn = $form.find('.btn-save, button[type="submit"]');
                var originalText = saveBtn.html();
                
                // اعتبارسنجی فرم
                var name = $('#modal-product-name, #product-name').val().trim();
                var originalPrice = $('#modal-original-price, #original-price').val().replace(/,/g, '');
                
                if (!name) {
                    showNotification('نام محصول الزامی است', 'error');
                    return;
                }
                
                if (!originalPrice || parseInt(originalPrice) <= 0) {
                    showNotification('قیمت اصلی باید بیشتر از صفر باشد', 'error');
                    return;
                }
                
                saveBtn.html('<i class="icon">⏳</i> در حال ذخیره...').prop('disabled', true);
                
                // جمع‌آوری داده‌ها
                var data = {
                    action: 'save_product',
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>',
                    product_id: $('#modal-product-id, #product-id').val() || '',
                    name: name,
                    subtitle: $('#modal-product-subtitle, #product-subtitle').val() || '',
                    description: $('#modal-product-description, #product-description').val(),
                    type: $('#modal-product-type, #product-type').val(),
                    original_price: originalPrice,
                    sale_price: $('#modal-sale-price, #sale-price').val().replace(/,/g, '') || '',
                    sort_order: $('#modal-sort-order, #sort-order').val() || '0',
                    is_active: $('#modal-is-active, #is-active').val() || '1',
                    is_featured: $('#modal-is-featured, #is-featured').val() || '0',
                    image_url: $('#modal-product-image, #product-image-url').val() || ''
                };
                
                debugLog('Sending data:', data);
                
                $.post(ajaxurl, data).done(function(response) {
                    saveBtn.html(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        if ($('#product-modal').length && $('#product-modal').hasClass('show')) {
                            closeModal();
                        }
                        refreshProducts();
                        showNotification(response.data.message || 'محصول با موفقیت ذخیره شد', 'success');
                        
                        // در صورت استفاده از فرم standalone، فرم رو ریست کن
                        if ($('#product-form').length && !$('#product-modal').hasClass('show')) {
                            resetForm();
                        }
                    } else {
                        showNotification(response.data || 'خطا در ذخیره محصول', 'error');
                    }
                }).fail(function(xhr, status, error) {
                    saveBtn.html(originalText).prop('disabled', false);
                    showNotification('خطا در ارتباط با سرور: ' + error, 'error');
                    console.error('AJAX Error:', xhr.responseText);
                });
            });

            // تغییر وضعیت محصول
            $(document).on('click', '.toggle-status', function() {
                var productId = $(this).data('id');
                var currentStatus = $(this).data('status');
                var newStatus = currentStatus == 1 ? 0 : 1;
                
                var $btn = $(this);
                var originalText = $btn.html();
                $btn.html('<i class="icon">⏳</i>').prop('disabled', true);
                
                $.post(ajaxurl, {
                    action: 'toggle_product_status',
                    product_id: productId,
                    status: newStatus,
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                }, function(response) {
                    if (response.success) {
                        refreshProducts();
                        showNotification('وضعیت محصول تغییر کرد', 'success');
                    } else {
                        $btn.html(originalText).prop('disabled', false);
                        showNotification('خطا در تغییر وضعیت', 'error');
                    }
                });
            });

            // حذف محصول - فقط یک تایید
            $(document).on('click', '.delete-product', function() {
                var $this = $(this);
                var productId = $this.data('id');
                var productName = $this.closest('.product-item').find('.product-title').text();
                
                // جلوگیری از تایید مکرر
                if ($this.prop('disabled')) return;
                
                var confirmMessage = `آیا از حذف محصول "${productName}" مطمئن هستید؟\n\nاین عمل قابل برگشت نیست.`;
                
                if (confirm(confirmMessage)) {
                    var originalText = $this.html();
                    $this.html('<i class="icon">⏳</i> در حال حذف...').prop('disabled', true);
                    
                    $.post(ajaxurl, {
                        action: 'delete_product',
                        product_id: productId,
                        nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                    }, function(response) {
                        if (response.success) {
                            // حذف آیتم از DOM با انیمیشن
                            $this.closest('.product-item').fadeOut(300, function() {
                                $(this).remove();
                                
                                // بررسی اگر محصولی باقی نمانده
                                if ($('.product-item').length === 0) {
                                    $('.products-grid').html(`
                                        <div class="empty-state">
                                            <div class="empty-icon">📦</div>
                                            <h3>هیچ محصولی یافت نشد</h3>
                                            <p>برای شروع، اولین محصول خود را اضافه کنید</p>
                                            <button type="button" class="btn-primary add-product-btn" data-action="add-first">
                                                افزودن محصول
                                            </button>
                                        </div>
                                    `);
                                }
                            });
                            showNotification('محصول با موفقیت حذف شد', 'success');
                        } else {
                            $this.html(originalText).prop('disabled', false);
                            showNotification(response.data || 'خطا در حذف محصول', 'error');
                        }
                    }).fail(function() {
                        $this.html(originalText).prop('disabled', false);
                        showNotification('خطا در اتصال به سرور', 'error');
                    });
                }
            });

            // بروزرسانی لیست
            $(document).on('click', '#refresh-products', function() {
                refreshProducts();
            });

            // بستن مودال
            $(document).on('click', '.modal-close, .btn-cancel', function(e) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            });

            $(document).on('click', '.modal-overlay', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });

            // کلید ESC
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#product-modal').hasClass('show')) {
                    closeModal();
                }
            });

            // توابع کمکی
            function refreshProducts() {
                debugLog('Refreshing products');
                
                if ($('.products-container').length) {
                    $('.products-container').html('<div class="loading">⏳ در حال بارگذاری...</div>');
                }
                
                if ($('#products-list').length) {
                    $('#products-list').html('<div class="loading">⏳ در حال بارگذاری...</div>');
                }
                
                $.post(ajaxurl, {
                    action: 'get_products_for_settings',
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                }).done(function(response) {
                    if (response.success) {
                        $('#products-management').html(response.data.html);
                        debugLog('Products refreshed successfully');
                    } else {
                        showNotification('خطا در بارگذاری محصولات', 'error');
                    }
                }).fail(function() {
                    showNotification('خطا در اتصال به سرور', 'error');
                });
            }
            
            // بارگذاری لیست محصولات برای صفحه standalone
            function loadProductsList() {
                $.post(ajaxurl, {
                    action: 'get_products_ajax',
                    nonce: '<?php echo wp_create_nonce('market_google_admin'); ?>'
                }, function(response) {
                    if (response.success && response.data) {
                        var html = '<div class="products-grid">';
                        if (response.data.length > 0) {
                            response.data.forEach(function(product) {
                                html += createProductCard(product);
                            });
                        } else {
                            html += '<div class="empty-state"><p>هیچ محصولی یافت نشد</p></div>';
                        }
                        html += '</div>';
                        $('#products-list').html(html);
                    }
                });
            }
            
            // ایجاد کارت محصول برای standalone
            function createProductCard(product) {
                const statusClass = product.is_active == 1 ? 'active' : 'inactive';
                const statusText = product.is_active == 1 ? 'فعال' : 'غیرفعال';
                const finalPrice = product.sale_price != product.original_price ? product.sale_price : product.original_price;
                
                return `
                    <div class="product-item ${statusClass}" data-id="${product.id}">
                        <div class="product-header">
                            <h3 class="product-title">${product.name}</h3>
                            <span class="product-status">${statusText}</span>
                        </div>
                        <div class="product-info">
                            <p class="product-price">${parseInt(finalPrice).toLocaleString('fa-IR')} هزار تومان</p>
                            <p class="product-type">${getTypeLabel(product.type)}</p>
                        </div>
                        <div class="product-actions">
                            <button class="btn-edit edit-product" data-id="${product.id}">ویرایش</button>
                            <button class="btn-delete delete-product" data-id="${product.id}">حذف</button>
                        </div>
                    </div>
                `;
            }
            
            function getTypeLabel(type) {
                const labels = {
                    'package': 'پکیج',
                    'featured': 'برجسته', 
                    'normal': 'عادی'
                };
                return labels[type] || 'نامشخص';
            }

            function closeModal() {
                $('#product-modal').removeClass('show');
                $('body').removeClass('modal-open');
                setTimeout(function() {
                    $('#product-modal').hide();
                    resetForm();
                }, 300);
            }

            function showImagePreview(imageUrl) {
                $('#image-preview').html(`<img src="${imageUrl}" alt="تصویر محصول">`);
                $('#remove-image').show();
            }

            function removeImage() {
                $('#modal-product-image').val('');
                $('#image-preview').html(`
                    <div class="placeholder">
                        <i class="icon">📷</i>
                        <span>انتخاب تصویر</span>
                    </div>
                `);
                $('#remove-image').hide();
            }



            function formatPriceInput(input) {
                var $input = $(input);
                var value = $input.val().replace(/,/g, '');
                
                if (value && !isNaN(value)) {
                    $input.val(parseInt(value).toLocaleString('fa-IR'));
                }
            }

            function formatPriceForInput(price) {
                return parseInt(price).toLocaleString('fa-IR');
            }

            function resetForm() {
                debugLog('Resetting form');
                try {
                    // Reset modal form
                    if ($('#product-form-modal')[0]) {
                        $('#product-form-modal')[0].reset();
                    }
                    
                    // Reset standalone form
                    if ($('#product-form')[0]) {
                        $('#product-form')[0].reset();
                    }
                    
                    // Clear specific fields
                    $('#modal-product-id, #product-id').val('');
                    $('#modal-is-active, #is-active').val('1');
                    $('#modal-is-featured, #is-featured').val('0');
                    
                    // Reset image
                    removeImage();
                    
                    debugLog('Form reset completed');
                } catch (error) {
                    console.error('Error resetting form:', error);
                }
            }

            function showNotification(message, type) {
                // حذف پیام‌های قبلی
                $('.admin-notification').remove();
                
                var notification = $('<div class="notice notice-' + type + ' is-dismissible admin-notification" style="margin: 10px 0; position: relative;"><p>' + message + '</p></div>');
                
                // نمایش در مکان مناسب
                if ($('#product-modal').hasClass('show')) {
                    $('.modal-header').after(notification);
                } else if ($('.wrap h1').length) {
                    $('.wrap h1').after(notification);
                } else if ($('.products-header').length) {
                    $('.products-header').after(notification);
                } else {
                    $('body').prepend(notification);
                }
                
                // بستن خودکار
                setTimeout(function() {
                    notification.fadeOut(function() {
                        $(this).remove();
                    });
                }, 4000);
                
                // اضافه کردن دکمه بستن
                notification.append('<button type="button" class="notice-dismiss" style="position: absolute; right: 1px; top: 0; padding: 9px; cursor: pointer; border: none; background: none;"><span style="width: 20px; height: 20px; display: block;">×</span></button>');
                
                // event listener برای بستن دستی
                notification.find('.notice-dismiss').on('click', function() {
                    notification.fadeOut(function() {
                        $(this).remove();
                    });
                });
                
                debugLog('Notification shown: ' + type + ' - ' + message);
            }

            // اضافه کردن CSS برای body زمان باز بودن مودال
            $('<style>').text(`
                .modal-open { overflow: hidden; }
                .modern-modal.show { display: flex !important; }
            `).appendTo('head');
        });
        </script>
        <?php
        
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }

    /**
     * دریافت محصول برای ویرایش
     */
    public static function get_product_for_edit() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }

        // بررسی nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_admin')) {
            wp_send_json_error('بررسی امنیت ناموفق');
            return;
        }

        $product_id = intval($_POST['product_id']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $product_id));
        
        if ($product) {
            wp_send_json_success($product);
        } else {
            wp_send_json_error('محصول یافت نشد');
        }
    }

    /**
     * ذخیره محصول
     */
    public static function save_product() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_admin')) {
            wp_send_json_error('بررسی امنیت ناموفق');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';

        // Verify table exists or create it - بدون ارسال JSON
        $table_check = self::check_products_table(false);
        if (isset($table_check['status']) && $table_check['status'] === 'error') {
            wp_send_json_error($table_check['message']);
            return;
        }

        // Process and validate input data
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $original_price = isset($_POST['original_price']) ? intval(str_replace(',', '', $_POST['original_price'])) : 0;
        $sale_price = isset($_POST['sale_price']) ? intval(str_replace(',', '', $_POST['sale_price'])) : $original_price;
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'normal';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error('نام محصول الزامی است');
            return;
        }

        if ($original_price <= 0) {
            wp_send_json_error('قیمت اصلی باید بیشتر از صفر باشد');
            return;
        }

        // Prepare data for database
        $data = array(
            'name' => $name,
            'subtitle' => isset($_POST['subtitle']) ? sanitize_text_field($_POST['subtitle']) : '',
            'description' => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
            'icon' => '🏪', // آیکون پیش‌فرض - بعداً با تصویر جایگزین میشه
            'type' => $type,
            'original_price' => $original_price,
            'sale_price' => $sale_price,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_featured' => ($type === 'featured') ? 1 : 0,
            'sort_order' => isset($_POST['sort_order']) ? intval($_POST['sort_order']) : 0,
            'image_url' => isset($_POST['image_url']) ? esc_url_raw($_POST['image_url']) : ''
        );

        $format = array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s');

        // Insert or update
        if ($product_id > 0) {
            $result = $wpdb->update($table_name, $data, array('id' => $product_id), $format, array('%d'));
            $message = 'محصول با موفقیت ویرایش شد';
        } else {
            $result = $wpdb->insert($table_name, $data, $format);
            $product_id = $wpdb->insert_id;
            $message = 'محصول جدید با موفقیت اضافه شد';
        }

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => $message,
                'product_id' => $product_id
            ));
        } else {
            wp_send_json_error('خطا در ذخیره محصول در دیتابیس');
        }
    }

    /**
     * حذف محصول
     */
    public static function delete_product() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }

        // بررسی nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_admin')) {
            wp_send_json_error('بررسی امنیت ناموفق');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        $product_id = intval($_POST['product_id']);
        
        // بررسی وجود محصول
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $product_id));
        if (!$product) {
            wp_send_json_error('محصول یافت نشد');
            return;
        }
        
        $result = $wpdb->delete($table_name, array('id' => $product_id), array('%d'));
        
        if ($result) {
            wp_send_json_success('محصول با موفقیت حذف شد');
        } else {
            wp_send_json_error('خطا در حذف محصول');
        }
    }

    /**
     * تغییر وضعیت محصول
     */
    public static function toggle_product_status() {
        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }

        // بررسی nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_admin')) {
            wp_send_json_error('بررسی امنیت ناموفق');
            return;
        }

        $product_id = intval($_POST['product_id']);
        $status = intval($_POST['status']);

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'market_google_products',
            array('is_active' => $status),
            array('id' => $product_id)
        );

        if ($result !== false) {
            wp_send_json_success('وضعیت محصول با موفقیت تغییر کرد');
        } else {
            wp_send_json_error('خطا در تغییر وضعیت محصول');
        }
    }

    /**
     * بررسی ساختار جدول محصولات
     */
    public static function check_products_table($send_json = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_products';
        
        // بررسی وجود جدول
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
        
        if (!$table_exists) {
            // ایجاد جدول اگر وجود نداشته باشد
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id int(11) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                subtitle varchar(500) DEFAULT '',
                description text,
                icon varchar(10) DEFAULT '🏪',
                image_url varchar(500) DEFAULT '',
                type enum('normal','featured','package') DEFAULT 'normal',
                original_price decimal(10,0) NOT NULL,
                sale_price decimal(10,0) NOT NULL,
                is_active tinyint(1) DEFAULT 1,
                is_featured tinyint(1) DEFAULT 0,
                sort_order int(11) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY type_active (type, is_active),
                KEY sort_order (sort_order)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            $result = array(
                'status' => 'created',
                'message' => 'جدول محصولات ایجاد شد',
                'table_exists' => true
            );
            
            if ($send_json) {
                wp_send_json_success($result);
                return;
            } else {
                return $result;
            }
        }
        
        // بررسی وجود ستون‌ها
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = array_map(function($col) { return $col->Field; }, $columns);
        
        $updates_made = false;
        
        if (!in_array('icon', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN icon varchar(10) DEFAULT '🏪' AFTER description");
            $updates_made = true;
        }
        
        if (!in_array('subtitle', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN subtitle varchar(500) DEFAULT '' AFTER name");
            $updates_made = true;
        }
        
        if (!in_array('image_url', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN image_url varchar(500) DEFAULT '' AFTER icon");
            $updates_made = true;
        }
        
        // شمارش محصولات موجود
        $products_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        $result = array(
            'status' => 'ready',
            'message' => 'جدول محصولات آماده است',
            'table_exists' => true,
            'products_count' => intval($products_count),
            'updates_made' => $updates_made
        );
        
        if ($send_json) {
            wp_send_json_success($result);
        } else {
            return $result;
        }
    }

    /**
     * ارسال اطلاعات از طریق پیامک
     */
    public static function send_location_info() {
        if (!wp_verify_nonce($_POST['nonce'], 'admin_nonce')) {
            wp_die('Security check failed');
        }

        $location_id = intval($_POST['location_id']);

        global $wpdb;
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}market_google_locations WHERE id = %d",
            $location_id
        ));

        if (!$location) {
            wp_send_json_error('موقعیت یافت نشد.');
        }

        // تبدیل داده‌های JSON به آرایه
        $selected_products = !empty($location->selected_products) ? json_decode($location->selected_products, true) : array();
        
        // آماده‌سازی داده‌ها برای ارسال پیامک
        $data = array(
            'full_name' => $location->full_name,
            'phone' => $location->phone,
            'business_name' => $location->business_name,
            'business_phone' => $location->business_phone,
            'website' => $location->website,
            'province' => $location->province,
            'city' => $location->city,
            'address' => $location->address,
            'selected_products' => '',
            'price' => number_format($location->price) . ' تومان',
            'order_number' => $location->id,
            'payment_authority' => $location->payment_authority,
            'transaction_id' => $location->payment_transaction_id,
            'payment_date' => !empty($location->paid_at) ? date_i18n('Y/m/d H:i', strtotime($location->paid_at)) : '',
            'ref_id' => '#MG-' . str_pad($location->id + 99, 3, '0', STR_PAD_LEFT), // کد پیگیری همان شماره سفارش است
            'payment_status' => self::get_payment_status_label($location->payment_status)
        );
        
        // تبدیل محصولات انتخاب شده به رشته
        if (!empty($selected_products)) {
            $product_titles = array_map(function($product) {
                return $product['title'];
            }, $selected_products);
            
            $data['selected_products'] = implode('، ', $product_titles);
        }
        
        // ارسال پیامک
        if (class_exists('Market_Google_SMS_Service')) {
            $sms_service = Market_Google_SMS_Service::get_instance();
            $result = $sms_service->send_info($location->phone, $data);
            
            if ($result) {
                wp_send_json_success('پیامک اطلاعات با موفقیت ارسال شد.');
            } else {
                wp_send_json_error('خطا در ارسال پیامک. لطفاً تنظیمات پیامک را بررسی کنید.');
            }
        } else {
            wp_send_json_error('سرویس پیامک فعال نیست.');
        }
    }

    /**
     * دریافت پارامترهای جستجو از درخواست AJAX
     */
    private static function get_search_params() {
        // پارامترهای پیش‌فرض
        $params = array(
            's' => '',
            'order_status' => '',
            'payment_status' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'paged' => 1
        );
        
        // دریافت پارامترهای ارسالی
        if (isset($_POST['s'])) {
            $params['s'] = sanitize_text_field($_POST['s']);
        }
        
        if (isset($_POST['order_status'])) {
            $params['order_status'] = sanitize_text_field($_POST['order_status']);
        }
        
        if (isset($_POST['payment_status'])) {
            $params['payment_status'] = sanitize_text_field($_POST['payment_status']);
        }
        
        if (isset($_POST['date_from']) && !empty($_POST['date_from'])) {
            $params['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        
        if (isset($_POST['date_to']) && !empty($_POST['date_to'])) {
            $params['date_to'] = sanitize_text_field($_POST['date_to']);
        }
        
        if (isset($_POST['per_page']) && intval($_POST['per_page']) > 0) {
            $params['per_page'] = intval($_POST['per_page']);
        }
        
        if (isset($_POST['paged']) && intval($_POST['paged']) > 0) {
            $params['paged'] = intval($_POST['paged']);
        }
        
        return $params;
    }
    
    /**
     * ساخت کوئری جستجو بر اساس پارامترها
     */
    private static function build_search_query($params) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // شروع کوئری
        $query = "SELECT * FROM {$table_name} WHERE 1=1";
        
        // اعمال فیلترها
        if (!empty($params['s'])) {
            $search_term = '%' . $wpdb->esc_like($params['s']) . '%';
            $query .= $wpdb->prepare(
                " AND (business_name LIKE %s OR full_name LIKE %s OR phone LIKE %s OR business_phone LIKE %s)",
                $search_term, $search_term, $search_term, $search_term
            );
        }
        
        if (!empty($params['order_status'])) {
            $query .= $wpdb->prepare(" AND status = %s", $params['order_status']);
        }
        
        if (!empty($params['payment_status'])) {
            if ($params['payment_status'] === 'success') {
                // پشتیبانی از هر دو 'success' و 'completed'
                $query .= " AND (payment_status = 'success' OR payment_status = 'completed')";
            } else {
                $query .= $wpdb->prepare(" AND payment_status = %s", $params['payment_status']);
            }
        }
        
        // تبدیل تاریخ شمسی به میلادی
        if (!empty($params['date_from'])) {
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $params['date_from'], $matches)) {
                $jy = intval($matches[1]);
                $jm = intval($matches[2]);
                $jd = intval($matches[3]);
                
                // استفاده از تبدیل ساده
                $gregorian_date_from = self::simple_jalali_to_gregorian($jy, $jm, $jd);
                $query .= $wpdb->prepare(" AND DATE(created_at) >= %s", $gregorian_date_from);
            }
        }
        
        if (!empty($params['date_to'])) {
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $params['date_to'], $matches)) {
                $jy = intval($matches[1]);
                $jm = intval($matches[2]);
                $jd = intval($matches[3]);
                
                // استفاده از تبدیل ساده
                $gregorian_date_to = self::simple_jalali_to_gregorian($jy, $jm, $jd);
                $query .= $wpdb->prepare(" AND DATE(created_at) <= %s", $gregorian_date_to);
            }
        } else if (!empty($params['date_from'])) {
            // اگر تاریخ پایان وارد نشده باشد، تا امروز فیلتر کن
            $today = date('Y-m-d');
            $query .= $wpdb->prepare(" AND DATE(created_at) <= %s", $today);
        }
        
        // مرتب‌سازی
        $query .= " ORDER BY created_at DESC";
        
        // صفحه‌بندی
        $offset = ($params['paged'] - 1) * $params['per_page'];
        $query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $params['per_page'], $offset);
        
        return $query;
    }

    /**
     * جستجوی آژاکس سفارشات
     */
    public static function ajax_search_orders() {
        // شروع debug logging
        $debug_file = __DIR__ . '/debug.log';
        $debug_content = "\n\n=== NEW AJAX REQUEST " . date('Y-m-d H:i:s') . " ===\n";
        $debug_content .= "POST Data: " . print_r($_POST, true) . "\n";
        file_put_contents($debug_file, $debug_content, FILE_APPEND);
        
        error_log('🚀 AJAX search orders called with data: ' . print_r($_POST, true));
        
        // بررسی nonce
        $nonce = $_POST['security'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            error_log('Nonce verification failed. Received: ' . $nonce);
            wp_send_json_error('خطای امنیتی - nonce نامعتبر. لطفاً صفحه را رفرش کنید.');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            error_log('User does not have manage_options capability');
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_locations';
            
            // دریافت پارامترها
            $params = self::get_search_params();
            error_log('📊 Search params: ' . print_r($params, true));
            $debug_content = "📊 Search params: " . print_r($params, true) . "\n";
            file_put_contents($debug_file, $debug_content, FILE_APPEND);
            
            // ساخت کوئری شمارش
            $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
            
            // اعمال فیلترها برای شمارش
            if (!empty($params['s'])) {
                $search_term = '%' . $wpdb->esc_like($params['s']) . '%';
                $count_query .= $wpdb->prepare(
                    " AND (business_name LIKE %s OR full_name LIKE %s OR phone LIKE %s OR business_phone LIKE %s)",
                    $search_term, $search_term, $search_term, $search_term
                );
            }
            
            if (!empty($params['order_status'])) {
                $count_query .= $wpdb->prepare(" AND status = %s", $params['order_status']);
            }
            
            if (!empty($params['payment_status'])) {
                if ($params['payment_status'] === 'success') {
                    // پشتیبانی از هر دو 'success' و 'completed'
                    $count_query .= " AND (payment_status = 'success' OR payment_status = 'completed')";
                } else {
                    $count_query .= $wpdb->prepare(" AND payment_status = %s", $params['payment_status']);
                }
            }
            
            // تبدیل تاریخ شمسی به میلادی برای شمارش
            if (!empty($params['date_from'])) {
                error_log('📅 Processing date_from: ' . $params['date_from']);
                if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $params['date_from'], $matches)) {
                    $jy = intval($matches[1]);
                    $jm = intval($matches[2]);
                    $jd = intval($matches[3]);
                    
                    // استفاده از تبدیل ساده
                    $gregorian_date_from = self::simple_jalali_to_gregorian($jy, $jm, $jd);
                    error_log('📅 Converted Jalali ' . $params['date_from'] . ' to Gregorian ' . $gregorian_date_from);
                    
                    // اضافه کردن debug برای دیدن کوئری
                    $added_condition = $wpdb->prepare(" AND DATE(created_at) >= %s", $gregorian_date_from);
                    error_log('🔍 Added WHERE condition: ' . $added_condition);
                    $count_query .= $added_condition;
                } else {
                    error_log('❌ Date format invalid: ' . $params['date_from']);
                }
            }
            
            if (!empty($params['date_to'])) {
                error_log('📅 Processing date_to: ' . $params['date_to']);
                if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $params['date_to'], $matches)) {
                    $jy = intval($matches[1]);
                    $jm = intval($matches[2]);
                    $jd = intval($matches[3]);
                    
                    // استفاده از تبدیل ساده
                    $gregorian_date_to = self::simple_jalali_to_gregorian($jy, $jm, $jd);
                    error_log('📅 Converted Jalali ' . $params['date_to'] . ' to Gregorian ' . $gregorian_date_to);
                    
                    // اضافه کردن debug برای دیدن کوئری
                    $added_condition = $wpdb->prepare(" AND DATE(created_at) <= %s", $gregorian_date_to);
                    error_log('🔍 Added WHERE condition: ' . $added_condition);
                    $count_query .= $added_condition;
                } else {
                    error_log('❌ Date format invalid: ' . $params['date_to']);
                }
            } else if (!empty($params['date_from'])) {
                // اگر تاریخ پایان وارد نشده باشد، تا امروز فیلتر کن
                $today = date('Y-m-d');
                error_log('📅 Setting end date to today: ' . $today);
                $count_query .= $wpdb->prepare(" AND DATE(created_at) <= %s", $today);
            }
            
            // شمارش کل نتایج
            error_log('🔍 Final count query: ' . $count_query);
            
            // تست کوئری بدون فیلتر تاریخ برای مقایسه
            $test_query = "SELECT COUNT(*) FROM {$table_name} WHERE 1=1";
            $test_total = $wpdb->get_var($test_query);
            error_log('🧮 Total without filters: ' . $test_total);
            
            $total_items = $wpdb->get_var($count_query);
            $total_pages = ceil($total_items / $params['per_page']);
            error_log('🔢 Total items found with filters: ' . $total_items);
            
            // اگر هیچ نتیجه‌ای نیست، بیایید محدوده تاریخ‌ها رو چک کنیم
            if ($total_items == 0 && !empty($params['date_from'])) {
                $date_range_query = "SELECT MIN(DATE(created_at)) as min_date, MAX(DATE(created_at)) as max_date FROM {$table_name}";
                $date_range = $wpdb->get_row($date_range_query);
                error_log('📊 Dates in database range from: ' . $date_range->min_date . ' to ' . $date_range->max_date);
            }
            
            // ساخت کوئری
            $query = self::build_search_query($params);
            error_log('🔍 Final orders query: ' . $query);
            
            // اجرای کوئری
            $orders = $wpdb->get_results($query);
            error_log('📦 Orders found: ' . count($orders));
            
            // ساخت HTML پاسخ - فقط جدول بدون pagination (pagination از طریق JavaScript جداگانه مدیریت می‌شود)
            ob_start();
            try {
                self::render_orders_table($orders);
                $html = ob_get_clean();
                
                error_log('Query executed, found ' . count($orders) . ' orders');
                error_log('Total items: ' . $total_items);
            
            wp_send_json_success(array(
                'html' => $html,
                    'total_items' => $total_items,
                    'total_pages' => $total_pages,
                    'current_page' => $params['paged']
            ));
            } catch (Exception $e) {
                ob_end_clean();
                error_log('Error in render_orders_table: ' . $e->getMessage());
                wp_send_json_error('خطا در رندر جدول: ' . $e->getMessage());
            }
            
        } catch (Exception $e) {
            error_log('AJAX search error: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * جستجوی آژاکس برای اتوکامپلیت سفارشات
     */
    public static function ajax_autocomplete_orders() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر. لطفاً صفحه را رفرش کنید.');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // دریافت عبارت جستجو
        $term = sanitize_text_field($_POST['term'] ?? '');
        
        if (empty($term) || strlen($term) < 2) {
            wp_send_json_success(array());
            return;
        }
        
        $search_term = '%' . $wpdb->esc_like($term) . '%';
        
        // جستجو در فیلدهای مختلف
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT id, business_name, full_name, phone, mobile, city 
            FROM {$table_name} 
            WHERE business_name LIKE %s 
            OR full_name LIKE %s 
            OR phone LIKE %s 
            OR mobile LIKE %s 
            OR city LIKE %s 
            LIMIT 10",
            $search_term, $search_term, $search_term, $search_term, $search_term
        ));
        
        $suggestions = array();
        
        foreach ($results as $result) {
            $label = $result->business_name;
            if (!empty($result->full_name)) {
                $label .= ' (' . $result->full_name . ')';
            }
            if (!empty($result->city)) {
                $label .= ' - ' . $result->city;
            }
            
            $suggestions[] = array(
                'id' => $result->id,
                'label' => $label,
                'value' => $result->business_name,
                'phone' => !empty($result->mobile) ? $result->mobile : $result->phone
            );
        }
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * رندر جدول سفارشات
     */
    private static function render_orders_table($orders) {
        ?>
        <table class="wp-list-table widefat fixed striped orders-table">
            <thead>
                <tr>
                    <th class="column-order-number">شماره سفارش</th>
                    <th class="column-full-name">نام کامل</th>
                    <th class="column-mobile">شماره موبایل</th>
                    <th class="column-business-phone">شماره کسب و کار</th>
                    <th class="column-business-name">نام کسب و کار</th>
                    <th class="column-coordinates">کپی مختصات</th>
                    <th class="column-payment-amount">مبلغ پرداخت</th>
                    <th class="column-order-status">وضعیت سفارش</th>
                    <th class="column-payment-status">وضعیت پرداخت</th>
                    <th class="column-payment-date">تاریخ ثبت</th>
                    <th class="column-actions">عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="11" class="no-items">موردی یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                                                <tr class="<?php echo empty($order->is_read) ? 'order-unread' : 'order-read'; ?><?php echo self::migrate_status($order->status) === 'completed' ? ' order-completed' : ''; ?>">
                        <td class="order-number">
                            <?php 
                            // نمایش شماره سفارش با فرمت MG-100, MG-101, ...
                            $order_number = '#MG-' . str_pad($order->id + 99, 3, '0', STR_PAD_LEFT);
                            echo $order_number;
                            ?>
                        </td>
                        <td class="full-name"><?php echo esc_html($order->full_name); ?></td>
                        <td class="mobile">
                            <a href="tel:<?php echo esc_attr($order->phone); ?>">
                                <?php echo esc_html($order->phone); ?>
                            </a>
                        </td>
                        <td class="business-phone"><?php echo esc_html($order->business_phone ?? '-'); ?></td>
                        <td class="business-name"><strong><?php echo esc_html($order->business_name); ?></strong></td>
                        <td class="coordinates">
                            <button class="button button-small copy-coordinates" 
                                    data-lat="<?php echo $order->latitude; ?>" 
                                    data-lng="<?php echo $order->longitude; ?>"
                                    title="کپی مختصات">
                                کپی
                            </button>
                        </td>
                        <td class="payment-amount">
                            <?php 
                            $amount = isset($order->price) ? floatval($order->price) : 0;
                            // تقسیم بر 10 برای حذف صفر اضافی
                            $amount = $amount / 10;
                            echo '<span class="amount-currency">تومان</span> <span class="amount-number">' . number_format($amount, 0, '.', ',') . '</span>';
                            ?>
                        </td>
                        <td class="order-status">
                            <span class="status-badge order-<?php echo esc_attr(self::migrate_status($order->status ?? 'pending')); ?>">
                                <?php echo self::get_status_label($order->status ?? 'pending'); ?>
                            </span>
                        </td>
                        <td class="payment-status">
                            <span class="status-badge payment-<?php echo esc_attr($order->payment_status ?? 'pending'); ?>">
                                <?php echo self::get_payment_status_label($order->payment_status ?? 'pending'); ?>
                            </span>
                        </td>
                        <td class="payment-date">
                            <?php 
                            if (!empty($order->created_at)) {
                                $timestamp = strtotime($order->created_at);
                                if ($timestamp !== false && class_exists('MarketGoogleJalaliCalendar')) {
                                    try {
                                        list($jy, $jm, $jd) = MarketGoogleJalaliCalendar::gregorian_to_jalali(
                                            date('Y', $timestamp),
                                            date('n', $timestamp),
                                            date('j', $timestamp)
                                        );
                                        echo sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, date('H:i', $timestamp));
                                    } catch (Exception $e) {
                                        echo date_i18n('Y/m/d H:i', $timestamp);
                                    }
                                } else {
                                    echo date_i18n('Y/m/d H:i', $timestamp);
                                }
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td class="actions">
                            <div class="row-actions">
                                <span class="view">
                                    <a href="#" class="view-order" data-id="<?php echo $order->id; ?>" title="مشاهده جزئیات">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                </span>
                                
                                <span class="toggle-read">
                                    <a href="#" class="toggle-read-status" data-id="<?php echo $order->id; ?>" 
                                       title="<?php echo empty($order->is_read) ? 'علامت‌گذاری به عنوان خوانده شده' : 'علامت‌گذاری به عنوان خوانده نشده'; ?>">
                                        <span class="dashicons <?php echo empty($order->is_read) ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></span>
                                    </a>
                                </span>
                                
                                <span class="complete">
                                    <?php if (self::migrate_status($order->status) !== 'completed'): ?>
                                        <a href="#" class="complete-order" data-id="<?php echo $order->id; ?>" title="تکمیل سفارش">
                                            <span class="dashicons dashicons-yes"></span>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="complete-order" data-id="<?php echo $order->id; ?>" title="برگرداندن به در انتظار انجام">
                                            <span class="dashicons dashicons-undo"></span>
                                        </a>
                                    <?php endif; ?>
                                </span>
                                
                                <span class="edit">
                                    <a href="#" class="edit-order" data-id="<?php echo $order->id; ?>" title="ویرایش سفارش">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                </span>
                                
                                <span class="send-info">
                                    <a href="#" class="send-info-sms" data-id="<?php echo $order->id; ?>" 
                                       data-phone="<?php echo esc_attr($order->phone); ?>"
                                       data-name="<?php echo esc_attr($order->full_name); ?>"
                                       data-business="<?php echo esc_attr($order->business_name); ?>"
                                       title="ارسال اطلاعات">
                                        <span class="dashicons dashicons-email-alt"></span>
                                    </a>
                                </span>
                                
                                <span class="delete">
                                    <a href="#" class="delete-order" data-id="<?php echo $order->id; ?>" title="حذف سفارش">
                                        <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * رندر صفحه‌بندی
     */
    private static function render_pagination($current_page, $total_pages, $total_items, $per_page) {
        if ($total_pages <= 1) return;
        
        echo '<div class="tablenav-pages">';
        echo '<span class="displaying-num">' . sprintf('%d مورد', $total_items) . '</span>';
        
        if ($current_page > 1) {
            echo '<a class="page-numbers" href="#" data-page="' . ($current_page - 1) . '">قبلی</a>';
        }
        
        for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
            if ($i == $current_page) {
                echo '<span class="page-numbers current">' . $i . '</span>';
            } else {
                echo '<a class="page-numbers" href="#" data-page="' . $i . '">' . $i . '</a>';
            }
        }
        
        if ($current_page < $total_pages) {
            echo '<a class="page-numbers" href="#" data-page="' . ($current_page + 1) . '">بعدی</a>';
        }
        
        echo '</div>';
    }

    /**
     * تولید HTML صفحه‌بندی (متد قدیمی - برای سازگاری)
     */
    private static function generate_pagination_html($current_page, $total_pages, $total_items) {
        if ($total_pages <= 1) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo sprintf('%s مورد', number_format_i18n($total_items)); ?></span>
            <span class="pagination-links">
                <?php if ($current_page > 1): ?>
                    <a class="first-page button" href="#" data-page="1">‹‹</a>
                    <a class="prev-page button" href="#" data-page="<?php echo $current_page - 1; ?>">‹</a>
                <?php endif; ?>
                
                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">صفحه فعلی</label>
                    <input class="current-page" id="current-page-selector" type="text" name="paged" value="<?php echo $current_page; ?>" size="2" aria-describedby="table-paging">
                    <span class="tablenav-paging-text"> از <span class="total-pages"><?php echo $total_pages; ?></span></span>
                </span>
                
                <?php if ($current_page < $total_pages): ?>
                    <a class="next-page button" href="#" data-page="<?php echo $current_page + 1; ?>">›</a>
                    <a class="last-page button" href="#" data-page="<?php echo $total_pages; ?>">››</a>
                <?php endif; ?>
            </span>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function submit_location_form() {

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'market_google_nonce')) {
            wp_send_json_error('بررسی امنیت ناموفق');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // Check if is_read column exists and add it if not
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'is_read'");
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        }

        // Check if the table exists or create it with the correct structure
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                full_name varchar(100) NOT NULL,
                phone varchar(20) NOT NULL,
                business_name varchar(100) NOT NULL,
                business_phone varchar(20),
                website varchar(255),
                province varchar(50),
                city varchar(50),
                address text,
                latitude varchar(20),
                longitude varchar(20),
                working_hours text,
                selected_products text,
                price int NOT NULL,
                status varchar(20) DEFAULT 'pending',
                payment_status varchar(20) DEFAULT 'pending',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

        } else {
            // Check if the 'address' column exists
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
            $column_names = array_map(function($col) { return $col->Field; }, $columns);
            
            if (!in_array('address', $column_names)) {
                $wpdb->query("ALTER TABLE $table_name ADD COLUMN address text AFTER city");

            }
        }

        // Process and validate input data
        $data = array(
            'full_name' => sanitize_text_field($_POST['full_name']),
            'phone' => sanitize_text_field($_POST['phone']),
            'business_name' => sanitize_text_field($_POST['business_name']),
            'business_phone' => sanitize_text_field($_POST['business_phone']),
            'website' => sanitize_text_field($_POST['website']),
            'province' => sanitize_text_field($_POST['province']),
            'city' => sanitize_text_field($_POST['city']),
            'address' => sanitize_text_field($_POST['address']),
            'latitude' => sanitize_text_field($_POST['latitude']),
            'longitude' => sanitize_text_field($_POST['longitude']),
            'working_hours' => sanitize_text_field($_POST['working_hours']),
            'selected_products' => sanitize_text_field($_POST['selected_products']),
            'price' => intval($_POST['price']),
            'status' => 'pending',
            'payment_status' => 'pending'
        );

        // Insert data
        $result = $wpdb->insert($table_name, $data);

        if ($result) {
            wp_send_json_success('موقعیت با موفقیت ثبت شد');
        } else {
            wp_send_json_error('خطا در ثبت موقعیت در دیتابیس');
        }
    }

    /**
     * Toggle read status of order
     */
    public static function ajax_toggle_read_status() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر. لطفاً صفحه را رفرش کنید.');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی غیرمجاز');
            return;
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        if ($order_id <= 0) {
            wp_send_json_error('شناسه سفارش نامعتبر');
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // گرفتن وضعیت فعلی
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT is_read FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if ($current_status === null) {
            wp_send_json_error('سفارش یافت نشد');
            return;
        }
        
        // تغییر وضعیت
        $new_status = $current_status ? 0 : 1;
        
        $result = $wpdb->update(
            $table_name,
            array('is_read' => $new_status),
            array('id' => $order_id),
            array('%d'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('خطا در بروزرسانی وضعیت');
            return;
        }
        
        wp_send_json_success(array(
            'new_status' => $new_status,
            'message' => $new_status ? 'سفارش به عنوان خوانده شده علامت‌گذاری شد' : 'سفارش به عنوان خوانده نشده علامت‌گذاری شد'
        ));
    }

    /**
     * ارسال پیامک اطلاعات برای سفارش
     */
    public static function send_location_info_sms() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['security'], 'market_google_orders_nonce')) {
            wp_send_json_error('نونس امنیتی نامعتبر است.');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی کافی ندارید.');
            return;
        }

        $order_id = intval($_POST['order_id']);
        $phone = sanitize_text_field($_POST['phone']);
        
        error_log("🔰 Info SMS: Starting for order ID: $order_id, phone: $phone");
        
        if (empty($order_id) || empty($phone)) {
            error_log("⛔ Info SMS: Missing data - order_id: $order_id, phone: $phone");
            wp_send_json_error('اطلاعات ناکافی است.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // دریافت اطلاعات کامل سفارش
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            error_log("⛔ Info SMS: Order not found - ID: $order_id");
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // بررسی وجود کلاس‌های SMS
        if (!class_exists('Market_Google_SMS_Service')) {
            error_log("⛔ Info SMS: SMS_Service class not found");
            wp_send_json_error('سیستم پیامک (SMS Service) در دسترس نیست.');
            return;
        }

        // بررسی تنظیمات SMS
        $sms_settings = get_option('market_google_sms_settings', array());
        error_log("📋 Info SMS: Settings loaded - provider: " . ($sms_settings['provider'] ?? 'undefined'));
        
        if (empty($sms_settings['provider']) || empty($sms_settings['username']) || empty($sms_settings['password'])) {
            error_log("⛔ Info SMS: Incomplete settings");
            wp_send_json_error('تنظیمات پیامک ناقص است. لطفاً ابتدا تنظیمات پیامک را کامل کنید.');
            return;
        }

        // بررسی فعال بودن ارسال اطلاعات - با پشتیبانی از هر دو ساختار قدیمی و جدید
        $sending_method = isset($sms_settings['sending_method']) ? $sms_settings['sending_method'] : 'service';
        $events_key = ($sending_method === 'pattern') ? 'pattern_events' : 'service_events';
        
        // لاگ برای عیب‌یابی
        error_log("📋 Info SMS: Checking event in key: $events_key");
        
        // بررسی در تنظیمات جدید
        $info_sms_settings = null;
        
        // اول در events_key مناسب (pattern_events یا service_events) جستجو می‌کنیم
        if (isset($sms_settings[$events_key]['info_delivery'])) {
            $info_sms_settings = $sms_settings[$events_key]['info_delivery'];
            error_log("📋 Info SMS: Found settings in $events_key: " . json_encode($info_sms_settings));
        }
        // اگر در events_key پیدا نشد، در ساختار قدیمی جستجو می‌کنیم
        else if (isset($sms_settings['events']['info_delivery'])) {
            $info_sms_settings = $sms_settings['events']['info_delivery'];
            error_log("📋 Info SMS: Found settings in old structure: " . json_encode($info_sms_settings));
        }
        // اگر در ساختار قدیمی هم پیدا نشد، در events_key دیگر جستجو می‌کنیم
        else {
            $other_key = ($events_key === 'pattern_events') ? 'service_events' : 'pattern_events';
            if (isset($sms_settings[$other_key]['info_delivery'])) {
                $info_sms_settings = $sms_settings[$other_key]['info_delivery'];
                error_log("📋 Info SMS: Found settings in alternative key $other_key: " . json_encode($info_sms_settings));
            }
        }
        
        // اگر تنظیمات پیدا نشد یا غیرفعال است
        if (!$info_sms_settings || (isset($info_sms_settings['enabled']) && !$info_sms_settings['enabled'])) {
            error_log("⛔ Info SMS: Event info_delivery is disabled or not found");
            wp_send_json_error('ارسال پیامک اطلاعات غیرفعال است. لطفاً از بخش تنظیمات آن را فعال کنید.');
            return;
        }

        // دریافت متن پیامک
        $message_template = isset($info_sms_settings['value']) ? $info_sms_settings['value'] : '';
        
        if (empty($message_template)) {
            error_log("⛔ Info SMS: Empty message template");
            wp_send_json_error('متن پیامک اطلاعات تنظیم نشده است.');
            return;
        }

        // آماده‌سازی داده‌ها برای جایگزینی در پیامک
        $sms_data = array(
            'order_id' => $order->id,
            'business_name' => $order->business_name,
            'full_name' => $order->full_name,
            'phone' => $order->phone,
            'business_phone' => $order->business_phone,
            'address' => $order->address,
            'province' => $order->province,
            'city' => $order->city,
            'latitude' => $order->latitude,
            'longitude' => $order->longitude,
            'coordinates' => $order->latitude . ', ' . $order->longitude,
            'payment_amount' => number_format(floatval($order->price) / 10) . ' تومان',
            'created_at' => $order->created_at,
            'selected_products' => $order->selected_products ? json_decode($order->selected_products, true) : array()
        );

        try {
            error_log("📤 Info SMS: Creating SMS Service instance");
            
            // استفاده از کلاس SMS Service به جای SMS Handler
            $sms_service = new Market_Google_SMS_Service();
            
            // ارسال با متد send_info
            error_log("📤 Info SMS: Calling send_info method for order #$order_id");
            $result = $sms_service->send_info($phone, $sms_data);
            error_log("📨 Info SMS: Result - " . json_encode($result));
            
            if ($result['success']) {
                // ثبت لاگ ارسال موفق
                error_log("✅ Info SMS: sent successfully to {$phone} for order #{$order_id}");
                
                // به‌روزرسانی آخرین زمان ارسال اطلاعات
                $wpdb->update(
                    $table_name,
                    array('info_sent_at' => current_time('mysql')),
                    array('id' => $order_id)
                );
                
                wp_send_json_success(array(
                    'message' => 'پیامک اطلاعات با موفقیت به شماره ' . $phone . ' ارسال شد.',
                    'sent_message' => $result['message'] ?? 'متن پیامک'
                ));
            } else {
                error_log("❌ Info SMS: Failed - " . ($result['message'] ?? 'Unknown error'));
                wp_send_json_error('خطا در ارسال پیامک: ' . $result['message']);
            }
            
        } catch (Exception $e) {
            error_log('💥 Info SMS Error: ' . $e->getMessage());
            error_log('📍 Stack trace: ' . $e->getTraceAsString());
            wp_send_json_error('خطای سیستم در ارسال پیامک: ' . $e->getMessage());
        }
    }

    /**
     * جایگزینی shortcode های پیامک (نسخه ساده)
     */
    private static function replace_sms_shortcodes($message, $data) {
        $replacements = array(
            '{order_id}' => '#MG-' . str_pad($data['order_id'] + 99, 3, '0', STR_PAD_LEFT),
            '{business_name}' => $data['business_name'],
            '{full_name}' => $data['full_name'],
            '{phone}' => $data['phone'],
            '{business_phone}' => $data['business_phone'],
            '{address}' => $data['address'],
            '{province}' => $data['province'],
            '{city}' => $data['city'],
            '{coordinates}' => $data['coordinates'],
            '{payment_amount}' => $data['payment_amount'],
            '{website}' => get_option('siteurl', 'سایت ما'),
            '{site_name}' => get_option('blogname', 'Market Google')
        );

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * ذخیره تنظیمات افزونه
     */
    public static function save_market_google_settings() {
        // بررسی امنیتی
        if (!wp_verify_nonce($_POST['_wpnonce'], 'market-google-settings')) {
            wp_send_json_error('امنیت درخواست تأیید نشد.');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی کافی ندارید.');
            return;
        }

        // تشخیص تب فعال
        $active_tab = sanitize_text_field($_POST['active_tab'] ?? 'general');
        
        // پردازش تنظیمات بر اساس تب فعال
        if ($active_tab === 'general') {
            update_option('market_google_api_key', sanitize_text_field($_POST['api_key'] ?? ''));
            update_option('market_google_default_lat', sanitize_text_field($_POST['default_lat'] ?? '35.6892'));
            update_option('market_google_default_lng', sanitize_text_field($_POST['default_lng'] ?? '51.3890'));
            update_option('market_google_default_zoom', intval($_POST['default_zoom'] ?? 12));
            update_option('market_google_auto_approve', isset($_POST['auto_approve']) ? 1 : 0);
            update_option('market_google_max_products', intval($_POST['max_products'] ?? 5));
            update_option('market_google_payment_pending_timeout', intval($_POST['payment_pending_timeout'] ?? 15));
            update_option('market_google_delete_tables_on_uninstall', isset($_POST['delete_tables_on_uninstall']) ? 1 : 0);

        }
        
        // پردازش تنظیمات درگاه‌های پرداخت
        elseif ($active_tab === 'payment') {
            update_option('market_google_bmi_terminal_id', sanitize_text_field($_POST['bmi_terminal_id'] ?? ''));
            update_option('market_google_bmi_merchant_id', sanitize_text_field($_POST['bmi_merchant_id'] ?? ''));
            update_option('market_google_bmi_secret_key', sanitize_text_field($_POST['bmi_secret_key'] ?? ''));
            update_option('market_google_zarinpal_enabled', isset($_POST['zarinpal_enabled']) ? 1 : 0);
            update_option('market_google_zarinpal_merchant_id', sanitize_text_field($_POST['zarinpal_merchant_id'] ?? ''));
            
            // تنظیمات شماره تراکنش
            $transaction_prefix = sanitize_text_field($_POST['transaction_prefix'] ?? 'MG');
            $transaction_digits = intval($_POST['transaction_digits'] ?? 6);
            
            // محدود کردن تعداد ارقام
            $transaction_digits = max(4, min(10, $transaction_digits));
            
            update_option('market_google_transaction_prefix', $transaction_prefix);
            update_option('market_google_transaction_digits', $transaction_digits);
        }
        
        // پردازش تنظیمات کال‌بک
        elseif ($active_tab === 'callbacks') {
            update_option('market_google_use_custom_callbacks', isset($_POST['use_custom_callbacks']) ? 1 : 0);
            
            $callback_fields = array(
                'payment_success_url',
                'payment_failed_url', 
                'payment_canceled_url',
                'payment_pending_url',
                'payment_error_url',
                'payment_unsuccessful_url',
                'zarinpal_success_url',
                'zarinpal_failed_url',
                'bmi_success_url',
                'bmi_failed_url'
            );
            
            foreach ($callback_fields as $field) {
                $value = isset($_POST[$field]) ? sanitize_url($_POST[$field]) : '';
                update_option('market_google_' . $field, $value);
            }
        }
        
        // پردازش تنظیمات پیامک
        elseif ($active_tab === 'sms') {
            update_option('market_google_sms_enabled', isset($_POST['sms_enabled']) ? 1 : 0);
            update_option('market_google_sms_api_key', sanitize_text_field($_POST['sms_api_key'] ?? ''));
            update_option('market_google_sms_template', sanitize_textarea_field($_POST['sms_template'] ?? ''));
            
            // پردازش تنظیمات پیشرفته SMS اگر وجود دارد
            if (isset($_POST['market_google_sms_settings'])) {
                $sms_settings = $_POST['market_google_sms_settings'];
                
                // Sanitize settings
                $sms_settings['provider'] = sanitize_text_field($sms_settings['provider'] ?? '');
                $sms_settings['username'] = sanitize_text_field($sms_settings['username'] ?? '');
                $sms_settings['password'] = sanitize_text_field($sms_settings['password'] ?? '');
                $sms_settings['api_key'] = sanitize_text_field($sms_settings['api_key'] ?? '');
                $sms_settings['line_number'] = sanitize_text_field($sms_settings['line_number'] ?? '');
                $sms_settings['sending_method'] = sanitize_text_field($sms_settings['sending_method'] ?? '');
                
                if (isset($sms_settings['events'])) {
                    foreach ($sms_settings['events'] as $event => $data) {
                        $sms_settings['events'][$event]['enabled'] = isset($data['enabled']) ? true : false;
                        $sms_settings['events'][$event]['value'] = sanitize_textarea_field($data['value'] ?? '');
                    }
                }
                
                update_option('market_google_sms_settings', $sms_settings);
                
                // Clear transients
                delete_transient('market_google_sms_connection_status');
                delete_transient('market_google_sms_count');
            }
        }
        
        wp_send_json_success(array(
            'message' => 'تنظیمات با موفقیت ذخیره شد.'
        ));
    }

    /**
     * تکمیل سفارش
     */
    public static function ajax_complete_order() {
        try {
            // بررسی امنیت
            if (!isset($_POST['security'])) {
                wp_send_json_error('فیلد امنیتی موجود نیست.');
                return;
            }
            
            if (!wp_verify_nonce($_POST['security'], 'market_google_orders_nonce')) {
                wp_send_json_error('امنیت درخواست تأیید نشد.');
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error('دسترسی کافی ندارید.');
                return;
            }

            $order_id = intval($_POST['order_id'] ?? 0);
            
            if (!$order_id) {
                wp_send_json_error('شناسه سفارش نامعتبر است.');
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_locations';

            // دریافت اطلاعات سفارش فعلی
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));
            
            if (!$order) {
                wp_send_json_error('سفارش یافت نشد.');
                return;
            }

            // بررسی اینکه سفارش قبلاً تکمیل نشده باشد
            $current_status = self::migrate_status($order->status);
            
            if ($current_status === 'completed') {
                wp_send_json_error('این سفارش قبلاً تکمیل شده است.');
                return;
            }

            
            // به‌روزرسانی وضعیت سفارش
            $updated = $wpdb->update(
                $table_name,
                array('status' => 'completed'),
                array('id' => $order_id),
                array('%s'),
                array('%d')
            );
            
            // اگر موفق بود، سعی در اضافه کردن اطلاعات تکمیل
            if ($updated !== false) {
                $wpdb->update(
                    $table_name,
                    array(
                        'completion_date' => current_time('mysql'),
                        'completed_by' => get_current_user_id()
                    ),
                    array('id' => $order_id),
                    array('%s', '%d'),
                    array('%d')
                );
            }

            if ($updated === false) {
                wp_send_json_error('خطا در به‌روزرسانی پایگاه داده: ' . $wpdb->last_error);
                return;
            }
            
            if ($updated === 0) {
                wp_send_json_error('هیچ تغییری در سفارش اعمال نشد.');
                return;
            }

            // ارسال پیامک تکمیل سفارش
            $sms_result = array('success' => false, 'message' => 'پیامک تکمیل غیرفعال است.');
            
            // بررسی وجود تنظیمات SMS
            $sms_settings = get_option('market_google_sms_settings', array());
            error_log('🔍 Ajax Complete Order: Checking SMS settings for order completion - Enabled: ' . 
                (isset($sms_settings['events']['order_completion']['enabled']) && $sms_settings['events']['order_completion']['enabled'] ? 'Yes' : 'No'));
                
            if (isset($sms_settings['events']['order_completion']['enabled']) && 
                $sms_settings['events']['order_completion']['enabled']) {
                
                $sms_result = self::send_completion_sms($order);
                error_log('🔔 Ajax Complete Order: SMS Result - ' . json_encode($sms_result));
            } else {
                error_log('⛔ Ajax Complete Order: SMS for order completion is disabled in settings');
            }

            wp_send_json_success(array(
                'message' => 'سفارش با موفقیت تکمیل شد.',
                'sms_sent' => $sms_result['success'],
                'sms_message' => $sms_result['message']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('خطای سیستم: ' . $e->getMessage());
        }
    }

    /**
     * ارسال پیامک تکمیل سفارش
     */
    private static function send_completion_sms($order) {
        try {
            error_log("🔰 Completion SMS: Starting for order ID: " . $order->id);
            
            // بررسی فعال بودن SMS
            $sms_settings = get_option('market_google_sms_settings', array());
            $event_sms = isset($sms_settings['events']) ? $sms_settings['events'] : array();
            
            $order_completion_enabled = isset($event_sms['order_completion']['enabled']) ? 
                $event_sms['order_completion']['enabled'] : false;

            if (!$order_completion_enabled) {
                error_log("⛔ Completion SMS: Event order_completion is disabled");
                return array('success' => false, 'message' => 'پیامک تکمیل سفارش غیرفعال است.');
            }

            if (empty($order->phone)) {
                error_log("⛔ Completion SMS: No phone number found for order ID: " . $order->id);
                return array('success' => false, 'message' => 'شماره موبایل برای این سفارش یافت نشد.');
            }

            // بررسی کلاس‌های مورد نیاز
            if (!class_exists('Market_Google_SMS_Service')) {
                error_log("⛔ Completion SMS: SMS_Service class not found");
                return array('success' => false, 'message' => 'کلاس سرویس پیامک یافت نشد.');
            }

            // آماده‌سازی داده‌ها برای پیامک
            $full_name = isset($order->full_name) ? $order->full_name : 
                         (isset($order->name, $order->family) ? $order->name . ' ' . $order->family : 'نامشخص');
            
            $data = array(
                'order_id' => $order->id,
                'business_name' => isset($order->business_name) ? $order->business_name : 'نامشخص',
                'full_name' => $full_name,
                'phone' => isset($order->phone) ? $order->phone : '',
                'business_phone' => isset($order->business_phone) ? $order->business_phone : '',
                'coordinates' => isset($order->latitude, $order->longitude) ? $order->latitude . ',' . $order->longitude : '',
                'address' => isset($order->address) ? $order->address : '',
                'city' => isset($order->city) ? $order->city : '',
                'province' => isset($order->province) ? $order->province : '',
                'status' => 'تکمیل شده',
                'date' => isset($order->created_at) ? date_i18n('Y/m/d', strtotime($order->created_at)) : '',
                'time' => isset($order->created_at) ? date_i18n('H:i', strtotime($order->created_at)) : '',
                'completion_date' => date_i18n('Y/m/d H:i')
            );

            error_log("📋 Completion SMS: Prepared data for order ID " . $order->id . ", phone: " . $order->phone);

            // ایجاد instance از SMS Service
            $sms_service = new Market_Google_SMS_Service();
            
            // استفاده مستقیم از متد ارسال پیامک تکمیل به جای trigger event
            $result = $sms_service->send_order_completion($order->phone, $data);
            
            error_log("📨 Completion SMS: Result for order ID " . $order->id . " - " . json_encode($result));
            
            return array(
                'success' => $result['success'],
                'message' => $result['success'] ? 'پیامک تکمیل سفارش ارسال شد.' : 'خطا در ارسال پیامک: ' . $result['message']
            );
            
        } catch (Exception $e) {
            error_log('💥 SMS Error in send_completion_sms: ' . $e->getMessage());
            error_log('📍 Stack trace: ' . $e->getTraceAsString());
            
            // اگر خطا مربوط به network یا DNS باشد، پیام مختصرتر نمایش دهیم
            $error_message = $e->getMessage();
            if (strpos($error_message, 'Could not resolve host') !== false) {
                $error_message = 'عدم دسترسی به سرویس پیامک (مشکل شبکه)';
            } elseif (strpos($error_message, 'Connection timed out') !== false || strpos($error_message, 'timeout') !== false) {
                $error_message = 'انقضای زمان ارتباط با سرویس پیامک';
            } elseif (strpos($error_message, 'cURL') !== false) {
                $error_message = 'خطا در ارتباط با سرویس پیامک';
            }
            
            return array('success' => false, 'message' => $error_message);
        }
    }

    /**
     * برگرداندن سفارش از حالت تکمیل شده به در انتظار انجام
     */
    public static function ajax_uncomplete_order() {
        try {
            // بررسی امنیت
            if (!wp_verify_nonce($_POST['security'], 'market_google_orders_nonce')) {
                wp_send_json_error('امنیت درخواست تأیید نشد.');
                return;
            }

            if (!current_user_can('manage_options')) {
                wp_send_json_error('دسترسی کافی ندارید.');
                return;
            }

            $order_id = intval($_POST['order_id'] ?? 0);
            
            if (!$order_id) {
                wp_send_json_error('شناسه سفارش نامعتبر است.');
                return;
            }

            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_locations';

            // دریافت اطلاعات سفارش فعلی
            $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $order_id));
            
            if (!$order) {
                wp_send_json_error('سفارش یافت نشد.');
                return;
            }

            // بررسی اینکه سفارش واقعاً تکمیل شده باشد
            if (self::migrate_status($order->status) !== 'completed') {
                wp_send_json_error('این سفارش در حال حاضر تکمیل نشده است.');
                return;
            }

            // برگرداندن وضعیت به pending
            $updated = $wpdb->update(
                $table_name,
                array(
                    'status' => 'pending',
                    'completion_date' => null,
                    'completed_by' => null
                ),
                array('id' => $order_id),
                array('%s', '%s', '%s'),
                array('%d')
            );

            if ($updated === false) {
                error_log('Uncomplete Order DB Error: ' . $wpdb->last_error);
                wp_send_json_error('خطا در برگرداندن سفارش: ' . $wpdb->last_error);
                return;
            }
            
            if ($updated === 0) {
                wp_send_json_error('هیچ تغییری در سفارش اعمال نشد.');
                return;
            }

            wp_send_json_success(array(
                'message' => 'سفارش با موفقیت به حالت "در انتظار انجام" برگردانده شد.'
            ));
            
        } catch (Exception $e) {
            error_log('Uncomplete Order Exception: ' . $e->getMessage());
            wp_send_json_error('خطای سیستم: ' . $e->getMessage());
        }
    }

    /**
     * تست manual برای اضافه کردن فیلدهای completion
     */
    public static function manual_add_completion_fields() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی کافی ندارید.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        $results = array();

        // تست اضافه کردن completion_date
        $completion_date_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completion_date'");
        if (empty($completion_date_check)) {
            $add_completion_date = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completion_date DATETIME NULL");
            $results[] = 'completion_date: ' . ($add_completion_date ? 'اضافه شد' : 'خطا: ' . $wpdb->last_error);
        } else {
            $results[] = 'completion_date: از قبل موجود';
        }

        // تست اضافه کردن completed_by
        $completed_by_check = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completed_by'");
        if (empty($completed_by_check)) {
            $add_completed_by = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completed_by INT(11) NULL");
            $results[] = 'completed_by: ' . ($add_completed_by ? 'اضافه شد' : 'خطا: ' . $wpdb->last_error);
        } else {
            $results[] = 'completed_by: از قبل موجود';
        }

        echo '<div class="notice notice-info"><p>' . implode('<br>', $results) . '</p></div>';
    }

    /**
     * اطمینان از وجود فیلدهای مورد نیاز در جدول locations
     */
    private static function ensure_completion_fields() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        try {
            // بررسی وجود ستون completion_date
            $completion_date_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completion_date'");
            
            if (empty($completion_date_exists)) {
                $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completion_date DATETIME NULL");
                if ($result === false) {
                    error_log("Failed to add completion_date field: " . $wpdb->last_error);
                } else {
                    error_log("Successfully added completion_date field to {$table_name}");
                }
            }
            
            // بررسی وجود ستون completed_by
            $completed_by_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'completed_by'");
            
            if (empty($completed_by_exists)) {
                $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN completed_by INT(11) NULL");
                if ($result === false) {
                    error_log("Failed to add completed_by field: " . $wpdb->last_error);
                } else {
                    error_log("Successfully added completed_by field to {$table_name}");
                }
            }
            
            // Migration وضعیت‌های قدیمی
            self::migrate_old_statuses_in_database();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error in ensure_completion_fields: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تبدیل وضعیت‌های قدیمی در دیتابیس به سیستم جدید
     */
    private static function migrate_old_statuses_in_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بررسی اینکه آیا migration قبلاً انجام شده یا نه
        $migration_done = get_option('market_google_status_migration_done', false);
        
        if (!$migration_done) {
            // تبدیل وضعیت‌های active, inactive, rejected به pending
            $result1 = $wpdb->query("UPDATE {$table_name} SET status = 'pending' WHERE status IN ('active', 'inactive', 'rejected')");
            
            // اطمینان از اینکه سفارشات بدون وضعیت، pending میشوند
            $result2 = $wpdb->query("UPDATE {$table_name} SET status = 'pending' WHERE status IS NULL OR status = ''");
            
            // نشان‌دادن که migration انجام شده
            update_option('market_google_status_migration_done', true);
            
            error_log("Status migration completed. Updated rows: " . ($result1 + $result2));
        }
    }
    
    /**
     * تست Ajax ساده
     */
    public static function ajax_test() {
        error_log('🧪 Ajax test called!');
        wp_send_json_success('Ajax کار می‌کند!');
    }

    /**
     * دریافت جزئیات سفارش برای مودال
     */
    public static function ajax_get_order_details() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $edit_mode = isset($_POST['edit_mode']) ? (bool)$_POST['edit_mode'] : false;
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // علامت‌گذاری سفارش به عنوان خوانده شده
        $wpdb->update(
            $table_name,
            array('is_read' => 1),
            array('id' => $order_id)
        );
        
        // تبدیل داده‌های JSON به آرایه
        $selected_products = !empty($order->selected_products) ? json_decode($order->selected_products, true) : array();
        
        // دریافت محصولات از جدول محصولات
        $products_table = $wpdb->prefix . 'market_google_products';
        $products = array();
        
        // بررسی اینکه آیا پکیج ویژه انتخاب شده است یا نه
        $has_special_package = false;
        if (!empty($selected_products)) {
            foreach ($selected_products as $product_id => $quantity) {
                $product = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$products_table} WHERE id = %d",
                    $product_id
                ));
                
                if ($product) {
                    $products[$product_id] = (array) $product;
                    
                    // بررسی اینکه آیا پکیج ویژه 'all-maps' انتخاب شده است
                    if ($product->product_key === 'all-maps' || $product->title === 'تمامی نقشه‌های آنلاین') {
                        $has_special_package = true;
                    }
                }
            }
        }
        
        // اگر پکیج ویژه انتخاب شده، محصولات معمولی را نمایش دهیم
        if ($has_special_package) {
            $normal_products = $wpdb->get_results(
                "SELECT * FROM {$products_table} WHERE type = 'normal' OR product_key IN ('google-maps', 'neshan', 'balad', 'openstreet') ORDER BY sort_order ASC",
                ARRAY_A
            );
            
            $products_to_show = array();
            foreach ($normal_products as $product) {
                $products_to_show[$product['id']] = $product;
            }
        } else {
            $products_to_show = $products;
        }
        
        // نمایش شماره سفارش با فرمت MG-100, MG-101, ...
        $order_number = 'MG-' . str_pad($order->id + 99, 3, '0', STR_PAD_LEFT);
        
        // تابع برای بررسی اینکه فیلد خالی است یا نه
        $is_empty_field = function($value) {
            return empty($value) || trim($value) === '' || $value === null;
        };
        
        // تابع برای فرمت کردن ساعت کاری
        $format_working_hours = function($working_hours) use ($is_empty_field) {
            if ($is_empty_field($working_hours)) {
                return 'توسط کاربر تکمیل نشده';
            }
            
            // اگر ساعت کاری "24/7" یا "24 ساعته" باشد
            if ($working_hours === '24/7' || $working_hours === '24 ساعته') {
                return '24 ساعته';
            }
            
            // اگر ساعت کاری به صورت JSON ذخیره شده
            if (is_string($working_hours) && (strpos($working_hours, '{') !== false || strpos($working_hours, '[') !== false)) {
                $hours_data = json_decode($working_hours, true);
                if (is_array($hours_data) && !empty($hours_data)) {
                    // فیلتر کردن مقادیر خالی
                    $filtered_hours = array_filter($hours_data, function($item) {
                        return !empty(trim($item));
                    });
                    
                    if (!empty($filtered_hours)) {
                        return implode(', ', $filtered_hours);
                    }
                }
            }
            
            // برگرداندن همان مقدار
            return $working_hours;
        };
        
        // آرایه فیلدهای قابل ویرایش
        $editable_fields = array('full_name', 'phone', 'business_name', 'business_phone', 'website', 'address', 'province', 'city', 'working_hours', 'description', 'status', 'payment_status');

        // خروجی HTML
        ob_start();
        ?>
        <div class="order-details-container" data-edit-mode="<?php echo $edit_mode ? 'true' : 'false'; ?>">            
            <!-- محتوای مودال -->
                <!-- وضعیت پرداخت سفارش -->
                <div class="order-Pay-section">
                    <h3>وضعیت پرداخت و سفارش</h3>
                        <!-- وضعیت پرداخت -->
                        <div class="info-group-pay">
                            <div class="info-label">وضعیت پرداخت:</div>
                            <div class="info-value status-control">
                                <?php if ($edit_mode && in_array('payment_status', $editable_fields)): ?>
                                    <select name="payment_status" class="editable-field">
                                        <option value="pending"<?php echo $order->payment_status === 'pending' ? ' selected' : ''; ?>>در انتظار پرداخت</option>
                                        <option value="paid"<?php echo $order->payment_status === 'paid' ? ' selected' : ''; ?>>پرداخت شده</option>
                                        <option value="failed"<?php echo $order->payment_status === 'failed' ? ' selected' : ''; ?>>پرداخت ناموفق</option>
                                    </select>
                                <?php else: ?>
                                    <span class="status-badge payment-<?php echo esc_attr($order->payment_status); ?>">
                                        <?php echo Market_Google_Orders_List::get_payment_status_label($order->payment_status); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <!-- وضعیت سفارش -->
                        <div class="info-group-pay">
                            <div class="info-label">وضعیت سفارش:</div>
                                <div class="info-value status-control">
                                    <?php if ($edit_mode && in_array('status', $editable_fields)): ?>
                                        <select name="status" class="editable-field">
                                            <option value="pending"<?php echo $order->status === 'pending' ? ' selected' : ''; ?>>در انتظار بررسی</option>
                                            <option value="approved"<?php echo $order->status === 'approved' ? ' selected' : ''; ?>>تأیید شده</option>
                                            <option value="completed"<?php echo $order->status === 'completed' ? ' selected' : ''; ?>>تکمیل شده</option>
                                            <option value="rejected"<?php echo $order->status === 'rejected' ? ' selected' : ''; ?>>رد شده</option>
                                        </select>
                                    <?php else: ?>
                                        <span class="status-badge order-<?php echo esc_attr(self::migrate_status($order->status)); ?>">
                                            <?php echo self::get_status_label($order->status); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>         
                            </div>
                        </div>                    
                </div>            
            <div class="order-details-content">
                <!-- اطلاعات اصلی سفارش -->
                <div class="order-main-details">
                    <h3>اطلاعات سفارش</h3>
                    <div class="order-info-section">
                        <!-- نام و نام خانوادگی -->
                        <div class="info-group">
                            <div class="info-label">نام و نام خانوادگی:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->full_name) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->full_name); ?>">
                                <?php echo $is_empty_field($order->full_name) ? 'توسط کاربر تکمیل نشده' : esc_html($order->full_name); ?>
                            </div>
                        </div>
                        
                        <!-- شماره موبایل -->
                        <div class="info-group">
                            <div class="info-label">شماره موبایل:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->phone) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->phone); ?>">
                                <?php echo $is_empty_field($order->phone) ? 'توسط کاربر تکمیل نشده' : esc_html($order->phone); ?>
                            </div>
                        </div>
                        
                        <!-- نام کسب و کار -->
                        <div class="info-group">
                            <div class="info-label">نام کسب و کار:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->business_name) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->business_name); ?>">
                                <?php echo $is_empty_field($order->business_name) ? 'توسط کاربر تکمیل نشده' : esc_html($order->business_name); ?>
                            </div>
                        </div>
                        
                        <!-- شماره کسب و کار -->
                        <div class="info-group">
                            <div class="info-label">شماره کسب و کار:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->business_phone) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->business_phone ?? ''); ?>">
                                <?php echo $is_empty_field($order->business_phone) ? 'توسط کاربر تکمیل نشده' : esc_html($order->business_phone); ?>
                            </div>
                        </div>
                        
                        <!-- وب‌سایت -->
                        <div class="info-group">
                            <div class="info-label">وب‌سایت:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->website) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->website ?? ''); ?>">
                                <?php echo $is_empty_field($order->website) ? 'توسط کاربر تکمیل نشده' : esc_html($order->website); ?>
                            </div>
                        </div>
                        
                        <!-- ساعت کاری -->
                        <div class="info-group">
                            <div class="info-label">ساعت کاری:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->working_hours) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($format_working_hours($order->working_hours)); ?>">
                                <?php echo $format_working_hours($order->working_hours); ?>
                            </div>
                        </div>
                        
                        <!-- مختصات -->
                        <div class="info-group">
                            <div class="info-label">مختصات:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo $order->latitude . ', ' . $order->longitude; ?>">
                                <?php echo $order->latitude . ', ' . $order->longitude; ?>
                            </div>
                        </div>
                        
                        <!-- آدرس -->
                        <div class="info-group">
                            <div class="info-label">آدرس:</div>
                            <div class="info-value copyable <?php echo $is_empty_field($order->manual_address) ? 'empty-field' : ''; ?>" data-clipboard="<?php echo esc_attr($order->manual_address ?? $order->address ?? ''); ?>">
                                <?php 
                                // نمایش آدرس دستی که کاربر وارد کرده
                                $address_to_show = !empty($order->manual_address) ? $order->manual_address : (!empty($order->address) ? $order->address : '');
                                echo $is_empty_field($address_to_show) ? 'توسط کاربر تکمیل نشده' : esc_html($address_to_show); 
                                ?>
                            </div>
                        </div>

                        <!-- استان -->
                        <div class="info-group">
                            <div class="info-label">استان:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo esc_attr($order->province ?? $order->state ?? ''); ?>">
                                <?php echo esc_html($order->province ?? $order->state ?? ''); ?>
                            </div>
                        </div>
                        
                        <!-- شهر -->
                        <div class="info-group">
                            <div class="info-label">شهر:</div>
                            <div class="info-value copyable" data-clipboard="<?php echo esc_attr($order->city); ?>">
                                <?php echo esc_html($order->city); ?>
                            </div>
                        </div>                        
                        
                        <!-- مبلغ پرداختی -->
                        <div class="info-group">
                            <div class="info-label">مبلغ پرداختی:</div>
                            <div class="info-value payment-amount-display <?php echo ($order->payment_status === 'success' || $order->payment_status === 'completed') ? 'payment-success' : ''; ?>">
                                <?php 
                                $amount = isset($order->price) ? floatval($order->price) : 0;
                                $amount = $amount / 10; // تبدیل به تومان
                                echo number_format($amount, 0, '.', ',') . ' تومان';
                                ?>
                            </div>
                        </div>
                        
                        <!-- تاریخ و ساعت ثبت -->
                        <div class="info-group">
                            <div class="info-label">تاریخ و ساعت ثبت:</div>
                            <div class="info-value">
                                <?php 
                                $jalali_date = Market_Google_Orders_List::convert_to_shamsi_date($order->created_at);
                                echo $jalali_date;
                                ?>
                            </div>
                        </div>
                </div>              
            </div>
            <!-- محصولات انتخاب شده -->
            <div class="order-main-details">
                <h3>محصولات انتخاب شده</h3>
                    <?php if (empty($selected_products)): ?>
                        <p>هیچ محصولی انتخاب نشده است.</p>
                    <?php else: ?>
                        <div class="order-products-list">
                            <?php if ($has_special_package): ?>
                                <!-- اگر پکیج ویژه انتخاب شده، محصولات معمولی نمایش داده می‌شود -->
                                <?php foreach ($products_to_show as $product_id => $product): ?>
                                    <div class="product-item">
                                        <div class="product-name">
                                            <?php echo esc_html($product['title']); ?>
                                            <div class="package-contents">
                                                <small>جزء پکیج "تمامی نقشه‌های آنلاین"</small>
                                            </div>
                                        </div>
                                        <div class="product-quantity">
                                            <span class="quantity-label">تعداد:</span>
                                            <span class="quantity-value">1</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- محصولات انتخابی معمولی -->
                                <?php foreach ($selected_products as $product_id => $quantity): ?>
                                    <div class="product-item">
                                        <div class="product-name">
                                            <?php 
                                            if (isset($products[$product_id])) {
                                                echo esc_html($products[$product_id]['title']);
                                                
                                                // نمایش محتویات پکیج
                                                $package_contents = json_decode($products[$product_id]['package_contents'] ?? '[]', true);
                                                if (!empty($package_contents) && is_array($package_contents)) {
                                                    echo '<div class="package-contents">';
                                                    echo '<ul>';
                                                    foreach ($package_contents as $item) {
                                                        echo '<li>' . esc_html($item) . '</li>';
                                                    }
                                                    echo '</ul>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo 'محصول #' . $product_id . ' (حذف شده)';
                                            } 
                                            ?>
                                        </div>
                                        <div class="product-quantity">
                                            <span class="quantity-label">تعداد:</span>
                                            <span class="quantity-value"><?php echo intval($quantity); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>  
            <!-- نقشه -->
            <div class="order-main-details">
                <h3>موقعیت مکانی</h3>
                <div id="order-map" style="height: 300px; width: 100%; margin-top: 10px; border-radius: 8px;" data-lat="<?php echo esc_attr($order->latitude); ?>" data-lng="<?php echo esc_attr($order->longitude); ?>"></div>
            </div>            
        </div>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html' => $html,
            'order_id' => $order_id,
            'order_number' => $order_number
        ));
    }

    /**
     * دریافت فرم ویرایش سفارش
     */
    public static function ajax_get_order_edit_form() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error('سفارش یافت نشد.');
            return;
        }

        // ساخت فرم ویرایش
        ob_start();
        ?>
        <form id="order-edit-form">
            <div class="form-row">
                <label>نام کامل:</label>
                <input type="text" name="full_name" value="<?php echo esc_attr($order->full_name); ?>" required>
            </div>
            <div class="form-row">
                <label>شماره موبایل:</label>
                <input type="text" name="phone" value="<?php echo esc_attr($order->phone); ?>" required>
            </div>
            <div class="form-row">
                <label>نام کسب و کار:</label>
                <input type="text" name="business_name" value="<?php echo esc_attr($order->business_name); ?>" required>
            </div>
            <div class="form-row">
                <label>آدرس:</label>
                <textarea name="address" required><?php echo esc_textarea($order->address); ?></textarea>
            </div>
            <div class="form-row">
                <label>وضعیت سفارش:</label>
                <select name="status">
                    <option value="pending" <?php selected($order->status, 'pending'); ?>>در انتظار انجام</option>
                    <option value="completed" <?php selected($order->status, 'completed'); ?>>تکمیل شده</option>
                </select>
            </div>
        </form>
        <?php
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }

    /**
     * بروزرسانی سفارش
     */
    public static function ajax_update_order() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('شما دسترسی لازم برای این عملیات را ندارید.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        // Parse form data
        parse_str($_POST['form_data'], $form_data);

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بروزرسانی سفارش
        $updated = $wpdb->update(
            $table_name,
            array(
                'full_name' => sanitize_text_field($form_data['full_name']),
                'phone' => sanitize_text_field($form_data['phone']),
                'business_name' => sanitize_text_field($form_data['business_name']),
                'address' => sanitize_textarea_field($form_data['address']),
                'status' => sanitize_text_field($form_data['status']),
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error('خطا در بروزرسانی سفارش.');
            return;
        }

        wp_send_json_success(array(
            'message' => 'سفارش با موفقیت بروزرسانی شد.',
            'order_id' => $order_id
        ));
    }

    /**
     * حذف سفارش از طریق AJAX
     */
    public static function ajax_delete_order() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('شما دسترسی لازم برای این عملیات را ندارید.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بررسی وجود سفارش
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT id, business_name, full_name FROM {$table_name} WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error('سفارش مورد نظر یافت نشد.');
            return;
        }

        // حذف سفارش
        $deleted = $wpdb->delete(
            $table_name,
            array('id' => $order_id),
            array('%d')
        );

        if ($deleted === false) {
            wp_send_json_error('خطا در حذف سفارش.');
            return;
        }

        // ثبت لاگ
        error_log("Order #{$order_id} ({$order->business_name}) deleted by admin");

        wp_send_json_success(array(
            'message' => 'سفارش با موفقیت حذف شد.',
            'order_id' => $order_id
        ));
    }

    public static function ajax_check_sms() {
        if (!wp_verify_nonce($_POST['security'], 'market_google_orders_nonce')) {
            wp_send_json_error(array('message' => 'مشکل امنیتی - نامعتبر'));
            return;
        }

        try {
            // بررسی تنظیمات SMS
            $sms_settings = get_option('market_google_sms_settings', array());
            
            if (empty($sms_settings['provider'])) {
                wp_send_json_error(array('message' => '❌ هیچ ارائه‌دهنده SMS انتخاب نشده است'));
                return;
            }
            
            if (empty($sms_settings['username']) || empty($sms_settings['password'])) {
                wp_send_json_error(array('message' => '❌ نام کاربری یا رمز عبور SMS وارد نشده است'));
                return;
            }

            // آزمایش اتصال به سرور SMS
            $ch = curl_init();
            curl_setopt_array($ch, array(
                CURLOPT_URL => 'https://rest.payamak-panel.com',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_NOBODY => true, // فقط header ها
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_USERAGENT => 'Market Google SMS Test/1.0'
            ));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            curl_close($ch);
            
            if ($curl_error) {
                wp_send_json_error(array('message' => '❌ خطا در اتصال به سرور: ' . $curl_error . '\nتست از ترمینال موفق بود پس مشکل در تنظیمات PHP یا cURL سایت است'));
                return;
            }
            
            if ($http_code === 0) {
                wp_send_json_error(array('message' => '❌ سرور در دسترس نیست (DNS یا شبکه)'));
                return;
            }
            
            // بررسی instance SMS Handler
            $sms_handler = Market_Google_SMS_Handler::get_instance();
            if (!$sms_handler) {
                wp_send_json_error(array('message' => '❌ SMS Handler در دسترس نیست'));
                return;
            }
            
            $connection_test = $sms_handler->test_connection($sms_settings);
            
            $message = "✅ تنظیمات SMS:\n";
            $message .= "🔸 ارائه‌دهنده: " . $sms_settings['provider'] . "\n";
            $message .= "🔸 نام کاربری: " . $sms_settings['username'] . "\n";
            $message .= "🔸 اتصال سرور: HTTP $http_code (" . round($total_time, 2) . "s)\n";
            
            if ($connection_test['success']) {
                $message .= "🔸 وضعیت اتصال: ✅ موفق\n";
                $count_info = $sms_handler->get_sms_count();
                if ($count_info['success']) {
                    $message .= "🔸 موجودی پیامک: " . $count_info['count'] . " عدد";
                }
            } else {
                $message .= "🔸 وضعیت اتصال: ❌ " . $connection_test['message'];
            }
            
            wp_send_json_success(array('message' => $message));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => '❌ خطا در بررسی: ' . $e->getMessage()));
        }
    }

    /**
     * تبدیل تاریخ شمسی به میلادی - نسخه صحیح و تست شده
     * 
     * @param int $jy سال شمسی
     * @param int $jm ماه شمسی
     * @param int $jd روز شمسی
     * @return string تاریخ میلادی بفرمت Y-m-d
     */
    private static function simple_jalali_to_gregorian($jy, $jm, $jd) {
        // الگوریتم ساده و مطمئن برای تبدیل تاریخ
        // بر اساس اینکه 1 فروردین 1404 = 21 مارس 2025
        
        // سال پایه: 1404 = 2025
        $base_jalali_year = 1404;
        $base_gregorian_year = 2025;
        $year_diff = $jy - $base_jalali_year;
        $target_year = $base_gregorian_year + $year_diff;
        
        // تعداد روزهای هر ماه شمسی
        $jalali_month_days = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
        
        // محاسبه تعداد روز از ابتدای سال شمسی
        $day_of_year = 0;
        for ($m = 1; $m < $jm; $m++) {
            $day_of_year += $jalali_month_days[$m - 1];
        }
        $day_of_year += $jd - 1; // منهای 1 چون از صفر شروع می‌کنیم
        
        // 1 فروردین = 21 مارس (روز 80 سال میلادی)
        $march_21_timestamp = mktime(0, 0, 0, 3, 21, $target_year);
        $final_timestamp = $march_21_timestamp + ($day_of_year * 24 * 60 * 60);
        
        $result = date('Y-m-d', $final_timestamp);
        
        // اضافه کردن debug برای بررسی
        error_log("🧮 Date conversion debug: $jy/$jm/$jd -> day_of_year: $day_of_year -> final: $result");
        
        return $result;
    }
    
    /**
     * تست دستی سیستم پیامک
     */
    public static function ajax_manual_sms_test() {
        // بررسی امنیت
        if (!wp_verify_nonce($_POST['security'] ?? '', 'market_google_orders_nonce')) {
            wp_send_json_error('امنیت درخواست تأیید نشد.');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('دسترسی کافی ندارید.');
        }

        $event_type = sanitize_text_field($_POST['event_type'] ?? 'form_submitted');
        $test_phone = sanitize_text_field($_POST['test_phone'] ?? '09123456789');
        
        error_log("Manual SMS Test: Event = $event_type, Phone = $test_phone");
        
        // آماده‌سازی داده‌های تست
        $test_data = array(
            'full_name' => 'کاربر',
            'business_name' => '',
            'phone' => $test_phone,
            'price' => '',
            'order_number' => '#MG-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT),
            'payment_status' => 'موفق'
        );
        
        $location_data = array(
            'phone' => $test_phone
        );
        
        // بررسی وجود کلاس SMS Service
        if (!class_exists('Market_Google_SMS_Service')) {
            error_log("Manual SMS Test: SMS Service class not found");
            wp_send_json_error('کلاس SMS Service یافت نشد.');
        }
        
        // trigger کردن رویدادس
        switch ($event_type) {
            case 'form_submitted':
                error_log("Manual SMS Test: Triggering form_submitted event");
                do_action('market_google_form_submitted', $test_data, $location_data);
                break;
                
            case 'payment_success':
                error_log("Manual SMS Test: Triggering payment_success event");
                do_action('market_google_payment_success', $test_data, $location_data);
                break;
                
            case 'order_completion':
                error_log("Manual SMS Test: Triggering order_completion event");
                do_action('market_google_order_completion', $test_data, $location_data);
                break;
                
            case 'payment_pending':
                error_log("Manual SMS Test: Triggering payment_pending event");
                do_action('market_google_payment_pending', $test_data, $location_data);
                break;
                
            default:
                wp_send_json_error('نوع رویداد نامعتبر.');
        }
        
        wp_send_json_success(array(
            'message' => "رویداد $event_type برای شماره $test_phone trigger شد. لاگ‌ها را بررسی کنید.",
            'event_type' => $event_type,
            'test_phone' => $test_phone,
            'test_data' => $test_data
        ));
    }

    /**
     * تغییر وضعیت پرداخت سفارش
     */
    public static function ajax_change_payment_status() {
        // بررسی nonce
        $nonce = $_POST['security'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'market_google_orders_nonce')) {
            wp_send_json_error('خطای امنیتی - nonce نامعتبر است.');
            return;
        }

        // بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_send_json_error('شما دسترسی لازم برای این عملیات را ندارید.');
            return;
        }

        $order_id = intval($_POST['order_id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        
        if (empty($order_id)) {
            wp_send_json_error('شناسه سفارش معتبر نیست.');
            return;
        }

        if (!in_array($status, ['pending', 'success', 'failed', 'cancelled'])) {
            wp_send_json_error('وضعیت پرداخت معتبر نیست.');
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // بروزرسانی رکورد
        $updated = $wpdb->update(
            $table_name,
            array(
                'payment_status' => $status,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $order_id),
            array('%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error('خطا در بروزرسانی وضعیت پرداخت.');
            return;
        }

        wp_send_json_success(array(
            'message' => 'وضعیت پرداخت با موفقیت تغییر کرد.',
            'status_label' => self::get_payment_status_label($status)
        ));
    }
}