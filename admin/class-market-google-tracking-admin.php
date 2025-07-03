<?php

/**
 * صفحه ادمین ردیابی کاربران - کامل و جامع
 */

if (!defined('ABSPATH')) {
    exit;
}

class Market_Google_Tracking_Admin {
    
    public function __construct() {
        add_action('admin_post_insert_fake_tracking_data', array($this, 'insert_fake_tracking_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_refresh_tracking_stats', array($this, 'refresh_tracking_stats'));
        
        // Add AJAX handlers for advanced analytics
        add_action('wp_ajax_get_session_data', array($this, 'ajax_get_session_data'));
        add_action('wp_ajax_get_live_sessions', array($this, 'ajax_get_live_sessions'));
        add_action('wp_ajax_filter_session_data', array($this, 'ajax_filter_session_data'));
        add_action('wp_ajax_export_analytics', array($this, 'ajax_export_analytics'));
        add_action('wp_ajax_clear_old_tracking_data', array($this, 'ajax_clear_old_data'));
        add_action('wp_ajax_export_user_data', array($this, 'export_user_data'));
        add_action('wp_ajax_check_user_status_for_transfer', array($this, 'ajax_check_user_status_for_transfer'));
        add_action('wp_ajax_get_user_session_details', array($this, 'ajax_get_user_session_details'));
        
        // Add AJAX handlers for filters
        add_action('wp_ajax_mg_location_suggest', array($this, 'ajax_location_suggest'));
        add_action('wp_ajax_mg_pages_suggest', array($this, 'ajax_pages_suggest'));
        add_action('wp_ajax_mg_browser_suggest', array($this, 'ajax_browser_suggest'));
        add_action('wp_ajax_mg_referrer_suggest', array($this, 'ajax_referrer_suggest'));
        add_action('wp_ajax_mg_event_suggest', array($this, 'ajax_event_suggest'));
        add_action('wp_ajax_mg_tracking_filter', array($this, 'ajax_tracking_filter'));
        add_action('wp_ajax_mg_ip_suggest', array($this, 'ajax_ip_suggest'));
        add_action('wp_ajax_mg_device_suggest', array($this, 'ajax_device_suggest'));

    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'market-google') !== false) {
            wp_enqueue_script('jquery');
            
            // اینکلود فایل‌های CSS
            wp_enqueue_style(
                'market-google-tracking-admin',
                plugins_url('admin/css/market-google-tracking-admin.css', dirname(__FILE__)),
                array(),
                time()
            );
            wp_enqueue_style(
                'market-google-tracking-filters',
                plugins_url('admin/css/market-google-tracking-filters.css', dirname(__FILE__)),
                array(),
                time()
            );
            
            // اینکلود فایل‌های JavaScript
            wp_enqueue_script(
                'market-google-tracking-admin',
                plugins_url('admin/js/market-google-tracking-admin.js', dirname(__FILE__)),
                array('jquery'),
                time(),
                true
            );
            wp_enqueue_script(
                'market-google-tracking-filters',
                plugins_url('admin/js/market-google-tracking-filters.js', dirname(__FILE__)),
                array('jquery'),
                time(),
                true
            );
            
            // افزودن Choices.js برای فیلترهای پیشرفته
            wp_enqueue_style('choices-css', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css');
            wp_enqueue_script('choices-js', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', array(), null, true);
        
        // لوکالایز کردن اسکریپت برای امنیت
        wp_localize_script('market-google-tracking-admin', 'market_google_admin', array(
            'nonce' => wp_create_nonce('market_google_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ));
        }
    }

    //محتوی اصلی صفحه
    public function admin_page_content() {
        if (!class_exists('Market_Google_User_Tracking')) {
            echo '<div class="wrap"><h1>ردیابی کاربران</h1>';
            echo '<div class="notice notice-error"><p>کلاس ردیابی یافت نشد. پلاگین را دوباره فعال کنید.</p></div></div>';
            return;
        }
        // دریافت لیست کاربران آنلاین (آخرین فعالیت < ۱۵ دقیقه)
        $online_sessions = Market_Google_User_Tracking::get_online_users();
        $online_ids = array_map(function($s){return $s->session_id;}, $online_sessions);

        // لیدهای مارکتینگ (فقط کسانی که آنلاین نیستند)
        $all_marketing_leads = Market_Google_User_Tracking::get_marketing_leads();
        $marketing_leads = array_filter($all_marketing_leads, function($s) use ($online_ids) {
            return !in_array($s->session_id, $online_ids);
        });
        $marketing_ids = array_map(function($s){return $s->session_id;}, $marketing_leads);

        // بازدیدکنندگان ناتمام (فقط کسانی که آنلاین نیستند و لید مارکتینگ نیستند)
        $all_incomplete_leads = Market_Google_User_Tracking::get_incomplete_leads();
        $incomplete_leads = array_filter($all_incomplete_leads, function($s) use ($online_ids, $marketing_ids) {
            return !in_array($s->session_id, $online_ids) && !in_array($s->session_id, $marketing_ids);
        });
        $incomplete_ids = array_map(function($s){return $s->session_id;}, $incomplete_leads);

        // اگر نیاز به لیست ربات‌ها داری، همین منطق را با حذف session_idهای قبلی اعمال کن
        $bot_sessions = $this->get_suspected_bot_sessions();
        $bot_sessions = array_filter($bot_sessions, function($s) use ($online_ids, $marketing_ids, $incomplete_ids) {
            return !in_array($s->session_id, $online_ids) && !in_array($s->session_id, $marketing_ids) && !in_array($s->session_id, $incomplete_ids);
        });

        // شمارش کل کاربران یکتا و غیرتکراری امروز
        $unique_today_sessions = array_merge($online_ids, $marketing_ids, $incomplete_ids);
        $total_today_users = count($unique_today_sessions);
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $tehran_tz = new \DateTimeZone('Asia/Tehran');
        $now = new \DateTime('now', $tehran_tz);
        $today_start = $now->format('Y-m-d') . ' 00:00:00';
        $current_time = $now->format('Y-m-d H:i:s');
        $last_15_min = (clone $now)->modify('-15 minutes')->format('Y-m-d H:i:s');
        try {

            // --- متغیرهای باکس اول: تحلیل کاربران ---
            // کل بازدیدکنندگان امروز (session_id یکتا)
            $total_today_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name",
                 
            ));
            // میانگین زمان بازدید امروز
            $avg_session_time_today = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(session_duration) FROM (
                    SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp)) as session_duration
                    FROM $table_name
                    
                    GROUP BY session_id
                ) as durations",
                 
            ));
            $avg_session_time_today = $avg_session_time_today ? intval($avg_session_time_today) : 0;
            $avg_session_time_today_formatted = sprintf('%02d:%02d:%02d', floor($avg_session_time_today/3600), floor(($avg_session_time_today%3600)/60), $avg_session_time_today%60);
            // کل صفحات بازدید شده امروز
            $page_views_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name event_type = 'page_load'",
                 
            ));
            // کاربران آنلاین امروز (آخرین فعالیت در ۱۵ دقیقه اخیر)
            $online_user_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT session_id FROM $table_name WHERE timestamp >= %s AND timestamp <= %s GROUP BY session_id HAVING MAX(timestamp) >= %s",
                $today_start, $current_time, $last_15_min
            ));

            $online_count = count($online_user_ids);
            $offline_count = max(0, $total_today_users - $online_count);
            $active_percent = $total_today_users > 0 ? round(($online_count / $total_today_users) * 100, 1) : 0;
            $idle_percent = $total_today_users > 0 ? round(($offline_count / $total_today_users) * 100, 1) : 0;
            // دستگاه کاربران امروز
            $user_agents = $wpdb->get_col($wpdb->prepare(
                "SELECT MAX(user_agent) FROM $table_name GROUP BY session_id",
                 
            ));
            $mobile_count = 0;
            $desktop_count = 0;
            foreach ($user_agents as $ua) {
                if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) {
                    $mobile_count++;
                } else {
                    $desktop_count++;
                }
            }
            $mobile_percent = $total_today_users > 0 ? round(($mobile_count / $total_today_users) * 100, 1) : 0;
            $desktop_percent = $total_today_users > 0 ? round(($desktop_count / $total_today_users) * 100, 1) : 0;

            $avg_session_time_today = $avg_session_time_today ? intval($avg_session_time_today) : 0;
            $avg_session_time_today_formatted = sprintf('%02d:%02d:%02d', floor($avg_session_time_today/3600), floor(($avg_session_time_today%3600)/60), $avg_session_time_today%60);
            


            // --- متغیرهای باکس دوم: لید‌های جدید  ---
           // کل بازدیدکنندگان امروز (session_id یکتا)
            $total_today_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
                 
            ));
            // پرداخت موفق امروز
            $successful_payments = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}market_google_locations WHERE created_at >= %s AND created_at <= %s AND payment_status = 'completed'",
                 
            ));
            // پرداخت ناموفق امروز
            $failed_payments = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}market_google_locations WHERE created_at >= %s AND created_at <= %s AND payment_status = 'failed'",
                 
            ));
            // لیدهای مارکتینگ امروز (کاربرانی که شماره موبایل دارند)
            $marketing_leads = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND element_id = 'phone' AND element_value != ''",
                 
            ));
            // فرم‌های ناتمام امروز
            $incomplete_sessions = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT t1.session_id) FROM $table_name t1
                LEFT JOIN $table_name t2 ON t1.session_id = t2.session_id AND t2.event_type = 'form_submit_detailed'
                WHERE t1.timestamp >= %s AND t1.timestamp <= %s AND t2.session_id IS NULL",
                 
            ));            


            // --- متغیرهای باکس سوم: تحلیل ترافیک امروز ---
            // کاربران جدید امروز (کسانی که اولین ورودشان امروز است)
            $new_users_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND is_returning = 0",
                 
            ));
            // کاربران برگشتی امروز (کسانی که قبلاً هم وارد شده‌اند)
            $returning_users_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND is_returning = 1",
                 
            ));
            $total_today_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
                 
            ));
            $returning_users_percent = $total_today_users > 0 ? round(($returning_users_today / $total_today_users) * 100, 1) : 0;
            // نرخ پرش: کاربرانی که فقط یک page_load داشته‌اند
            $bounce_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM (
                    SELECT session_id FROM $table_name AND event_type = 'page_load' GROUP BY session_id HAVING COUNT(*) = 1
                ) as single_page_sessions",
                 
            ));
            $bounce_percent = $total_today_users > 0 ? round(($bounce_count / $total_today_users) * 100, 1) : 0;
            // نرخ رشد نسبت به دیروز
            $yesterday_start = (clone $now)->modify('-1 day')->format('Y-m-d') . ' 00:00:00';
            $yesterday_end = (clone $now)->modify('-1 day')->format('Y-m-d') . ' 23:59:59';
            $new_users_yesterday = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND is_returning = 0",
                 
            ));
            $growth_count = $new_users_today - $new_users_yesterday;
            $growth_percent = $new_users_yesterday > 0 ? round(($growth_count / $new_users_yesterday) * 100, 1) : 0;
            // ورودی از سئو (مثلاً referrer شامل google یا bing یا yahoo)
            $seo_users = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND (referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%yahoo%')",
                 
            ));
            $seo_percent = $total_today_users > 0 ? round(($seo_users / $total_today_users) * 100, 1) : 0;

            // --- متغیرهای باکس چهارم: نرخ تبدیل ---
            // تعداد کل کاربران جدید امروز (مبنای نرخ تبدیل)
            $new_users_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND is_returning = 0",
                 
            ));

            // تعداد تبدیل امروز (مثلاً پرداخت موفق یا فرم تکمیل شده)
            $completed_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND event_type = 'form_submit_detailed'",
                 
            ));

            // نرخ تبدیل امروز
            $conversion_rate_today = $new_users_today > 0 ? round(($completed_today / $new_users_today) * 100, 1) : 0;

            // تعداد کل کاربران جدید دیروز
            $yesterday_start = (clone $now)->modify('-1 day')->format('Y-m-d') . ' 00:00:00';
            $yesterday_end = (clone $now)->modify('-1 day')->format('Y-m-d') . ' 23:59:59';
            $new_users_yesterday = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND is_returning = 0",
                 
            ));

            // تعداد تبدیل دیروز
            $completed_yesterday = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name AND event_type = 'form_submit_detailed'",
                 
            ));

            // نرخ تبدیل دیروز
            $conversion_rate_yesterday = $new_users_yesterday > 0 ? round(($completed_yesterday / $new_users_yesterday) * 100, 1) : 0;

            // اختلاف نرخ و تعداد تبدیل نسبت به دیروز
            $conversion_rate_diff = $conversion_rate_today - $conversion_rate_yesterday;
            $conversion_count_diff = $completed_today - $completed_yesterday;



            // بهترین ساعت (بازه ساعتی با بیشترین پرداخت تکمیل شده)
            $best_hour_data = $wpdb->get_row($wpdb->prepare(
                "SELECT FLOOR(HOUR(timestamp)/2)*2 AS hour_start, COUNT(*) as count
                FROM $table_name
                AND event_type = 'form_submit_detailed'
                GROUP BY hour_start
                ORDER BY count DESC
                LIMIT 1",
                 
            ));
            if ($best_hour_data) {
                $start = $best_hour_data->hour_start;
                $end = $start + 2;
                $best_hour_label = sprintf('%02d الی %02d', $start, $end);
                $best_hour_count = intval($best_hour_data->count);
                $best_hour_percent = $completed_today > 0 ? round(($best_hour_count / $completed_today) * 100, 1) : 0;
            } else {
                $best_hour_label = '-';
                $best_hour_count = 0;
                $best_hour_percent = 0;
            }

            // بهترین دستگاه
            // موبایل
            $mobile_conversion_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                AND event_type = 'form_submit_detailed'
                AND user_agent REGEXP 'Mobile|Android|iPhone|iPad'",
                 
            ));
            $mobile_conversion_percent = $completed_today > 0 ? round(($mobile_conversion_count / $completed_today) * 100, 1) : 0;

            // دسکتاپ
            $desktop_conversion_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT session_id) FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                AND event_type = 'form_submit_detailed'
                AND user_agent NOT REGEXP 'Mobile|Android|iPhone|iPad'",
                 
            ));
            $desktop_conversion_percent = $completed_today > 0 ? round(($desktop_conversion_count / $completed_today) * 100, 1) : 0;

            // بهترین منبع ورودی
            $best_referral_data = $wpdb->get_row($wpdb->prepare(
                "SELECT referrer, COUNT(DISTINCT session_id) as count
                FROM $table_name
                AND event_type = 'form_submit_detailed'
                GROUP BY referrer
                ORDER BY count DESC
                LIMIT 1",
                 
            ));
            if ($best_referral_data) {
                $best_referral = $best_referral_data->referrer ?: 'مستقیم';
                $best_referral_count = intval($best_referral_data->count);
                $best_referral_percent = $completed_today > 0 ? round(($best_referral_count / $completed_today) * 100, 1) : 0;
            } else {
                $best_referral = '-';
                $best_referral_count = 0;
                $best_referral_percent = 0;
            }

            $live_sessions = $this->get_live_sessions();
            $online_count = count($live_sessions);

            $today_sessions = $this->get_recent_sessions_24h();
            $today_sessions_count = count($today_sessions);

            $offline_incomplete = Market_Google_User_Tracking::get_incomplete_sessions();
            $incomplete_count = count($offline_incomplete);

            $bot_sessions = $this->get_suspected_bot_sessions();
            $bot_count = count($bot_sessions);

        } catch (Exception $e) {
            echo '<div class="wrap"><h1>ردیابی کاربران</h1>';
            echo '<div class="notice notice-error"><p>خطا: ' . esc_html($e->getMessage()) . '</p></div></div>';
            return;
        }
        ?>
        
        <div class="wrap">
            <div class="tracking-dashboard">
                <!-- هدر کوچک و ساده -->
                <div class="dashboard-header">
                    <h1>ردیابی کاربران</h1>
                    <div class="header-controls">
                        <?php
                        $tehran_tz = new DateTimeZone('Asia/Tehran');
                        $now = new DateTime('now', $tehran_tz);
                        $date_str = $now->format('Y/m/d');
                        $time_str = $now->format('H:i:s');
                        ?>
                        <div class="datetime-display" style="display: flex; align-items: center; gap: 8px;">
                            <span>تاریخ <?php echo date('Y / mm / d - dd'); ?> - ساعت <?php echo date('H:i:s'); ?></span>
                        </div>
                        <div class="live-indicator">
                            <div class="live-dot"></div>
                            <span>آپدیت خودکار</span>
                        </div>
                    </div>
                </div>
                
                <!-- فیلترهای پیشرفته -->
                <?php include(plugin_dir_path(__FILE__) . 'partials/market-google-tracking-filters.html'); ?>
                
                <!-- آمار حرفه‌ای و پر از جزئیات -->
                <div class="professional-stats-grid">
                    <!-- باکس 1: کاربران آنلاین زنده -->
                    <div class="pro-stat-card online-users">
                        <div class="pro-stat-header">
                            <span class="pro-stat-icon">🟢</span>
                            <span class="pro-stat-title">تحلیل کاربران امروز</span>
                        </div>
                        <div class="pro-stat-main">
                            <!-- تعداد کل ورودی‌های امروز -->
                            <div class="pro-main-number"><?php echo intval($total_today_users); ?> نفر</div>
                            <!-- میانگین زمان بازدید سایت -->
                            <div class="pro-main-subtitle">
                                ⏱️ میانگین زمان بازدید سایت: 
                                <?php echo $avg_session_time_today_formatted; ?>
                            </div>
                        </div>
                        <div class="pro-stat-details">
                            <!-- صفحات بازدید شده -->
                            <div class="pro-detail-row">
                                <span>🔄 صفحات بازدید شده:</span>
                                <span><?php echo intval($page_views_today); ?> صفحه</span>
                            </div>
                            <!-- وضعیت فعلی کاربران امروز -->
                            <div class="pro-detail-row">
                                <span>📊 وضعیت فعلی:</span>
                                <?php
                                    $live_sessions = $this->get_live_sessions();
                                    $online_count = count($live_sessions);
                                ?>
                                <span><?php echo intval($online_count); ?> آنلاین (<?php echo $active_percent; ?>%)</span>
                                <span class="subtle-separator">•</span>
                                <span><?php echo intval($offline_count); ?> آفلاین (<?php echo $idle_percent; ?>%)</span>
                            </div>
                            <!-- دستگاه کاربران امروز -->
                            <div class="pro-detail-row">
                                <span>📱 دستگاه:</span>
                                <span><?php echo intval($mobile_count); ?> موبایل (<?php echo $mobile_percent; ?>%)</span>
                                <span class="subtle-separator">•</span>
                                <span><?php echo intval($desktop_count); ?> دسکتاپ (<?php echo $desktop_percent; ?>%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- باکس 2: فعالیت‌های 24 ساعت -->
                    <div class="pro-stat-card activity-24h">
                        <div class="pro-stat-header">
                            <span class="pro-stat-icon">👥</span>
                            <span class="pro-stat-title">لید‌های جدید امروز</span>
                        </div>
                        <div class="pro-stat-main">
                            <!-- آمار کلی: تعداد پرداخت موفق امروز -->
                            <div class="pro-main-number"><?php echo intval($successful_payments); ?> پرداخت</div>
                            <div class="pro-main-subtitle">
                                پرداخت‌های ناموفق: <?php echo intval($failed_payments); ?>
                            </div>
                        </div>
                        <div class="pro-stat-details">
                            <div class="pro-detail-row">
                                <span>✅ تبدیل‌های امروز:</span>
                                <span><?php echo intval($today_conversions); ?> نفر</span>
                            </div>
                            <div class="pro-detail-row">
                                <span>📞 لیدهای جدید:</span>
                                <span><?php echo intval($marketing_leads); ?> نفر</span>
                            </div>
                            <div class="pro-detail-row">
                                <span>⚠️ ناتمام:</span>
                                <span><?php echo intval($incomplete_sessions); ?> نفر</span>
                            </div>
                        </div>
                    </div>

                    <!-- باکس 3: تحلیل کیفیت ترافیک -->
                    <div class="pro-stat-card traffic-quality">
                        <div class="pro-stat-header">
                            <span class="pro-stat-icon">🆕</span>
                            <span class="pro-stat-title">تحلیل ترافیک امروز</span>
                        </div>
                        <div class="pro-stat-main">
                            <div class="pro-main-number"><?php echo number_format($new_users_today); ?> نفر</div>
                            <div class="pro-main-subtitle">
                                🔄 کاربران برگشتی: <?php echo $returning_users_today; ?> نفر (<?php echo $returning_users_percent; ?>%)
                            </div>
                        </div>
                        <div class="pro-stat-details">
                            <div class="pro-detail-row">
                                <span>🚪 نرخ پرش:</span>
                                <span><?php echo $bounce_count; ?> نفر (<?php echo $bounce_percent; ?>%)</span>
                            </div>
                            <div class="pro-detail-row">
                                <span>
                                    <?php if ($growth_count >= 0): ?>
                                        <span style="color:green;">📈</span>
                                    <?php else: ?>
                                        <span style="color:red;">📉</span>
                                    <?php endif; ?>
                                    نرخ رشد نسبت به دیروز:
                                </span>
                                <span>
                                    <?php echo abs($growth_count); ?> نفر (<?php echo abs($growth_percent); ?>%)
                                </span>
                            </div>
                            <div class="pro-detail-row">
                                <span>⭐ ورودی سئو:</span>
                                <span><?php echo $seo_users; ?> نفر (<?php echo $seo_percent; ?>%)</span>
                            </div>
                        </div>
                    </div>

                    <!-- باکس 4: نرخ تبدیل پیشرفته -->
                    <div class="pro-stat-card conversion-advanced">
                    <div class="pro-stat-header">
                        <span class="pro-stat-icon">📈</span>
                        <span class="pro-stat-title">نرخ تبدیل پیشرفته</span>
                    </div>
                    <div class="pro-stat-main">
                        <div class="pro-main-number"><?php echo $conversion_rate_today; ?>% تبدیل</div>
                        <div class="pro-main-subtitle">
                            <?php if ($conversion_rate_diff > 0): ?>
                                <span style="color:green;">📈</span> <?php echo abs($conversion_rate_diff); ?>٪ (<?php echo abs($conversion_count_diff); ?> نفر) بیشتر از دیروز
                            <?php elseif ($conversion_rate_diff < 0): ?>
                                <span style="color:red;">📉</span> <?php echo abs($conversion_rate_diff); ?>٪ (<?php echo abs($conversion_count_diff); ?> نفر) کمتر از دیروز
                            <?php else: ?>
                                بدون تغییر نسبت به دیروز
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pro-stat-details">
                        <div class="pro-detail-row">
                            <span>🕐 بهترین ساعت:</span>
                            <span><?php echo $best_hour; ?> — <?php echo $best_hour_count; ?> پرداخت (<?php echo $best_hour_percent; ?>%)</span>
                        </div>
                        <div class="pro-detail-row">
                            <span>📱 دستگاه:</span>
                            <span><?php echo $mobile_conversion_count; ?> موبایل (<?php echo $mobile_conversion_percent; ?>%)</span>
                            <span class="subtle-separator">•</span>
                            <span><?php echo $desktop_conversion_count; ?> دسکتاپ (<?php echo $desktop_conversion_percent; ?>%)</span>
                        </div>
                        <div class="pro-detail-row">
                            <span>🔗 بهترین منبع ورودی:</span>
                            <span><?php echo $best_referral; ?> (<?php echo $best_referral_count; ?> نفر، <?php echo $best_referral_percent; ?>%)</span>
                        </div>                      
                    </div>
                    </div>
                </div>
                
                <!-- بخش‌های اصلی -->
                <div class="sections-container">
                    <!-- کاربران آنلاین -->
                    <div class="section-card">
                        <?php 
                        $live_sessions = $this->get_live_sessions();
                        $online_count = count($live_sessions);
                        ?>
                        <h2 class="section-title">🟢 کاربران آنلاین (<span class="online-count"><?php echo $online_count; ?></span> نفر)</h2>
                        <div id="online-users-list">
                            <?php echo $this->render_online_users($live_sessions); ?>
                        </div>
                    </div>
                    
                    <!-- خلاصه فعالیت ۲۴ ساعته -->
                    <div class="section-card">
                        <h2 class="section-title">🕒 همه فعالیت‌های امروز (<span class="today-activity-count"><?php echo $today_sessions_count; ?></span> نفر)</h2>
                        <div id="recent-users-list-24h">
                            <?php echo $this->render_online_users($today_sessions, true); ?>
                        </div>
                    </div>
                </div>
                
                <div class="sections-container">                   
                    <!-- بازدیدکنندگان ناتمام -->
                    <div class="section-card">
                        <h2 class="section-title">⚠️ بازدیدکنندگان ناتمام (<?php echo $incomplete_count; ?> نفر)</h2>
                        <div class="scrollable-content">
                            <?php echo $this->render_incomplete_sessions($offline_incomplete); ?>
                        </div>
                    </div>
                    
                    <!-- ربات‌های مشکوک -->
                    <div class="section-card">
                        <h2 class="section-title">🤖 ربات‌های مشکوک (<?php echo $bot_count; ?> مورد)</h2>
                        <div class="scrollable-content">
                            <?php echo $this->render_bot_sessions($bot_sessions); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- مودال نمایش جزئیات کاربر -->
        <div id="user-details-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px; border-radius: 8px;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2>جزئیات کاربر: <?php echo esc_html($fullname); ?></h2>
                    <span class="close" onclick="closeUserModal()" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                </div>
                <div id="modalContent" style="max-height: 500px; overflow-y: auto;">
                    <div style="text-align: center; padding: 20px;">
                        <div class="loading">در حال بارگذاری...</div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function openUserModal(sessionId) {
            document.getElementById('user-details-modal').style.display = 'block';
            document.getElementById('modalContent').innerHTML = '<div style="text-align: center; padding: 20px;"><div class="loading">در حال بارگذاری...</div></div>';
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_session_details',
                    session_id: sessionId,
                    nonce: '<?php echo wp_create_nonce('market_google_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        document.getElementById('modalContent').innerHTML = response.data.html;
                        
                        // Add event listeners for new modal close buttons
                        var closeButtons = document.querySelectorAll('.modal-close');
                        closeButtons.forEach(function(button) {
                            button.addEventListener('click', function() {
                                closeUserModal();
                            });
                        });
                        
                        // Add event listener for overlay click
                        var overlay = document.querySelector('.modal-overlay');
                        if (overlay) {
                            overlay.addEventListener('click', function() {
                                closeUserModal();
                            });
                        }
                    } else {
                        document.getElementById('modalContent').innerHTML = '<div style="text-align: center; padding: 20px; color: red;">خطا در بارگذاری اطلاعات</div>';
                    }
                },
                error: function() {
                    document.getElementById('modalContent').innerHTML = '<div style="text-align: center; padding: 20px; color: red;">خطا در ارتباط با سرور</div>';
                }
            });
        }
        
        function closeUserModal() {
            document.getElementById('user-details-modal').style.display = 'none';
        }

        // بستن مودال با کلیک خارج از آن
        window.onclick = function(event) {
            var modal = document.getElementById('user-details-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
        
        // Handle ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeUserModal();
            }
        });
        </script>
       
        <?php
    }

    //فیلتر‌های صحفه ردیابی کاربران
    public function ajax_tracking_filter() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';

        // ورودی‌ها را از body بخوان (fetch با JSON)
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) $input = $_POST;

        // فیلترها
        $where = ['1=1'];
        $params = [];

        if (!empty($input['visitor_type'])) {
            if ($input['visitor_type'] === 'new') {
                $where[] = "is_returning = 0";
            } elseif ($input['visitor_type'] === 'returning') {
                $where[] = "is_returning = 1";
            }
        }
        if (!empty($input['duration'])) {
            if ($input['duration'] === 'lt1') {
                $where[] = "duration < 60";
            } elseif ($input['duration'] === '1to5') {
                $where[] = "duration >= 60 AND duration < 300";
            } elseif ($input['duration'] === '5to10') {
                $where[] = "duration >= 300 AND duration < 600";
            } elseif ($input['duration'] === 'gt10') {
                $where[] = "duration >= 600";
            }
        }  

        if (!empty($input['date_from'])) {
            $where[] = "timestamp >= %s";
            $params[] = $input['date_from'] . " 00:00:00";
        }
        if (!empty($input['date_to'])) {
            $where[] = "timestamp <= %s";
            $params[] = $input['date_to'] . " 23:59:59";
        }
        if (!empty($input['ip'])) {
            $where[] = "user_ip = %s";
            $params[] = $input['ip'];
        }
        if (!empty($input['location'])) {
            $where[] = "(ip_country LIKE %s OR ip_city LIKE %s OR ip_region LIKE %s)";
            $params[] = '%' . $input['location'] . '%';
            $params[] = '%' . $input['location'] . '%';
            $params[] = '%' . $input['location'] . '%';
        }
        if (!empty($input['referrer'])) {
            $where[] = "referrer LIKE %s";
            $params[] = '%' . $input['referrer'] . '%';
        }
        if (!empty($input['device'])) {
            $where[] = "user_agent LIKE %s";
            $params[] = '%' . $input['device'] . '%';
        }
        if (!empty($input['browser'])) {
            $where[] = "user_agent LIKE %s";
            $params[] = '%' . $input['browser'] . '%';
        }
        if (!empty($input['pages'])) {
            $where[] = "page_url LIKE %s";
            $params[] = '%' . $input['pages'] . '%';
        }
        if (!empty($input['event'])) {
            $where[] = "event_type = %s";
            $params[] = $input['event'];
        }
        if (!empty($input['utm'])) {
            $where[] = "(utm_campaign LIKE %s OR utm_source LIKE %s)";
            $params[] = '%' . $input['utm'] . '%';
            $params[] = '%' . $input['utm'] . '%';
        }
        if (!empty($input['s'])) {
            $where[] = "(element_value LIKE %s OR element_id LIKE %s)";
            $params[] = '%' . $input['s'] . '%';
            $params[] = '%' . $input['s'] . '%';
        }

        // ساخت کوئری
        $where_sql = implode(' AND ', $where);
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE $where_sql ORDER BY timestamp DESC", ...$params);
        $sessions = $wpdb->get_results($query);

        // گروه‌بندی بر اساس session_id
        $grouped = [];
        foreach ($sessions as $row) {
            $grouped[$row->session_id][] = $row;
        }

        // ساخت آرایه session برای توابع رندر
        $session_objs = [];
        foreach ($grouped as $sid => $rows) {
            $last = end($rows);
            $session_objs[] = $last;
        }

        // خروجی HTML هر بخش
        $online_html = $this->render_online_users($session_objs);
        $stats_html = $this->render_stats($session_objs);
        $incomplete_html = $this->render_incomplete_sessions($session_objs);
        $bot_html = $this->render_bot_sessions($session_objs);

        // خروجی JSON
        wp_send_json_success([
            'lists_html' => '
                <div class="sections-container">
                    <div class="section-card"><h2>🟢 کاربران آنلاین</h2><div id="online-users-list">'.$online_html.'</div></div>
                    <div class="section-card"><h2>🔍 فعالیت‌های اخیر</h2><div id="stats-container">'.$stats_html.'</div></div>
                    <div class="section-card"><h2>⚠️ بازدیدکنندگان ناتمام</h2><div class="scrollable-content">'.$incomplete_html.'</div></div>
                    <div class="section-card"><h2>🤖 ربات‌های مشکوک</h2><div class="scrollable-content">'.$bot_html.'</div></div>
                </div>
            ',
        ]);
    }

    

    // نمایش فقط امروز
    private function get_recent_sessions_today() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'market_google_user_tracking';
    $tehran_tz = new \DateTimeZone('Asia/Tehran');
    $now = new \DateTime('now', $tehran_tz);
    $today_start = $now->format('Y-m-d') . ' 00:00:00';
    $current_time = $now->format('Y-m-d H:i:s');

    $query = $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY timestamp DESC",
         
    );
    return $wpdb->get_results($query);
    }
    
    //کاربران آنلاین
    private function render_online_users($sessions, $show_all = false) {
        $html = '';
        $unique = [];
        $filtered_sessions = [];

        foreach ($sessions as $session) {
            $online_status = $this->get_online_status($session->last_activity);
            if (!$show_all && !$online_status['is_online']) continue;
            $filtered_sessions[] = $session;
        }
        if (empty($filtered_sessions)) {
            return '<div class="no-data">🔍 هیچ کاربری یافت نشد</div>';
        }
        foreach ($filtered_sessions as $session) {
        
        // اگر آی‌پی یا user_agent خالی بود، از دیتابیس واکشی کن
        if (empty($session->user_ip) || empty($session->user_agent)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_user_tracking';
            $session_info = $wpdb->get_row($wpdb->prepare(
                "SELECT user_ip, user_agent FROM $table_name WHERE session_id = %s AND user_ip IS NOT NULL AND user_agent IS NOT NULL ORDER BY timestamp DESC LIMIT 1",
                $session->session_id
            ));
            if ($session_info) {
                if (empty($session->user_ip)) {
                    $session->user_ip = $session_info->user_ip;
                }
                if (empty($session->user_agent)) {
                    $session->user_agent = $session_info->user_agent;
                }
            }
        } 
            
            $device_id = $this->get_device_fingerprint_for_session($session);
            $ip = $session->user_ip;
            $unique_key = $device_id . '_' . $ip;
            if (isset($unique[$unique_key])) continue;
            $unique[$unique_key] = true;
            
            $user_display_name = $this->get_enhanced_user_display_name($session->session_id);
            // اجباری کردن نمایش وضعیت آنلاین برای کاربران زنده
            $online_status = array(
                'class' => 'mg-status-online',
                'text'  => 'آنلاین',
                'is_online' => true
            );
            $form_progress = $this->calculate_detailed_form_progress($session->session_id);
            $user_score = $this->calculate_user_score($session->session_id);
            $status_timeline = $this->get_user_status_timeline($session->session_id);

            $current_activity_text = $status_timeline['current'];
            $previous_activity_text = $status_timeline['previous'];

            $html .= '<div class="mg-user-container">';
            $html .= '<div class="mg-user-row">';
            $html .= '<div class="mg-user-column mg-user-info">';
            $html .= '<div class="mg-user-identity">';
            $html .= '<div class="mg-user-avatar">👤</div>';
            $html .= '<div class="mg-user-details">';
            $html .= '<div class="mg-user-name">' . $user_display_name . '</div>';
            $html .= '<div class="mg-user-status ' . $online_status['class'] . '">';
            $html .= '<span class="mg-status-indicator"></span>';
            $html .= $online_status['text'];
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="mg-user-column mg-user-activity">';
            $html .= '<div class="mg-activity-row">';
            $html .= '<div class="mg-activity-previous">';
            $html .= '<span class="mg-activity-label">وضعیت قبلی:</span>';
            $html .= '<span class="mg-activity-value">' . $previous_activity_text . '</span>';
            $html .= '</div>';
            $html .= '<div class="mg-activity-separator">←</div>';
            $html .= '<div class="mg-activity-current">';
            $html .= '<span class="mg-activity-label">وضعیت فعلی:</span>';
            $html .= '<span class="mg-activity-value">' . $current_activity_text . '</span>';
            $html .= '<button class="mg-details-btn" data-tooltip="مشاهده جزئیات کامل" onclick="window.openUserModal(\'' . $session->session_id . '\')">';
            $html .= 'نمایش جزئیات';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="mg-user-column mg-user-progress">';
            $html .= '<div class="mg-progress-container">';
            $html .= '<div class="mg-progress-header">';
            $html .= '<div class="mg-progress-text">' . $form_progress['percentage'] . '% (' . $form_progress['current_step'] . ')</div>';
            $html .= '<div class="mg-progress-label">پیشرفت فرم</div>';
            $html .= '</div>';
            $html .= '<div class="mg-progress-bar">';
            $html .= '<div class="mg-progress-fill" style="width: ' . $form_progress['percentage'] . '%"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="mg-separator"></div>';
            
            $device_model = $this->get_device_model_for_session($session);
            $browser = $this->get_browser_info($session->user_agent);
            $ip = $session->user_ip;
            $location = $this->get_location_info_for_session($session);
            $clicks = $this->get_click_count($session->session_id);
            $exit_point = $this->get_drop_off_point($session->session_id);
            $last_action = $this->get_last_action($session->session_id);
            $current_page = $this->get_current_page($session->session_id);
            $last_activity = $this->time_ago($session->last_activity);
            
            $entry_point = $this->get_user_entry_point($session->session_id);
            $previous_page = $this->get_previous_page($session->session_id);
            $location_only = $this->get_location_only($session->session_id);
            $last_action_detailed = $this->get_last_action_detailed($session->session_id);
            $os_info = $this->get_os_info($session->user_agent);
            
            $score_color = $user_score >= 80 ? '#4CAF50' : ($user_score >= 60 ? '#FF9800' : ($user_score >= 40 ? '#FF5722' : '#F44336'));

            $is_incognito = (stripos($session->user_agent, 'incognito') !== false || stripos($session->user_agent, 'private') !== false || stripos($session->user_agent, 'headless') !== false);

            $html .= '<div class="mg-chip-row">'
                . '<div class="mg-chip" data-tooltip="نقطه شروع">🎯 <span>' . $entry_point . '</span></div>'
                . '<div class="mg-chip" data-tooltip="صفحه فعلی">🗂️ <span>' . $current_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="صفحه قبلی">⬅️ <span>' . $previous_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="مکان کاربر">📍 <span>' . $location_only . '</span></div>'
                . '<div class="mg-chip" data-tooltip="آی‌پی کاربر">🌐 <span>' . $ip . '</span></div>'
                . '<div class="mg-chip" data-tooltip="شناسه یکتا دستگاه">🔑 <span>' . $device_id . '</span></div>'
                . '<div class="mg-chip" data-tooltip="تعداد کلیک">🖱️ <span>' . $clicks . ' عدد</span></div>'
                . ($online_status['class'] === 'offline' ? '<div class="mg-chip" data-tooltip="نقطه خروج">🚪 <span>' . $exit_point . '</span></div>' : '')
                . '<div class="mg-chip" data-tooltip="مرورگر و سیستم عامل"> <span>' . $browser . ' - ' . $os_info . '</span></div>'
                . '<div class="mg-chip" data-tooltip="تاریخ و ساعت ورود">📅 <span>' . $this->get_user_entry_time($session->session_id) . '</span></div>'
                . '<div class="mg-chip mg-chip-score" data-tooltip="امتیاز کاربر (0-100)" style="background-color: ' . $score_color . '; color: white;">⭐ <span>' . $user_score . ' امتیاز</span></div>'
                . ($is_incognito ? '<div class="mg-chip " data-tooltip="مرورگر ناشناس"></div>' : '<div class="mg-chip incognito-chip" data-tooltip="مرورگر ناشناس"><span class="incognito-badge"> مخفی </span></div>')
                .'</div>';
            $html .= '</div>';
        }
        return $html;
    
    }

    //کاربران ناتمام
    private function render_incomplete_sessions($incomplete_sessions) {
        if (empty($incomplete_sessions)) {
            return '<div class="no-data">🎉 همه فرم‌ها تکمیل شده‌اند!</div>';
        }
        $html = '';
        foreach ($incomplete_sessions as $session) {

                // اگر آی‌پی یا user_agent خالی بود، از دیتابیس واکشی کن
            if (empty($session->user_ip) || empty($session->user_agent)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'market_google_user_tracking';
                $session_info = $wpdb->get_row($wpdb->prepare(
                    "SELECT user_ip, user_agent FROM $table_name WHERE session_id = %s AND user_ip IS NOT NULL AND user_agent IS NOT NULL ORDER BY timestamp DESC LIMIT 1",
                    $session->session_id
                ));
                if ($session_info) {
                    if (empty($session->user_ip)) {
                        $session->user_ip = $session_info->user_ip;
                    }
                    if (empty($session->user_agent)) {
                        $session->user_agent = $session_info->user_agent;
                    }
                }
            } 
            // دریافت اطلاعات کاربر
            $user_display_name = $this->get_enhanced_user_display_name($session->session_id);
            $online_status = $this->get_online_status($session->last_activity);
            $form_progress = $this->calculate_detailed_form_progress($session->session_id);
            $drop_point = $this->get_drop_off_point($session->session_id);
            $current_activity = $this->get_current_activity_detailed($session->session_id);
            $previous_activity = $this->get_previous_activity($session->session_id);
            $user_score = $this->calculate_user_score($session->session_id);

            // شروع باکس کاربر
            $html .= '<div class="mg-user-container">';
            // ردیف اصلی با 3 ستون
            $html .= '<div class="mg-user-row">';
            // ستون راست: اطلاعات کاربر
            $html .= '<div class="mg-user-column mg-user-info">';
            $html .= '<div class="mg-user-identity">';
            $html .= '<div class="mg-user-avatar">⚠️</div>';
            $html .= '<div class="mg-user-details">';
            $html .= '<div class="mg-user-name">' . $user_display_name . '</div>';
            $html .= '<div class="mg-user-status ' . $online_status['class'] . '">';
            $html .= '<span class="mg-status-indicator"></span>';
            $html .= $online_status['text'] . ' - ناتمام';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            // ستون وسط: وضعیت فعالیت
            $html .= '<div class="mg-user-column mg-user-activity">';
            $html .= '<div class="mg-activity-row">';
            $html .= '<div class="mg-activity-previous">';
            $html .= '<span class="mg-activity-label">آخرین فعالیت:</span>';
            $html .= '<span class="mg-activity-value">' . $this->time_ago($session->last_activity) . '</span>';
            $html .= '</div>';
            $html .= '<div class="mg-activity-separator">←</div>';
            $html .= '<div class="mg-activity-current">';
            $html .= '<span class="mg-activity-label">متوقف در:</span>';
            $html .= '<span class="mg-activity-value">' . $drop_point . '</span>';
            $html .= '</div>';
            $html .= '<button class="mg-details-btn" data-tooltip="مشاهده جزئیات کامل" onclick="window.openUserModal(\'' . $session->session_id . '\')">';
            $html .= 'نمایش جزئیات';
            $html .= '</button>';
            $html .= '</div>';
            $html .= '</div>';
            // ستون چپ: نوار پیشرفت فرم
            $html .= '<div class="mg-user-column mg-user-progress">';
            $html .= '<div class="mg-progress-container">';
            $html .= '<div class="mg-progress-header">';
            $html .= '<div class="mg-progress-text">' . $form_progress['percentage'] . '% (' . $form_progress['current_step'] . ')</div>';
            $html .= '<div class="mg-progress-label">پیشرفت فرم</div>';
            $html .= '</div>';
            $html .= '<div class="mg-progress-bar">';
            $html .= '<div class="mg-progress-fill" style="width: ' . $form_progress['percentage'] . '%"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>'; // پایان mg-user-row
            // خط جداکننده
            $html .= '<div class="mg-separator"></div>';
            // ردیف چیپ‌ها (مشابه کاربران آنلاین)
            $device_model = $this->get_device_model_for_session($session);
            $browser = $this->get_browser_info($session->user_agent);
            $ip = $session->user_ip;
            $location = $this->get_location_info_for_session($session);
            $clicks = $this->get_click_count($session->session_id);
            $exit_point = $this->get_drop_off_point($session->session_id);
            $last_action = $this->get_last_action($session->session_id);
            $device_id = $this->get_device_fingerprint_for_session($session);
            $current_page = $this->get_current_page($session->session_id);
            
            // دریافت اطلاعات جدید
            $entry_point = $this->get_user_entry_point($session->session_id);
            $previous_page = $this->get_previous_page($session->session_id);
            $location_only = $this->get_location_only($session->session_id);
            $last_action_detailed = $this->get_last_action_detailed($session->session_id);
            $os_info = $this->get_os_info($session->user_agent);
            
            // تعیین رنگ امتیاز برای کاربران ناتمام
            $score_color = $user_score >= 80 ? '#4CAF50' : ($user_score >= 60 ? '#FF9800' : ($user_score >= 40 ? '#FF5722' : '#F44336'));
            
            $is_incognito = (stripos($session->user_agent, 'incognito') !== false || stripos($session->user_agent, 'private') !== false || stripos($session->user_agent, 'headless') !== false);
            $html .= '<div class="mg-chip-row">'
                . '<div class="mg-chip" data-tooltip="نقطه شروع">🎯 <span>' . $entry_point . '</span></div>'
                . '<div class="mg-chip" data-tooltip="صفحه فعلی">🗂️ <span>' . $current_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="صفحه قبلی">⬅️ <span>' . $previous_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="مکان کاربر">📍 <span>' . $location_only . '</span></div>'
                . '<div class="mg-chip" data-tooltip="آی‌پی کاربر">🌐 <span>' . $ip . '</span></div>'
                . '<div class="mg-chip" data-tooltip="شناسه یکتا دستگاه">🔑 <span>' . $device_id . '</span></div>'
                . '<div class="mg-chip" data-tooltip="تعداد کلیک">🖱️ <span>' . $clicks . ' عدد</span></div>'
                . ($online_status['class'] === 'offline' ? '<div class="mg-chip" data-tooltip="نقطه خروج">🚪 <span>' . $exit_point . '</span></div>' : '')
                . '<div class="mg-chip" data-tooltip="مرورگر">🌎 <span>' . $browser . '</span></div>'
                . '<div class="mg-chip" data-tooltip="سیستم عامل">💻 <span>' . $os_info . '</span></div>'
                . '<div class="mg-chip" data-tooltip="تاریخ و ساعت ورود">📅 <span>' . $this->get_user_entry_time($session->session_id) . '</span></div>'
                . '<div class="mg-chip mg-chip-score" data-tooltip="امتیاز کاربر (0-100)" style="background-color: ' . $score_color . '; color: white;">⭐ <span>' . $user_score . ' امتیاز</span></div>'                
                . ($is_incognito ? '<div class="mg-chip" data-tooltip="مرورگر ناشناس"><span class="incognito-badge"> مخفی </span></div>' : '')
                .'</div>';
            $html .= '</div>'; // پایان mg-user-container
        }
        return $html;
    }
    
    // همه فعالیت‌های امروز
    private function render_recent_events($recent_events) {
        if (empty($recent_events)) {
            return '<div class="no-data">📭 هیچ فعالیت اخیری یافت نشد</div>';
        }
        
        $html = '';
        foreach ($recent_events as $event) {

                // اگر آی‌پی یا user_agent خالی بود، از دیتابیس واکشی کن
            if (empty($session->user_ip) || empty($session->user_agent)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'market_google_user_tracking';
                $session_info = $wpdb->get_row($wpdb->prepare(
                    "SELECT user_ip, user_agent FROM $table_name WHERE session_id = %s AND user_ip IS NOT NULL AND user_agent IS NOT NULL ORDER BY timestamp DESC LIMIT 1",
                    $session->session_id
                ));
                if ($session_info) {
                    if (empty($session->user_ip)) {
                        $session->user_ip = $session_info->user_ip;
                    }
                    if (empty($session->user_agent)) {
                        $session->user_agent = $session_info->user_agent;
                    }
                }
            }

            // Get detailed current activity
            $activity_details = $this->get_detailed_activity($event);
            
            $html .= '<div class="event-item-detailed ' . esc_attr($event->event_type) . '">';
            $html .= '<div class="event-header-detailed">';
            $html .= '<div class="event-user-info">';
            $html .= '<span class="session-id">👤 کاربر: ' . $this->get_user_display_name($event->session_id) . '</span>';
            $html .= '<span class="event-time-detailed" style="margin-right: 10px;">' . $this->time_ago($event->timestamp) . '</span>';
            $html .= '</div>';
            $html .= '<div class="current-activity">';
            $html .= '<strong>' . $activity_details['main_action'] . '</strong>';
            $html .= '</div>';
            $html .= '<button class="mg-details-btn" data-tooltip="مشاهده جزئیات کامل" onclick="window.openUserModal(\'' . $session->session_id . '\')">';
            $html .= 'نمایش جزئیات';
            $html .= '</button>';            
            $html .= '</div>';            
            $html .= '<div class="event-details-expanded">';
            $html .= '<div class="activity-row">';
            $html .= '<span class="activity-label">🌍 مکان:</span>';
            $html .= '<span class="activity-value">' . $this->get_location_info($event) . '</span>';
            $html .= '<span class="activity-label" style="margin-right: 15px;">📱 دستگاه:</span>';
            $html .= '<span class="activity-value">' . $this->get_device_model($event) . '</span>';
            $html .= '<span class="activity-label" style="margin-right: 15px;">🔒 شناسه:</span>';
            $html .= '<span class="activity-value">' . $this->get_device_fingerprint($event) . '</span>';
            $html .= '</div>';
            
            if (!empty($activity_details['field_info'])) {
                $html .= '<div class="activity-row">';
                $html .= '<span class="activity-label">📝 فیلد فعال:</span>';
                $html .= '<span class="activity-value">' . $activity_details['field_info'] . '</span>';
                $html .= '</div>';
            }
            
            if (!empty($activity_details['typing_info'])) {
                $html .= '<div class="activity-row">';
                $html .= '<span class="activity-label">⌨️ در حال تایپ:</span>';
                $html .= '<span class="activity-value">' . $activity_details['typing_info'] . '</span>';
                $html .= '</div>';
            }
            
            if (!empty($activity_details['form_progress'])) {
                $html .= '<div class="activity-row">';
                $html .= '<span class="activity-label">📊 پیشرفت فرم:</span>';
                $html .= '<span class="activity-value">';
                $html .= '<div class="progress-bar-mini" style="width: 100px; height: 6px; background: #f0f0f0; border-radius: 3px; display: inline-block; margin-right: 10px;">';
                $html .= '<div style="width: ' . $activity_details['form_progress'] . '%; height: 100%; background: #4CAF50; border-radius: 3px;"></div>';
                $html .= '</div>';
                $html .= $activity_details['form_progress'] . '%';
                $html .= '</span>';
                $html .= '</div>';
            }
            
            // نمایش نقطه خروج
            $exit_point = $this->get_drop_off_point($event->session_id);
            if (!empty($exit_point)) {
                $html .= '<div class="activity-row">';
                $html .= '<span class="activity-label">🚪 نقطه خروج:</span>';
                $html .= '<span class="activity-value">' . $exit_point . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        return $html;
    }        
    
    //ربات‌های شناسایی شده
    private function render_bot_sessions($bot_sessions) {
        if (empty($bot_sessions)) {
            return '<div class="no-data">🛡️ هیچ فعالیت مشکوکی شناسایی نشد!</div>';
        }

        $html = '';
        foreach ($bot_sessions as $bot) {
            // اگر آی‌پی یا user_agent خالی بود، از دیتابیس واکشی کن
            if (empty($bot->user_ip) || empty($bot->user_agent)) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'market_google_user_tracking';
                $session_info = $wpdb->get_row($wpdb->prepare(
                    "SELECT user_ip, user_agent FROM $table_name WHERE session_id = %s AND user_ip IS NOT NULL AND user_agent IS NOT NULL ORDER BY timestamp DESC LIMIT 1",
                    $bot->session_id
                ));
                if ($session_info) {
                    if (empty($bot->user_ip)) {
                        $bot->user_ip = $session_info->user_ip;
                    }
                    if (empty($bot->user_agent)) {
                        $bot->user_agent = $session_info->user_agent;
                    }
                }
            }

            // اطلاعات تکمیلی
            $ip = $bot->user_ip;
            $device_id = $this->get_device_fingerprint_for_session($bot);
            $location_only = $this->get_location_only($bot->session_id);
            $browser = $this->get_browser_info($bot->user_agent);
            $os_info = $this->get_os_info($bot->user_agent);
            $entry_point = $this->get_user_entry_point($bot->session_id);
            $current_page = $this->get_current_page($bot->session_id);
            $previous_page = $this->get_previous_page($bot->session_id);
            $clicks = $this->get_click_count($bot->session_id);
            $exit_point = $this->get_drop_off_point($bot->session_id);
            $user_score = $this->calculate_user_score($bot->session_id);
            $score_color = $user_score >= 80 ? '#4CAF50' : ($user_score >= 60 ? '#FF9800' : ($user_score >= 40 ? '#FF5722' : '#F44336'));
            $is_incognito = (stripos($bot->user_agent, 'incognito') !== false || stripos($bot->user_agent, 'private') !== false || stripos($bot->user_agent, 'headless') !== false);

            // شروع باکس ربات
            $html .= '<div class="mg-user-container">';
            $html .= '<div class="mg-user-row">';
            // ستون راست: اطلاعات ربات
            $html .= '<div class="mg-user-column mg-user-info">';
            $html .= '<div class="mg-user-identity">';
            $html .= '<div class="mg-user-avatar">🤖</div>';
            $html .= '<div class="mg-user-details">';
            $html .= '<div class="mg-user-name">ربات مشکوک</div>';
            $html .= '<div class="mg-user-status mg-status-bot">';
            $html .= '<span class="mg-status-indicator"></span>فعالیت مشکوک';
            $html .= '</div>';
            $html .= '</div></div></div>';
            // ستون وسط: فعالیت
            $html .= '<div class="mg-user-column mg-user-activity">';
            $html .= '<div class="mg-activity-row">';
            $html .= '<div class="mg-activity-previous">';
            $html .= '<span class="mg-activity-label">آخرین فعالیت:</span>';
            $html .= '<span class="mg-activity-value">' . $this->time_ago($bot->last_activity) . '</span>';
            $html .= '</div>';
            $html .= '<div class="mg-activity-separator">←</div>';
            $html .= '<div class="mg-activity-current">';
            $html .= '<span class="mg-activity-label">تعداد رویداد:</span>';
            $html .= '<span class="mg-activity-value">' . (isset($bot->event_count) ? $bot->event_count : '-') . ' عدد</span>';
            $html .= '</div>';
            $html .= '</div></div>';
            // ستون چپ: وضعیت امنیتی
            $html .= '<div class="mg-user-column mg-user-progress">';
            $html .= '<div class="mg-progress-container">';
            $html .= '<div class="mg-progress-header">';
            $html .= '<div class="mg-progress-text">تشخیص شده</div>';
            $html .= '<div class="mg-progress-label">وضعیت امنیتی</div>';
            $html .= '</div>';
            $html .= '</div></div>';
            $html .= '</div>'; // پایان mg-user-row

            // خط جداکننده
            $html .= '<div class="mg-separator"></div>';

            // ردیف چیپ‌ها (استاندارد و یکسان با سایر لیست‌ها)
            $html .= '<div class="mg-chip-row">'
                . '<div class="mg-chip" data-tooltip="صفحه فعلی">🗂️ <span>' . $current_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="صفحه قبلی">⬅️ <span>' . $previous_page . '</span></div>'
                . '<div class="mg-chip" data-tooltip="مکان کاربر">📍 <span>' . $location_only . '</span></div>'
                . '<div class="mg-chip" data-tooltip="آی‌پی کاربر">🌐 <span>' . $ip . '</span></div>'
                . '<div class="mg-chip" data-tooltip="شناسه یکتا دستگاه">🔑 <span>' . $device_id . '</span></div>'
                . '<div class="mg-chip" data-tooltip="Session ID">🆔 <span>' . esc_html(substr($bot->session_id, -8)) . '</span></div>'                
                . '<div class="mg-chip" data-tooltip="تعداد کلیک">🖱️ <span>' . $clicks . ' عدد</span></div>'
                . '<div class="mg-chip" data-tooltip="نقطه خروج">🚪 <span>' . $exit_point . '</span></div>'
                . ($is_incognito
                    ? '<div class="mg-chip incognito-chip" data-tooltip="مرورگر ناشناس"><span class="incognito-badge"> مخفی </span></div>'
                    : '<div class="mg-chip" data-tooltip="مرورگر و سیستم عامل">🌎 <span>' . $browser . ' - ' . $os_info . '</span></div>')
                . '<div class="mg-chip" data-tooltip="تاریخ و ساعت ورود">📅 <span>' . $this->get_user_entry_time($bot->session_id) . '</span></div>'
                . '</div>';

            $html .= '</div>'; // پایان mg-user-container
        }
        return $html;
    }
    
    //ردیابی کاربران
    public function refresh_tracking_stats() {
        // امنیت: بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        // امنیت: بررسی nonce
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        try {
            $stats = Market_Google_User_Tracking::get_tracking_stats();
            $tehran_tz = new \DateTimeZone('Asia/Tehran');
            $now = new \DateTime('now', $tehran_tz);
            $live_sessions = $this->get_live_sessions();
            $recent_events = $this->get_recent_events();
            $raw_incomplete_sessions = Market_Google_User_Tracking::get_incomplete_sessions(20);
            $bot_sessions = $this->get_suspected_bot_sessions();
            $advanced_stats = $this->get_advanced_dashboard_stats();
            
            // فیلتر رویدادهای فعال برای عدم نمایش در «فعالیت‌های امروز»
            $recent_events = array_filter($recent_events, function($e) use ($live_sessions) {
                return ! in_array($e->session_id, wp_list_pluck($live_sessions, 'session_id'));
            });
            // فیلتر کردن کاربران غیرآنلاین پس از 15 دقیقه
            $live_session_ids = wp_list_pluck($live_sessions, 'session_id');
            $inactive_sessions = array_filter($raw_incomplete_sessions, function($s) use ($live_session_ids) {
                return !in_array($s->session_id, $live_session_ids);
            });
            // تفکیک به لیدهای مارکتینگ و کاربران ناتمام
            $marketing_leads = array_filter($inactive_sessions, function($s) {
                return $this->user_has_phone_number($s->session_id);
            });
            $offline_incomplete = array_filter($inactive_sessions, function($s) {
                return !$this->user_has_phone_number($s->session_id);
            });
            
            // محاسبه آمار دقیق
        $online_sessions = array_filter($live_sessions, function($session) {
            $status = $this->get_online_status($session->last_activity);
            return $status['is_online'];
        });
        $online_count = count($online_sessions);
            $today_activity_count = count($recent_events);
            $incomplete_count = count($offline_incomplete);
            $total_users = $advanced_stats['total_users'];
            
            $conversion_rate = $stats['total_sessions'] > 0 ? 
                round(($stats['completed_sessions'] / $stats['total_sessions']) * 100, 1) : 0;
            
            // ساخت تاریخ شمسی فقط با jdate
            $jdate_str = jdate('Y/m/d', $now->getTimestamp());
            $current_time = $now->format('H:i:s');
            $datetime = "تاریخ $jdate_str - ساعت $current_time";

            $response_data = array(
                'online_count' => $online_count,
                'today_activity_count' => $today_activity_count,
                'incomplete_count' => $incomplete_count,
                'total_users' => $total_users,
                'total_sessions' => $stats['total_sessions'],
                'datetime' => $datetime,
                'completed_sessions' => $stats['completed_sessions'],
                'conversion_rate' => $conversion_rate,
                'advanced_stats' => $advanced_stats,
                'online_users_html' => $this->render_online_users($live_sessions),
                'recent_events_html' => $this->render_recent_events($recent_events),
                'incomplete_sessions_html' => $this->render_incomplete_sessions($offline_incomplete),
                'bot_sessions_html' => $this->render_bot_sessions($bot_sessions),
                'datetime' => $datetime
            );
            
            wp_send_json_success($response_data);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'خطا در بارگذاری داده‌ها: ' . esc_html($e->getMessage())));
        }
    }
    
    //اقدامات زنده در سایت
    private function get_live_sessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $tehran_tz = new \DateTimeZone('Asia/Tehran');
        $now = new \DateTime('now', $tehran_tz);
        $current_time = $now->format('Y-m-d H:i:s');
        $today_start = (clone $now)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $last_15_min = (clone $now)->modify('-15 minutes')->format('Y-m-d H:i:s');

        // فقط آخرین session هر device_fingerprint که در ۱۵ دقیقه اخیر فعال بوده
        $query = $wpdb->prepare("
            SELECT t1.*
            FROM $table_name t1
            INNER JOIN (
                SELECT device_fingerprint, MAX(timestamp) as max_time
                FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                GROUP BY device_fingerprint
            ) t2 ON t1.device_fingerprint = t2.device_fingerprint AND t1.timestamp = t2.max_time
            WHERE t1.timestamp >= %s AND t1.timestamp <= %s
            AND t1.timestamp >= %s
            GROUP BY t1.device_fingerprint
            ORDER BY t1.timestamp DESC
        ", $last_15_min);

        $sessions = $wpdb->get_results($query);
        return $sessions ? array_values($sessions) : array();
    }
    
    private function get_recent_events() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        if (!$this->table_exists($table_name)) {
            return array();
        }
        
        try {
            // تنظیم timezone تهران
            date_default_timezone_set('Asia/Tehran');
            
            // محاسبه شروع روز جاری (00:00:00)
            $today_start = date('Y-m-d 00:00:00');
            $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
            
            // امنیت: استفاده از prepared statement
            $query = $wpdb->prepare("
                SELECT 
                    t1.session_id, 
                    t1.event_type, 
                    t1.element_id, 
                    t1.user_ip, 
                    t1.timestamp,
                    CASE WHEN t2.has_phone = 1 THEN 'marketing_lead'
                         ELSE 'incomplete'
                    END as user_type
                FROM {$table_name} t1
                LEFT JOIN (
                    SELECT 
                        session_id,
                        CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END as has_phone
                    FROM {$table_name}
                    WHERE element_id = 'phone'
                    AND element_value IS NOT NULL
                    AND element_value != ''
                    GROUP BY session_id
                ) t2 ON t1.session_id = t2.session_id
                WHERE t1.timestamp >= %s AND t1.timestamp <= %s
                AND t1.timestamp < %s
                ORDER BY t1.timestamp DESC
            ", $today_start, $tomorrow_start);
            
            $results = $wpdb->get_results($query);
            return $results ? $results : array();
            
        } catch (Exception $e) {
            error_log('Market Google Tracking: Error getting recent events: ' . $e->getMessage());
            return array();
        }
    }
    
    //محاسبه درصد پیشرفت فرم
    private function calculate_form_progress($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        try {
            $fields = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT element_id FROM $table_name WHERE session_id = %s AND event_type = 'field_input' AND element_id IS NOT NULL", $session_id));
            $all_fields = array('full_name','phone','business_name','business_phone','province','city','address','website');
            $filled = 0;
            foreach ($all_fields as $f) {
                foreach ($fields as $ef) {
                    if (stripos($ef, $f) !== false) { $filled++; break; }
                }
            }
            $progress = $filled > 0 ? min(100, ($filled / count($all_fields)) * 100) : 0;
            return round($progress);
        } catch (Exception $e) { return 0; }
    }
    
    // محاسبه زمان گذشته
    private function time_ago($datetime) {
        // تنظیم timezone ایران
        $tehran_tz = new DateTimeZone('Asia/Tehran');
        $now = new DateTime('now', $tehran_tz);
        $event_time = new DateTime($datetime, $tehran_tz);
        
        $diff = $now->diff($event_time);
        $total_seconds = $now->getTimestamp() - $event_time->getTimestamp();
        
        if ($total_seconds < 0) {
            return 'همین الان';
        }
        
        $days = $diff->days;
        $hours = $diff->h;
        $minutes = $diff->i;
        $seconds = $diff->s;
        
        $parts = array();
        
        if ($days > 0) {
            $parts[] = $days . ' روز';
        }
        if ($hours > 0) {
            $parts[] = $hours . ' ساعت';
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' دقیقه';
        }
        if ($seconds > 0 || empty($parts)) {
            $parts[] = $seconds . ' ثانیه';
        }
        
        return implode(' و ', $parts) . ' پیش';
    }
    
    // لیست اقدامات انجام شده
    private function get_event_label($event_type) {
        $labels = array(
            'page_load' => '📄 بارگذاری صفحه',
            'field_focus' => '👁️ فوکوس فیلد',
            'field_input' => '✏️ ورود داده',
            'field_blur' => '👋 خروج از فیلد',
            'form_submit' => '📤 ارسال فرم',
            'heartbeat' => '💓 آنلاین',
            'heartbeat_detailed' => 'بررسی وضعیت آنلاین',
            'activity_check' => '🔄 بررسی فعالیت',
            'page_exit' => '🚪 خروج از صفحه',
            'click' => 'کلیک روی فرم',
        );
        return isset($labels[$event_type]) ? $labels[$event_type] : 'نامشخص';
    }
    
    private function get_device_info($user_agent) {
        if (strpos($user_agent, 'Mobile') !== false) return '📱 موبایل';
        if (strpos($user_agent, 'Tablet') !== false) return '📺 تبلت';
        return '💻 دسکتاپ';
    }
    
    private function table_exists($table_name) {
        global $wpdb;
        $query = $wpdb->prepare("SHOW TABLES LIKE %s", $table_name);
        return $wpdb->get_var($query) === $table_name;
    }  

    private function get_suspected_bot_sessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';

        if (!$this->table_exists($table_name)) {
            return array();
        }

        try {
            // فقط ربات‌های امروز (از ۰۰:۰۰ تا الان)
            $today_start = date('Y-m-d 00:00:00');
            $tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));

            $query = $wpdb->prepare("
                SELECT session_id, user_ip, user_agent, 
                    COUNT(*) as event_count,
                    MAX(timestamp) as last_activity
                FROM {$table_name} 
                WHERE timestamp >= %s 
                AND timestamp < %s
                AND (
                    user_agent LIKE %s OR user_agent LIKE %s OR user_agent LIKE %s OR
                    user_agent LIKE %s OR user_agent LIKE %s OR user_agent LIKE %s OR
                    user_agent LIKE %s OR user_agent LIKE %s OR user_agent LIKE %s OR
                    user_agent = '' OR user_agent IS NULL OR
                    LENGTH(user_agent) < 20 OR
                    user_ip LIKE '10.%' OR user_ip LIKE '192.168.%' OR user_ip LIKE '172.%' OR
                    user_ip IN ('127.0.0.1', '0.0.0.0', '::1')
                )
                GROUP BY session_id, user_ip 
                ORDER BY last_activity DESC
            ", 
            $today_start, $tomorrow_start,
            '%bot%', '%crawler%', '%spider%',
            '%scraper%', '%curl%', '%wget%',
            '%python%', '%headless%', '%phantom%'
            );

            $results = $wpdb->get_results($query);
            return $results ? $results : array();

        } catch (Exception $e) {
            error_log('Market Google Tracking: Error getting bot sessions: ' . $e->getMessage());
            return array();
        }
    }
    
    private function get_last_action($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $last_event = $wpdb->get_row($wpdb->prepare("
                SELECT event_type, element_id 
                FROM $table_name 
                WHERE session_id = %s 
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if ($last_event) {
                $label = $this->get_event_label($last_event->event_type);
                if (!empty($last_event->element_id)) {
                    return $label . ': ' . $last_event->element_id;
                }
                return $label;
            }
            return 'هیچ فعالیتی ثبت نشده';
        } catch (Exception $e) {
            return 'خطا در دریافت';
        }
    }
    
    private function get_current_page($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $page_url = $wpdb->get_var($wpdb->prepare("
                SELECT page_url 
                FROM $table_name 
                WHERE session_id = %s 
                AND page_url IS NOT NULL 
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if ($page_url) {
                $parsed = parse_url($page_url);
                $path = isset($parsed['path']) ? $parsed['path'] : '/';
                if ($path === '/') return 'صفحه اصلی';
                if (strpos($path, 'form') !== false || strpos($path, 'register') !== false) return 'فرم ثبت نام';
                return basename($path);
            }
            return 'نامشخص';
        } catch (Exception $e) {
            return 'خطا';
        }
    }
    
    private function get_session_summary($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $events = $wpdb->get_results($wpdb->prepare("
                SELECT event_type, COUNT(*) as count 
                FROM $table_name 
                WHERE session_id = %s 
                GROUP BY event_type
            ", $session_id));
            
            if (!$events) {
                return 'هیچ فعالیتی ثبت نشده';
            }
            
            $summary = array();
            foreach ($events as $event) {
                $label = $this->get_event_label($event->event_type);
                $summary[] = $label . ' (' . $event->count . ')';
            }
            
            return implode(' | ', $summary);
        } catch (Exception $e) {
            return 'خطا در دریافت خلاصه';
        }
    }
    
    private function get_drop_off_point($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت آخرین فیلدی که کاربر روی آن کار کرده
            $last_field = $wpdb->get_var($wpdb->prepare("
                SELECT element_id 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type IN ('field_focus', 'field_input', 'field_blur')
                AND element_id IS NOT NULL 
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if ($last_field) {
                return $this->get_field_persian_name($last_field);
            }
            
            // اگر هیچ فیلدی یافت نشد، چک کنیم که آیا اصلاً page_load داشته یا نه
            $has_page_load = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type = 'page_load'
            ", $session_id));
            
            if ($has_page_load > 0) {
                return 'صفحه فرم (بدون تعامل)';
            }
            
            return 'قبل از ورود به فرم';
        } catch (Exception $e) {
            return 'خطا در تشخیص';
        }
    }
    
    /**
     * Get detailed current activity for a user session
     */
    private function get_detailed_activity($event) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        $activity = array(
            'main_action' => 'فعالیت نامشخص',
            'location' => 'نامشخص',
            'field_info' => '',
            'typing_info' => '',
            'form_progress' => 0
        );
        
        try {
            // Get current page/location
            $current_page = $this->get_current_page($event->session_id);
            $activity['location'] = $current_page;
            
            // Get form progress
            $progress = $this->calculate_form_progress($event->session_id);
            $activity['form_progress'] = $progress;
            
            // Analyze event type for main action
            switch ($event->event_type) {
                case 'field_focus':
                    $activity['main_action'] = '👁️ در حال فوکوس روی فیلد';
                    $activity['field_info'] = $this->get_field_persian_name($event->element_id);
                    break;
                    
                case 'field_input':
                case 'keystroke':
                    $activity['main_action'] = '⌨️ در حال تایپ کردن';
                    $activity['field_info'] = $this->get_field_persian_name($event->element_id);
                    $activity['typing_info'] = $this->get_current_typing_info($event);
                    break;
                    
                case 'field_blur':
                    $activity['main_action'] = '✅ تکمیل فیلد';
                    $activity['field_info'] = $this->get_field_persian_name($event->element_id);
                    break;
                    
                case 'mouse_move':
                    $activity['main_action'] = '🖱️ حرکت موس روی صفحه';
                    break;
                    
                case 'scroll':
                    $activity['main_action'] = '📜 اسکرول صفحه';
                    break;
                    
                case 'page_load':
                    $activity['main_action'] = '📄 بارگذاری صفحه';
                    break;
                    
                case 'heartbeat':
                    // Get last meaningful activity
                    $last_activity = $this->get_last_meaningful_activity($event->session_id);
                    if ($last_activity) {
                        $activity['main_action'] = '🟢 آنلاین (' . $last_activity['action'] . ')';
                        $activity['field_info'] = $last_activity['field'] ?? '';
                    } else {
                        $activity['main_action'] = '🟢 آنلاین (در حال مشاهده صفحه)';
                    }
                    break;
                    
                case 'form_submit':
                    $activity['main_action'] = '📤 ارسال فرم';
                    break;
                    
                default:
                    $activity['main_action'] = $this->get_event_label($event->event_type);
            }
            
        } catch (Exception $e) {
            $activity['main_activity'] = 'خطا در دریافت فعالیت';
        }
        
        return $activity;
    }
    
    /**
     * Get Persian name for form fields
     */
    private function get_field_persian_name($element_id) {
        if (empty($element_id)) return '';
        
        $field_names = array(
            'full_name' => 'نام کامل',
            'phone' => 'شماره موبایل',
            'business_name' => 'نام کسب و کار',
            'business_phone' => 'تلفن کسب و کار',
            'province' => 'استان',
            'city' => 'شهر',
            'address' => 'آدرس',
            'manual_address' => 'آدرس دقیق',
            'website' => 'وب سایت',
            'package' => 'انتخاب بسته',
            'payment' => 'پرداخت',
            'latitude' => 'مختصات عرض جغرافیایی',
            'longitude' => 'مختصات طول جغرافیایی',
            'lat' => 'مختصات عرض',
            'lng' => 'مختصات طول',
            'location' => 'انتخاب موقعیت',
            'map_click' => 'کلیک روی نقشه',
            'working_hours' => 'ساعت کاری',
            'working_hours_text' => 'ساعت کاری',
            'selected_packages' => 'انتخاب محصولات',
            'terms' => 'پذیرش قوانین'
        );
        
        // Check exact match first
        if (isset($field_names[$element_id])) {
            return $field_names[$element_id];
        }
        
        // Check partial matches
        foreach ($field_names as $key => $persian_name) {
            if (strpos($element_id, $key) !== false) {
                return $persian_name;
            }
        }
        
        return $element_id; // Return original if no match
    }
    
    /**
     * Get current typing information
     */
    private function get_current_typing_info($event) {
        if (empty($event->element_value)) {
            return 'شروع تایپ...';
        }
        
        $value_length = strlen($event->element_value);
        if ($value_length < 3) {
            return 'چند کاراکتر اول...';
        } elseif ($value_length < 10) {
            return $value_length . ' کاراکتر تایپ شده';
        } else {
            return $value_length . ' کاراکتر - در حال تکمیل';
        }
    }
    
    /**
     * Get last meaningful activity for heartbeat events
     */
    private function get_last_meaningful_activity($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $last_event = $wpdb->get_row($wpdb->prepare("
                SELECT event_type, element_id, element_value 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type NOT IN ('heartbeat', 'activity_check')
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if (!$last_event) {
                return null;
            }
            
            $activity = array();
            
            switch ($last_event->event_type) {
                case 'field_focus':
                    $activity['action'] = 'فوکوس روی فیلد';
                    $activity['field'] = $this->get_field_persian_name($last_event->element_id);
                    break;
                case 'field_input':
                    $activity['action'] = 'تایپ در فیلد';
                    $activity['field'] = $this->get_field_persian_name($last_event->element_id);
                    break;
                case 'field_blur':
                    $activity['action'] = 'تکمیل فیلد';
                    $activity['field'] = $this->get_field_persian_name($last_event->element_id);
                    break;
                default:
                    $activity['action'] = 'مشاهده صفحه';
            }
            
            return $activity;
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function get_current_user_activity($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $activity = array('main_activity' => 'در حال مشاهده صفحه','field_activity' => '','field_activity_value' => '');
        try {
            $recent_events = $wpdb->get_results($wpdb->prepare("SELECT event_type, element_id, element_value, timestamp FROM $table_name WHERE session_id = %s ORDER BY timestamp DESC LIMIT 3", $session_id));
            if (empty($recent_events)) return $activity;
            $latest_event = $recent_events[0];
            $tehran_tz = new DateTimeZone('Asia/Tehran');
            $now = new DateTime('now', $tehran_tz);
            $event_time = new DateTime($latest_event->timestamp, $tehran_tz);
            $time_since_last = $now->getTimestamp() - $event_time->getTimestamp();
            if ($time_since_last > 30) {
                $activity['main_activity'] = '😴 غیرفعال (' . $this->time_ago($latest_event->timestamp) . ')';
                return $activity;
            }
            switch ($latest_event->event_type) {
                case 'field_focus':
                    $activity['main_activity'] = '👁️ فوکوس روی فیلد';
                    $activity['field_activity'] = $this->get_field_persian_name($latest_event->element_id);
                    break;
                case 'field_input':
                case 'keystroke':
                    $activity['main_activity'] = '⌨️ در حال تایپ';
                    $activity['field_activity'] = $this->get_field_persian_name($latest_event->element_id);
                    $activity['field_activity_value'] = $latest_event->element_value;
                    break;
                case 'field_blur':
                    $activity['main_activity'] = '✅ تکمیل فیلد';
                    $activity['field_activity'] = $this->get_field_persian_name($latest_event->element_id);
                    $activity['field_activity_value'] = $latest_event->element_value;
                    break;
                case 'mouse_move':
                    if (count($recent_events) > 1 && $recent_events[1]->event_type === 'field_focus') {
                        $activity['main_activity'] = '🖱️ حرکت در فیلد';
                        $activity['field_activity'] = $this->get_field_persian_name($recent_events[1]->element_id);
                    } else {
                        $activity['main_activity'] = '🖱️ حرکت موس روی صفحه';
                    }
                    break;
                case 'scroll':
                    $activity['main_activity'] = '📜 اسکرول صفحه';
                    break;
                case 'page_load':
                    $activity['main_activity'] = '📄 بارگذاری صفحه جدید';
                    break;
                case 'heartbeat':
                case 'heartbeat_detailed':
                    foreach ($recent_events as $event) {
                        if ($event->event_type !== 'heartbeat' && $event->event_type !== 'heartbeat_detailed') {
                            $meaningful_activity = $this->get_last_meaningful_activity($session_id);
                            if ($meaningful_activity) {
                                $activity['main_activity'] = '🟢 آنلاین (' . $meaningful_activity['action'] . ')';
                                $activity['field_activity'] = $meaningful_activity['field'] ?? '';
                            }
                            break;
                        }
                    }
                    break;
                default:
                    $activity['main_activity'] = '🔍 ' . $this->get_event_label($latest_event->event_type);
            }
        } catch (Exception $e) {
            $activity['main_activity'] = 'خطا در دریافت فعالیت';
        }
        return $activity;
    }

    /**
     * Display real user data for marketing and UX analysis
     */
    public static function display_real_user_analytics() {
        global $wpdb;

        $tracking_table = $wpdb->prefix . 'market_google_user_tracking';

        // تنظیم تایم‌زون تهران
        $tehran_tz = new DateTimeZone('Asia/Tehran');
        $now = new DateTime('now', $tehran_tz);
        $today_start = $now->format('Y-m-d') . ' 00:00:00';
        $current_time = $now->format('Y-m-d H:i:s');

        // چک وجود جدول
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$tracking_table}'");
        if (!$table_exists) {
            echo '<div class="wrap">';
            echo '<h1>📊 تحلیل کامل کاربران و UX فرم</h1>';
            echo '<div class="notice notice-error">';
            echo '<p><strong>خطا:</strong> جدول ردیابی وجود ندارد. لطفاً ابتدا جدول را ایجاد کنید:</p>';
            echo '<p><a href="' . admin_url('?debug_tracking=1&reset_table=1') . '" class="button button-primary">ایجاد جدول جدید</a></p>';
            echo '</div>';
            echo '</div>';
            return;
        }

        // پیام موفقیت در صورت ریست جدول
        if (isset($_GET['table_reset']) && $_GET['table_reset'] === 'success') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>موفق:</strong> جدول ردیابی با موفقیت ایجاد شد!</p>';
            echo '</div>';
        }

        // --- آمار امروز ---
        // تعداد کل ورودی‌های امروز (session_id یکتا)
        $total_today_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
             
        ));

        // میانگین زمان بازدید امروز (فقط سشن‌هایی که امروز شروع شده‌اند)
        $avg_session_time_today = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp)) as session_duration
                FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                GROUP BY session_id
            ) as durations",
             
        ));
        $avg_session_time_today = $avg_session_time_today ? intval($avg_session_time_today) : 0;
        $avg_session_time_today_formatted = sprintf('%02d:%02d:%02d', floor($avg_session_time_today/3600), floor(($avg_session_time_today%3600)/60), $avg_session_time_today%60);

        // کاربران کامل و ناتمام امروز
        $completed_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $tracking_table AND event_type = 'form_submit_detailed'",
             
        ));
        $incomplete_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT t1.session_id) FROM $tracking_table t1
            LEFT JOIN $tracking_table t2 ON t1.session_id = t2.session_id AND t2.event_type = 'form_submit_detailed'
            WHERE t1.timestamp >= %s AND t1.timestamp <= %s AND t2.session_id IS NULL",
             
        ));

        // دریافت داده‌های تکمیلی برای نمایش جدول‌ها و تحلیل‌ها
        try {
            $user_sessions = self::get_complete_user_sessions($tracking_table);
            $form_abandonment_analysis = self::get_form_abandonment_analysis($tracking_table);
            $user_journey_data = self::get_user_journey_data($tracking_table);
        } catch (Exception $e) {
            echo '<div class="wrap">';
            echo '<h1>📊 تحلیل کامل کاربران و UX فرم</h1>';
            echo '<div class="notice notice-error">';
            echo '<p><strong>خطا در بارگذاری داده‌ها:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><a href="' . admin_url('?debug_tracking=1') . '" class="button">بررسی مشکل</a></p>';
            echo '</div>';
            echo '</div>';
            
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('📊 تحلیل کامل کاربران و UX فرم', 'market-google'); ?></h1>
            
            <!-- آمار کلی در باکس‌های جذاب -->
            <div class="stats-overview">
                <div class="stats-boxes">
                    <div class="stat-card total-visitors">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stats-title">تعداد کل ورودی‌های امروز</div>
                            <div class="stats-value" style="font-size:2.5em;font-weight:bold;"><?php echo intval($total_today_users); ?></div>
                            <div class="stats-detail">تعداد session_id یکتا از ساعت ۰۰:۰۰ تا الان</div>
                            <div class="stat-number"><?php echo count($user_sessions['completed']) + count($user_sessions['incomplete']); ?></div>
                            <div class="stat-label">کل بازدیدکنندگان</div>
                            </div>
                        </div>
                    
                    <div class="stat-card completed-forms">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo count($user_sessions['completed']); ?></div>
                            <div class="stat-label">فرم تکمیل شده</div>
                        </div>
                    </div>
                    
                    <div class="stat-card incomplete-forms">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php echo count($user_sessions['incomplete']); ?></div>
                            <div class="stat-label">فرم ناتمام</div>
                        </div>
                    </div>
                    
                    <div class="stat-card conversion-rate">
                        <div class="stat-icon">📈</div>
                        <div class="stat-content">
                            <div class="stat-number"><?php 
                                $total = count($user_sessions['completed']) + count($user_sessions['incomplete']);
                                $rate = $total > 0 ? round((count($user_sessions['completed']) / $total) * 100, 1) : 0;
                                echo $rate . '%';
                            ?></div>
                            <div class="stat-label">نرخ تبدیل</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- تحلیل دقیق نقاط خروج -->
            <div class="exit-analysis-section">
                <h2>🔍 تحلیل دقیق نقاط خروج از فرم</h2>
                <div class="exit-analysis-container">
                    <div class="exit-stats-grid">
                        <?php 
                        $exit_data = self::get_detailed_exit_analysis($tracking_table);
                        foreach ($exit_data['steps'] as $step => $data): 
                        ?>
                        <div class="exit-stat-card">
                            <div class="exit-stat-header">
                                <span class="exit-step-name"><?php echo esc_html($data['label']); ?></span>
                                <span class="exit-count"><?php echo $data['total_exits']; ?></span>
                            </div>
                            <div class="exit-breakdown">
                                <div class="exit-before">
                                    <span class="exit-label">قبل از تکمیل:</span>
                                    <span class="exit-number before"><?php echo $data['before_completion']; ?></span>
                                </div>
                                <div class="exit-after">
                                    <span class="exit-label">پس از تکمیل:</span>
                                    <span class="exit-number after"><?php echo $data['after_completion']; ?></span>
                                </div>
                            </div>
                            <div class="exit-percentage">
                                <?php echo round(($data['total_exits'] / max($exit_data['total_exits'], 1)) * 100, 1); ?>% از کل خروجی‌ها
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="exit-chart-container">
                        <h3>نمودار نقاط خروج</h3>
                        <canvas id="exitPointsChart" width="400" height="400"></canvas>
                        <div class="chart-legend" id="chartLegend"></div>
                    </div>
                </div>
            </div>
            
            <div class="real-data-dashboard">
                
                <!-- کاربران کامل (با اطلاعات واقعی) -->
                <div class="analytics-section">
                    <h2><?php _e('✅ کاربران تکمیل‌کننده فرم', 'market-google'); ?></h2>
                    <div class="completed-users-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>نام کامل</th>
                                    <th>شماره موبایل</th>
                                    <th>نام کسب و کار</th>
                                    <th>شهر</th>
                                    <th>زمان تکمیل</th>
                                    <th>تاریخ</th>
                                    <th>جزئیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo self::render_completed_users($user_sessions['completed']); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- کاربران ناتمام (با اطلاعات جزئی) -->
                <div class="analytics-section">
                    <h2><?php _e('⚠️ کاربران ناتمام - تحلیل دقیق خروج', 'market-google'); ?></h2>
                    <div class="incomplete-users-table">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>نام کامل</th>
                                    <th>شماره موبایل</th>
                                    <th>نقطه شروع</th>
                                    <th>نقطه خروج</th>
                                    <th>وضعیت خروج</th>
                                    <th>درصد تکمیل</th>
                                    <th>زمان صرف شده</th>
                                    <th>تاریخ</th>
                                    <th>اقدام</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php echo self::render_incomplete_users_detailed($user_sessions['incomplete']); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- لیدهای مارکتینگ - فقط کاربران با اطلاعات تماس -->
                <div class="analytics-section">
                    <h2><?php _e('📞 لیدهای مارکتینگ (فقط کاربران با شماره موبایل)', 'market-google'); ?></h2>
                    <div class="marketing-explanation">
                        <p><strong>تفاوت با فرم ناتمام:</strong> این بخش فقط کاربرانی را نشان می‌دهد که شماره موبایل خود را وارد کرده‌اند و قابل پیگیری هستند.</p>
                    </div>
                    <div class="marketing-data">
                        <div class="marketing-leads">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>نام کامل</th>
                                        <th>شماره موبایل</th>
                                        <th>نقطه شروع</th>
                                        <th>نقطه خروج</th>
                                        <th>علاقه‌مندی</th>
                                        <th>وضعیت</th>
                                        <th>اولویت پیگیری</th>
                                        <th>اقدام</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php echo self::render_marketing_leads_detailed($user_sessions); ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Export داده‌ها -->
                <div class="analytics-section">
                    <h2><?php _e('📤 دریافت داده‌ها', 'market-google'); ?></h2>
                    <div class="export-options">
                        <button class="button button-primary" onclick="exportUserData('completed')">
                            دریافت لیست کاربران تکمیل‌کننده
                        </button>
                        <button class="button button-primary" onclick="exportUserData('incomplete')">
                            دریافت لیست کاربران ناتمام
                        </button>
                        <button class="button button-primary" onclick="exportUserData('marketing')">
                            دریافت لیدهای مارکتینگ
                        </button>
                    </div>
                </div>
            </div>
        </div>               
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        function exportUserData(type) {
            window.location.href = ajaxurl + '?action=export_user_data&type=' + type + '&security=<?php echo wp_create_nonce("export_user_nonce"); ?>';
        }
        
        // نمودار دایره‌ای نقاط خروج
        jQuery(document).ready(function($) {
            const exitData = <?php echo json_encode($exit_data); ?>;
            
            if (exitData && exitData.steps) {
                const labels = [];
                const data = [];
                const colors = [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF',
                    '#4BC0C0', '#FF6384', '#36A2EB'
                ];
                
                Object.keys(exitData.steps).forEach((step, index) => {
                    const stepData = exitData.steps[step];
                    labels.push(stepData.label);
                    data.push(stepData.total_exits);
                });
                
                const ctx = document.getElementById('exitPointsChart');
                if (ctx && data.length > 0) {
                    new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: data,
                                backgroundColor: colors.slice(0, data.length),
                                borderWidth: 2,
                                borderColor: '#fff'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 20
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const percentage = ((context.parsed / exitData.total_exits) * 100).toFixed(1);
                                            return context.label + ': ' + context.parsed + ' نفر (' + percentage + '%)';
                                        }
                                    }
                                }
                            }
                        });
                }
            }
        });
        </script>
        <?php
    }
    
    /**
     * Get complete user sessions with real data
     */
    private static function get_complete_user_sessions($table_name) {
        global $wpdb;
        
        // Get completed sessions
        $completed_sessions = $wpdb->get_results("
            SELECT DISTINCT session_id,
                   MAX(CASE WHEN element_id = 'full_name' THEN element_value END) as user_name,
                   MAX(CASE WHEN element_id = 'phone' THEN element_value END) as user_phone,
                   MAX(CASE WHEN element_id = 'business_name' THEN element_value END) as business_name,
                   MAX(CASE WHEN element_id = 'city' THEN element_value END) as user_location,
                   MAX(form_completion_time) as completion_time,
                   MAX(timestamp) as completion_date
            FROM {$table_name}
            WHERE event_type = 'form_submit_detailed'
            GROUP BY session_id
            ORDER BY completion_date DESC
        ");
        
        // Get incomplete sessions
        $incomplete_sessions = $wpdb->get_results("
            SELECT DISTINCT t1.session_id,
                   MAX(CASE WHEN t1.element_id = 'full_name' THEN t1.element_value END) as user_name,
                   MAX(CASE WHEN t1.element_id = 'phone' THEN t1.element_value END) as user_phone,
                   MAX(t1.conversion_funnel_step) as last_step,
                   MAX(t1.form_progress) as progress_percent,
                   MAX(t1.session_duration) as time_spent,
                   MAX(t1.timestamp) as last_activity
            FROM {$table_name} t1
            LEFT JOIN {$table_name} t2 ON t1.session_id = t2.session_id AND t2.event_type = 'form_submit_detailed'
            WHERE t2.session_id IS NULL
              AND t1.element_value IS NOT NULL
              AND t1.element_value != ''
              AND t1.form_progress > 0
            GROUP BY t1.session_id
            HAVING user_name IS NOT NULL OR user_phone IS NOT NULL
            ORDER BY last_activity DESC
        ");
        
        return array(
            'completed' => $completed_sessions,
            'incomplete' => $incomplete_sessions
        );
    }
    
    /**
     * Get form abandonment analysis
     */
    private static function get_form_abandonment_analysis($table_name) {
        global $wpdb;
        
        $analysis = array();
        
        // Count exits at each step
        $step_exits = $wpdb->get_results("
            SELECT conversion_funnel_step, COUNT(DISTINCT session_id) as exit_count
            FROM {$table_name}
            WHERE event_type = 'form_abandoned'
            GROUP BY conversion_funnel_step
        ");
        
        $analysis['step_1_exits'] = 0;
        $analysis['step_2_exits'] = 0;
        $analysis['step_3_exits'] = 0;
        $analysis['step_4_exits'] = 0;
        $analysis['step_5_exits'] = 0;
        
        foreach ($step_exits as $step) {
            switch ($step->conversion_funnel_step) {
                case 'step_1_personal_name':
                    $analysis['step_1_exits'] = $step->exit_count;
                    break;
                case 'step_2_email':
                    $analysis['step_2_exits'] = $step->exit_count;
                    break;
                case 'step_3_phone':
                    $analysis['step_3_exits'] = $step->exit_count;
                    break;
                case 'step_4_location':
                    $analysis['step_4_exits'] = $step->exit_count;
                    break;
                case 'step_5_service_selection':
                    $analysis['step_5_exits'] = $step->exit_count;
                    break;
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get user journey data
     */
    private static function get_user_journey_data($table_name) {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT conversion_funnel_step, COUNT(*) as step_count
            FROM {$table_name}
            WHERE conversion_funnel_step IS NOT NULL
            GROUP BY conversion_funnel_step
            ORDER BY step_count DESC
        ");
    }
    
    /**
     * Render completed users table
     */
    private static function render_completed_users($completed_users) {
        $output = '';
        
        if (empty($completed_users)) {
            return '<tr><td colspan="9">هنوز هیچ کاربری فرم را تکمیل نکرده است.</td></tr>';
        }
        
        foreach ($completed_users as $user) {
            // Extract form data directly from database query
            $full_name = $user->user_name ?: 'نامشخص';
            $mobile = $user->user_phone ?: '';
            $business_name = $user->business_name ?: '';
            $city = $user->user_location ?: '';
            
            $completion_time = $user->completion_time ? gmdate('i:s', $user->completion_time) : 'نامشخص';
            $date = date_i18n('Y/m/d H:i', strtotime($user->completion_date));
            
            $output .= "<tr>";
            $output .= "<td><strong>" . esc_html($full_name) . "</strong></td>";
            $output .= "<td class='contact-info'>" . esc_html($mobile) . "</td>";
            $output .= "<td>" . esc_html($business_name) . "</td>";
            $output .= "<td>" . esc_html($city) . "</td>";
            $output .= "<td>" . $completion_time . "</td>";
            $output .= "<td>" . $date . "</td>";
            $output .= "<td>
                <button class='button button-small' onclick='viewUserDetails(\"" . esc_js($user->session_id) . "\")'>جزئیات</button>
            </td>";
            $output .= "</tr>";
        }
        
        return $output;
    }
    
    /**
     * Render incomplete users table
     */
    private static function render_incomplete_users($incomplete_users) {
        $html = '';
        
        foreach ($incomplete_users as $user) {
            $priority = self::calculate_lead_priority($user);
            
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($user->user_name ?: 'نامشخص') . '</strong></td>';
            $html .= '<td class="contact-info">' . esc_html($user->user_phone ?: '-') . '</td>';
            $html .= '<td class="contact-info">' . esc_html($user->user_email ?: '-') . '</td>';
            $html .= '<td><span class="step-indicator">' . self::get_step_label($user->last_step) . '</span></td>';
            $html .= '<td>' . esc_html($user->last_step) . '</td>';
            $html .= '<td>' . round($user->progress_percent, 1) . '%</td>';
            $html .= '<td>' . ($user->time_spent ? round($user->time_spent / 60, 1) . ' دقیقه' : '-') . '</td>';
            $html .= '<td>' . date('Y/m/d H:i', strtotime($user->last_activity)) . '</td>';
            $html .= '<td>';
            if ($user->user_phone) {
                $html .= '<a href="tel:' . $user->user_phone . '" class="button button-small">تماس</a> ';
            }
            $html .= '</td>';
            $html .= '</tr>';
        }
        
        if (empty($incomplete_users)) {
            $html = '<tr><td colspan="9" style="text-align: center; padding: 20px;">کاربر ناتمامی یافت نشد.</td></tr>';
        }
        
        return $html;
    }
    
    /**
     * Render marketing leads
     */
    private static function render_marketing_leads($user_sessions) {
        $html = '';
        
        // Combine incomplete users with contact info
        foreach ($user_sessions['incomplete'] as $user) {
            if ($user->user_phone || $user->user_email) {
                $priority = self::calculate_lead_priority($user);
                $status = self::determine_lead_status($user);
                
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($user->user_name ?: 'نامشخص') . '</strong></td>';
                $html .= '<td class="contact-info">' . esc_html($user->user_phone ?: '-') . '</td>';
                $html .= '<td class="contact-info">' . esc_html($user->user_email ?: '-') . '</td>';
                $html .= '<td>' . self::get_interest_level($user) . '</td>';
                $html .= '<td><span class="user-status ' . $status['class'] . '">' . $status['label'] . '</span></td>';
                $html .= '<td><span class="priority-' . $priority['level'] . '">' . $priority['label'] . '</span></td>';
                $html .= '<td>';
                $html .= '<button class="button button-primary button-small" onclick="contactLead(\'' . $user->session_id . '\')">پیگیری</button>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        
        if (empty($html)) {
            $html = '<tr><td colspan="7" style="text-align: center; padding: 20px;">لید قابل پیگیری یافت نشد.</td></tr>';
        }
        
        return $html;
    }
    
    /**
     * Calculate lead priority based on form progress and data quality
     */
    private static function calculate_lead_priority($user) {
        $score = 0;
        
        // Has name
        if ($user->user_name) $score += 20;
        
        // Has phone
        if ($user->user_phone) $score += 30;
        
        // Has email
        if ($user->user_email) $score += 20;
        
        // Form progress
        $score += ($user->progress_percent / 100) * 30;
        
        if ($score >= 70) {
            return array('level' => 'high', 'label' => 'بالا');
        } elseif ($score >= 40) {
            return array('level' => 'medium', 'label' => 'متوسط');
        } else {
            return array('level' => 'low', 'label' => 'پایین');
        }
    }
    
    /**
     * Determine lead status
     */
    private static function determine_lead_status($user) {
        if ($user->progress_percent >= 80) {
            return array('class' => 'status-incomplete', 'label' => 'تقریباً تکمیل');
        } elseif ($user->progress_percent >= 50) {
            return array('class' => 'status-incomplete', 'label' => 'نیمه تکمیل');
        } else {
            return array('class' => 'status-abandoned', 'label' => 'رها شده');
        }
    }
    
    /**
     * Get interest level based on user behavior
     */
    private static function get_interest_level($user) {
        if ($user->progress_percent >= 70) {
            return 'علاقه‌مند بالا';
        } elseif ($user->progress_percent >= 40) {
            return 'علاقه‌مند متوسط';
        } else {
            return 'علاقه‌مند اولیه';
        }
    }
    
    /**
     * Get step label in Persian
     */
    private static function get_step_label($step) {
        $labels = array(
            'step_1_personal_name' => 'نام',
            'step_2_email' => 'ایمیل',
            'step_3_phone' => 'تلفن',
            'step_4_location' => 'لوکیشن',
            'step_5_service_selection' => 'سرویس',
            'step_6_additional_info' => 'اطلاعات تکمیلی'
        );
        
        return isset($labels[$step]) ? $labels[$step] : 'نامشخص';
    }
    
    /**
     * Get detailed exit analysis for each form step
     */
    private static function get_detailed_exit_analysis($table_name) {
        global $wpdb;
        
        $exit_analysis = array(
            'steps' => array(),
            'total_exits' => 0
        );
        
        // Define step labels
        $step_labels = array(
            'step_1_first_name' => 'نام',
            'step_2_last_name' => 'نام خانوادگی',
            'step_3_mobile_number' => 'شماره موبایل',
            'step_4_email' => 'ایمیل',
            'step_5_business_name' => 'نام کسب و کار',
            'step_6_business_address' => 'آدرس کسب و کار',
            'step_7_province_selection' => 'انتخاب استان',
            'step_8_city_selection' => 'انتخاب شهر',
            'step_9_business_category' => 'نوع کسب و کار',
            'step_10_additional_info' => 'توضیحات اضافی',
            'step_11_terms_agreement' => 'تایید قوانین',
            'step_unknown_field' => 'فیلد نامشخص'
        );
        
        // Get all incomplete sessions
        $incomplete_sessions = $wpdb->get_results(
            "SELECT conversion_funnel_step, element_value, element_id
             FROM {$table_name} 
             WHERE form_completed = 0 
             AND conversion_funnel_step IS NOT NULL
             ORDER BY timestamp DESC"
        );
        
        // Analyze exit points
        foreach ($incomplete_sessions as $session) {
            $step = $session->conversion_funnel_step ?: 'step_unknown_field';
            $has_value = !empty($session->element_value);
            
            if (!isset($exit_analysis['steps'][$step])) {
                $exit_analysis['steps'][$step] = array(
                    'label' => $step_labels[$step] ?? 'نامشخص',
                    'before_completion' => 0,
                    'after_completion' => 0,
                    'total_exits' => 0
                );
            }
            
            if ($has_value) {
                $exit_analysis['steps'][$step]['after_completion']++;
            } else {
                $exit_analysis['steps'][$step]['before_completion']++;
            }
            
            $exit_analysis['steps'][$step]['total_exits']++;
            $exit_analysis['total_exits']++;
        }
        
        // Sort by total exits (descending)
        uasort($exit_analysis['steps'], function($a, $b) {
            return $b['total_exits'] - $a['total_exits'];
        });
        
        return $exit_analysis;
    }
    
    /**
     * Render incomplete users with detailed exit analysis
     */
    private static function render_incomplete_users_detailed($incomplete_users) {
        $output = '';
        
        if (empty($incomplete_users)) {
            return '<tr><td colspan="9">همه کاربران فرم را تکمیل کرده‌اند! 🎉</td></tr>';
        }
        
        foreach ($incomplete_users as $user) {
            // Extract available data directly from query
            $name = $user->user_name ?: 'نامشخص';
            $mobile = $user->user_phone ?: '';
            
            // Get entry and exit points
            $entry_point = self::get_user_entry_point_static($user->session_id);
            $exit_point = self::get_drop_off_point_static($user->session_id);
            
            // Determine exit status more precisely based on specific fields filled
            $exit_status_info = self::determine_detailed_exit_status($user->session_id);
            $exit_status = $exit_status_info['status'];
            $exit_class = $exit_status_info['class'];
            
            // Calculate completion percentage
            $completion_percentage = round($user->progress_percent ?: 0);
            
            $time_spent = $user->time_spent ? gmdate('i:s', $user->time_spent) : 'نامشخص';
            $date = date_i18n('Y/m/d H:i', strtotime($user->last_activity));
            
            $output .= "<tr>";
            $output .= "<td><strong>" . esc_html($name) . "</strong></td>";
            $output .= "<td class='contact-info'>" . esc_html($mobile) . "</td>";
            $output .= "<td>
                <span class='step-indicator entry-point'>" . esc_html($entry_point) . "</span>
            </td>";
            $output .= "<td>
                <span class='step-indicator exit-point'>" . esc_html($exit_point) . "</span>
            </td>";
            $output .= "<td>
                <span class='exit-status " . $exit_class . "'>" . $exit_status . "</span>
            </td>";
            $output .= "<td>" . $completion_percentage . "%</td>";
            $output .= "<td>" . $time_spent . "</td>";
            $output .= "<td>" . $date . "</td>";
            $output .= "<td>";
            if (!empty($mobile)) {
                $output .= "<a href='tel:" . esc_attr($mobile) . "' class='button button-small'>☎️ تماس</a>";
            }
            $output .= "</td>";
            $output .= "</tr>";
        }
        
        return $output;
    }
    
    /**
     * Render marketing leads (only users with contact info)
     */
    private static function render_marketing_leads_detailed($user_sessions) {
        $output = '';
        $marketing_leads = array();
        
        // Collect leads from incomplete users who have mobile number
        foreach ($user_sessions['incomplete'] as $user) {
            $mobile = $user->user_phone ?: '';
            
            // Only include users with mobile number
            if (!empty($mobile)) {
                $name = $user->user_name ?: 'نامشخص';
                $business_interest = self::determine_user_interest_level($user->session_id);
                
                // Determine priority based on completion level
                $progress = $user->progress_percent ?: 0;
                
                if ($progress >= 70) {
                    $priority = 'high';
                    $priority_label = 'بالا';
                } elseif ($progress >= 40) {
                    $priority = 'medium';
                    $priority_label = 'متوسط';
                } else {
                    $priority = 'low';
                    $priority_label = 'پایین';
                }
                
                // Status is always mobile-only since we don't have email
                $status = 'mobile-only';
                $status_label = 'موبایل موجود';
                
                $marketing_leads[] = array(
                    'name' => $name,
                    'mobile' => $mobile,
                    'interest' => $business_interest,
                    'status' => $status,
                    'status_label' => $status_label,
                    'priority' => $priority,
                    'priority_label' => $priority_label,
                    'entry_point' => self::get_user_entry_point_static($user->session_id),
                    'exit_point' => self::get_drop_off_point_static($user->session_id),
                    'session_id' => $user->session_id
                );
            }
        }
        
        if (empty($marketing_leads)) {
            return '<tr><td colspan="8">هیچ لید مارکتینگی با شماره موبایل یافت نشد.</td></tr>';
        }
        
        // Sort by priority (high first)
        usort($marketing_leads, function($a, $b) {
            $priority_order = array('high' => 3, 'medium' => 2, 'low' => 1);
            return $priority_order[$b['priority']] - $priority_order[$a['priority']];
        });
        
        foreach ($marketing_leads as $lead) {
            $output .= "<tr>";
            $output .= "<td><strong>" . esc_html($lead['name']) . "</strong></td>";
            $output .= "<td class='contact-info'>" . esc_html($lead['mobile']) . "</td>";
            $output .= "<td>
                <span class='step-indicator entry-point'>" . esc_html($lead['entry_point']) . "</span>
            </td>";
            $output .= "<td>
                <span class='step-indicator exit-point'>" . esc_html($lead['exit_point']) . "</span>
            </td>";
            $output .= "<td>" . esc_html($lead['interest']) . "</td>";
            $output .= "<td>
                <span class='user-status status-" . $lead['status'] . "'>" . $lead['status_label'] . "</span>
            </td>";
            $output .= "<td>
                <span class='priority-" . $lead['priority'] . "'>" . $lead['priority_label'] . "</span>
            </td>";
            $output .= "<td>";
            
            if (!empty($lead['mobile'])) {
                $output .= "<a href='tel:" . esc_attr($lead['mobile']) . "' class='button button-small'>☎️ تماس</a>";
            }
            
            $output .= "</td>";
            $output .= "</tr>";
        }
        
        return $output;
    }
    
    /**
     * Export user data for marketing analysis
     */
    public function export_user_data() {
        if (!wp_verify_nonce($_GET['security'], 'export_user_nonce')) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $tracking_table = $wpdb->prefix . 'market_google_user_tracking';
        $type = sanitize_text_field($_GET['type']);
        
        $filename = 'market_google_users_' . $type . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        switch ($type) {
            case 'completed':
                $this->export_completed_users($output, $tracking_table);
                break;
            case 'incomplete':
                $this->export_incomplete_users($output, $tracking_table);
                break;
            case 'marketing':
                $this->export_marketing_leads($output, $tracking_table);
                break;
            case 'all':
                $this->export_all_data($output, $tracking_table);
                break;
        }
        
        fclose($output);
        exit;
    }
    
    private function export_completed_users($output, $table_name) {
        global $wpdb;
        
        // CSV headers
        fputcsv($output, array('نام', 'شماره تماس', 'ایمیل', 'لوکیشن', 'سرویس انتخابی', 'زمان تکمیل (ثانیه)', 'تاریخ تکمیل', 'IP آدرس'));
        
        $results = $wpdb->get_results("
            SELECT DISTINCT session_id,
                   MAX(CASE WHEN element_name LIKE '%name%' THEN element_value END) as user_name,
                   MAX(CASE WHEN element_name LIKE '%email%' THEN element_value END) as user_email,
                   MAX(CASE WHEN element_name LIKE '%phone%' OR element_name LIKE '%mobile%' THEN element_value END) as user_phone,
                   MAX(CASE WHEN element_name LIKE '%location%' OR element_name LIKE '%city%' THEN element_value END) as user_location,
                   MAX(CASE WHEN element_name LIKE '%service%' THEN element_value END) as selected_service,
                   MAX(form_completion_time) as completion_time,
                   MAX(timestamp) as completion_date,
                   MAX(user_ip) as user_ip
            FROM {$table_name}
            WHERE event_type = 'form_submit_detailed'
            GROUP BY session_id
            ORDER BY completion_date DESC
        ");
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->user_name ?: 'نامشخص',
                $row->user_phone ?: '-',
                $row->user_email ?: '-',
                $row->user_location ?: '-',
                $row->selected_service ?: '-',
                $row->completion_time ?: '-',
                $row->completion_date,
                $row->user_ip
            ));
        }
    }
    
    private function export_incomplete_users($output, $table_name) {
        global $wpdb;
        
        // CSV headers
        fputcsv($output, array('نام', 'شماره تماس', 'ایمیل', 'آخرین مرحله', 'درصد تکمیل', 'زمان صرف شده (دقیقه)', 'آخرین فعالیت', 'IP آدرس'));
        
        $results = $wpdb->get_results("
            SELECT DISTINCT t1.session_id,
                   MAX(CASE WHEN t1.element_name LIKE '%name%' THEN t1.element_value END) as user_name,
                   MAX(CASE WHEN t1.element_name LIKE '%email%' THEN t1.element_value END) as user_email,
                   MAX(CASE WHEN t1.element_name LIKE '%phone%' OR t1.element_name LIKE '%mobile%' THEN t1.element_value END) as user_phone,
                   MAX(t1.conversion_funnel_step) as last_step,
                   MAX(t1.form_progress) as progress_percent,
                   MAX(t1.session_duration) as time_spent,
                   MAX(t1.timestamp) as last_activity
            FROM {$table_name} t1
            LEFT JOIN {$table_name} t2 ON t1.session_id = t2.session_id AND t2.event_type = 'form_submit_detailed'
            WHERE t2.session_id IS NULL
              AND t1.element_value IS NOT NULL
              AND t1.element_value != ''
              AND t1.form_progress > 0
            GROUP BY t1.session_id
            HAVING user_name IS NOT NULL OR user_phone IS NOT NULL OR user_email IS NOT NULL
            ORDER BY last_activity DESC
        ");
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->user_name ?: 'نامشخص',
                $row->user_phone ?: '-',
                $row->user_email ?: '-',
                $row->last_step,
                round($row->progress_percent, 1) . '%',
                $row->time_spent ? round($row->time_spent / 60, 1) : '-',
                $row->last_activity,
                $row->user_ip
            ));
        }
    }
    
    private function export_marketing_leads($output, $table_name) {
        global $wpdb;
        
        // CSV headers
        fputcsv($output, array('نام', 'شماره تماس', 'ایمیل', 'آخرین مرحله', 'درصد تکمیل', 'وضعیت', 'اولویت پیگیری', 'آخرین فعالیت', 'IP آدرس'));
        
        $results = $wpdb->get_results("
            SELECT DISTINCT t1.session_id,
                   MAX(CASE WHEN t1.element_name LIKE '%name%' THEN t1.element_value END) as user_name,
                   MAX(CASE WHEN t1.element_name LIKE '%email%' THEN t1.element_value END) as user_email,
                   MAX(CASE WHEN t1.element_name LIKE '%phone%' OR t1.element_name LIKE '%mobile%' THEN t1.element_value END) as user_phone,
                   MAX(t1.conversion_funnel_step) as last_step,
                   MAX(t1.form_progress) as progress_percent,
                   MAX(t1.session_duration) as time_spent,
                   MAX(t1.timestamp) as last_activity,
                   MAX(t1.user_ip) as user_ip
            FROM {$table_name} t1
            LEFT JOIN {$table_name} t2 ON t1.session_id = t2.session_id AND t2.event_type = 'form_submit_detailed'
            WHERE t2.session_id IS NULL
              AND t1.element_value IS NOT NULL
              AND t1.element_value != ''
              AND t1.form_progress > 0
              AND (t1.element_name LIKE '%phone%' OR t1.element_name LIKE '%email%')
            GROUP BY t1.session_id
            HAVING user_phone IS NOT NULL OR user_email IS NOT NULL
            ORDER BY last_activity DESC
        ");
        
        foreach ($results as $row) {
            // Calculate priority and status
            $priority_data = self::calculate_lead_priority_for_export($row);
            $status_data = self::determine_lead_status_for_export($row);
            
            fputcsv($output, array(
                $row->user_name ?: 'نامشخص',
                $row->user_phone ?: '-',
                $row->user_email ?: '-',
                $row->last_step,
                round($row->progress_percent, 1) . '%',
                $status_data,
                $priority_data,
                $row->last_activity,
                $row->user_ip
            ));
        }
    }
    
    private function export_all_data($output, $table_name) {
        global $wpdb;
        
        // CSV headers
        fputcsv($output, array('Session ID', 'نوع رویداد', 'نام فیلد', 'مقدار فیلد', 'IP آدرس', 'مرحله فرم', 'درصد پیشرفت', 'زمان', 'User Agent'));
        
        $results = $wpdb->get_results("
            SELECT session_id, event_type, element_name, element_value, user_ip, 
                   conversion_funnel_step, form_progress, timestamp, user_agent
            FROM $table_name
            WHERE element_value IS NOT NULL AND element_value != ''
            ORDER BY timestamp DESC
            LIMIT 10000
        ");
        
        foreach ($results as $row) {
            fputcsv($output, array(
                $row->session_id,
                $row->event_type,
                $row->element_name ?: '-',
                $row->element_value ?: '-',
                $row->user_ip,
                $row->conversion_funnel_step ?: '-',
                $row->form_progress ?: '0',
                $row->timestamp,
                substr($row->user_agent, 0, 100) // Limit user agent length
            ));
        }
    }
    
    private static function calculate_lead_priority_for_export($user) {
        $score = 0;
        if ($user->user_name) $score += 20;
        if ($user->user_phone) $score += 30;
        if ($user->user_email) $score += 20;
        $score += ($user->progress_percent / 100) * 30;
        
        if ($score >= 70) return 'بالا';
        elseif ($score >= 40) return 'متوسط';
        else return 'پایین';
    }
    
    private static function determine_lead_status_for_export($user) {
        if ($user->progress_percent >= 80) return 'تقریباً تکمیل';
        elseif ($user->progress_percent >= 50) return 'نیمه تکمیل';
        else return 'رها شده';
    }
    
    /**
     * Get location information from event data
     */
    private function get_location_info($event) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Get location data from the latest event for this session
        $location_data = $wpdb->get_row($wpdb->prepare("
            SELECT ip_location_string, ip_country, ip_city, ip_isp
            FROM $table_name 
            WHERE session_id = %s 
            AND ip_location_string IS NOT NULL 
            AND ip_location_string != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $event->session_id));
        
        if ($location_data && !empty($location_data->ip_location_string)) {
            return esc_html($location_data->ip_location_string) . ' (' . esc_html($event->user_ip) . ')';
        }
        
        return esc_html($event->user_ip) . ' (نامشخص)';
    }
    
    /**
     * Get device model from event data
     */
    private function get_device_model($event) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        if (!is_object($event) || !isset($event->session_id)) {
            return 'نامشخص';
        }
        // Get device model from the latest event for this session
        $device_data = $wpdb->get_var($wpdb->prepare("
            SELECT device_model
            FROM $table_name 
            WHERE session_id = %s 
            AND device_model IS NOT NULL 
            AND device_model != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $event->session_id));
        
        if (!empty($device_data)) {
            return esc_html($device_data);
        }
        
        // Fallback to basic device detection from user agent
        return $this->get_device_info($event->user_agent ?? '');
    }
    
    /**
     * Get device fingerprint from event data
     */
    private function get_device_fingerprint($event) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        if (!is_object($event) || !isset($event->session_id)) {
            return 'نامشخص';
        }
        // Get device fingerprint from the latest event for this session
        $fingerprint = $wpdb->get_var($wpdb->prepare("
            SELECT device_fingerprint
            FROM $table_name 
            WHERE session_id = %s 
            AND device_fingerprint IS NOT NULL 
            AND device_fingerprint != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $event->session_id));
        
        if (!empty($fingerprint)) {
            return $fingerprint;
        }
        
        return 'نامشخص';
    }
    
    /**
     * Get location information for session
     */
    private function get_location_info_for_session($session) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Get location data from the latest event for this session
        $location_data = $wpdb->get_row($wpdb->prepare("
            SELECT ip_location_string, ip_country, ip_city, ip_isp
            FROM $table_name 
            WHERE session_id = %s 
            AND ip_location_string IS NOT NULL 
            AND ip_location_string != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session->session_id));
        
        if ($location_data && !empty($location_data->ip_location_string)) {
            return esc_html($location_data->ip_location_string) . ' (' . esc_html($session->user_ip) . ')';
        }
        
        return esc_html($session->user_ip) . ' (نامشخص)';
    }
    
    /**
     * Get device model for session
     */
    private function get_device_model_for_session($session) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Get device model from the latest event for this session
        $device_data = $wpdb->get_var($wpdb->prepare("
            SELECT device_model
            FROM $table_name 
            WHERE session_id = %s 
            AND device_model IS NOT NULL 
            AND device_model != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session->session_id));
        
        if (!empty($device_data)) {
            return esc_html($device_data);
        }
        
        // Fallback to basic device detection from user agent
        return $this->get_device_info($session->user_agent ?? '');
    }
    
    /**
     * Get device fingerprint for session
     */
    private function get_device_fingerprint_for_session($session) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // Get device fingerprint from the latest event for this session
        $fingerprint = $wpdb->get_var($wpdb->prepare("
            SELECT device_fingerprint
            FROM $table_name 
            WHERE session_id = %s 
            AND device_fingerprint IS NOT NULL 
            AND device_fingerprint != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session->session_id));
        
        if (!empty($fingerprint)) {
            return $fingerprint;
        }
        
        return 'نامشخص';
    }
    
    /**
     * Get user display name (real name or User_ID)
     */
    private function get_user_display_name($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        // Get user's real name from full_name field or similar patterns
        $user_name = $wpdb->get_var($wpdb->prepare("
            SELECT element_value
            FROM $table_name 
            WHERE session_id = %s 
            AND (element_id = 'full_name' OR element_name = 'full_name')
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session_id));
        if (!empty($user_name) && strlen(trim($user_name)) > 1) {
            return esc_html(trim($user_name));
        }
        // اگر نامی پیدا نشد، User_xxxx و 6 رقم آخر session_id را نمایش بده
        $hash = crc32($session_id);
        $user_id = abs($hash) % 9999;
        $last_digits = substr($session_id, -6);
        return 'User_' . sprintf('%04d', $user_id) . ' [' . $last_digits . ']';
    }
    
    /**
     * دریافت نام کاربر با بهبود تشخیص نام
     */
    private function get_enhanced_user_display_name($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $name_queries = array(
            // اولویت 1: فیلد full_name در فرم Market Location
            "SELECT element_value, timestamp
            FROM $table_name 
            WHERE session_id = %s 
            AND (element_id = 'full_name' OR element_name = 'full_name')
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
            AND LENGTH(TRIM(element_value)) > 2
            ORDER BY timestamp DESC 
            LIMIT 1",
            // اولویت 2: فیلدهای نام عمومی
            "SELECT element_value, timestamp
            FROM $table_name 
            WHERE session_id = %s 
            AND (
                element_id IN ('name', 'user_name', 'customer_name', 'first_name') OR
                element_name IN ('name', 'user_name', 'customer_name', 'first_name') OR
                element_id LIKE '%name%' OR
                element_name LIKE '%name%'
            )
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
            AND LENGTH(TRIM(element_value)) > 2
            ORDER BY timestamp DESC 
            LIMIT 1",
            // اولویت 3: نام کسب‌وکار
            "SELECT element_value, timestamp
            FROM $table_name 
            WHERE session_id = %s 
            AND (element_id = 'business_name' OR element_name = 'business_name')
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
            AND LENGTH(TRIM(element_value)) > 2
            ORDER BY timestamp DESC 
            LIMIT 1"
        );
        foreach ($name_queries as $query) {
            $result = $wpdb->get_row($wpdb->prepare($query, $session_id));
            if ($result && $result->element_value && strlen(trim($result->element_value)) > 2) {
                $name = trim($result->element_value);
                if (!preg_match('/^[\w\s\u0600-\u06FF\u0621-\u063A\u0640-\u06FF]+$/u', $name)) {
                    continue;
                }
                $name = preg_replace('/\s+/', ' ', $name);
                if (strlen($name) > 50) {
                    $name = substr($name, 0, 47);
                }
                return esc_html($name);
            }
        }
        // اگر نامی پیدا نشد، User_xxxx با 4 رقم آخر session_id را نمایش بده
        $user_id = substr($session_id, -4);
        return 'User_' . $user_id;
    }
    
    /**
     * دریافت وضعیت آنلاین/آفلاین کاربر
     */
    private function get_online_status($last_activity) {
        $last_activity_time = strtotime($last_activity);
        $current_time = time();
        $diff_minutes = ($current_time - $last_activity_time) / 60;
        
        // نمایش آنلاین برای فعالیت در ۵ دقیقه قبل
        if ($diff_minutes <= 5) {
            return array(
                'class' => 'mg-status-online',
                'text' => 'آنلاین',
                'is_online' => true
            );
        }
        // در غیر این صورت آفلاین
        return array(
            'class' => 'mg-status-offline',
            'text' => 'آفلاین',
            'is_online' => false
        );
    }
    
    /**
     * دریافت وضعیت فعلی کاربر با جزئیات بیشتر
     */
    private function get_current_activity_detailed($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // دریافت آخرین فعالیت معنادار (غیر از heartbeat و activity_check)
        $latest_meaningful = $wpdb->get_row($wpdb->prepare("
            SELECT event_type, element_id, element_value, page_url, timestamp
            FROM $table_name 
            WHERE session_id = %s 
            AND event_type NOT IN ('heartbeat', 'activity_check', 'mouse_move')
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session_id));
        
        if (empty($latest_meaningful)) {
            return 'بدون فعالیت';
        }
        
        $field_name = $this->get_field_persian_name($latest_meaningful->element_id);
        
        // بررسی زمان آخرین فعالیت برای تشخیص خروج از سایت
        $tehran_tz = new DateTimeZone('Asia/Tehran');
        $now = new DateTime('now', $tehran_tz);
        $last_activity_time = new DateTime($latest_meaningful->timestamp, $tehran_tz);
        $diff = $now->diff($last_activity_time);
        
        // اگر بیش از 5 دقیقه گذشته، احتمالاً از سایت خارج شده
        if ($diff->i > 5) {
            return '🚪 خروج از سایت (' . $last_activity_time->format('Y/m/d H:i') . ')';
        }
        
        // وضعیت فعلی بر اساس آخرین فعالیت معنادار
        switch ($latest_meaningful->event_type) {
            case 'field_focus':
                return '🎯 در حال پر کردن ' . $field_name;
            case 'field_input':
                return '⌨️ در حال تایپ در ' . $field_name;
            case 'field_blur':
                return '✅ تکمیل ' . $field_name;
            case 'page_load':
                $page = $this->get_current_page($session_id);
                return '📄 ورود به ' . $page;
            case 'click':
                return '🖱️ کلیک روی فرم';
            case 'scroll':
                return '📜 اسکرول صفحه';
            case 'form_submit':
                return '📤 ارسال فرم';
            default:
                return '🔍 ' . $this->get_event_label($latest_meaningful->event_type);
        }
    }
    
    private function get_previous_activity($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // دریافت دو فعالیت معنادار آخر (غیر از heartbeat و activity_check)
        $recent_meaningful = $wpdb->get_results($wpdb->prepare("
            SELECT event_type, element_id, element_value, page_url, timestamp
            FROM $table_name 
            WHERE session_id = %s 
            AND event_type NOT IN ('heartbeat', 'activity_check', 'mouse_move')
            ORDER BY timestamp DESC 
            LIMIT 3
        ", $session_id));
        
        if (count($recent_meaningful) < 2) {
            return '';
        }
        
        $latest = $recent_meaningful[0];
        $prev = $recent_meaningful[1];
        $field_name = $this->get_field_persian_name($latest->element_id);
        $prev_field = $this->get_field_persian_name($prev->element_id);
        
        // منطق هوشمند برای جمله‌سازی دقیق
        if ($prev->event_type === 'page_load' && $latest->event_type === 'field_focus') {
            $page = $this->get_current_page($session_id);
            return 'ورود به ' . $page . ' و شروع پر کردن ' . $field_name;
        }
        if ($prev->event_type === 'field_blur' && $latest->event_type === 'page_load') {
            $page = $this->get_current_page($session_id);
            return 'خروج از فیلد ' . $prev_field . ' و ورود به ' . $page;
        }
        if ($prev->event_type === 'field_blur' && $latest->event_type === 'field_focus') {
            return 'پایان پر کردن ' . $prev_field . ' و شروع پر کردن ' . $field_name;
        }
        if ($prev->event_type === 'field_focus' && $latest->event_type === 'field_focus') {
            return 'جابجایی فوکوس از ' . $prev_field . ' به ' . $field_name;
        }
        if ($prev->event_type === 'page_load' && $latest->event_type === 'page_load') {
            $curr_page = $this->get_current_page($session_id);
            return 'انتقال به صفحه ' . $curr_page;
        }
        if ($prev->event_type === 'field_input' && $latest->event_type === 'field_blur') {
            return 'تکمیل تایپ در ' . $prev_field;
        }
        if ($prev->event_type === 'click' && $latest->event_type === 'field_focus') {
            return 'کلیک و شروع پر کردن ' . $field_name;
        }
        
        // حالت‌های ساده‌تر
        switch ($prev->event_type) {
            case 'field_focus':
                return 'فوکوس روی ' . $prev_field;
            case 'field_input':
            case 'keystroke':
                return 'تایپ در ' . $prev_field;
            case 'field_blur':
                return 'تکمیل ' . $prev_field;
            case 'page_load':
                $page = $this->get_current_page($session_id);
                return 'ورود به ' . $page;
            case 'click':
                return 'کلیک روی فرم';
            case 'scroll':
                return 'اسکرول صفحه';
            case 'form_submit':
                return 'ارسال فرم';
            default:
                return $this->get_event_label($prev->event_type);
        }
    }
    
    /**
     * محاسبه پیشرفت دقیق فرم
     */
    private function calculate_detailed_form_progress($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $progress = 0;
            $current_step = 'شروع فرم';
            $completed_steps = 0;
            
            // بررسی وجود event_type برای تشخیص نوع فرم
            $form_events = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT event_type, element_id, element_name, element_value
            FROM $table_name 
            WHERE session_id = %s 
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
            WHERE session_id = %s 
            AND element_value IS NOT NULL 
            AND element_value != ''
            AND TRIM(element_value) != ''
                ORDER BY timestamp DESC
        ", $session_id));
        
            // بررسی آیا فرم Market Location است
            $has_market_form = false;
            foreach ($form_events as $event) {
                if (in_array($event->element_id, ['full_name', 'business_name', 'selected_packages']) || 
                    in_array($event->element_name, ['full_name', 'business_name', 'selected_packages'])) {
                    $has_market_form = true;
                    break;
                }
            }
            
            if ($has_market_form) {
                // محاسبه دقیق پیشرفت برای فرم Market Location - تمام 17 فیلد
                
                // مرحله 1: اطلاعات شخصی (20% کل)
                // فیلد 1: نام کامل (10%)
                $full_name = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
            WHERE session_id = %s 
                    AND (element_id = 'full_name' OR element_name = 'full_name')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
        ", $session_id));
        
                if ($full_name && strlen(trim($full_name)) > 2) {
                    $progress += 10;
                    $current_step = 'نام کامل: ' . $full_name;
                    $completed_steps++;
                }
                
                // فیلد 2: تلفن همراه (10%)
                $phone = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'phone' OR element_name = 'phone')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($phone && strlen(trim($phone)) > 10) {
                    $progress += 10;
                    $current_step = 'شماره تماس: ' . $phone;
                    $completed_steps++;
                }
                
                // مرحله 2: کسب‌وکار و موقعیت (50% کل)
                // فیلد 3: نام کسب‌وکار (8%)
                $business_name = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'business_name' OR element_name = 'business_name')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($business_name && strlen(trim($business_name)) > 2) {
                    $progress += 8;
                    $current_step = 'نام کسب‌وکار: ' . $business_name;
                    $completed_steps++;
                }
                
                // فیلد 4: تلفن کسب‌وکار (7%)
                $business_phone = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'business_phone' OR element_name = 'business_phone')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($business_phone && strlen(trim($business_phone)) > 10) {
                    $progress += 7;
                    $current_step = 'تلفن کسب‌وکار: ' . $business_phone;
                    $completed_steps++;
                }
                
                // فیلد 5+6: انتخاب موقعیت روی نقشه (15%)
                $latitude = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'latitude' OR element_name = 'latitude')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                $longitude = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'longitude' OR element_name = 'longitude')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($latitude && $longitude) {
                    $progress += 15;
                    $current_step = 'موقعیت روی نقشه: ' . $latitude . ', ' . $longitude;
                    $completed_steps += 2; // دو فیلد lat/lng
                }
                
                // فیلد 7: انتخاب استان (5%)
                $province = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'province' OR element_name = 'province')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($province) {
                    $progress += 5;
                    $current_step = 'استان: ' . $province;
                    $completed_steps++;
                }
                
                // فیلد 8: انتخاب شهر (5%)
                $city = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'city' OR element_name = 'city')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($city) {
                    $progress += 5;
                    $current_step = 'شهر: ' . $city;
                    $completed_steps++;
                }
                
                // فیلد 9: آدرس دقیق (اختیاری - 3%)
                $manual_address = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'manual_address' OR element_name = 'manual_address')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    AND LENGTH(TRIM(element_value)) > 5
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($manual_address) {
                    $progress += 3;
                    $current_step = 'آدرس دقیق: ' . $manual_address;
                    $completed_steps++;
                }
                
                // فیلد 10: وب سایت (اختیاری - 2%)
                $website = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'website' OR element_name = 'website')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    AND LENGTH(TRIM(element_value)) > 3
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($website) {
                    $progress += 2;
                    $current_step = 'آدرس وب‌سایت: ' . $website;
                    $completed_steps++;
                }
                
                // فیلد 11: آخرین تعامل با فرم (2%)
                $last_interaction = $wpdb->get_row($wpdb->prepare("
                    SELECT element_id, element_value, event_type FROM $table_name 
                    WHERE session_id = %s 
                    AND event_type IN ('field_input', 'field_blur', 'click', 'map_click', 'location_select')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($last_interaction) {
                    $progress += 2;
                    $field_name = $this->get_field_persian_name($last_interaction->element_id);
                    $value = strlen($last_interaction->element_value) > 25 ? 
                        substr($last_interaction->element_value, 0, 25) . '...' : 
                        $last_interaction->element_value;
                    $current_step = $field_name . ': ' . $value;
                    $completed_steps++;
                }
                
                // فیلد 12: ساعت کاری (3%)
                $working_hours = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'working_hours_text' OR element_name = 'working_hours_text')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($working_hours) {
                    $progress += 3;
                    $current_step = 'ساعت کاری';
                    $completed_steps++;
                }
                
                // مرحله 3: انتخاب محصولات (20% کل)
                // فیلد 13: انتخاب پکیج (20%)
                $selected_packages = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'selected_packages' OR element_name = 'selected_packages' OR event_type = 'package_selected')
                    AND element_value IS NOT NULL AND TRIM(element_value) != ''
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($selected_packages) {
                    $progress += 20;
                    $current_step = 'انتخاب محصولات';
                    $completed_steps++;
                }
                
                // مرحله 4: تایید و پرداخت (10% کل)
                // فیلد 14: تایید قوانین (5%)
                $terms_accepted = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND (element_id = 'terms' OR element_name = 'terms')
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($terms_accepted) {
                    $progress += 5;
                    $current_step = 'پذیرش قوانین';
                    $completed_steps++;
                }
                
                // فیلد 15: کلیک دکمه submit (شروع پرداخت)
                $form_submitted = $wpdb->get_var($wpdb->prepare("
                    SELECT event_type FROM $table_name 
                    WHERE session_id = %s 
                    AND event_type = 'form_submit_attempt'
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($form_submitted) {
                    $progress += 5;
                    $current_step = 'شروع پرداخت';
                    $completed_steps++;
                }
                
                // فیلد 16-17: نتایج پرداخت (5% باقی‌مانده)
                $payment_status = $wpdb->get_var($wpdb->prepare("
                    SELECT element_value FROM $table_name 
                    WHERE session_id = %s 
                    AND event_type = 'payment_result'
                    ORDER BY timestamp DESC LIMIT 1
                ", $session_id));
                
                if ($payment_status === 'success' || $payment_status === 'completed') {
                    $progress = 100;
                    $current_step = '✅ تکمیل موفق پرداخت';
                    $completed_steps++;
                } elseif ($payment_status === 'failed' || $payment_status === 'error') {
                    $progress = min($progress + 2, 96);
                    $current_step = '❌ پرداخت ناموفق';
                    $completed_steps++;
                } elseif ($payment_status === 'cancelled') {
                    $progress = min($progress + 1, 94);
                    $current_step = '🚫 انصراف از پرداخت';
                    $completed_steps++;
                } elseif ($payment_status === 'pending') {
                    $progress = min($progress + 3, 98);
                    $current_step = '⏳ در انتظار پرداخت';
                    $completed_steps++;
                }
                
            } else {
                // فرم عمومی - محاسبه پیشرفت کلی
                $form_fields = array(
                    'name' => 2, 'full_name' => 2, 'first_name' => 1, 'last_name' => 1,
                    'phone' => 2, 'mobile' => 2, 'tel' => 2, 'email' => 1,
                    'business_name' => 2, 'company_name' => 2,
                    'province' => 1, 'city' => 1, 'address' => 1
                );
                
                $total_weight = array_sum($form_fields);
                $filled_weight = 0;
                $last_field = '';
                
                foreach ($form_fields as $field => $weight) {
                    $has_value = $wpdb->get_var($wpdb->prepare("
                        SELECT element_id FROM $table_name 
                        WHERE session_id = %s 
                        AND (element_id = %s OR element_name = %s OR element_id LIKE %s)
                        AND element_value IS NOT NULL AND TRIM(element_value) != ''
                        ORDER BY timestamp DESC LIMIT 1
                    ", $session_id, $field, $field, "%{$field}%"));
                    
                    if ($has_value) {
                        $filled_weight += $weight;
                        $last_field = $has_value;
                        $completed_steps++;
                    }
                }
                
                $progress = $total_weight > 0 ? min(100, round(($filled_weight / $total_weight) * 100)) : 0;
                $current_step = $last_field ? $this->get_field_persian_name($last_field) : 'شروع فرم';
            }
        
        return array(
                'percentage' => min($progress, 100),
            'current_step' => $current_step,
                'completed_steps' => $completed_steps
            );
            
        } catch (Exception $e) {
            error_log('Calculate detailed form progress error: ' . $e->getMessage());
            return array(
                'percentage' => 0,
                'current_step' => 'خطا در محاسبه',
                'completed_steps' => 0
            );
        }
    }
    
    // تابع شمارش کلیک کاربر روی فرم
    private function get_click_count($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE session_id = %s AND event_type = 'click'", $session_id));
        return $count ? $count : 0;
    }
    
    // دریافت اطلاعات لید (نام، موبایل، ایمیل) از آخرین مقدارهای ثبت شده
    private function get_lead_info($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $full_name = $wpdb->get_var($wpdb->prepare("SELECT element_value FROM $table_name WHERE session_id = %s AND (element_id LIKE '%full_name%' OR element_id LIKE '%نام%') AND element_value IS NOT NULL AND element_value != '' ORDER BY timestamp DESC LIMIT 1", $session_id));
        $phone = $wpdb->get_var($wpdb->prepare("SELECT element_value FROM $table_name WHERE session_id = %s AND (element_id LIKE '%phone%' OR element_id LIKE '%موبایل%') AND element_value IS NOT NULL AND element_value != '' ORDER BY timestamp DESC LIMIT 1", $session_id));
        return array('full_name' => $full_name ?: '', 'phone' => $phone ?: '');
    }
    
    // دریافت همه فیلدهای پرشده فرم برای نمایش لید مارکتینگ
    private function get_all_filled_fields($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $results = $wpdb->get_results($wpdb->prepare("SELECT element_id, element_value FROM $table_name WHERE session_id = %s AND element_value IS NOT NULL AND element_value != '' ORDER BY timestamp ASC", $session_id));
        $fields = array();
        foreach ($results as $row) {
            $label = $this->get_field_persian_name($row->element_id);
            $fields[$label] = $row->element_value;
        }
        return $fields;
    }
    
    // دریافت کشور و استان از اطلاعات موقعیت
    private function get_country_for_session($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $row = $wpdb->get_row($wpdb->prepare("SELECT ip_country FROM $table_name WHERE session_id = %s AND ip_country IS NOT NULL AND ip_country != '' ORDER BY timestamp DESC LIMIT 1", $session_id));
        return $row && $row->ip_country ? $row->ip_country : 'نامشخص';
    }
    
    // دریافت نقطه شروع کاربر (منبع ترافیک)
    private function get_user_entry_point($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // اول بررسی UTM parameters
            $utm_data = $wpdb->get_row($wpdb->prepare("
                SELECT utm_source, utm_campaign, utm_medium, page_referrer 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type = 'page_load'
                ORDER BY timestamp ASC 
                LIMIT 1
            ", $session_id));
            
            if ($utm_data) {
                // اگر UTM source موجود باشد
                if (!empty($utm_data->utm_source)) {
                    $source = $utm_data->utm_source;
                    
                    // تبدیل منابع معروف به فارسی
                    $sources = array(
                        'google' => 'گوگل ادوردز',
                        'facebook' => 'فیسبوک',
                        'instagram' => 'اینستاگرام',
                        'telegram' => 'تلگرام',
                        'whatsapp' => 'واتساپ',
                        'direct' => 'ورود مستقیم',
                        'email' => 'ایمیل مارکتینگ',
                        'sms' => 'پیامک',
                        'organic' => 'جستجوی آزاد گوگل',
                        'cpc' => 'تبلیغات کلیکی',
                        'banner' => 'بنر تبلیغاتی',
                        'social' => 'شبکه‌های اجتماعی',
                        'referral' => 'سایت دیگر'
                    );
                    
                    $persian_source = isset($sources[strtolower($source)]) ? $sources[strtolower($source)] : $source;
                    
                    // اگر کمپین هم موجود باشد اضافه کن
                    if (!empty($utm_data->utm_campaign)) {
                        return $persian_source . ' (' . $utm_data->utm_campaign . ')';
                    }
                    
                    return $persian_source;
                }
                
                // اگر UTM نباشد، بررسی referrer
                if (!empty($utm_data->page_referrer)) {
                    $referrer = $utm_data->page_referrer;
                    
                    if (strpos($referrer, 'google.com') !== false) {
                        if (strpos($referrer, 'gclid') !== false) {
                            return 'گوگل ادوردز';
                        } else {
                            return 'جستجوی گوگل';
                        }
                    } elseif (strpos($referrer, 'facebook.com') !== false) {
                        return 'فیسبوک';
                    } elseif (strpos($referrer, 'instagram.com') !== false) {
                        return 'اینستاگرام';
                    } elseif (strpos($referrer, 't.me') !== false || strpos($referrer, 'telegram') !== false) {
                        return 'تلگرام';
                    } elseif (strpos($referrer, 'youtube.com') !== false) {
                        return 'یوتیوب';
                    } elseif (strpos($referrer, 'linkedin.com') !== false) {
                        return 'لینکدین';
                    } elseif (strpos($referrer, 'twitter.com') !== false) {
                        return 'توییتر';
                    } elseif (strpos($referrer, 'whatsapp.com') !== false) {
                        return 'واتساپ';
                    } else {
                        // Extract domain name
                        $parsed = parse_url($referrer);
                        $host = $parsed['host'] ?? 'نامشخص';
                        return 'سایت: ' . $host;
                    }
                }
            }
            
            return 'ورود مستقیم';
        } catch (Exception $e) {
            return 'خطا در تشخیص';
        }
    }
    
    // نسخه static برای استفاده در توابع static - منبع ترافیک
    private static function get_user_entry_point_static($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // اول بررسی UTM parameters
            $utm_data = $wpdb->get_row($wpdb->prepare("
                SELECT utm_source, utm_campaign, utm_medium, page_referrer 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type = 'page_load'
                ORDER BY timestamp ASC 
                LIMIT 1
            ", $session_id));
            
            if ($utm_data) {
                // اگر UTM source موجود باشد
                if (!empty($utm_data->utm_source)) {
                    $source = $utm_data->utm_source;
                    
                    // تبدیل منابع معروف به فارسی
                    $sources = array(
                        'google' => 'گوگل ادوردز',
                        'facebook' => 'فیسبوک',
                        'instagram' => 'اینستاگرام',
                        'telegram' => 'تلگرام',
                        'whatsapp' => 'واتساپ',
                        'direct' => 'ورود مستقیم',
                        'email' => 'ایمیل مارکتینگ',
                        'sms' => 'پیامک',
                        'organic' => 'جستجوی آزاد گوگل',
                        'cpc' => 'تبلیغات کلیکی',
                        'banner' => 'بنر تبلیغاتی',
                        'social' => 'شبکه‌های اجتماعی',
                        'referral' => 'سایت دیگر'
                    );
                    
                    $persian_source = isset($sources[strtolower($source)]) ? $sources[strtolower($source)] : $source;
                    
                    // اگر کمپین هم موجود باشد اضافه کن
                    if (!empty($utm_data->utm_campaign)) {
                        return $persian_source . ' (' . $utm_data->utm_campaign . ')';
                    }
                    
                    return $persian_source;
                }
                
                // اگر UTM نباشد، بررسی referrer
                if (!empty($utm_data->page_referrer)) {
                    $referrer = $utm_data->page_referrer;
                    
                    if (strpos($referrer, 'google.com') !== false) {
                        return 'جستجوی گوگل';
                    } elseif (strpos($referrer, 'facebook.com') !== false) {
                        return 'فیسبوک';
                    } elseif (strpos($referrer, 'instagram.com') !== false) {
                        return 'اینستاگرام';
                    } elseif (strpos($referrer, 't.me') !== false || strpos($referrer, 'telegram') !== false) {
                        return 'تلگرام';
                    } elseif (strpos($referrer, 'youtube.com') !== false) {
                        return 'یوتیوب';
                    } elseif (strpos($referrer, 'linkedin.com') !== false) {
                        return 'لینکدین';
                    } else {
                        // Extract domain name
                        $parsed = parse_url($referrer);
                        return 'سایت: ' . ($parsed['host'] ?? 'نامشخص');
                    }
                }
            }
            
            return 'ورود مستقیم';
        } catch (Exception $e) {
            return 'خطا در تشخیص';
        }
    }
    
    // نسخه static برای get_drop_off_point
    private static function get_drop_off_point_static($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت آخرین فیلدی که کاربر روی آن کار کرده
            $last_field = $wpdb->get_var($wpdb->prepare("
                SELECT element_id 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type IN ('field_focus', 'field_input', 'field_blur')
                AND element_id IS NOT NULL 
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if ($last_field) {
                return self::get_field_persian_name_static($last_field);
            }
            
            // اگر هیچ فیلدی یافت نشد، چک کنیم که آیا اصلاً page_load داشته یا نه
            $has_page_load = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type = 'page_load'
            ", $session_id));
            
            if ($has_page_load > 0) {
                return 'صفحه فرم (بدون تعامل)';
            }
            
            return 'قبل از ورود به فرم';
        } catch (Exception $e) {
            return 'خطا در تشخیص';
        }
    }
    
    // نسخه static برای get_field_persian_name
    private static function get_field_persian_name_static($element_id) {
        if (empty($element_id)) return '';
        
        $field_names = array(
            'full_name' => 'نام کامل',
            'phone' => 'شماره موبایل',
            'business_name' => 'نام کسب و کار',
            'business_phone' => 'تلفن کسب و کار',
            'province' => 'استان',
            'city' => 'شهر',
            'address' => 'آدرس',
            'manual_address' => 'آدرس دقیق',
            'website' => 'وب سایت',
            'package' => 'انتخاب بسته',
            'payment' => 'پرداخت',
            'latitude' => 'مختصات عرض جغرافیایی',
            'longitude' => 'مختصات طول جغرافیایی',
            'lat' => 'مختصات عرض',
            'lng' => 'مختصات طول',
            'location' => 'انتخاب موقعیت',
            'map_click' => 'کلیک روی نقشه',
            'working_hours' => 'ساعت کاری',
            'working_hours_text' => 'ساعت کاری',
            'selected_packages' => 'انتخاب محصولات',
            'terms' => 'پذیرش قوانین'
        );
        
        // Check exact match first
        if (isset($field_names[$element_id])) {
            return $field_names[$element_id];
        }
        
        // Check partial matches
        foreach ($field_names as $key => $persian_name) {
            if (strpos($element_id, $key) !== false) {
                return $persian_name;
            }
        }
        
        return $element_id; // Return original if no match
    }
    
    // تعیین وضعیت دقیق خروج بر اساس فیلدهای پر شده
    private static function determine_detailed_exit_status($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت همه فیلدهای پر شده
            $filled_fields = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT element_id, element_value
                FROM $table_name 
                WHERE session_id = %s 
                AND element_value IS NOT NULL 
                AND element_value != '' 
                AND TRIM(element_value) != ''
                AND event_type IN ('field_blur', 'field_input')
            ", $session_id));
            
            $has_name = false;
            $has_phone = false;
            $has_business_name = false;
            $has_address = false;
            $has_city = false;
            $has_province = false;
            $total_fields = count($filled_fields);
            
            foreach ($filled_fields as $field) {
                $element_id = strtolower($field->element_id);
                
                if (strpos($element_id, 'full_name') !== false || strpos($element_id, 'name') !== false) {
                    $has_name = true;
                } elseif (strpos($element_id, 'phone') !== false || strpos($element_id, 'mobile') !== false) {
                    $has_phone = true;
                } elseif (strpos($element_id, 'business_name') !== false) {
                    $has_business_name = true;
                } elseif (strpos($element_id, 'address') !== false) {
                    $has_address = true;
                } elseif (strpos($element_id, 'city') !== false) {
                    $has_city = true;
                } elseif (strpos($element_id, 'province') !== false) {
                    $has_province = true;
                }
            }
            
            // منطق تشخیص دقیق وضعیت خروج
            if ($total_fields == 0) {
                return array(
                    'status' => 'فقط بازدید صفحه (بدون تعامل)',
                    'class' => 'no-interaction'
                );
            } elseif ($has_name && $has_phone && $has_business_name && $has_address) {
                return array(
                    'status' => 'پس از پر کردن اطلاعات کامل (قبل از ثبت)',
                    'class' => 'almost-complete'
                );
            } elseif ($has_name && $has_phone && $has_business_name) {
                return array(
                    'status' => 'پس از پر کردن اطلاعات کسب و کار',
                    'class' => 'business-info'
                );
            } elseif ($has_name && $has_phone) {
                return array(
                    'status' => 'پس از پر کردن اطلاعات شخصی',
                    'class' => 'personal-info'
                );
            } elseif ($has_name || $has_phone) {
                return array(
                    'status' => 'در حین پر کردن اطلاعات اولیه',
                    'class' => 'partial-fill'
                );
            } elseif ($total_fields >= 1) {
                return array(
                    'status' => 'شروع پر کردن فرم (ناکامل)',
                    'class' => 'started-form'
                );
            } else {
                return array(
                    'status' => 'نامشخص',
                    'class' => 'unknown'
                );
            }
        } catch (Exception $e) {
            return array(
                'status' => 'خطا در تشخیص',
                'class' => 'error'
            );
        }
    }
    
    // تعیین سطح علاقه‌مندی کاربر بر اساس فیلدهای پر شده و رفتار
    private static function determine_user_interest_level($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // بررسی فیلدهای پر شده مربوط به کسب و کار
            $business_fields = $wpdb->get_results($wpdb->prepare("
                SELECT element_id, element_value
                FROM $table_name 
                WHERE session_id = %s 
                AND (element_id LIKE '%business%' OR element_id LIKE '%کسب%')
                AND element_value IS NOT NULL 
                AND element_value != ''
            ", $session_id));
            
            // بررسی زمان صرف شده
            $time_spent = $wpdb->get_var($wpdb->prepare("
                SELECT UNIX_TIMESTAMP(MAX(timestamp)) - UNIX_TIMESTAMP(MIN(timestamp))
                FROM $table_name 
                WHERE session_id = %s
            ", $session_id));
            
            // شمارش کلیک‌ها و تعاملات
            $interactions = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type IN ('field_focus', 'field_input', 'click')
            ", $session_id));
            
            $has_business_info = count($business_fields) > 0;
            $spent_time = intval($time_spent);
            $interaction_count = intval($interactions);
            
            // منطق تعیین علاقه‌مندی
            if ($has_business_info && $spent_time > 180 && $interaction_count > 10) {
                return 'علاقه‌مند جدی به گسترش کسب‌وکار';
            } elseif ($has_business_info && $spent_time > 60) {
                return 'علاقه‌مند به خدمات کسب‌وکار';
            } elseif ($spent_time > 120 && $interaction_count > 8) {
                return 'در حال بررسی خدمات';
            } elseif ($spent_time > 30 && $interaction_count > 3) {
                return 'علاقه‌مند اولیه';
            } else {
                return 'بازدیدکننده معمولی';
            }
        } catch (Exception $e) {
            return 'نامشخص';
        }
    }
    
    private function get_province_for_session($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $row = $wpdb->get_row($wpdb->prepare("SELECT ip_city FROM $table_name WHERE session_id = %s AND ip_city IS NOT NULL AND ip_city != '' ORDER BY timestamp DESC LIMIT 1", $session_id));
        return $row && $row->ip_city ? $row->ip_city : 'نامشخص';
    }

    // دریافت مرورگر از user agent
    private function get_browser_info($user_agent) {
        if (empty($user_agent)) {
            return '🔒 مخفی';
        }
        
        $user_agent_lower = strtolower($user_agent);
        
        // Chrome
        if (strpos($user_agent_lower, 'chrome') !== false) {
            if (preg_match('/chrome\/(\d+)/', $user_agent_lower, $matches)) {
                return '🌐 Chrome ' . $matches[1];
            } else {
                return '🌐 Chrome';
            }
        }
        
        // Firefox
        if (strpos($user_agent_lower, 'firefox') !== false) {
            if (preg_match('/firefox\/(\d+)/', $user_agent_lower, $matches)) {
                return '🦊 Firefox ' . $matches[1];
            } else {
                return '🦊 Firefox';
            }
        }
        
        // Safari
        if (strpos($user_agent_lower, 'safari') !== false && strpos($user_agent_lower, 'chrome') === false) {
            if (preg_match('/version\/(\d+)/', $user_agent_lower, $matches)) {
                return '🍎 Safari ' . $matches[1];
            } else {
                return '🍎 Safari';
            }
        }
        
        // Edge
        if (strpos($user_agent_lower, 'edge') !== false) {
            if (preg_match('/edge\/(\d+)/', $user_agent_lower, $matches)) {
                return '🌐 Edge ' . $matches[1];
            } else {
                return '🌐 Edge';
            }
        }
        
        // Opera
        if (strpos($user_agent_lower, 'opera') !== false) {
            if (preg_match('/opera\/(\d+)/', $user_agent_lower, $matches)) {
                return '🌐 Opera ' . $matches[1];
            } else {
                return '🌐 Opera';
            }
        }
        
        // Internet Explorer
        if (strpos($user_agent_lower, 'msie') !== false || strpos($user_agent_lower, 'trident') !== false) {
            if (preg_match('/msie (\d+)/', $user_agent_lower, $matches)) {
                return '🌐 IE ' . $matches[1];
            } else {
                return '🌐 IE';
            }
        }
        
        // Mobile browsers
        if (strpos($user_agent_lower, 'mobile') !== false) {
            if (strpos($user_agent_lower, 'chrome') !== false) {
                return '📱 Chrome Mobile';
            } elseif (strpos($user_agent_lower, 'safari') !== false) {
                return '📱 Safari Mobile';
            } else {
                return '📱 مرورگر موبایل';
            }
        }
        
        // Incognito/Private mode detection
        if (strpos($user_agent_lower, 'incognito') !== false || 
            strpos($user_agent_lower, 'private') !== false ||
            strpos($user_agent_lower, 'stealth') !== false) {
            return '🥷 مرورگر مخفی';
        }
        
        if (strlen($user_agent) < 20) {
            return '⚠️ مشکوک';
        }
        
        return '❓ نامشخص';
    }
    
    public function ajax_get_session_data() {
        // امنیت: بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        // امنیت: بررسی nonce
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        // امنیت: sanitize ورودی
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => 'شناسه جلسه نامعتبر است'));
            return;
        }
        
        try {
            $session_data = $this->get_session_summary($session_id);
            wp_send_json_success($session_data);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'خطا در دریافت اطلاعات: ' . esc_html($e->getMessage())));
        }
    }
    
    public function ajax_export_analytics() {
        // امنیت: بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        // امنیت: بررسی nonce
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        // امنیت: sanitize ورودی
        $export_type = sanitize_text_field($_POST['export_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        
        try {
            $this->export_user_data($export_type, $date_from, $date_to);
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'خطا در export: ' . esc_html($e->getMessage())));
        }
    }
    
    public function ajax_clear_old_data() {
        // امنیت: بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        // امنیت: بررسی nonce
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        // امنیت: sanitize ورودی
        $days = intval($_POST['days']);
        
        if ($days < 1 || $days > 365) {
            wp_send_json_error(array('message' => 'تعداد روز نامعتبر است'));
            return;
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_user_tracking';
            
            // امنیت: استفاده از prepared statement
            $deleted = $wpdb->query($wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                date('Y-m-d H:i:s', strtotime("-{$days} days"))
            ));
            
            wp_send_json_success(array(
                'message' => "{$deleted} رکورد قدیمی حذف شد",
                'deleted_count' => $deleted
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'خطا در حذف داده‌ها: ' . esc_html($e->getMessage())));
        }
    }
    
    /**
     * دریافت صفحه قبلی کاربر
     */
    private function get_previous_page($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت دو صفحه آخر
            $pages = $wpdb->get_results($wpdb->prepare("
                SELECT page_url, timestamp
                FROM $table_name 
                WHERE session_id = %s 
                AND page_url IS NOT NULL 
                AND page_url != ''
                ORDER BY timestamp DESC 
                LIMIT 2
            ", $session_id));
            
            if (count($pages) < 2) {
                return 'صفحه اول';
            }
            
            $current_page = $pages[0]->page_url;
            $previous_page = $pages[1]->page_url;
            
            // اگر صفحه قبلی با صفحه فعلی متفاوت باشد
            if ($previous_page !== $current_page) {
                $parsed = parse_url($previous_page);
                $path = isset($parsed['path']) ? $parsed['path'] : '/';
                if ($path === '/') return 'صفحه اصلی';
                if (strpos($path, 'form') !== false || strpos($path, 'register') !== false) return 'فرم ثبت نام';
                return basename($path);
            }
            
            return 'همان صفحه';
        } catch (Exception $e) {
            return 'نامشخص';
        }
    }
    
    /**
     * دریافت فقط استان و شهر (بدون آی‌پی)
     */
    private function get_location_only($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت اطلاعات مکان از IP
            $location_info = $wpdb->get_row($wpdb->prepare("
                SELECT ip_country, ip_region, ip_city
                FROM $table_name 
                WHERE session_id = %s 
                AND (ip_country IS NOT NULL OR ip_region IS NOT NULL OR ip_city IS NOT NULL)
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if ($location_info) {
                $location_parts = array();
                if (!empty($location_info->ip_country)) $location_parts[] = $location_info->ip_country;
                if (!empty($location_info->ip_region)) $location_parts[] = $location_info->ip_region;
                if (!empty($location_info->ip_city)) $location_parts[] = $location_info->ip_city;
                
                if (!empty($location_parts)) {
                    return implode(' - ', $location_parts);
                }
            }
            
            // اگر اطلاعات IP موجود نبود، مختصات جغرافیایی را بررسی کن
            $coordinates = $wpdb->get_row($wpdb->prepare("
                SELECT element_value as latitude FROM $table_name 
                WHERE session_id = %s 
                AND (element_id = 'latitude' OR element_name = 'latitude')
                AND element_value IS NOT NULL AND TRIM(element_value) != ''
                ORDER BY timestamp DESC LIMIT 1
            ", $session_id));
            
            $longitude = $wpdb->get_var($wpdb->prepare("
                SELECT element_value FROM $table_name 
                WHERE session_id = %s 
                AND (element_id = 'longitude' OR element_name = 'longitude')
                AND element_value IS NOT NULL AND TRIM(element_value) != ''
                ORDER BY timestamp DESC LIMIT 1
            ", $session_id));
            
            if ($coordinates && $coordinates->latitude && $longitude) {
                return number_format($coordinates->latitude, 6) . ', ' . number_format($longitude, 6);
            }
            
            return 'نامشخص';
        } catch (Exception $e) {
            return 'نامشخص';
        }
    }
    
    /**
     * دریافت آخرین اقدام به صورت دقیق و معنادار
     */
    private function get_last_action_detailed($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            // دریافت آخرین فعالیت معنادار
            $last_event = $wpdb->get_row($wpdb->prepare("
                SELECT event_type, element_id, element_value, timestamp
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type NOT IN ('heartbeat', 'activity_check', 'mouse_move')
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if (!$last_event) {
                return 'بدون فعالیت';
            }
            
            $field_name = $this->get_field_persian_name($last_event->element_id);
            
            // بررسی زمان آخرین فعالیت
            $tehran_tz = new DateTimeZone('Asia/Tehran');
            $now = new DateTime('now', $tehran_tz);
            $event_time = new DateTime($last_event->timestamp, $tehran_tz);
            $time_since_last = $now->getTimestamp() - $event_time->getTimestamp();
            
            // اگر بیش از 5 دقیقه گذشته
            if ($time_since_last > 300) {
                return 'خروج از سایت';
            }
            
            // نمایش بر اساس نوع فعالیت
            switch ($last_event->event_type) {
                case 'field_focus':
                    return 'فوکوس روی ' . $field_name;
                case 'field_input':
                case 'keystroke':
                    return 'تایپ در ' . $field_name;
                case 'field_blur':
                    return 'تکمیل ' . $field_name;
                case 'page_load':
                    $page = $this->get_current_page($session_id);
                    return 'ورود به ' . $page;
                case 'click':
                    return 'کلیک روی فرم';
                case 'scroll':
                    return 'اسکرول صفحه';
                case 'form_submit':
                    return 'ارسال فرم';
                default:
                    return $this->get_event_label($last_event->event_type);
            }
        } catch (Exception $e) {
            return 'خطا در دریافت';
        }
    }
    
    /**
     * محاسبه امتیاز کاربر (0 تا 100)
     */
    private function calculate_user_score($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $score = 0;
            
            // 1. مدت زمان حضور در سایت (20 امتیاز)
            $time_spent = $wpdb->get_var($wpdb->prepare("
                SELECT TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp))
                FROM $table_name 
                WHERE session_id = %s
            ", $session_id));
            
            if ($time_spent) {
                if ($time_spent > 900) $score += 20;      // بیش از 15 دقیقه = 20 امتیاز
                elseif ($time_spent > 600) $score += 16;  // 10-15 دقیقه = 16 امتیاز
                elseif ($time_spent > 300) $score += 12;  // 5-10 دقیقه = 12 امتیاز
                elseif ($time_spent > 120) $score += 8;   // 2-5 دقیقه = 8 امتیاز
                elseif ($time_spent > 60) $score += 4;    // 1-2 دقیقه = 4 امتیاز
            }
            
            // 2. تعداد مشاهده صفحات (15 امتیاز)
            $page_views = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT page_url)
                FROM $table_name 
                WHERE session_id = %s 
                AND page_url IS NOT NULL
                AND event_type = 'page_load'
            ", $session_id));
            
            if ($page_views >= 5) $score += 15;
            elseif ($page_views >= 4) $score += 12;
            elseif ($page_views >= 3) $score += 9;
            elseif ($page_views >= 2) $score += 6;
            elseif ($page_views >= 1) $score += 3;
            
            // 3. تعاملات با فرم (25 امتیاز)
            $form_interactions = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT element_id)
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type IN ('field_focus', 'field_input', 'field_blur')
                AND element_id NOT LIKE 'button%'
            ", $session_id));
            
            if ($form_interactions >= 10) $score += 25;
            elseif ($form_interactions >= 8) $score += 20;
            elseif ($form_interactions >= 6) $score += 15;
            elseif ($form_interactions >= 4) $score += 10;
            elseif ($form_interactions >= 2) $score += 5;
            
            // 4. میزان پیشرفت فرم (30 امتیاز)
            $form_progress = $this->calculate_detailed_form_progress($session_id);
            $progress_score = min(30, round(($form_progress['progress'] / 100) * 30));
            $score += $progress_score;
            
            // 5. کیفیت تعاملات (10 امتیاز)
            $filled_fields = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT element_id)
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type = 'field_blur'
                AND element_value IS NOT NULL 
                AND TRIM(element_value) != ''
                AND LENGTH(TRIM(element_value)) > 2
            ", $session_id));
            
            if ($filled_fields >= 6) $score += 10;
            elseif ($filled_fields >= 4) $score += 7;
            elseif ($filled_fields >= 2) $score += 4;
            elseif ($filled_fields >= 1) $score += 2;
            
            return min(100, max(0, $score));
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * دریافت وضعیت قبلی و فعلی کاربر
     */
    private function get_user_status_timeline($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        try {
            $events = $wpdb->get_results($wpdb->prepare("
                SELECT event_type, element_id, element_value, timestamp
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type NOT IN ('heartbeat', 'activity_check', 'mouse_move')
                AND event_type IN ('field_focus', 'field_input', 'field_blur', 'click', 'form_submit', 'map_click', 'location_select')
                ORDER BY timestamp DESC 
                LIMIT 2
            ", $session_id));
            
            $current_status = 'بدون فعالیت';
            $previous_status = 'ورود به سایت';
            
            if (!empty($events)) {
                $latest = $events[0];
                $current_status = $this->format_activity_status($latest);
                
                if (count($events) > 1) {
                    $previous = $events[1];
                    $previous_status = $this->format_activity_status($previous);
                }
            }
            
            return array(
                'current' => $current_status,
                'previous' => $previous_status
            );
            
        } catch (Exception $e) {
            return array(
                'current' => 'خطا در دریافت',
                'previous' => 'نامشخص'
            );
        }
    }
    
    /**
     * فرمت کردن وضعیت فعالیت
     */
    private function format_activity_status($event) {
        $field_name = $this->get_field_persian_name($event->element_id);
        
        switch ($event->event_type) {
            case 'field_focus':
                return 'فوکوس روی ' . $field_name;
            case 'field_input':
                if (!empty($event->element_value)) {
                    $value = strlen($event->element_value) > 25 ? 
                        substr($event->element_value, 0, 25) . '...' : 
                        $event->element_value;
                    return 'تایپ در ' . $field_name . ': ' . $value;
                } else {
                    return 'شروع تایپ در ' . $field_name;
                }
            case 'field_blur':
                if (!empty($event->element_value)) {
                    $value = strlen($event->element_value) > 25 ? 
                        substr($event->element_value, 0, 25) . '...' : 
                        $event->element_value;
                    return 'تکمیل ' . $field_name . ': ' . $value;
                } else {
                    return 'خروج از ' . $field_name;
                }
            case 'click':
                if (strpos($event->element_id, 'button_') !== false) {
                    $button_name = $this->get_button_persian_name($event->element_id);
                    return 'کلیک روی دکمه: ' . $button_name;
                } elseif (strpos($event->element_id, 'link_') !== false) {
                    return 'کلیک روی لینک';
                } else {
                    return 'کلیک روی ' . ($field_name ?: 'عنصر');
                }
            case 'scroll':
                return 'اسکرول صفحه';
            case 'mouse_move':
                return 'حرکت موس';
            case 'page_load':
                return 'بارگذاری صفحه';
            case 'form_submit':
                return 'ارسال فرم';
            case 'map_click':
                return 'انتخاب موقعیت روی نقشه';
            case 'location_select':
                return 'انتخاب موقعیت';
            case 'heartbeat':
            default:
                return $this->get_event_label($event->event_type);
        }
    }    
    
    /**
     * دریافت نام فارسی دکمه‌ها
     */
    private function get_button_persian_name($element_id) {
        // ابتدا بررسی کنیم که آیا متن دکمه در دیتابیس ذخیره شده یا نه
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // جستجو برای یافتن متن دکمه از element_value
        $button_text = $wpdb->get_var($wpdb->prepare("
            SELECT element_value 
            FROM $table_name 
            WHERE element_id = %s 
            AND event_type = 'click' 
            AND element_value IS NOT NULL 
            AND element_value != '' 
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $element_id));
        
        if ($button_text && strlen($button_text) > 0) {
            return 'کلیک روی دکمه: ' . $button_text;
        }
        
        // اگر متن دکمه پیدا نشد، از نام‌های پیش‌فرض استفاده کنیم
        $button_names = array(
            'button_0' => 'دکمه ارسال',
            'button_1' => 'دکمه بعدی',
            'button_2' => 'دکمه قبلی',
            'button_submit' => 'دکمه ارسال',
            'button_next' => 'دکمه بعدی',
            'button_prev' => 'دکمه قبلی',
            'button_save' => 'دکمه ذخیره',
            'button_cancel' => 'دکمه لغو',
            'button_confirm' => 'دکمه تایید',
            'button_back' => 'دکمه بازگشت'
        );
        
        if (isset($button_names[$element_id])) {
            return 'کلیک روی ' . $button_names[$element_id];
        }
        
        if (preg_match('/button_(\d+)/', $element_id, $matches)) {
            return 'کلیک روی دکمه شماره ' . $matches[1];
        }
        
        return 'کلیک روی دکمه';
    }
    
    
    // دریافت اطلاعات سیستم عامل    
    private function get_os_info($user_agent) {
        if (empty($user_agent)) {
            return '🔒 مخفی';
        }
        
        $user_agent_lower = strtolower($user_agent);
        
        // Windows
        if (strpos($user_agent_lower, 'windows') !== false) {
            if (strpos($user_agent_lower, 'windows nt 10.0') !== false) {
                return '🪟 Windows 10/11';
            } elseif (strpos($user_agent_lower, 'windows nt 6.3') !== false) {
                return '🪟 Windows 8.1';
            } elseif (strpos($user_agent_lower, 'windows nt 6.2') !== false) {
                return '🪟 Windows 8';
            } elseif (strpos($user_agent_lower, 'windows nt 6.1') !== false) {
                return '🪟 Windows 7';
            } else {
                return '🪟 Windows';
            }
        }
        
        // macOS
        if (strpos($user_agent_lower, 'mac os x') !== false || strpos($user_agent_lower, 'macos') !== false) {
            if (strpos($user_agent_lower, 'mac os x 10_15') !== false || strpos($user_agent_lower, 'macos 10.15') !== false) {
                return '🍎 macOS Catalina';
            } elseif (strpos($user_agent_lower, 'mac os x 10_14') !== false) {
                return '🍎 macOS Mojave';
            } elseif (strpos($user_agent_lower, 'mac os x 10_13') !== false) {
                return '🍎 macOS High Sierra';
            } elseif (strpos($user_agent_lower, 'mac os x 11_') !== false || strpos($user_agent_lower, 'macos 11') !== false) {
                return '🍎 macOS Big Sur';
            } elseif (strpos($user_agent_lower, 'mac os x 12_') !== false || strpos($user_agent_lower, 'macos 12') !== false) {
                return '🍎 macOS Monterey';
            } else {
                return '🍎 macOS';
            }
        }
        
        // Linux
        if (strpos($user_agent_lower, 'linux') !== false) {
            if (strpos($user_agent_lower, 'ubuntu') !== false) {
                return '🐧 Ubuntu';
            } elseif (strpos($user_agent_lower, 'debian') !== false) {
                return '🐧 Debian';
            } elseif (strpos($user_agent_lower, 'fedora') !== false) {
                return '🐧 Fedora';
            } elseif (strpos($user_agent_lower, 'centos') !== false) {
                return '🐧 CentOS';
            } else {
                return '🐧 Linux';
            }
        }
        
        // Android
        if (strpos($user_agent_lower, 'android') !== false) {
            // تشخیص ورژن Android
            if (preg_match('/android (\d+)/', $user_agent_lower, $matches)) {
                return '📱 Android ' . $matches[1];
            } else {
                return '📱 Android';
            }
        }
        
        // iOS
        if (strpos($user_agent_lower, 'iphone') !== false || strpos($user_agent_lower, 'ipad') !== false) {
            // تشخیص ورژن iOS
            if (preg_match('/os (\d+)_(\d+)/', $user_agent_lower, $matches)) {
                return '📱 iOS ' . $matches[1] . '.' . $matches[2];
            } else {
                return '📱 iOS';
            }
        }
        
        // بررسی VPN یا پروکسی (شناسایی برخی نشانه‌ها)
        if (strpos($user_agent_lower, 'vpn') !== false || 
            strpos($user_agent_lower, 'proxy') !== false || 
            strpos($user_agent_lower, 'tor') !== false) {
            return '🔐 پروکسی/VPN';
        }
        
        // بررسی ربات
        if (strpos($user_agent_lower, 'bot') !== false || 
            strpos($user_agent_lower, 'crawler') !== false || 
            strpos($user_agent_lower, 'spider') !== false) {
            return '🤖 ربات';
        }
        
        // مخفی یا نامشخص
        if (strlen($user_agent) < 20) {
            return '⚠️ مشکوک';
        }
        
        return '❓ نامشخص';
    }

    //محاسبه آمار حرفه‌ای برای باکس‌های داشبورد
    private function get_advanced_dashboard_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $tehran_tz = new \DateTimeZone('Asia/Tehran');
        $now = new \DateTime('now', $tehran_tz);
        $today_start = (clone $now)->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $current_time = $now->format('Y-m-d H:i:s');

        // مجموع کاربران یکتا ۲۴ ساعت اخیر
        $total_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
             
        ));

        // مجموع سشن‌ها ۲۴ ساعت اخیر
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
             
        ));

        // مجموع رویدادها ۲۴ ساعت اخیر
        $total_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name WHERE timestamp >= %s AND timestamp <= %s",
             
        ));

        // میانگین زمان بازدید ۲۴ ساعت اخیر
        $avg_duration = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp)) as session_duration
                FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                GROUP BY session_id
            ) as durations",
             
        ));

        return array(
            'total_users' => intval($total_users),
            'total_sessions' => intval($total_sessions),
            'total_events' => intval($total_events),
            'avg_duration' => $avg_duration ? round($avg_duration) : 0,
        );
    }    

    // محاسبه آمار کاربران آنلاین
    private function calculate_online_stats($table_name, $last_15_min) {
        global $wpdb;

        // --- کاربران آنلاین (آخرین فعالیت در ۱۵ دقیقه اخیر) ---
        $online_users = $wpdb->get_results($wpdb->prepare(
            "SELECT session_id, user_agent, MAX(timestamp) as last_activity 
            FROM $table_name 
            WHERE timestamp >= %s 
            GROUP BY session_id 
            ORDER BY last_activity DESC",
            $last_15_min
        ));

        // --- همه کاربران امروز (صرف نظر از آنلاین بودن) ---
        $today = date('Y-m-d');
        $today_entries = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
            WHERE DATE(timestamp) = %s",
            $today
        ));
        $total_today_users = count($all_today_users);
        $total_online_users = count($online_users);
        $total_offline_users = max(0, $total_today_users - $total_online_users);

        // --- شمارش دستگاه‌ها و وضعیت برای کاربران آنلاین ---
        $mobile_count_online = 0;
        $desktop_count_online = 0;
        $active_users = 0;
        $idle_users = 0;
        $total_remaining_time = 0;
        $last_activity_times = [];
        foreach ($online_users as $user) {
            // تشخیص نوع دستگاه
            if (preg_match('/Mobile|Android|iPhone|iPad/', $user->user_agent)) {
                $mobile_count_online++;
            } else {
                $desktop_count_online++;
            }
            // محاسبه زمان باقی‌مانده و وضعیت
            $last_activity_time = strtotime($user->last_activity);
            $current_time = time();
            $elapsed = $current_time - $last_activity_time;
            $remaining = max(0, 900 - $elapsed); // 900 ثانیه = 15 دقیقه
            $total_remaining_time += $remaining;
            $last_activity_times[] = $elapsed;
            // تشخیص وضعیت: اگر کمتر از 2 دقیقه فعالیت نداشته = منتظر
            if ($elapsed < 120) { // 2 دقیقه
                $active_users++;
            } else {
                $idle_users++;
            }
        }
        $avg_remaining = $total_online_users > 0 ? $total_remaining_time / $total_online_users : 0;
        $avg_remaining_formatted = sprintf('%d:%02d', floor($avg_remaining / 60), $avg_remaining % 60);
        $avg_last_activity_online = $total_online_users > 0 ? array_sum($last_activity_times) / $total_online_users : 0;
        $avg_last_activity_online_formatted = sprintf('%d:%02d', floor($avg_last_activity_online / 60), $avg_last_activity_online % 60);

        // --- شمارش دستگاه‌ها برای کل کاربران امروز ---
        $mobile_count_today = 0;
        $desktop_count_today = 0;
        foreach ($all_today_users as $user) {
            if (preg_match('/Mobile|Android|iPhone|iPad/', $user->user_agent)) {
                $mobile_count_today++;
            } else {
                $desktop_count_today++;
            }
        }

        // --- صفحات بازدید شده امروز ---
        $page_views_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name AND event_type = 'page_load'",
             
        ));

        // --- میانگین زمان حضور کاربران امروز ---
        $avg_session_time_today = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(session_duration) FROM (
                SELECT session_id, TIMESTAMPDIFF(SECOND, MIN(timestamp), MAX(timestamp)) as session_duration
                FROM $table_name
                WHERE timestamp >= %s AND timestamp <= %s
                GROUP BY session_id
            ) as durations",
             
        ));
        $avg_session_time_today = $avg_session_time_today ? intval($avg_session_time_today) : 0;
        $avg_session_time_today_formatted = sprintf('%02d:%02d:%02d', floor($avg_session_time_today/3600), floor(($avg_session_time_today%3600)/60), $avg_session_time_today%60);
        // --- درصدها ---
        $mobile_percent_online = $total_online_users > 0 ? round(($mobile_count_online / $total_online_users) * 100, 1) : 0;
        $desktop_percent_online = $total_online_users > 0 ? round(($desktop_count_online / $total_online_users) * 100, 1) : 0;
        $mobile_percent_today = $total_today_users > 0 ? round(($mobile_count_today / $total_today_users) * 100, 1) : 0;
        $desktop_percent_today = $total_today_users > 0 ? round(($desktop_count_today / $total_today_users) * 100, 1) : 0;
        $active_percent = $total_online_users > 0 ? round(($active_users / $total_online_users) * 100, 1) : 0;
        $idle_percent = $total_online_users > 0 ? round(($idle_users / $total_online_users) * 100, 1) : 0;
        $online_percent_of_today = $total_today_users > 0 ? round(($total_online_users / $total_today_users) * 100, 1) : 0;
        $offline_percent_of_today = $total_today_users > 0 ? round(($total_offline_users / $total_today_users) * 100, 1) : 0;

        // --- ورودی امروز (کل کاربران یکتا امروز) ---
        $today_entries = $total_today_users;

        return array(
            // کاربران آنلاین و آفلاین
            'total_today_users' => $total_today_users,
            'total_online_users' => $total_online_users,
            'total_offline_users' => $total_offline_users,
            'online_percent_of_today' => $online_percent_of_today,
            'offline_percent_of_today' => $offline_percent_of_today,
            // دستگاه‌ها (آنلاین و کل امروز)
            'mobile_count_online' => $mobile_count_online,
            'desktop_count_online' => $desktop_count_online,
            'mobile_percent_online' => $mobile_percent_online,
            'desktop_percent_online' => $desktop_percent_online,
            'mobile_count_today' => $mobile_count_today,
            'desktop_count_today' => $desktop_count_today,
            'mobile_percent_today' => $mobile_percent_today,
            'desktop_percent_today' => $desktop_percent_today,
            // وضعیت فعلی آنلاین/منتظر
            'active_users' => $active_users,
            'idle_users' => $idle_users,
            'active_percent' => $active_percent,
            'idle_percent' => $idle_percent,
            // میانگین زمان باقی‌مانده آنلاین‌ها
            'avg_remaining_time' => $avg_remaining_formatted,
            'avg_last_activity_online' => $avg_last_activity_online_formatted,
            // آمار امروز
            'today_entries' => (int) $today_entries,
            'page_views_today' => (int) $page_views_today,
            // میانگین زمان حضور امروز
            'avg_session_time_today' => $avg_session_time_today_formatted,
        );
    }

    /**
     * محاسبه آمار 24 ساعت اخیر
     */
    private function calculate_24h_activity($table_name, $last_24h) {
        global $wpdb;
        
        // کل بازدیدکنندگان 24 ساعت
        $total_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s",
            $last_24h
        ));
        
        // فرم‌های تکمیل شده
        $completed_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s 
             AND event_type = 'form_completed'",
            $last_24h
        ));
        
        // کاربران با شماره تلفن (لیدهای جدید)
        $new_leads_24h = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s 
             AND (element_id = 'phone' OR element_id = 'business_phone') 
             AND event_type = 'field_completed'",
            $last_24h
        ));
        
        // ناتمام‌ها
        $incomplete_24h = max(0, $total_24h - $completed_24h);
        
        $completed_24h_percent = $total_24h > 0 ? round(($completed_24h / $total_24h) * 100, 1) : 0;
        $incomplete_24h_percent = $total_24h > 0 ? round(($incomplete_24h / $total_24h) * 100, 1) : 0;
        
        return array(
            'total_24h' => (int) $total_24h,
            'completed_24h' => (int) $completed_24h,
            'incomplete_24h' => (int) $incomplete_24h,
            'new_leads_24h' => (int) $new_leads_24h,
            'completed_24h_percent' => $completed_24h_percent,
            'incomplete_24h_percent' => $incomplete_24h_percent
        );
    }

    /**
     * محاسبه کیفیت ترافیک
     */
    private function calculate_traffic_quality($table_name, $today_start) {
        global $wpdb;
        
        // کل ترافیک امروز
        $total_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s",
            $today_start
        ));
        
        // ربات‌های مشکوک (بر اساس الگوهای غیرطبیعی)
        $suspicious_bots = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
            WHERE created_at >= %s 
            AND (
                user_agent LIKE '%%bot%%'
                OR user_agent LIKE '%%spider%%'
                OR user_agent LIKE '%%crawl%%'
                OR user_agent LIKE '%%headless%%'
                OR user_agent LIKE '%%python%%'
                OR user_agent LIKE '%%curl%%'
                OR user_agent LIKE '%%wget%%'
                OR user_agent LIKE '%%scrapy%%'
                OR user_agent LIKE '%%selenium%%'
                OR user_agent LIKE '%%puppeteer%%'
                OR user_agent LIKE '%%phantomjs%%'
                OR (LENGTH(user_agent) < 20)
            )",
            $today_start
        ));
        
        // بلاک شده‌ها امروز (از جدول دستگاه‌های بلاک شده)
        $blocked_table = $wpdb->prefix . 'market_google_blocked_devices';
        $blocked_today = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$blocked_table'") == $blocked_table) {
            $blocked_today = $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(attempt_count), 0) FROM $blocked_table 
                 WHERE DATE(last_attempt) = %s AND is_active = 1",
                date('Y-m-d')
            ));
        }
        
        $real_traffic = max(0, $total_today - $suspicious_bots);
        $real_traffic_percent = $total_today > 0 ? round(($real_traffic / $total_today) * 100, 1) : 100;
        $bots_percent = $total_today > 0 ? round(($suspicious_bots / $total_today) * 100, 1) : 0;
        $blocked_percent = $total_today > 0 ? round(($blocked_today / $total_today) * 100, 1) : 0;
        
        // تعیین کیفیت کلی
        if ($real_traffic_percent >= 90) {
            $quality_class = 'excellent';
            $quality_text = 'عالی';
        } elseif ($real_traffic_percent >= 80) {
            $quality_class = 'good';
            $quality_text = 'خوب';
        } elseif ($real_traffic_percent >= 70) {
            $quality_class = 'average';
            $quality_text = 'متوسط';
        } else {
            $quality_class = 'poor';
            $quality_text = 'ضعیف';
        }
        
        return array(
            'real_traffic' => (int) $real_traffic,
            'real_traffic_percent' => $real_traffic_percent,
            'suspicious_bots' => (int) $suspicious_bots,
            'bots_percent' => $bots_percent,
            'blocked_today' => (int) $blocked_today,
            'blocked_percent' => $blocked_percent,
            'quality_class' => $quality_class,
            'quality_text' => $quality_text
        );
    }

    public function ajax_referrer_suggest() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $table = $wpdb->prefix . 'market_google_user_tracking';
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT referrer FROM $table WHERE referrer LIKE %s AND referrer != '' ORDER BY referrer LIMIT 20",
            '%' . $wpdb->esc_like($q) . '%'
        ));
        wp_send_json(['suggestions' => array_map('esc_html', $results)]);
    }

    public function ajax_location_suggest() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $table = $wpdb->prefix . 'market_google_user_tracking';
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT location FROM $table WHERE location LIKE %s AND location != '' ORDER BY location LIMIT 20",
            '%' . $wpdb->esc_like($q) . '%'
        ));
        wp_send_json(['suggestions' => array_map('esc_html', $results)]);
    }

    public function ajax_pages_suggest() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $table = $wpdb->prefix . 'market_google_user_tracking';
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT page_url FROM $table WHERE page_url LIKE %s AND page_url != '' ORDER BY page_url LIMIT 20",
            '%' . $wpdb->esc_like($q) . '%'
        ));
        wp_send_json(['suggestions' => array_map('esc_html', $results)]);
    }

    /**
     * محاسبه نرخ تبدیل پیشرفته
     */
    private function calculate_advanced_conversion($table_name, $last_24h) {
        global $wpdb;
        
        // نرخ تبدیل کلی
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s",
            $last_24h
        ));
        
        $completed_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s 
             AND event_type = 'form_completed'",
            $last_24h
        ));
        
        $conversion_rate = $total_sessions > 0 ? round(($completed_sessions / $total_sessions) * 100, 1) : 0;
        
        // بهترین منطقه (بر اساس IP)
        $region_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                SUBSTRING_INDEX(SUBSTRING_INDEX(user_ip, '.', 2), '.', -1) as region_prefix,
                COUNT(DISTINCT session_id) as total,
                SUM(CASE WHEN event_type = 'form_completed' THEN 1 ELSE 0 END) as completed
             FROM $table_name 
             WHERE created_at >= %s 
             GROUP BY region_prefix 
             HAVING total >= 5 
             ORDER BY (completed / total) DESC 
             LIMIT 1",
            $last_24h
        ));
        
        $best_region = 'نامشخص';
        $best_region_rate = 0;
        if (!empty($region_stats)) {
            $best_region = 'منطقه ' . $region_stats[0]->region_prefix . '.x.x';
            $best_region_rate = round(($region_stats[0]->completed / $region_stats[0]->total) * 100, 1);
        }
        
        // نرخ تبدیل موبایل vs دسکتاپ
        $mobile_conversion = $this->calculate_device_conversion($table_name, $last_24h, 'mobile');
        $desktop_conversion = $this->calculate_device_conversion($table_name, $last_24h, 'desktop');
        
        // بهترین ساعت
        $best_hour = $wpdb->get_var($wpdb->prepare(
            "SELECT HOUR(created_at) as hour
             FROM $table_name 
             WHERE created_at >= %s 
             AND event_type = 'form_completed'
             GROUP BY HOUR(created_at) 
             ORDER BY COUNT(*) DESC 
             LIMIT 1",
            $last_24h
        ));
        
        $best_hour_formatted = $best_hour ? sprintf('%02d:00-%02d:00', $best_hour, $best_hour + 1) : 'نامشخص';
        
        return array(
            'conversion_rate' => $conversion_rate,
            'best_region' => $best_region,
            'best_region_rate' => $best_region_rate,
            'mobile_conversion' => $mobile_conversion,
            'desktop_conversion' => $desktop_conversion,
            'best_hour' => $best_hour_formatted
        );
    }

    /**
     * محاسبه نرخ تبدیل بر اساس نوع دستگاه
     */
    private function calculate_device_conversion($table_name, $last_24h, $device_type) {
        global $wpdb;
        
        $mobile_pattern = "(user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%')";
        $desktop_pattern = "user_agent NOT LIKE '%Mobile%' AND user_agent NOT LIKE '%Android%' AND user_agent NOT LIKE '%iPhone%'";
        
        $device_condition = $device_type == 'mobile' ? $mobile_pattern : $desktop_pattern;
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s 
             AND $device_condition",
            $last_24h
        ));
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM $table_name 
             WHERE created_at >= %s 
             AND event_type = 'form_completed'
             AND $device_condition",
            $last_24h
        ));
        
        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
    }
    
    /**
     * AJAX handler برای بررسی وضعیت کاربر و انتقال به لیست مناسب
     */
    public function ajax_check_user_status_for_transfer() {
        // امنیت: بررسی دسترسی
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        
        // امنیت: بررسی nonce
        check_ajax_referer('market_tracking_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($session_id)) {
            wp_send_json_error('شناسه سشن نامعتبر است');
            return;
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_user_tracking';
            
            // بررسی آخرین فعالیت کاربر
            $last_activity = $wpdb->get_var($wpdb->prepare("
                SELECT MAX(timestamp) 
                FROM $table_name 
                WHERE session_id = %s
            ", $session_id));
            
            if (!$last_activity) {
                wp_send_json_error('کاربر یافت نشد');
                return;
            }
            
            $last_activity_time = strtotime($last_activity);
            $current_time = time();
            $time_diff = $current_time - $last_activity_time;
            
            // اگر بیش از 15 دقیقه گذشته، کاربر را انتقال بده
            if ($time_diff > 900) { // 15 دقیقه = 900 ثانیه
                
                // بررسی وضعیت فرم کاربر
                $form_progress = $this->calculate_detailed_form_progress($session_id);
                $has_phone = $this->user_has_phone_number($session_id);
                $has_form_data = $this->user_has_form_data($session_id);
                
                // تصمیم‌گیری برای انتقال به لیست مناسب
                if ($form_progress['percentage'] >= 80) {
                    // کاربران تکمیل شده
                    $this->move_user_to_completed_list($session_id);
                    wp_send_json_success('کاربر به لیست تکمیل شده منتقل شد');
                } elseif ($has_phone && $has_form_data) {
                    // لیدهای مارکتینگ
                    $this->move_user_to_marketing_leads($session_id);
                    wp_send_json_success('کاربر به لیست لیدهای مارکتینگ منتقل شد');
                } else {
                    // بازدیدکنندگان ناتمام
                    $this->move_user_to_incomplete_list($session_id);
                    wp_send_json_success('کاربر به لیست بازدیدکنندگان ناتمام منتقل شد');
                }
            } else {
                wp_send_json_error('کاربر هنوز آنلاین است');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('خطا در بررسی وضعیت کاربر: ' . $e->getMessage());
        }
    }
    
    /**
     * بررسی اینکه آیا کاربر شماره تلفن دارد
     */
    private function user_has_phone_number($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        $phone_data = $wpdb->get_var($wpdb->prepare("
            SELECT element_value 
            FROM $table_name 
            WHERE session_id = %s 
            AND (element_id = 'phone' OR element_name = 'phone')
            AND element_value IS NOT NULL 
            AND TRIM(element_value) != ''
            ORDER BY timestamp DESC 
            LIMIT 1
        ", $session_id));
        
        return !empty($phone_data);
    }
    
    /**
     * بررسی اینکه آیا کاربر داده‌ای در فرم دارد
     */
    private function user_has_form_data($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        $form_data_count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM $table_name 
            WHERE session_id = %s 
            AND element_value IS NOT NULL 
            AND TRIM(element_value) != ''
            AND element_id IN ('full_name', 'business_name', 'phone', 'province', 'city')
        ", $session_id));
        
        return $form_data_count > 0;
    }
    
    /**
     * انتقال کاربر به لیست تکمیل شده
     */
    private function move_user_to_completed_list($session_id) {
        // اینجا می‌توانید منطق انتقال به لیست تکمیل شده را پیاده‌سازی کنید
        // مثلاً ذخیره در جدول جداگانه یا تغییر وضعیت
        error_log("User $session_id moved to completed list");
    }
    
    /**
     * انتقال کاربر به لیست لیدهای مارکتینگ
     */
    private function move_user_to_marketing_leads($session_id) {
        // اینجا می‌توانید منطق انتقال به لیست لیدهای مارکتینگ را پیاده‌سازی کنید
        error_log("User $session_id moved to marketing leads list");
    }
    
    /**
     * انتقال کاربر به لیست بازدیدکنندگان ناتمام
     */
    private function move_user_to_incomplete_list($session_id) {
        // اینجا می‌توانید منطق انتقال به لیست بازدیدکنندگان ناتمام را پیاده‌سازی کنید
        error_log("User $session_id moved to incomplete list");
    }

    // نمایش بازه زمانی فقط امروز
    private function get_recent_sessions_24h() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        $tehran_tz = new \DateTimeZone('Asia/Tehran');
        $now = new \DateTime('now', $tehran_tz);
        $today_start = $now->format('Y-m-d') . ' 00:00:00';
        $current_time = (new \DateTime('now', $tehran_tz))->format('Y-m-d H:i:s');

        $query = $wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY timestamp DESC",
            $today_start, $current_time
        );
        return $wpdb->get_results($query);
    }
    
    private function get_user_entry_time($session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_user_tracking';
        
        // دریافت اولین فعالیت کاربر
        $first_activity = $wpdb->get_var($wpdb->prepare("
            SELECT created_at 
            FROM $table_name 
            WHERE session_id = %s 
            ORDER BY created_at ASC 
            LIMIT 1
        ", $session_id));
        
        if (!$first_activity) {
            return 'نامشخص';
        }
        
        // تنظیم timezone به تهران
        date_default_timezone_set('Asia/Tehran');
        
        // تبدیل تاریخ به timestamp
        $timestamp = strtotime($first_activity);
        
        // گرفتن اجزای تاریخ میلادی
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);
        $day = date('d', $timestamp);
        
        // دریافت تاریخ شمسی (رشته‌ای مانند YYYY/MM/DD)
        $jalali_str = $this->gregorian_to_jalali($year, intval($month), intval($day));
        // ترکیب تاریخ شمسی و زمان
        return $jalali_str . ' ' . date('H:i:s', $timestamp);
    }

    public function insert_fake_tracking_data() {
        if (!current_user_can('manage_options')) {
            wp_die('دسترسی غیرمجاز');
        }
        // فراخوانی متد اصلی درج داده فیک با ساختار صحیح جدول
        if (class_exists('Market_Google_User_Tracking')) {
            $tracker = new Market_Google_User_Tracking();
            $result = $tracker->insert_fake_data_bulk(150); // تعداد دلخواه
            if ($result) {
                wp_die('دیتای تستی با موفقیت اضافه شد!');
            } else {
                wp_die('درج داده تستی با خطا مواجه شد. لطفاً لاگ خطا را بررسی کنید.');
            }
        } else {
            wp_die('کلاس ردیابی کاربران پیدا نشد!');
        }
    }

    public function ajax_ip_suggest() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        global $wpdb;
        $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $table = $wpdb->prefix . 'market_google_user_tracking';
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT ip FROM $table WHERE ip LIKE %s AND ip REGEXP '^[0-9]{1,3}(\\.[0-9]{1,3}){3}$' AND ip != '' ORDER BY ip LIMIT 20",
            '%' . $wpdb->esc_like($q) . '%'
        ));
        wp_send_json(['suggestions' => array_map('esc_html', $results)]);
    }
    
    // تابع تبدیل تاریخ میلادی به شمسی
    private function gregorian_to_jalali($gy, $gm, $gd) {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * ((int)($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int)($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int)($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int)(($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
    }
    
    /**
     * AJAX handler برای دریافت جزئیات کامل کاربر
     */
    public function ajax_get_user_session_details() {
        check_ajax_referer('market_google_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'دسترسی غیرمجاز'));
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        
        if (empty($session_id)) {
            wp_send_json_error(array('message' => 'شناسه جلسه وارد نشده'));
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'market_google_user_tracking';
            
            // دریافت اطلاعات کلی
            $user_name = $this->get_enhanced_user_display_name($session_id);
            $user_score = $this->calculate_user_score($session_id);
            $form_progress = $this->calculate_detailed_form_progress($session_id);
            
            // دریافت اطلاعات فنی
            $session_info = $wpdb->get_row($wpdb->prepare("
                SELECT user_ip, user_agent, ip_country, ip_city, ip_region, timestamp as last_activity
                FROM $table_name 
                WHERE session_id = %s 
                AND user_ip IS NOT NULL
                ORDER BY timestamp DESC 
                LIMIT 1
            ", $session_id));
            
            if (!$session_info) {
                wp_send_json_error(array('message' => 'اطلاعات جلسه یافت نشد'));
            }
            
            // Debug: بررسی وجود IP
            if (empty($session_info->user_ip)) {
                // اگر IP خالی بود، از هر رکوردی که موجود است استفاده کن
                $session_info_alt = $wpdb->get_row($wpdb->prepare("
                    SELECT user_ip, user_agent, ip_country, ip_city, ip_region, timestamp as last_activity
                    FROM $table_name 
                    WHERE session_id = %s 
                    ORDER BY timestamp DESC 
                    LIMIT 1
                ", $session_id));
                
                if ($session_info_alt && !empty($session_info_alt->user_ip)) {
                    $session_info = $session_info_alt;
                }
            }
            
            $browser = $this->get_browser_info($session_info->user_agent);
            $os = $this->get_os_info($session_info->user_agent);
            $device_type = $this->get_device_info($session_info->user_agent);
            
            // دریافت اطلاعات مکان
            $location_parts = array();
            if (!empty($session_info->ip_country)) $location_parts[] = $session_info->ip_country;
            if (!empty($session_info->ip_region)) $location_parts[] = $session_info->ip_region;
            if (!empty($session_info->ip_city)) $location_parts[] = $session_info->ip_city;
            $full_location = !empty($location_parts) ? implode(' - ', $location_parts) : 'نامشخص';
            
            // دریافت مختصات جغرافیایی
            $coordinates = $this->get_location_only($session_id);
            
            // دریافت لاگ کامل فعالیت‌ها
            $activities = $wpdb->get_results($wpdb->prepare("
                SELECT event_type, element_id, element_value, timestamp, page_url
                FROM $table_name 
                WHERE session_id = %s 
                AND event_type NOT IN ('heartbeat', 'activity_check', 'mouse_move')
                ORDER BY timestamp ASC
            ", $session_id));
            
            // دریافت اطلاعات فرم پر شده
            $filled_fields = $this->get_all_filled_fields($session_id);
            
            // دریافت اطلاعات دستگاه
            $device_fingerprint = $this->get_device_fingerprint_for_session($session_info);
            
            // بررسی مرورگر مخفی
            $is_incognito = stripos($session_info->user_agent, 'incognito') !== false || 
                           stripos($session_info->user_agent, 'private') !== false ||
                           stripos($session_info->user_agent, 'headless') !== false;
            
            // مقدار فیلد fullname را واکشی کن:
            $fullname = '';
            foreach ($filled_fields as $field_name => $field_value) {
                if (stripos($field_name, 'نام کامل') !== false || stripos($field_name, 'fullname') !== false) {
                    $fullname = $field_value;
                    break;
                }
            }
            if (empty($fullname)) {
                $fullname = $user_name;
            }
            
            // ساخت HTML مودال با دیزاین مدرن
            ob_start();
            ?>
            <div class="modern-modal" id="user-details-modal">
                <div class="modal-overlay"></div>
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>جزئیات کاربر: <?php echo esc_html($fullname); ?></h3>
                        <button type="button" class="modal-close">&times;</button>
                    </div>
                    
                    <div class="modal-content">
                        <!-- اطلاعات کلی کاربر -->
                        <div class="user-main-details">
                            <h3>اطلاعات کلی</h3>
                            <div class="user-info-section">
                                <div class="info-group">
                                    <div class="info-label">نام کاربر:</div>
                                    <div class="info-value"><?php echo esc_html($fullname); ?></div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">امتیاز کاربر:</div>
                                    <div class="info-value">
                                        <span class="user-score"><?php echo $user_score; ?>/100</span>
                                    </div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">پیشرفت فرم:</div>
                                    <div class="info-value">
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $form_progress['percentage']; ?>%"></div>
                                        </div>
                                        <span class="progress-text"><?php echo $form_progress['percentage']; ?>% (مرحله: <?php echo isset($form_progress['current_step']) ? esc_html($form_progress['current_step']) : 'نامشخص'; ?>)</span>
                                    </div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">آخرین فعالیت:</div>
                                    <div class="info-value"><?php 
                                        // محاسبه دقیق آخرین فعالیت
                                        $tehran_tz = new DateTimeZone('Asia/Tehran');
                                        $now = new DateTime('now', $tehran_tz);
                                        $last_time = new DateTime($session_info->last_activity, $tehran_tz);
                                        $diff = $now->diff($last_time);
                                        
                                        if ($diff->days > 0) {
                                            echo $diff->days . ' روز پیش';
                                        } elseif ($diff->h > 0) {
                                            echo $diff->h . ' ساعت پیش';
                                        } elseif ($diff->i > 0) {
                                            echo $diff->i . ' دقیقه پیش';
                                        } else {
                                            echo $diff->s . ' ثانیه پیش';
                                        }
                                    ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- اطلاعات فنی -->
                        <div class="user-main-details">
                            <h3>اطلاعات فنی</h3>
                            <div class="user-info-section">
                                <div class="info-group">
                                    <div class="info-label">آدرس IP:</div>
                                    <div class="info-value copyable" data-clipboard="<?php echo esc_attr($session_info->user_ip ?? 'نامشخص'); ?>">
                                        <?php echo esc_html($session_info->user_ip ?? 'نامشخص'); ?>
                                    </div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">مکان IP:</div>
                                    <div class="info-value">
                                        <?php echo esc_html($full_location); ?>
                                    </div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">مرورگر:</div>
                                    <div class="info-value">
                                        <?php 
                                        echo esc_html($browser);
                                        if ($is_incognito) {
                                            echo ' <span class="incognito-badge-small">مخفی</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">سیستم عامل:</div>
                                    <div class="info-value"><?php echo esc_html($os); ?></div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">نوع دستگاه:</div>
                                    <div class="info-value"><?php echo esc_html($device_type); ?></div>
                                </div>
                                
                                <div class="info-group">
                                    <div class="info-label">شناسه یکتا دستگاه:</div>
                                    <div class="info-value copyable" data-clipboard="<?php echo esc_attr($device_fingerprint); ?>">
                                        <?php echo esc_html($device_fingerprint); ?>
                                    </div>
                                </div>
                                
                                <?php if ($coordinates !== 'نامشخص'): ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- اطلاعات مارکتینگ و UX -->
                        <div class="user-main-details">
                            <h3>اطلاعات مارکتینگ و UX</h3>
                            <div class="user-info-section">
                                <div class="info-group">
                                    <div class="info-label">منبع ورود:</div>
                                    <div class="info-value"><?php echo esc_html($this->get_user_entry_point($session_id)); ?></div>
                                </div>                            
                                <div class="info-group">
                                    <div class="info-label">نقطه خروج از فرم:</div>
                                    <div class="info-value"><?php echo esc_html($this->get_drop_off_point($session_id)); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- فیلدهای پر شده -->
                        <?php if (!empty($filled_fields)): ?>
                        <div class="user-main-details">
                            <h3>اطلاعات وارد شده</h3>
                            <div class="user-info-section">
                                <?php foreach ($filled_fields as $field_name => $field_value): ?>
                                <div class="info-group">
                                    <div class="info-label"><?php echo esc_html($field_name); ?>:</div>
                                    <div class="info-value copyable" data-clipboard="<?php echo esc_attr($field_value); ?>">
                                        <?php echo esc_html($field_value); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- تاریخچه فعالیت‌ها -->
                        <div class="user-main-details">
                            <h3>تاریخچه فعالیت‌ها</h3>
                            <div class="activity-timeline">
                                <?php if (!empty($activities)): ?>
                                    <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-time">
                                            <?php echo date('H:i:s', strtotime($activity->timestamp)); ?>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-action">
                                                <?php echo $this->format_activity_status($activity); ?>
                                            </div>
                                            <?php if (!empty($activity->element_id)): ?>
                                            <div class="activity-field">فیلد: <?php echo esc_html($this->get_field_persian_name($activity->element_id)); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($activity->element_value)): ?>
                                            <div class="activity-value">مقدار: <?php echo esc_html(strlen($activity->element_value) > 50 ? substr($activity->element_value, 0, 50) . '...' : $activity->element_value); ?></div>
                                            <?php endif; ?>
                                            <div class="activity-page">صفحه: <?php echo esc_html($activity->page_url); ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p>هیچ فعالیتی ثبت نشده است.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html,
                'user_name' => $fullname,
                'user_score' => $user_score,
                'ip' => $session_info->user_ip,
                'browser' => $browser,
                'os' => $os,
                'location' => $full_location,
                'form_progress' => $form_progress['percentage'],
                'last_activity' => $this->time_ago($session_info->last_activity)
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'خطا در دریافت اطلاعات: ' . $e->getMessage()));
        }
    }
 }