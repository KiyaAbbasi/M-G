<?php
/**
 * نمایش جدول لیست سفارشات
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی وجود متغیر $orders
if (!isset($orders)) {
    echo '<div class="notice notice-error"><p>خطا در بارگذاری سفارشات</p></div>';
    return;
}
?>

<table class="wp-list-table widefat fixed striped orders-table">
    <thead>
        <tr>
            <th class="column-order-number">شماره سفارش</th>
            <th class="column-full-name">نام کامل</th>
            <th class="column-mobile">شماره موبایل</th>
            <th class="column-business-number">شماره کسب و کار</th>
            <th class="column-business-name">نام کسب و کار</th>
            <th class="column-coordinates">کپی مختصات</th>
            <th class="column-payment-amount">مبلغ پرداخت</th>
            <th class="column-payment-status">وضعیت پرداخت</th>
            <th class="column-payment-date">تاریخ ثبت</th>
            <th class="column-actions">عملیات</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($orders)): ?>
            <tr>
                <td colspan="10" class="no-items">موردی یافت نشد.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <tr class="<?php echo empty($order->viewed_at) ? 'order-unviewed' : 'order-viewed'; ?>">
                <td class="order-number">
                    <?php 
                    // نمایش شماره سفارش با فرمت MG-100, MG-101, ...
                    echo '#MG-' . str_pad($order->id + 99, 3, '0', STR_PAD_LEFT);
                    ?>
                </td>
                <td class="full-name"><?php echo esc_html($order->full_name); ?></td>
                <td class="mobile">
                    <a href="tel:<?php echo esc_attr($order->phone); ?>">
                        <?php echo esc_html($order->phone); ?>
                    </a>
                </td>
                <td class="business-number"><?php echo esc_html($order->business_phone ?? '-'); ?></td>
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
                <td class="payment-status">
                    <span class="status-badge payment-<?php echo $order->payment_status ?? 'pending'; ?>">
                        <?php 
                        // فال‌بک برای وضعیت‌ها بدون unknown
                        switch ($order->payment_status ?? 'pending') {
                            case 'pending': echo 'درانتظار پرداخت'; break;
                            case 'success': echo 'پرداخت موفق'; break;
                            case 'failed': echo 'پرداخت ناموفق'; break;
                            case 'cancelled': echo 'لغو پرداخت'; break;
                            default: echo 'درانتظار پرداخت';
                        }
                        ?>
                    </span>
                </td>
                
                <td class="payment-date">
                    <?php 
                    if (!empty($order->created_at)) {
                        if (class_exists('MarketGoogleJalaliCalendar')) {
                            echo MarketGoogleJalaliCalendar::format_date('Y/m/d', $order->created_at);
                        } else {
                            echo date_i18n('Y/m/d', strtotime($order->created_at));
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
                        
                        <?php if ($order->payment_status !== 'success' && $order->payment_status !== 'completed'): ?>
                            <span class="complete">
                                <a href="#" class="complete-order" data-id="<?php echo $order->id; ?>" title="تکمیل سفارش">
                                    <span class="dashicons dashicons-yes"></span>
                                </a>
                            </span>
                        <?php endif; ?>
                        
                        <span class="edit">
                            <a href="#" class="edit-order" data-id="<?php echo $order->id; ?>" title="ویرایش سفارش">
                                <span class="dashicons dashicons-edit"></span>
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