<?php
/**
 * قالب نمایش فرم ثبت موقعیت مکانی
 *
 * @package MarketGoogle\Templates\Public
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// پارامترهای ارسالی از شورت‌کد
$height = $args['height'] ?? '500';
$unique_id = 'market-map-' . uniqid();
?>

<div class="market-location-container">
    <form id="market-location-form" class="market-location-form" method="post">

        <div id="<?php echo esc_attr($unique_id); ?>" class="market-location-map" style="height: <?php echo esc_attr($height); ?>px;">
            <!-- نقشه در اینجا توسط جاوا اسکریپت بارگذاری می‌شود -->
        </div>

        <div class="market-location-fields">

            <h3>اطلاعات کسب و کار</h3>

            <p>
                <label for="business_name">نام کسب و کار:</label>
                <input type="text" name="business_name" id="business_name" required>
            </p>
            <p>
                <label for="phone">شماره تماس:</label>
                <input type="text" name="phone" id="phone" required>
            </p>

            <!-- سایر فیلدها در آینده اضافه خواهند شد -->

        </div>

        <?php wp_nonce_field('market_google_nonce', 'nonce'); ?>
        <input type="hidden" name="latitude" id="latitude" value="">
        <input type="hidden" name="longitude" id="longitude" value="">

        <div class="market-location-submit">
            <button type="submit" class="button button-primary">ثبت و پرداخت</button>
        </div>

    </form>
</div>
