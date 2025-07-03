<?php

/**
 * Device Manager Admin Page
 * مدیریت دستگاه‌ها و بلاک کردن
 */

if (!defined('ABSPATH')) {
    exit;
}

class Market_Google_Device_Manager {
    
    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_get_device_details', array($this, 'get_device_details'));
        add_action('wp_ajax_market_google_add_whitelist', array($this, 'ajax_add_whitelist'));
        add_action('wp_ajax_market_google_remove_suspicious_ip', array($this, 'ajax_remove_suspicious_ip'));
        add_action('wp_ajax_market_google_remove_whitelist', array($this, 'ajax_remove_whitelist'));
    }
    
    /**
     * اضافه کردن scripts و styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'market-google-location_page_market-google-device-manager') {
            return;
        }
        
        // بارگذاری jQuery
        wp_enqueue_script('jquery');
        
        // بارگذاری CSS مخصوص Device Manager
        wp_enqueue_style(
            'market-google-device-manager',
            plugin_dir_url(__FILE__) . 'css/market-google-device-manager.css',
            array(),
            '1.0.0'
        );
        
        // بارگذاری JavaScript Device Manager
        wp_enqueue_script(
            'market-google-device-manager-admin',
            plugin_dir_url(__FILE__) . 'js/market-google-device-manager.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // ارسال داده‌های AJAX
        wp_localize_script('market-google-device-manager-admin', 'marketGoogleDeviceManager', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('market_google_admin_nonce'),
        ));
    }
    
    /**
     * محتوی صفحه admin
     */
    public function admin_page_content() {
        // Create blocker instance if not exists
        if (!class_exists('Market_Google_Device_Blocker')) {
            echo '<div class="wrap"><h1>مدیریت دستگاه‌ها</h1><p>کلاس Device Blocker یافت نشد.</p></div>';
            return;
        }
        
        $blocker = new Market_Google_Device_Blocker();
        $block_stats = Market_Google_Device_Blocker::get_block_stats();
        $attack_patterns = Market_Google_Device_Blocker::analyze_attack_patterns();
        $settings = $blocker->get_settings();
        
        global $wpdb;
        $whitelist_table = $wpdb->prefix . 'market_google_tracking_whitelist';
        $whitelist = $wpdb->get_results("SELECT * FROM $whitelist_table ORDER BY added_at DESC");
        
        ?>
        <div class="wrap">
            <h1>🛡️ مدیریت دستگاه‌ها و بلاک کردن ربات‌ها</h1>
            
            <div class="notice notice-info">
                <p><strong>قابلیت‌های این صفحه:</strong></p>
                <ul>
                    <li>🤖 تشخیص و بلاک خودکار ربات‌ها</li>
                    <li>📱 مدیریت دستگاه‌های موبایل و دسکتاپ</li>
                    <li>🌐 بلاک IP های مشکوک</li>
                    <li>🔍 تشخیص User Agent های فیک</li>
                    <li>🎯 محافظت در برابر کلیک فیک Google Ads</li>
                </ul>
            </div>
            
            <!-- آمار بلاک -->
            <div class="device-stats-grid">
                <div class="stat-card">
                    <h3>🚫 کل موارد بلاک شده</h3>
                    <div class="stat-number"><?php echo number_format($block_stats['total_blocked']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>🌐 IP های بلاک شده</h3>
                    <div class="stat-number"><?php echo number_format($block_stats['blocked_ips']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>🔍 User Agent های بلاک شده</h3>
                    <div class="stat-number"><?php echo number_format($block_stats['blocked_user_agents']); ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>⚠️ کل تلاش‌های مسدود شده</h3>
                    <div class="stat-number blocked-attempts"><?php echo number_format($block_stats['total_attempts']); ?></div>
                </div>
            </div>

            <!-- تنظیمات بلاک خودکار -->
            <div class="device-section">
                <h2>⚙️ تنظیمات بلاک خودکار</h2>
                <form id="blocker-settings-form" method="post">
                    <?php wp_nonce_field('market_google_admin_nonce', 'nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">بلاک خودکار</th>
                            <td>
                                <label for="auto_block_enabled">
                                    <input type="checkbox" id="auto_block_enabled" name="auto_block_enabled" value="1" 
                                           <?php checked($settings['auto_block_enabled']); ?>>
                                    فعال کردن بلاک خودکار ربات‌ها
                                </label>
                                <p class="description">با فعال کردن این گزینه، ربات‌ها بر اساس امتیاز به صورت خودکار بلاک می‌شوند.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">حداقل امتیاز ربات</th>
                            <td>
                                <input type="number" id="bot_score_threshold" name="bot_score_threshold" 
                                       value="<?php echo esc_attr($settings['bot_score_threshold']); ?>" 
                                       min="0" max="100" step="1">
                                <p class="description">
                                    دستگاه‌هایی با امتیاز ربات بالاتر از این مقدار بلاک می‌شوند.<br>
                                    <strong>توصیه:</strong> 70-80 برای محیط عادی، 60-70 برای حالت محافظانه
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">حداکثر تلاش‌ها</th>
                            <td>
                                <input type="number" id="max_attempts" name="max_attempts" 
                                       value="<?php echo esc_attr($settings['max_attempts']); ?>" 
                                       min="1" max="10" step="1">
                                <p class="description">پس از این تعداد تلاش مشکوک، دستگاه بلاک می‌شود.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">مدت زمان بلاک (ساعت)</th>
                            <td>
                                <input type="number" id="block_duration" name="block_duration" 
                                       value="<?php echo esc_attr($settings['block_duration']); ?>" 
                                       min="1" max="168" step="1">
                                <p class="description">دستگاه‌ها برای این مدت زمان بلاک می‌شوند (168 ساعت = 1 هفته)</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button-primary" id="save-settings-btn">💾 ذخیره تنظیمات</button>
                        <button type="button" class="button-secondary" id="toggle-auto-block-btn">
                            <?php echo $settings['auto_block_enabled'] ? '⏸️ غیرفعال کردن' : '▶️ فعال کردن'; ?> بلاک خودکار
                        </button>
                    </p>
                </form>
            </div>            
            
            <!-- بلاک کردن دستی -->
            <div class="device-row" style="display: flex; gap: 24px; margin-bottom: 24px;">
                <!-- ستون راست: بلاک دستی -->
                <div class="device-section" style="flex: 1;">
                    <h2>🔨 بلاک کردن دستی</h2>
                    <form id="manual-block-form">
                        <?php wp_nonce_field('market_google_admin_nonce', 'nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">نوع بلاک</th>
                                <td>
                                    <select name="block_type" id="block_type">
                                        <option value="ip">🌐 IP Address</option>
                                        <option value="user_agent">🔍 User Agent</option>
                                        <option value="device_fingerprint">📱 Device Fingerprint</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">مقدار</th>
                                <td>
                                    <input type="text" name="block_value" id="block_value" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">دلیل</th>
                                <td>
                                    <input type="text" name="reason" id="reason" class="regular-text" placeholder="مثال: کلیک فیک Google Ads">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button-primary">🚫 بلاک کردن</button>
                        </p>
                    </form>
                </div>
                <!-- ستون چپ: لیست سفید دستی -->
                <div class="device-section" style="flex: 1;">
                    <h2>🟢 لیست سفید دستی</h2>
                    <form id="add-whitelist-form" style="margin-bottom: 16px;">
                        <input type="hidden" name="action" value="market_google_add_whitelist">
                        <?php wp_nonce_field('market_google_admin_nonce', 'whitelist_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">نوع</th>
                                <td>
                                    <select name="whitelist_type" id="whitelist_type">
                                        <option value="ip">🌐 IP Address</option>
                                        <option value="fingerprint">📱 Device Fingerprint</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">مقدار</th>
                                <td>
                                    <input type="text" name="whitelist_value" id="whitelist_value" class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">علت</th>
                                <td>
                                    <input type="text" name="whitelist_reason" id="whitelist_reason" class="regular-text" placeholder="مثال: رفع بلاک اشتباه یا کاربر معتبر">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button-primary">➕ افزودن به لیست سفید</button>
                        </p>
                    </form>
                </div>
            </div>
            <div class="device-row" style="display: flex; gap: 24px; margin-bottom: 24px;">
                <!-- ستون راست: لیست آی‌پی مشکوک -->
                <div class="device-section" style="flex: 1;">
                    <h2>🚨 آی‌پی‌های مشکوک</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="text-align:center; width: 15%;">🌐 Ip </th>
                                <th style="text-align:center; width: 12%;">📊 Session</th>
                                <th style="text-align:center; width: 12%;">🖱️ click </th>
                                <th style="text-align:center; width: 12%;">📄 page </th>
                                <th style="text-align:center; width: 15%;">🤖 bot score</th>
                                <th style="text-align:center; width: 12%;">⚡ status</th>
                                <th style="text-align:center; width: 22%;">🔧 operation</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($attack_patterns)): foreach ($attack_patterns as $pattern): ?>
                            <?php
                            // محاسبه تعداد کلیک و صفحات بازدید برای هر آی‌پی
                            global $wpdb;
                            $click_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}market_google_user_tracking WHERE user_ip = %s AND event_type = 'click'", $pattern->user_ip));
                            $page_view_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}market_google_user_tracking WHERE user_ip = %s AND event_type = 'page_load'", $pattern->user_ip));
                            ?>
                            <tr class="<?php echo $this->is_ip_blocked($pattern->user_ip) ? 'blocked-row' : ''; ?>">
                                <td style="text-align:center;"><code><?php echo esc_html($pattern->user_ip); ?></code></td>
                                <td style="text-align:center;"><strong><?php echo number_format($pattern->session_count); ?></strong></td>
                                <td style="text-align:center;"><?php echo $click_count; ?></td>
                                <td style="text-align:center;"><?php echo $page_view_count; ?></td>
                                <td style="text-align:center;">
                                    <?php 
                                    $score = round($pattern->avg_bot_score ?: 0);
                                    $class = $score > 80 ? 'high-risk' : ($score > 60 ? 'medium-risk' : 'low-risk');
                                    $emoji = $score > 80 ? '🚨' : ($score > 60 ? '⚠️' : '✅');
                                    ?>
                                    <span class="risk-score <?php echo $class; ?>">
                                        <?php echo $emoji; ?> <?php echo $score; ?>%
                                    </span>
                                </td>
                                <td style="text-align:center;">
                                    <?php if ($this->is_ip_blocked($pattern->user_ip)): ?>
                                        <span class="status-blocked">🚫 بلاک شده</span>
                                    <?php else: ?>
                                        <span class="status-active">✅ فعال</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:center;">
                                    <?php if (!$this->is_ip_blocked($pattern->user_ip)): ?>
                                        <button class="button block-ip-btn" data-ip="<?php echo esc_attr($pattern->user_ip); ?>">🚫 بلاک</button>
                                    <?php else: ?>
                                        <button class="button unblock-ip-btn" data-ip="<?php echo esc_attr($pattern->user_ip); ?>">✅ آنبلاک</button>
                                    <?php endif; ?>
                                    <button class="button button-danger remove-suspicious-ip-btn" data-ip="<?php echo esc_attr($pattern->user_ip); ?>">🗑 حذف</button>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" style="text-align:center;">هنوز آی‌پی مشکوکی شناسایی نشده</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- ستون چپ: لیست سفید -->
                <div class="device-section" style="flex: 1;">
                    <h2>🟢 لیست سفید</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="text-align:center; width: 15%;">نوع</th>
                                <th style="text-align:center; width: 25%;">مقدار</th>
                                <th style="text-align:center; width: 25%;">علت</th>
                                <th style="text-align:center; width: 20%;">تاریخ</th>
                                <th style="text-align:center; width: 15%;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($whitelist)): foreach ($whitelist as $item): ?>
                            <tr>
                                <td style="text-align:center;"><?php echo $item->type === 'ip' ? '🌐 IP' : '📱 Fingerprint'; ?></td>
                                <td style="text-align:center;"><code><?php echo esc_html($item->value); ?></code></td>
                                <td style="text-align:center;"><?php echo esc_html($item->reason ?? ''); ?></td>
                                <td style="text-align:center;">
                                    <?php 
                                    $date = new DateTime($item->added_at);
                                    echo $date->format('Y/m/d H:i');
                                    ?>
                                </td>
                                <td style="text-align:center;"><button class="button button-danger remove-whitelist-btn" data-id="<?php echo $item->id; ?>">🗑 حذف</button></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align:center;">هنوز موردی در لیست سفید ثبت نشده است.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- لیست بلاک شده‌ها (تمام عرض) -->
            <div class="device-section">
                <h2>📋 موارد بلاک شده</h2>
                <div id="blocked-devices-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>نوع</th><th>مقدار</th><th>دلیل</th><th>تاریخ</th><th>عملیات</th></tr></thead>
                        <tbody>
                        <tr><td colspan="5" style="text-align:center;">هنوز موردی بلاک نشده است.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Modal برای نمایش جزئیات -->
        <div id="device-details-modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>🔍 جزئیات کامل دستگاه</h3>
                <div id="device-details-content"></div>
            </div>
        </div>
        
        <style>
        .device-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-card h3 { color: #666; font-size: 14px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #0073aa; }
        .device-section { background: #fff; margin: 20px 0; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .risk-score.high-risk { color: #d63384; font-weight: bold; }
        .risk-score.medium-risk { color: #fd7e14; font-weight: bold; }
        .risk-score.low-risk { color: #198754; }
        .status-blocked { color: #dc3545; font-weight: bold; }
        .status-active { color: #198754; font-weight: bold; }
        .blocked-row { background-color: #fff2f2; }
        .loading { text-align: center; padding: 20px; color: #666; }
        .device-row { display: flex; gap: 12px !important; margin-bottom: 12px !important; flex-wrap: wrap; }
        .device-section { background: #fff; margin: 8px 0 !important; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); min-width: 320px; max-width: 100%; flex: 1 1 350px; }
        .wrap { overflow-x: hidden; }
        #blocked-devices-list { overflow-x: auto; }
        .wp-list-table th, .wp-list-table td { text-align: center !important; vertical-align: middle !important; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // ذخیره تنظیمات
            $('#blocker-settings-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'market_google_save_blocker_settings');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            location.reload();
                        } else {
                            alert('❌ خطا: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ خطا در ارتباط با سرور');
                    }
                });
            });
            
            // تغییر وضعیت بلاک خودکار
            $('#toggle-auto-block-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'market_google_toggle_auto_block',
                        nonce: $('[name="nonce"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            location.reload();
                        } else {
                            alert('❌ خطا: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ خطا در ارتباط با سرور');
                    },
                    complete: function() {
                        btn.prop('disabled', false);
                    }
                });
            });
            
            // بلاک کردن دستی
            $('#manual-block-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'market_google_block_device');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert('✅ ' + response.data.message);
                            $('#manual-block-form')[0].reset();
                            loadBlockedDevices();
                        } else {
                            alert('❌ خطا: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ خطا در ارتباط با سرور');
                    }
                });
            });
            
            // بلاک کردن IP از جدول
            $(document).on('click', '.block-ip-btn', function() {
                const ip = $(this).data('ip');
                
                if (confirm('آیا مطمئن هستید که می‌خواهید این آی‌پی را بلاک کنید؟\n' + ip)) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'market_google_block_device',
                            block_type: 'ip',
                            block_value: ip,
                            reason: 'بلاک دستی از صفحه مدیریت',
                            nonce: $('[name="nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ ' + response.data.message);
                                location.reload();
                            } else {
                                alert('❌ خطا: ' + response.data.message);
                            }
                        }
                    });
                }
            });
            
            // آنبلاک کردن از لیست
            $(document).on('click', '.unblock-btn', function() {
                const blockId = $(this).data('id');
                
                if (confirm('آیا مطمئن هستید که می‌خواهید این مورد را آنبلاک کنید؟')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'market_google_unblock_device',
                            block_id: blockId,
                            nonce: $('[name="nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('✅ با موفقیت آنبلاک شد');
                                loadBlockedDevices();
                            } else {
                                alert('❌ خطا: ' + response.data.message);
                            }
                        }
                    });
                }
            });
            
            // بارگذاری لیست موارد بلاک شده
            function loadBlockedDevices() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'market_google_get_blocked_list',
                        nonce: $('[name="nonce"]').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            let html = '';
                            if (response.data.length > 0) {
                                html += '<table class="wp-list-table widefat fixed striped">';
                                html += '<thead><tr><th>نوع</th><th>مقدار</th><th>دلیل</th><th>تاریخ</th><th>عملیات</th></tr></thead>';
                                html += '<tbody>';
                                
                                response.data.forEach(function(item) {
                                    const typeLabel = item.block_type === 'ip' ? '🌐 IP' : 
                                                    item.block_type === 'user_agent' ? '🔍 User Agent' : '📱 Device';
                                    
                                    // تبدیل تاریخ میلادی به شمسی
                                    const blockDate = new Date(item.block_date);
                                    const persianDate = blockDate.toLocaleDateString('fa-IR', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                    
                                    html += '<tr>';
                                    html += '<td>' + typeLabel + '</td>';
                                    html += '<td><code>' + item.block_value + '</code></td>';
                                    html += '<td>' + (item.block_reason || '-') + '</td>';
                                    html += '<td>' + persianDate + '</td>';
                                    html += '<td><button class="button unblock-btn" data-id="' + item.id + '">🔓 آنبلاک</button></td>';
                                    html += '</tr>';
                                });
                                
                                html += '</tbody></table>';
                            } else {
                                html = '<p>هنوز موردی بلاک نشده است.</p>';
                            }
                            
                            $('#blocked-devices-list').html(html);
                        }
                    }
                });
            }
            
            // بارگذاری اولیه
            loadBlockedDevices();

            // [2] --- دکمه حذف از لیست آی‌پی‌های مشکوک ---
            $(document).on('click', '.add-whitelist-ip-btn', function() {
                var ip = $(this).data('ip');
                if (!confirm('آیا مطمئن هستید که می‌خواهید این IP را به لیست سفید اضافه کنید؟\n' + ip)) return;
                $.post(ajaxurl, {action: 'market_google_add_whitelist', whitelist_type: 'ip', whitelist_value: ip, whitelist_nonce: $('[name=whitelist_nonce]').val()}, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('خطا: ' + response.data.message);
                    }
                });
            });

            // هندلر AJAX حذف آی‌پی مشکوک
            $(document).on('click', '.remove-suspicious-ip-btn', function() {
                var ip = $(this).data('ip');
                if (!confirm('آیا مطمئن هستید که می‌خواهید این آی‌پی مشکوک را حذف کنید؟\n' + ip)) return;
                $.post(ajaxurl, {action: 'market_google_remove_suspicious_ip', suspicious_ip: ip, nonce: $('[name="nonce"]').val()}, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('خطا: ' + response.data.message);
                    }
                });
            });

            $('#add-whitelist-form').on('submit', function(e) {
                e.preventDefault(); // جلوگیری از ارسال عادی فرم

                var formData = $(this).serialize();
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        alert('✅ با موفقیت به لیست سفید اضافه شد');
                        location.reload();
                    } else {
                        alert('❌ خطا: ' + response.data.message);
                    }
                });
            });

            // [3] --- دکمه حذف از لیست سفید ---
            $(document).on('click', '.remove-whitelist-btn', function() {
                var id = $(this).data('id');
                var row = $(this).closest('tr');
                
                if (confirm('آیا از حذف این آیتم از لیست سفید مطمئن هستید؟')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'market_google_remove_whitelist',
                            whitelist_id: id,
                            whitelist_nonce: $('[name="whitelist_nonce"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                row.fadeOut(300, function() {
                                    $(this).remove();
                                });
                                alert('✅ آیتم با موفقیت حذف شد');
                            } else {
                                alert('❌ خطا: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('❌ خطا در ارتباط با سرور');
                        }
                    });
                }
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * بررسی اینکه آیا IP بلاک شده است
     */
    private function is_ip_blocked($ip) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_blocked_devices';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE block_type = 'ip' AND block_value = %s AND is_active = 1",
            $ip
        ));
        
        return $count > 0;
    }
    
    /**
     * بررسی اینکه آیا IP مشکوک است
     */
    private function is_suspicious_ip($ip) {
        // بررسی IP های محلی و خصوصی
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }
        
        // بررسی IP های شناخته شده ربات‌ها
        $bot_ips = array('66.249.', '157.55.', '207.46.'); // Google, Bing
        foreach ($bot_ips as $bot_ip) {
            if (strpos($ip, $bot_ip) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * دریافت جزئیات دستگاه
     */
    public function get_device_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'device_details_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $ip = sanitize_text_field($_POST['ip']);
        
        global $wpdb;
        $tracking_table = $wpdb->prefix . 'market_google_user_tracking';
        
        $sessions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tracking_table WHERE user_ip = %s ORDER BY timestamp DESC LIMIT 20",
            $ip
        ));
        
        $html = '<h4>🌐 جزئیات IP: <code>' . esc_html($ip) . '</code></h4>';
        
        if (empty($sessions)) {
            $html .= '<p>❌ هیچ داده‌ای برای این IP یافت نشد.</p>';
        } else {
            $html .= '<p>📊 <strong>' . count($sessions) . '</strong> فعالیت یافت شد:</p>';
            
            foreach ($sessions as $session) {
                $html .= '<div class="device-info">';
                $html .= '<strong>🆔 Session:</strong> <code>' . esc_html($session->session_id) . '</code><br>';
                $html .= '<strong>⚡ Action:</strong> ' . esc_html($session->action_type) . '<br>';
                $html .= '<strong>🕒 Time:</strong> ' . esc_html($session->timestamp) . '<br>';
                
                if ($session->user_agent) {
                    $html .= '<strong>🔍 User Agent:</strong> <br><code>' . esc_html(substr($session->user_agent, 0, 100)) . '...</code><br>';
                }
                
                if ($session->field_value) {
                    $decoded = json_decode($session->field_value, true);
                    if ($decoded && isset($decoded['deviceInfo'])) {
                        $deviceInfo = $decoded['deviceInfo'];
                        $html .= '<strong>📱 Device:</strong> ' . esc_html($deviceInfo['type'] ?? 'نامشخص') . '<br>';
                        $html .= '<strong>💻 OS:</strong> ' . esc_html($deviceInfo['os'] ?? 'نامشخص') . '<br>';
                        $html .= '<strong>🌐 Browser:</strong> ' . esc_html($deviceInfo['browser'] ?? 'نامشخص') . '<br>';
                    } else {
                        $html .= '<strong>📄 Data:</strong> <br><code>' . esc_html(substr($session->field_value, 0, 200)) . '...</code><br>';
                    }
                }
                
                $html .= '</div>';
            }
        }
        
        wp_send_json_success($html);
    }

    public function ajax_add_whitelist() {
        check_ajax_referer('market_google_admin_nonce', 'whitelist_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        $type = isset($_POST['whitelist_type']) ? sanitize_text_field($_POST['whitelist_type']) : '';
        $value = isset($_POST['whitelist_value']) ? sanitize_text_field($_POST['whitelist_value']) : '';
        $reason = isset($_POST['whitelist_reason']) ? sanitize_text_field($_POST['whitelist_reason']) : '';
        if (!$type || !$value) {
            wp_send_json_error(['message' => 'نوع و مقدار الزامی است']);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'market_google_tracking_whitelist';
        $wpdb->insert($table, [
            'type' => $type,
            'value' => $value,
            'reason' => $reason,
            'added_at' => current_time('mysql')
        ]);
        wp_send_json_success(['message' => 'با موفقیت به لیست سفید اضافه شد']);
    }

    public function ajax_remove_suspicious_ip() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        $ip = isset($_POST['suspicious_ip']) ? sanitize_text_field($_POST['suspicious_ip']) : '';
        if (!$ip) {
            wp_send_json_error(['message' => 'آی‌پی نامعتبر']);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'market_google_user_tracking';
        $wpdb->delete($table, ['user_ip' => $ip]);
        wp_send_json_success(['message' => 'آی‌پی مشکوک حذف شد']);
    }

    public function ajax_remove_whitelist() {
        check_ajax_referer('market_google_admin_nonce', 'whitelist_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز']);
        }
        $id = isset($_POST['whitelist_id']) ? intval($_POST['whitelist_id']) : 0;
        if (!$id) {
            wp_send_json_error(['message' => 'شناسه نامعتبر']);
        }
        global $wpdb;
        $table = $wpdb->prefix . 'market_google_tracking_whitelist';
        $result = $wpdb->delete($table, ['id' => $id]);
        if ($result) {
            wp_send_json_success(['message' => 'آیتم با موفقیت حذف شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در حذف آیتم']);
        }
    }
} 