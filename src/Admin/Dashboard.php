<?php
namespace MarketGoogle\Admin;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Dashboard
 *
 * مدیریت منو‌ها و صفحات اصلی پنل مدیریت افزونه
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
            __('Market Google Location', 'market-google-location'),
            __('ثبت لوکیشن', 'market-google-location'),
            'manage_options',
            'market-google-location',
            [$this, 'render_dashboard_page'],
            'dashicons-location-alt',
            30
        );

        add_submenu_page(
            'market-google-location',
            __('داشبورد', 'market-google-location'),
            __('🏠 داشبورد', 'market-google-location'),
            'manage_options',
            'market-google-location',
            [$this, 'render_dashboard_page']
        );

        add_submenu_page(
            'market-google-location',
            __('لیست سفارشات', 'market-google-location'),
            __('📋 لیست سفارشات', 'market-google-location'),
            'manage_options',
            'market-google-orders',
            [new OrdersList(), 'render_page']
        );

        add_submenu_page(
            'market-google-location',
            __('تنظیمات', 'market-google-location'),
            __('⚙️ تنظیمات', 'market-google-location'),
            'manage_options',
            'market-google-settings',
            [new Settings(), 'render_page']
        );

        // منوی ردیابی کاربران (در صورت فعال بودن)
        // add_submenu_page(...)
    }

    /**
     * رندر کردن صفحه داشبورد
     * در آینده، این متد از یک فایل template برای نمایش محتوا استفاده خواهد کرد.
     */
    public function render_dashboard_page() {
        echo '<div class="wrap"><h1>داشبورد افزونه ثبت لوکیشن</h1><p>اینجا آمار و گزارش‌های کلی نمایش داده خواهد شد.</p></div>';
    }
}
