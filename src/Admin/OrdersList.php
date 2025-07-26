<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class OrdersList
 *
 * مدیریت صفحه لیست سفارشات در پنل مدیریت
 *
 * @package MarketGoogle\Admin
 */
class OrdersList {

    /**
     * رندر کردن صفحه لیست سفارشات
     */
    public function render_page() {
        // در آینده، این بخش شامل جدول سفارشات با قابلیت فیلتر و جستجو خواهد بود
        // و از فایل template برای نمایش استفاده خواهد کرد.

        echo '<div class="wrap"><h1>لیست سفارشات</h1><p>در این بخش، لیست تمام سفارشات ثبت شده نمایش داده می‌شود.</p>';

        // نمونه‌ای از نحوه نمایش جدول در آینده
        // $this->display_orders_table();

        echo '</div>';
    }

    /**
     * نمایش جدول سفارشات
     * (این متد در آینده تکمیل خواهد شد)
     */
    private function display_orders_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';

        // دریافت پارامترهای فیلتر و جستجو
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        // ساخت کوئری
        $where_conditions = ['1=1'];
        if (!empty($search)) {
            $where_conditions[] = $wpdb->prepare(
                "(business_name LIKE %s OR full_name LIKE %s OR phone LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        if (!empty($status_filter)) {
            $where_conditions[] = $wpdb->prepare("status = %s", $status_filter);
        }

        $where_clause = implode(' AND ', $where_conditions);
        $orders = $wpdb->get_results("SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC");

        // نمایش جدول ...
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>نام کسب و کار</th>
                    <th>مشتری</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo esc_html($order->business_name); ?></td>
                            <td><?php echo esc_html($order->full_name); ?></td>
                            <td><?php echo esc_html($order->status); ?></td>
                            <td><?php echo esc_html($order->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">هیچ سفارشی یافت نشد.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }
}
