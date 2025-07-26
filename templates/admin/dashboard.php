<?php
/**
 * قالب نمایش صفحه داشبورد مدیریت
 *
 * @package MarketGoogle\Templates\Admin
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// دریافت آمار (در آینده از یک کلاس Helper دریافت می‌شود)
$total_locations = 0;
$completed_payments = 0;

?>

<div class="wrap market-google-dashboard">
    <h1><span class="dashicons dashicons-location-alt"></span> داشبورد ثبت لوکیشن</h1>

    <div class="welcome-panel">
        <h2>به پنل مدیریت افزونه ثبت لوکیشن خوش آمدید!</h2>
        <p class="about-description">در این بخش می‌توانید آمار کلی، گزارش‌ها و آخرین فعالیت‌های کاربران را مشاهده کنید.</p>
    </div>

    <div class="metabox-holder">
        <div class="postbox-container">
            <div class="postbox">
                <h2 class="hndle"><span>آمار کلی</span></h2>
                <div class="inside">
                    <ul>
                        <li>تعداد کل موقعیت‌های ثبت شده: <strong><?php echo esc_html($total_locations); ?></strong></li>
                        <li>تعداد پرداخت‌های موفق: <strong><?php echo esc_html($completed_payments); ?></strong></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="postbox-container">
            <div class="postbox">
                <h2 class="hndle"><span>راهنمای سریع</span></h2>
                <div class="inside">
                    <p>برای نمایش فرم ثبت موقعیت در یک برگه، از شورت‌کد <strong>[market_location_form]</strong> استفاده کنید.</p>
                    <p>برای مدیریت سفارشات به صفحه <a href="<?php echo admin_url('admin.php?page=market-google-orders'); ?>">لیست سفارشات</a> مراجعه کنید.</p>
                    <p>برای انجام تنظیمات افزونه به صفحه <a href="<?php echo admin_url('admin.php?page=market-google-settings'); ?>">تنظیمات</a> بروید.</p>
                </div>
            </div>
        </div>
    </div>

</div>
