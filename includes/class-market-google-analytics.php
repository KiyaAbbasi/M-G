<?php
/**
 * کلاس آمار و گزارش‌گیری افزونه
 */
class Market_Google_Analytics {
    
    /**
     * راه‌اندازی کلاس
     */
    public static function init() {
        $instance = new self();
        
        // اضافه کردن منوی آمار در پنل مدیریت
        add_action('admin_menu', array($instance, 'add_analytics_menu'), 20);
        
        // اضافه کردن ویجت آمار به داشبورد
        add_action('wp_dashboard_setup', array($instance, 'add_dashboard_widget'));
        
        // اضافه کردن شورت‌کد آمار
        add_shortcode('market_location_stats', array($instance, 'stats_shortcode'));
        
        // ثبت اکشن‌های AJAX برای آمار
        add_action('wp_ajax_get_location_stats', array($instance, 'get_location_stats_ajax'));

        // Hook ها برای آمارگیری
        add_action('wp_ajax_get_analytics_data', array(__CLASS__, 'get_analytics_data'));
    }
    
    /**
     * اضافه کردن منوی آمار
     */
    public function add_analytics_menu() {
        // منو حذف شد - آمار و گزارش‌گیری حالا در منوی اصلی موجود است
    }
    
    /**
     * نمایش صفحه آمار
     */
    public function display_analytics_page() {
        $stats = $this->get_general_stats();
        $chart_data = $this->get_chart_data();
        $top_cities = $this->get_top_cities();
        $top_business_types = $this->get_top_business_types();
        
        ?>
        <div class="wrap">
            <h1><?php _e('آمار و گزارش‌های لوکیشن‌ها', 'market-google-location'); ?></h1>
            
            <div class="market-analytics-container">
                <!-- کارت‌های آمار کلی -->
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="stat-icon">📍</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_locations']); ?></h3>
                            <p><?php _e('کل لوکیشن‌ها', 'market-google-location'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['completed_payments']); ?></h3>
                            <p><?php _e('پرداخت‌های موفق', 'market-google-location'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['total_revenue']); ?> تومان</h3>
                            <p><?php _e('کل درآمد', 'market-google-location'); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo number_format($stats['unique_users']); ?></h3>
                            <p><?php _e('کاربران فعال', 'market-google-location'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- نمودار ثبت‌نام‌ها -->
                <div class="chart-container">
                    <h2><?php _e('ثبت لوکیشن‌ها در 30 روز گذشته', 'market-google-location'); ?></h2>
                    <canvas id="registrations-chart" width="400" height="200"></canvas>
                </div>
                
                <!-- جداول آماری -->
                <div class="analytics-tables">
                    <div class="analytics-table">
                        <h3><?php _e('برترین شهرها', 'market-google-location'); ?></h3>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('شهر', 'market-google-location'); ?></th>
                                    <th><?php _e('تعداد لوکیشن', 'market-google-location'); ?></th>
                                    <th><?php _e('درصد', 'market-google-location'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_cities as $city) : ?>
                                <tr>
                                    <td><?php echo esc_html($city->city); ?></td>
                                    <td><?php echo $city->count; ?></td>
                                    <td><?php echo round(($city->count / $stats['total_locations']) * 100, 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="analytics-table">
                        <h3><?php _e('برترین انواع کسب و کار', 'market-google-location'); ?></h3>
                        <table class="wp-list-table widefat">
                            <thead>
                                <tr>
                                    <th><?php _e('نوع کسب و کار', 'market-google-location'); ?></th>
                                    <th><?php _e('تعداد', 'market-google-location'); ?></th>
                                    <th><?php _e('درصد', 'market-google-location'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_business_types as $type) : ?>
                                <tr>
                                    <td><?php echo esc_html($this->get_business_type_label($type->business_type)); ?></td>
                                    <td><?php echo $type->count; ?></td>
                                    <td><?php echo round(($type->count / $stats['total_locations']) * 100, 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- فیلترهای پیشرفته -->
                <div class="advanced-filters">
                    <h3><?php _e('فیلترهای پیشرفته', 'market-google-location'); ?></h3>
                    <form id="analytics-filter-form">
                        <div class="filter-row">
                            <div class="filter-field">
                                <label><?php _e('از تاریخ:', 'market-google-location'); ?></label>
                                <input type="date" name="date_from" id="date_from">
                            </div>
                            
                            <div class="filter-field">
                                <label><?php _e('تا تاریخ:', 'market-google-location'); ?></label>
                                <input type="date" name="date_to" id="date_to">
                            </div>
                            
                            <div class="filter-field">
                                <label><?php _e('شهر:', 'market-google-location'); ?></label>
                                <select name="city" id="city_filter">
                                    <option value=""><?php _e('همه شهرها', 'market-google-location'); ?></option>
                                    <?php foreach ($top_cities as $city) : ?>
                                    <option value="<?php echo esc_attr($city->city); ?>"><?php echo esc_html($city->city); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-field">
                                <label><?php _e('نوع کسب و کار:', 'market-google-location'); ?></label>
                                <select name="business_type" id="business_type_filter">
                                    <option value=""><?php _e('همه انواع', 'market-google-location'); ?></option>
                                    <?php foreach ($top_business_types as $type) : ?>
                                    <option value="<?php echo esc_attr($type->business_type); ?>"><?php echo esc_html($this->get_business_type_label($type->business_type)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="button button-primary"><?php _e('اعمال فیلتر', 'market-google-location'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        // کد جاوااسکریپت برای نمودارها
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('registrations-chart').getContext('2d');
            const chartData = <?php echo json_encode($chart_data); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: [{
                        label: '<?php _e('تعداد ثبت‌نام‌ها', 'market-google-location'); ?>',
                        data: chartData.data,
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
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * دریافت آمار کلی
     */
    public function get_general_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $stats = array();
        
        // کل لوکیشن‌ها
        $stats['total_locations'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // پرداخت‌های موفق
        $stats['completed_payments'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE payment_status = 'completed'");
        
        // کل درآمد
        $stats['total_revenue'] = $wpdb->get_var("SELECT SUM(payment_amount) FROM $table_name WHERE payment_status = 'completed'") ?: 0;
        
        // کاربران منحصر به فرد
        $stats['unique_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name");
        
        // متوسط درآمد روزانه (30 روز گذشته)
        $stats['avg_daily_revenue'] = $wpdb->get_var("
            SELECT AVG(daily_revenue) FROM (
                SELECT DATE(created_at) as day, SUM(payment_amount) as daily_revenue 
                FROM $table_name 
                WHERE payment_status = 'completed' 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
            ) as daily_stats
        ") ?: 0;
        
        return $stats;
    }
    
    /**
     * دریافت داده‌های نمودار
     */
    public function get_chart_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        // آمار 30 روز گذشته
        $results = $wpdb->get_results("
            SELECT DATE(created_at) as date, COUNT(*) as count
            FROM $table_name 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        $labels = array();
        $data = array();
        
        // پر کردن 30 روز گذشته (حتی اگر داده نداشته باشیم)
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('j M', strtotime($date));
            
            // پیدا کردن تعداد برای این تاریخ
            $count = 0;
            foreach ($results as $result) {
                if ($result->date === $date) {
                    $count = $result->count;
                    break;
                }
            }
            $data[] = $count;
        }
        
        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
    
    /**
     * دریافت برترین شهرها
     */
    public function get_top_cities($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT city, COUNT(*) as count
            FROM $table_name 
            WHERE city != '' AND payment_status = 'completed'
            GROUP BY city
            ORDER BY count DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * دریافت برترین انواع کسب و کار
     */
    public function get_top_business_types($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT business_type, COUNT(*) as count
            FROM $table_name 
            WHERE business_type != '' AND payment_status = 'completed'
            GROUP BY business_type
            ORDER BY count DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * دریافت برچسب نوع کسب و کار
     */
    private function get_business_type_label($type) {
        $types = array(
            'restaurant' => __('رستوران', 'market-google-location'),
            'cafe' => __('کافه', 'market-google-location'),
            'shop' => __('فروشگاه', 'market-google-location'),
            'hotel' => __('هتل', 'market-google-location'),
            'office' => __('دفتر کار', 'market-google-location'),
            'medical' => __('مرکز درمانی', 'market-google-location'),
            'education' => __('مرکز آموزشی', 'market-google-location'),
            'entertainment' => __('مرکز تفریحی', 'market-google-location'),
            'other' => __('سایر', 'market-google-location')
        );
        
        return isset($types[$type]) ? $types[$type] : $type;
    }
    
    /**
     * اضافه کردن ویجت آمار به داشبورد
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'market_google_stats_widget',
            __('آمار لوکیشن‌های کسب و کار', 'market-google-location'),
            array($this, 'dashboard_widget_content')
        );
    }
    
    /**
     * محتوای ویجت داشبورد
     */
    public function dashboard_widget_content() {
        $stats = $this->get_general_stats();
        
        ?>
        <div class="market-dashboard-stats">
            <div class="stat-row">
                <span class="stat-label"><?php _e('کل لوکیشن‌ها:', 'market-google-location'); ?></span>
                <span class="stat-value"><?php echo number_format($stats['total_locations']); ?></span>
            </div>
            
            <div class="stat-row">
                <span class="stat-label"><?php _e('پرداخت‌های موفق:', 'market-google-location'); ?></span>
                <span class="stat-value"><?php echo number_format($stats['completed_payments']); ?></span>
            </div>
            
            <div class="stat-row">
                <span class="stat-label"><?php _e('کل درآمد:', 'market-google-location'); ?></span>
                <span class="stat-value"><?php echo number_format($stats['total_revenue']); ?> تومان</span>
            </div>
            
            <div class="stat-row">
                <span class="stat-label"><?php _e('کاربران فعال:', 'market-google-location'); ?></span>
                <span class="stat-value"><?php echo number_format($stats['unique_users']); ?></span>
            </div>
        </div>
        
        <p><a href="<?php echo admin_url('admin.php?page=market-google-analytics'); ?>" class="button button-primary">
            <?php _e('مشاهده آمار کامل', 'market-google-location'); ?>
        </a></p>
        <?php
    }
    
    /**
     * شورت‌کد آمار برای نمایش در فرانت‌اند
     */
    public function stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'total_locations,completed_payments', // آمارهای قابل نمایش
            'style' => 'cards' // نحوه نمایش: cards, list, inline
        ), $atts);
        
        $stats = $this->get_general_stats();
        $show_stats = explode(',', $atts['show']);
        
        ob_start();
        ?>
        <div class="market-public-stats <?php echo esc_attr($atts['style']); ?>">
            <?php foreach ($show_stats as $stat_key) : ?>
                <?php if (isset($stats[trim($stat_key)])) : ?>
                <div class="stat-item">
                    <span class="stat-label"><?php echo $this->get_stat_label(trim($stat_key)); ?>:</span>
                    <span class="stat-value"><?php echo $this->format_stat_value(trim($stat_key), $stats[trim($stat_key)]); ?></span>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * دریافت برچسب آمار
     */
    private function get_stat_label($key) {
        $labels = array(
            'total_locations' => __('کل لوکیشن‌ها', 'market-google-location'),
            'completed_payments' => __('پرداخت‌های موفق', 'market-google-location'),
            'total_revenue' => __('کل درآمد', 'market-google-location'),
            'unique_users' => __('کاربران فعال', 'market-google-location')
        );
        
        return isset($labels[$key]) ? $labels[$key] : $key;
    }
    
    /**
     * فرمت کردن مقدار آمار
     */
    private function format_stat_value($key, $value) {
        switch ($key) {
            case 'total_revenue':
            case 'avg_daily_revenue':
                return number_format($value) . ' تومان';
            default:
                return number_format($value);
        }
    }
    
    /**
     * AJAX برای دریافت آمار
     */
    public function get_location_stats_ajax() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $business_type = isset($_POST['business_type']) ? sanitize_text_field($_POST['business_type']) : '';
        
        // اعمال فیلترها و دریافت آمار جدید
        $filtered_stats = $this->get_filtered_stats($date_from, $date_to, $city, $business_type);
        
        wp_send_json_success($filtered_stats);
    }
    
    /**
     * دریافت آمار با فیلتر
     */
    private function get_filtered_stats($date_from, $date_to, $city, $business_type) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        
        $where_clauses = array('1=1');
        
        if (!empty($date_from)) {
            $where_clauses[] = $wpdb->prepare("DATE(created_at) >= %s", $date_from);
        }
        
        if (!empty($date_to)) {
            $where_clauses[] = $wpdb->prepare("DATE(created_at) <= %s", $date_to);
        }
        
        if (!empty($city)) {
            $where_clauses[] = $wpdb->prepare("city = %s", $city);
        }
        
        if (!empty($business_type)) {
            $where_clauses[] = $wpdb->prepare("business_type = %s", $business_type);
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        return array(
            'total_locations' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where_sql"),
            'completed_payments' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $where_sql AND payment_status = 'completed'"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(payment_amount) FROM $table_name WHERE $where_sql AND payment_status = 'completed'") ?: 0,
            'unique_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_name WHERE $where_sql")
        );
    }

    /**
     * دریافت داده‌های آمار برای مدیریت
     */
    public static function get_analytics_data() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی ندارید');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        $data = array(
            'total_locations' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'active_locations' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'active'"),
            'pending_locations' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE status = 'pending'"),
            'total_revenue' => $wpdb->get_var("SELECT SUM(price) FROM {$table_name} WHERE payment_status = 'completed'")
        );

        wp_send_json_success($data);
    }
} 