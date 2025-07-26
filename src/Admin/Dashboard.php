<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dashboard
 *
 * مدیریت منو‌ها و صفحه داشبورد پنل مدیریت
 *
 * @package MarketGoogle\Admin
 */
class Dashboard {

    /**
     * سازنده کلاس
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }

    /**
     * اضافه کردن منوهای افزونه به پیشخوان وردپرس
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Market Google', 'market-google-location'),
            __('ثبت لوکیشن', 'market-google-location'),
            'manage_options',
            'market-google-dashboard',
            [$this, 'render_dashboard_page'],
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'market-google-dashboard',
            __('داشبورد', 'market-google-location'),
            __('🏠 داشبورد', 'market-google-location'),
            'manage_options',
            'market-google-dashboard',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'market-google-dashboard',
            __('لیست سفارشات', 'market-google-location'),
            __('📋 لیست سفارشات', 'market-google-location'),
            'manage_options',
            'market-google-orders',
            [new OrdersList(), 'render_page']
        );

        add_submenu_page(
            'market-google-dashboard',
            __('تنظیمات', 'market-google-location'),
            __('⚙️ تنظیمات', 'market-google-location'),
            'manage_options',
            'market-google-settings',
            [new Settings(), 'render_page']
        );
    }

    /**
     * رندر کردن صفحه داشبورد
     */
    public function render_dashboard_page() {
        global $wpdb;
        $locations_table = $wpdb->prefix . 'market_google_locations';

        $total_locations = $wpdb->get_var("SELECT COUNT(*) FROM {$locations_table}");
        $completed_payments = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$locations_table} WHERE payment_status = %s", 'completed'));

        // استفاده از فایل قالب برای نمایش
        include_once MARKET_GOOGLE_LOCATION_PATH . 'templates/admin/dashboard.php';
    }
}
