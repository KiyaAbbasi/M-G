<?php
/**
 * قالب نمایش صفحه تنظیمات
 *
 * @package MarketGoogle\Templates\Admin
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap market-google-settings">
    <h1><span class="dashicons dashicons-admin-settings"></span> تنظیمات افزونه ثبت لوکیشن</h1>

    <form method="post" action="options.php">
        <?php
        // این بخش برای ذخیره‌سازی تنظیمات وردپرس ضروری است
        settings_fields('market_google_settings_group');
        ?>

        <h2 class="nav-tab-wrapper">
            <a href="#general" class="nav-tab nav-tab-active">تنظیمات عمومی</a>
            <a href="#payment" class="nav-tab">درگاه‌های پرداخت</a>
            <a href="#sms" class="nav-tab">پیامک</a>
        </h2>

        <div id="tab-general" class="tab-content active">
            <h3>تنظیمات عمومی</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">کلید API نقشه (اختیاری)</th>
                    <td><input type="text" name="market_google_settings[api_key]" value="<?php echo esc_attr(get_option('market_google_settings')['api_key'] ?? ''); ?>" class="regular-text"/></td>
                </tr>
            </table>
        </div>

        <div id="tab-payment" class="tab-content">
            <h3>تنظیمات درگاه پرداخت</h3>
            <p>تنظیمات مربوط به درگاه‌های بانک ملی و زرین‌پال در این بخش قرار خواهد گرفت.</p>
        </div>

        <div id="tab-sms" class="tab-content">
            <h3>تنظیمات سیستم پیامک</h3>
            <p>تنظیمات مربوط به پنل پیامکی و متن پیام‌ها در این بخش قرار خواهد گرفت.</p>
        </div>

        <?php submit_button('ذخیره تنظیمات'); ?>

    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // مدیریت تب‌ها
    $('.nav-tab-wrapper a').click(function(e) {
        e.preventDefault();
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        $(this.hash).addClass('active');
    });
});
</script>
