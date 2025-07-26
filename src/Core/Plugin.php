<?php
namespace MarketGoogle\Core;

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Plugin
 *
 * کلاس اصلی افزونه که تمام بخش‌ها را راه‌اندازی و مدیریت می‌کند.
 *
 * @package MarketGoogle\Core
 */
final class Plugin {

    /**
     * @var Plugin
     */
    private static $instance = null;

    /**
     * @var \MarketGoogle\Admin\Dashboard
     */
    public $admin;

    /**
     * @var \MarketGoogle\Public\Shortcodes
     */
    public $public;

    /**
     * @var \MarketGoogle\Gateway\Payment
     */
    public $payment;

    /**
     * @var \MarketGoogle\Services\Sms\SmsService
     */
    public $sms;

    /**
     * @var \MarketGoogle\Services\TrackingService
     */
    public $tracking;

    /**
     * برگرداندن یک نمونه از کلاس
     *
     * @return Plugin
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * سازنده کلاس
     */
    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->init_classes();
    }

    /**
     * تعریف ثابت‌ها
     */
    private function define_constants() {
        define('MARKET_GOOGLE_LOCATION_SRC_PATH', MARKET_GOOGLE_LOCATION_PATH . 'src/');
        define('MARKET_GOOGLE_LOCATION_ASSETS_URL', MARKET_GOOGLE_LOCATION_URL . 'assets/');
    }

    /**
     * بارگذاری فایل‌های مورد نیاز
     */
    private function includes() {
        // فایل‌های این بخش به تدریج با Autoloader جایگزین خواهند شد
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Utils/Helper.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Core/Activator.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Core/Deactivator.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Database/Migrations.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Admin/Dashboard.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Public/Shortcodes.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Gateway/Payment.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Services/Sms/SmsService.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Services/TrackingService.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Ajax/AdminAjax.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Ajax/PublicAjax.php';
    }

    /**
     * راه‌اندازی هوک‌های وردپرس
     */
    private function init_hooks() {
        // هوک‌های فعال‌سازی و غیرفعال‌سازی
        register_activation_hook(MARKET_GOOGLE_LOCATION_FILE, [__NAMESPACE__ . '\Activator', 'activate']);
        register_deactivation_hook(MARKET_GOOGLE_LOCATION_FILE, [__NAMESPACE__ . '\Deactivator', 'deactivate']);

        // بارگذاری فایل ترجمه
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * راه‌اندازی کلاس‌های اصلی
     */
    private function init_classes() {
        $this->admin = new \MarketGoogle\Admin\Dashboard();
        $this->public = new \MarketGoogle\Public\Shortcodes();
        $this->payment = new \MarketGoogle\Gateway\Payment();
        $this->sms = new \MarketGoogle\Services\Sms\SmsService();
        $this->tracking = new \MarketGoogle\Services\TrackingService();

        // راه‌اندازی کلاس‌های Ajax
        new \MarketGoogle\Ajax\AdminAjax();
        new \MarketGoogle\Ajax\PublicAjax();
    }

    /**
     * بارگذاری فایل ترجمه
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'market-google-location',
            false,
            dirname(plugin_basename(MARKET_GOOGLE_LOCATION_FILE)) . '/languages'
        );
    }
}
