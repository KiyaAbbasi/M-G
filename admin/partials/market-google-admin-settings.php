<?php
/**
 * نمایش تنظیمات کال‌بک پرداخت
 */

// بررسی دسترسی مستقیم
if (!defined('WPINC')) {
    die;
}

// دریافت تنظیمات callback
$use_custom_callbacks = get_option('market_google_use_custom_callbacks', 0);
$payment_success_url = get_option('market_google_payment_success_url', '');
$payment_failed_url = get_option('market_google_payment_failed_url', '');
$payment_canceled_url = get_option('market_google_payment_canceled_url', '');
$payment_pending_url = get_option('market_google_payment_pending_url', '');
$payment_error_url = get_option('market_google_payment_error_url', '');
$zarinpal_success_url = get_option('market_google_zarinpal_success_url', '');
$zarinpal_failed_url = get_option('market_google_zarinpal_failed_url', '');
$bmi_success_url = get_option('market_google_bmi_success_url', '');
$bmi_failed_url = get_option('market_google_bmi_failed_url', '');
?>

<table class="form-table">
    <tr>
        <th scope="row">
            <label><?php _e('استفاده از صفحات سفارشی', 'market-google-location'); ?></label>
        </th>
        <td>
            <label>
                <input type="checkbox" name="use_custom_callbacks" value="1" <?php checked($use_custom_callbacks, 1); ?>>
                <?php _e('فعال‌سازی صفحات بازگشت سفارشی پرداخت', 'market-google-location'); ?>
            </label>
            <p class="description">
                <?php _e('در صورت غیرفعال بودن، از صفحات پیش‌فرض سیستم استفاده می‌شود.', 'market-google-location'); ?>
            </p>
        </td>
    </tr>
</table>

<div id="custom-callback-settings" style="margin-top: 20px; <?php echo $use_custom_callbacks ? '' : 'display:none;'; ?>">
    <h3><?php _e('صفحات عمومی پرداخت', 'market-google-location'); ?></h3>
    <p class="description"><?php _e('این صفحات به صورت پیش‌فرض برای همه درگاه‌های پرداخت استفاده می‌شوند، مگر اینکه صفحات اختصاصی تعریف شده باشند.', 'market-google-location'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="payment_success_url"><?php _e('صفحه موفقیت پرداخت', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="payment_success_url" name="payment_success_url" value="<?php echo esc_attr($payment_success_url); ?>" class="regular-text" placeholder="https://yoursite.com/payment-success">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="payment_failed_url"><?php _e('صفحه شکست پرداخت', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="payment_failed_url" name="payment_failed_url" value="<?php echo esc_attr($payment_failed_url); ?>" class="regular-text" placeholder="https://yoursite.com/payment-failed">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="payment_canceled_url"><?php _e('صفحه لغو پرداخت', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="payment_canceled_url" name="payment_canceled_url" value="<?php echo esc_attr($payment_canceled_url); ?>" class="regular-text" placeholder="https://yoursite.com/payment-canceled">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="payment_pending_url"><?php _e('صفحه پرداخت در انتظار', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="payment_pending_url" name="payment_pending_url" value="<?php echo esc_attr($payment_pending_url); ?>" class="regular-text" placeholder="https://yoursite.com/payment-pending">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="payment_unsuccessful_url">صفحه پرداخت ناموفق</label>
            </th>
            <td>
                <input type="url" id="payment_unsuccessful_url" name="payment_unsuccessful_url" 
                       value="<?php echo esc_attr(get_option('market_google_payment_unsuccessful_url', '')); ?>" 
                       class="regular-text" placeholder="https://example.com/payment-unsuccessful">
                <p class="description">URL صفحه‌ای که کاربر در صورت ناموفق بودن پرداخت به آن هدایت می‌شود</p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="payment_error_url"><?php _e('صفحه خطای پرداخت', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="payment_error_url" name="payment_error_url" value="<?php echo esc_attr($payment_error_url); ?>" class="regular-text" placeholder="https://yoursite.com/payment-error">
            </td>
        </tr>
    </table>
    
    <hr>
    <h3><?php _e('صفحات اختصاصی زرین‌پال', 'market-google-location'); ?></h3>
    <p class="description"><?php _e('در صورت تعریف، این صفحات به جای صفحات عمومی برای پرداخت‌های زرین‌پال استفاده می‌شوند.', 'market-google-location'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="zarinpal_success_url"><?php _e('صفحه موفقیت زرین‌پال', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="zarinpal_success_url" name="zarinpal_success_url" value="<?php echo esc_attr($zarinpal_success_url); ?>" class="regular-text" placeholder="https://yoursite.com/zarinpal-success">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="zarinpal_failed_url"><?php _e('صفحه شکست زرین‌پال', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="zarinpal_failed_url" name="zarinpal_failed_url" value="<?php echo esc_attr($zarinpal_failed_url); ?>" class="regular-text" placeholder="https://yoursite.com/zarinpal-failed">
            </td>
        </tr>
    </table>
    
    <hr>
    <h3><?php _e('صفحات اختصاصی بانک ملی', 'market-google-location'); ?></h3>
    <p class="description"><?php _e('در صورت تعریف، این صفحات به جای صفحات عمومی برای پرداخت‌های بانک ملی استفاده می‌شوند.', 'market-google-location'); ?></p>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="bmi_success_url"><?php _e('صفحه موفقیت بانک ملی', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="bmi_success_url" name="bmi_success_url" value="<?php echo esc_attr($bmi_success_url); ?>" class="regular-text" placeholder="https://yoursite.com/bmi-success">
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="bmi_failed_url"><?php _e('صفحه شکست بانک ملی', 'market-google-location'); ?></label>
            </th>
            <td>
                <input type="url" id="bmi_failed_url" name="bmi_failed_url" value="<?php echo esc_attr($bmi_failed_url); ?>" class="regular-text" placeholder="https://yoursite.com/bmi-failed">
            </td>
        </tr>
    </table>
    
    <hr>
    <h3><?php _e('راهنمای استفاده از پارامترها', 'market-google-location'); ?></h3>
    <div class="notice notice-info inline">
        <p><strong><?php _e('پارامترهای قابل استفاده در URL های بازگشت:', 'market-google-location'); ?></strong></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('پارامتر', 'market-google-location'); ?></th>
                    <th><?php _e('توضیحات', 'market-google-location'); ?></th>
                    <th><?php _e('مثال استفاده', 'market-google-location'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>{location_id}</code></td>
                    <td><?php _e('شناسه لوکیشن ثبت شده', 'market-google-location'); ?></td>
                    <td><code>?location_id={location_id}</code></td>
                </tr>
                <tr>
                    <td><code>{transaction_id}</code></td>
                    <td><?php _e('شناسه تراکنش پرداخت', 'market-google-location'); ?></td>
                    <td><code>?transaction_id={transaction_id}</code></td>
                </tr>
                <tr>
                    <td><code>{ref_id}</code></td>
                    <td><?php _e('شماره مرجع پرداخت از درگاه', 'market-google-location'); ?></td>
                    <td><code>?ref_id={ref_id}</code></td>
                </tr>
                <tr>
                    <td><code>{gateway}</code></td>
                    <td><?php _e('نام درگاه پرداخت (zarinpal, bmi)', 'market-google-location'); ?></td>
                    <td><code>?gateway={gateway}</code></td>
                </tr>
                <tr>
                    <td><code>{amount}</code></td>
                    <td><?php _e('مبلغ پرداخت شده (تومان)', 'market-google-location'); ?></td>
                    <td><code>?amount={amount}</code></td>
                </tr>
                <tr>
                    <td><code>{user_name}</code></td>
                    <td><?php _e('نام کاربر ثبت کننده', 'market-google-location'); ?></td>
                    <td><code>?user_name={user_name}</code></td>
                </tr>
                <tr>
                    <td><code>{user_family}</code></td>
                    <td><?php _e('نام خانوادگی کاربر', 'market-google-location'); ?></td>
                    <td><code>?user_family={user_family}</code></td>
                </tr>
                <tr>
                    <td><code>{user_mobile}</code></td>
                    <td><?php _e('شماره موبایل کاربر', 'market-google-location'); ?></td>
                    <td><code>?user_mobile={user_mobile}</code></td>
                </tr>
                <tr>
                    <td><code>{business_name}</code></td>
                    <td><?php _e('نام کسب و کار', 'market-google-location'); ?></td>
                    <td><code>?business_name={business_name}</code></td>
                </tr>
                <tr>
                    <td><code>{business_category}</code></td>
                    <td><?php _e('دسته‌بندی کسب و کار', 'market-google-location'); ?></td>
                    <td><code>?business_category={business_category}</code></td>
                </tr>
                <tr>
                    <td><code>{business_address}</code></td>
                    <td><?php _e('آدرس کسب و کار', 'market-google-location'); ?></td>
                    <td><code>?business_address={business_address}</code></td>
                </tr>
                <tr>
                    <td><code>{business_phone}</code></td>
                    <td><?php _e('تلفن کسب و کار', 'market-google-location'); ?></td>
                    <td><code>?business_phone={business_phone}</code></td>
                </tr>
                <tr>
                    <td><code>{payment_date}</code></td>
                    <td><?php _e('تاریخ پرداخت', 'market-google-location'); ?></td>
                    <td><code>?payment_date={payment_date}</code></td>
                </tr>
                <tr>
                    <td><code>{payment_time}</code></td>
                    <td><?php _e('زمان پرداخت', 'market-google-location'); ?></td>
                    <td><code>?payment_time={payment_time}</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php _e('مثال کاربردی:', 'market-google-location'); ?></h4>
        <p><?php _e('برای نمایش نام، نام کسب و کار و شماره موبایل در صفحه موفقیت:', 'market-google-location'); ?></p>
        <code>https://yoursite.com/payment-success?name={user_name}&family={user_family}&business={business_name}&mobile={user_mobile}&amount={amount}</code>
        
        <p class="description">
            <?php _e('سپس در صفحه مقصد می‌توانید با استفاده از $_GET این اطلاعات را دریافت و نمایش دهید.', 'market-google-location'); ?>
        </p>
    </div>
</div>