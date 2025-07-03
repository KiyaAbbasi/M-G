<?php
/**
 * صفحه لیست سفارشات
 */

if (!defined('ABSPATH')) {
    exit;
}

class Market_Google_Orders_List {
    
    public static function display_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // پردازش عملیات
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $action = sanitize_text_field($_GET['action']);
            $order_id = intval($_GET['id']);

            switch ($action) {
                case 'complete':
                    $wpdb->update($table_name, array('payment_status' => 'success'), array('id' => $order_id));
                    echo '<div class="notice notice-success"><p>سفارش با موفقیت تکمیل شد.</p></div>';
                    break;
                
                case 'delete':
                    $wpdb->delete($table_name, array('id' => $order_id));
                    echo '<div class="notice notice-success"><p>سفارش حذف شد.</p></div>';
                    break;
            }
        }

        // فیلتر و جستجو
        $where_conditions = array('1=1');
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $order_status_filter = isset($_GET['order_status']) ? sanitize_text_field($_GET['order_status']) : '';
        $payment_status_filter = isset($_GET['payment_status']) ? sanitize_text_field($_GET['payment_status']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

        if (!empty($search)) {
            // تصحیح جستجو برای کاراکترهای فارسی
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $where_conditions[] = $wpdb->prepare(
                "(business_name LIKE %s OR full_name LIKE %s OR phone LIKE %s OR business_phone LIKE %s)",
                $search_term,
                $search_term,
                $search_term,
                $search_term
            );
        }

        if (!empty($order_status_filter)) {
            $where_conditions[] = $wpdb->prepare("status = %s", $order_status_filter);
        }

        if (!empty($payment_status_filter)) {
            if ($payment_status_filter === 'success') {
                // پشتیبانی از هر دو 'success' و 'completed'
                $where_conditions[] = "(payment_status = 'success' OR payment_status = 'completed')";
            } else {
            $where_conditions[] = $wpdb->prepare("payment_status = %s", $payment_status_filter);
            }
        }

        // تبدیل تاریخ شمسی به میلادی
        if (!empty($date_from)) {
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date_from, $matches)) {
                $jy = intval($matches[1]);
                $jm = intval($matches[2]);
                $jd = intval($matches[3]);
                list($gy, $gm, $gd) = MarketGoogleJalaliCalendar::jalali_to_gregorian($jy, $jm, $jd);
                $gregorian_date_from = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                $where_conditions[] = $wpdb->prepare("DATE(created_at) >= %s", $gregorian_date_from);
            }
        }

        if (!empty($date_to)) {
            if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date_to, $matches)) {
                $jy = intval($matches[1]);
                $jm = intval($matches[2]);
                $jd = intval($matches[3]);
                list($gy, $gm, $gd) = MarketGoogleJalaliCalendar::jalali_to_gregorian($jy, $jm, $jd);
                $gregorian_date_to = sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
                $where_conditions[] = $wpdb->prepare("DATE(created_at) <= %s", $gregorian_date_to);
            }
        } else if (!empty($date_from)) {
            // اگر تاریخ پایان وارد نشده باشد، تا امروز فیلتر کن
            $today = date('Y-m-d');
            $where_conditions[] = $wpdb->prepare("DATE(created_at) <= %s", $today);
        }

        $where_clause = implode(' AND ', $where_conditions);

        // صفحه‌بندی
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}");
        $total_pages = ceil($total_items / $per_page);

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        ?>
        <div class="wrap orders-list-wrap">
            <h1>لیست سفارشات</h1>

            <!-- فیلتر و جستجو -->
            <div class="orders-filters">
                <form method="get" class="orders-search-form">
                    <input type="hidden" name="page" value="market-google-orders-list">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>وضعیت سفارش:</label>
                            <select name="order_status">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="pending" <?php selected($order_status_filter, 'pending'); ?>>در انتظار انجام</option>
                                <option value="completed" <?php selected($order_status_filter, 'completed'); ?>>تکمیل شده</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>وضعیت پرداخت:</label>
                            <select name="payment_status">
                                <option value="">همه وضعیت‌ها</option>
                                <option value="pending" <?php selected($payment_status_filter, 'pending'); ?>>درانتظار پرداخت</option>
                                <option value="success" <?php selected($payment_status_filter, 'success'); ?>>پرداخت موفق</option>
                                <option value="failed" <?php selected($payment_status_filter, 'failed'); ?>>پرداخت ناموفق</option>
                                <option value="cancelled" <?php selected($payment_status_filter, 'cancelled'); ?>>لغو پرداخت</option>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>از تاریخ:</label>
                            <input type="text" id="date_from" name="date_from" class="jalali-datepicker" value="<?php echo esc_attr($date_from); ?>" placeholder="انتخاب تاریخ">
                        </div>

                        <div class="filter-group">
                            <label>تا تاریخ:</label>
                            <input type="text" id="date_to" name="date_to" class="jalali-datepicker" value="<?php echo esc_attr($date_to); ?>" placeholder="انتخاب تاریخ">
                        </div>

                        <div class="filter-group">
                            <label>تعداد نمایش:</label>
                            <select name="per_page">
                                <option value="10" <?php selected($per_page, 10); ?>>10</option>
                                <option value="20" <?php selected($per_page, 20); ?>>20</option>
                                <option value="50" <?php selected($per_page, 50); ?>>50</option>
                                <option value="100" <?php selected($per_page, 100); ?>>100</option>
                            </select>
                        </div>
                    </div>

                    <div class="search-row">
                        <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="جستجو در نام کسب و کار، نام کامل، تلفن، شماره کسب و کار...">
                        <input type="submit" class="button button-primary" value="جستجو و فیلتر">
                        <a href="?page=market-google-orders-list" class="button">پاک کردن فیلترها</a>

                    </div>
                </form>
            </div>

            <!-- جدول سفارشات -->
            <div class="orders-table-container">
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
                            <tr class="<?php echo empty($order->is_read) ? 'order-unread' : 'order-read'; ?><?php echo Market_Google_Admin::migrate_status($order->status) === 'completed' ? ' order-completed' : ''; ?>">
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
                                <td class="business-phone"><?php echo esc_html($order->business_phone ?? $order->phone ?? '-'); ?></td>
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
                                    echo '<span class="amount-number">' . number_format($amount, 0, '.', ',') . '</span> <span class="amount-currency">تومان</span>';
                                    ?>
                                </td>
                                <td class="order-status">
                                    <span class="status-badge order-<?php echo esc_attr(Market_Google_Admin::migrate_status($order->status ?? 'pending')); ?>">
                                        <?php echo Market_Google_Admin::get_status_label($order->status ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td class="payment-status">
                                    <span class="status-badge payment-<?php echo $order->payment_status ?? 'pending'; ?>">
                                        <?php echo self::get_payment_status_label($order->payment_status ?? 'pending'); ?>
                                    </span>
                                </td>
                                
                                <td class="payment-date">
                                    <?php echo self::convert_to_shamsi_date($order->created_at); ?>
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
                                            <?php if (Market_Google_Admin::migrate_status($order->status) !== 'completed'): ?>
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
            </div>

            <!-- صفحه‌بندی -->
            <?php if ($total_pages > 1): ?>
                <div class="orders-pagination">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '‹ قبلی',
                            'next_text' => 'بعدی ›',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        
                        if ($page_links) {
                            echo '<span class="displaying-num">' . 
                                 sprintf('%s مورد از %s', number_format_i18n($total_items), number_format_i18n($total_items)) . 
                                 '</span>';
                            echo $page_links;
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- مودال مشاهده جزئیات -->
        <div id="order-details-modal" class="orders-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>جزئیات سفارش</h3>
                    <div class="modal-close">&times;</div>
                </div>
                <div id="order-details-body" class="modal-body order-details-content"></div>
                <div class="modal-footer">
                    <button type="button" class="button button-primary send-info-sms-modal">ارسال پیامک اطلاعات</button>
                    <button type="button" class="button edit-order-modal" style="background: #fbbf24 !important; color: #1f2937 !important; border-color: #fbbf24 !important;">ویرایش سفارش</button>
                    <button type="button" class="button button-danger delete-order-modal">حذف سفارش</button>
                </div>
            </div>
        </div>

        <!-- مودال ویرایش سفارش -->
        <div id="order-edit-modal" class="orders-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>ویرایش سفارش</h3>
                    <div class="modal-close">&times;</div>
                </div>
                <div id="order-edit-body" class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="button button-primary save-order">ذخیره تغییرات</button>
                    <button type="button" class="button modal-close">انصراف</button>
                </div>
            </div>
        </div>
        
        <!-- اضافه کردن nonce و parameters برای AJAX -->
        <script type="text/javascript">
            // تعریف متغیرهای مورد نیاز برای AJAX
            window.market_google_orders_params = {
                ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                security: '<?php echo wp_create_nonce('market_google_orders_nonce'); ?>'
            };
            
            // تنظیمات AJAX برای سفارشات
            jQuery(document).ready(function($) {
                // کدهای دیگر در اینجا اضافه خواهد شد
            });
        </script>
        
        <!-- فرم مخفی برای nonce -->
        <form style="display: none;">
            <?php wp_nonce_field('market_google_orders_nonce', 'market_google_orders_nonce'); ?>
        </form>
        <?php
    }

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

    public static function convert_to_shamsi_date($date) {
        if (empty($date)) return '-';
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return '-';
        
        list($jy, $jm, $jd) = MarketGoogleJalaliCalendar::gregorian_to_jalali(
            date('Y', $timestamp),
            date('n', $timestamp),
            date('j', $timestamp)
        );
        
        // فقط تاریخ و ساعت بدون ثانیه
        $shamsi_date = sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, date('H:i:s', $timestamp));
        return $shamsi_date;
    }

    public static function convert_to_shamsi_date_only($date) {
        if (empty($date)) return '-';
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return '-';
        
        list($jy, $jm, $jd) = MarketGoogleJalaliCalendar::gregorian_to_jalali(
            date('Y', $timestamp),
            date('n', $timestamp),
            date('j', $timestamp)
        );
        
        // فقط تاریخ
        $shamsi_date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        return $shamsi_date;
    }

    public static function convert_to_shamsi_datetime($date) {
        if (empty($date)) return '-';
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return '-';
        
        list($jy, $jm, $jd) = MarketGoogleJalaliCalendar::gregorian_to_jalali(
            date('Y', $timestamp),
            date('n', $timestamp),
            date('j', $timestamp)
        );
        
        // تاریخ و ساعت کامل
        $shamsi_date = sprintf('%04d/%02d/%02d %s', $jy, $jm, $jd, date('H:i:s', $timestamp));
        return $shamsi_date;
    }
}
?>