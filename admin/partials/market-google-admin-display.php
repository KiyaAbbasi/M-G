<?php
/**
 * نمایش صفحه مدیریت افزونه
 */

// بررسی دسترسی مستقیم
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="market-google-admin-container">
        <div class="market-google-admin-header">
            <h2><?php _e('لیست لوکیشن‌های ثبت شده', 'market-google-location'); ?></h2>
        </div>
        
        <?php
        // دریافت لیست لوکیشن‌ها از دیتابیس
        global $wpdb;
        $table_name = $wpdb->prefix . 'market_google_locations';
        $locations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        
        <?php if (empty($locations)) : ?>
            <div class="market-google-admin-notice">
                <p><?php _e('هنوز هیچ لوکیشنی ثبت نشده است.', 'market-google-location'); ?></p>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('شناسه', 'market-google-location'); ?></th>
                        <th><?php _e('نام کسب و کار', 'market-google-location'); ?></th>
                        <th><?php _e('شماره تماس', 'market-google-location'); ?></th>
                        <th><?php _e('آدرس', 'market-google-location'); ?></th>
                        <th><?php _e('مختصات', 'market-google-location'); ?></th>
                        <th><?php _e('تاریخ ثبت', 'market-google-location'); ?></th>
                        <th><?php _e('عملیات', 'market-google-location'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location) : ?>
                        <tr>
                            <td><?php echo $location->id; ?></td>
                            <td><?php echo esc_html($location->business_name); ?></td>
                            <td><?php echo esc_html($location->business_phone); ?></td>
                            <td><?php echo esc_html($location->address); ?></td>
                            <td>
                                <?php echo esc_html($location->latitude); ?>, 
                                <?php echo esc_html($location->longitude); ?>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($location->created_at)); ?></td>
                            <td>
                                <a href="#" class="button view-location" data-id="<?php echo $location->id; ?>"><?php _e('مشاهده', 'market-google-location'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- مودال نمایش جزئیات لوکیشن -->
    <div id="location-details-modal" class="market-google-modal" style="display:none;">
        <div class="market-google-modal-content">
            <span class="market-google-modal-close">&times;</span>
            <h2><?php _e('جزئیات لوکیشن', 'market-google-location'); ?></h2>
            <div id="location-details-content"></div>
            <div id="location-details-map" style="height:300px;"></div>
        </div>
    </div>
</div>