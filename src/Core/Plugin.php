<?php
namespace MarketGoogle\Core;

use MarketGoogle\Admin\Dashboard;
use MarketGoogle\Admin\Assets as AdminAssets;
use MarketGoogle\Public\Shortcodes;
use MarketGoogle\Public\Assets as PublicAssets;
use MarketGoogle\Gateway\Payment;
use MarketGoogle\Services\Sms\SmsService;
use MarketGoogle\Ajax\AdminAjax;
use MarketGoogle\Ajax\PublicAjax;
use MarketGoogle\Database\Migrations;

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

    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        $this->init_classes();
    }

    private function define_constants() {
        define('MARKET_GOOGLE_LOCATION_SRC_PATH', MARKET_GOOGLE_LOCATION_PATH . 'src/');
        define('MARKET_GOOGLE_LOCATION_ASSETS_URL', MARKET_GOOGLE_LOCATION_URL . 'assets/');
    }

    private function includes() {
        // در آینده این بخش با autoloader جایگزین می‌شود
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Core/Activator.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Core/Deactivator.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Database/Migrations.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Admin/Dashboard.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Admin/OrdersList.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Admin/Settings.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Admin/Assets.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Public/Shortcodes.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Public/Assets.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Gateway/Payment.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Gateway/Bmi.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Gateway/Zarinpal.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Services/Sms/SmsService.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Services/Sms/Melipayamak.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Ajax/AdminAjax.php';
        require_once MARKET_GOOGLE_LOCATION_SRC_PATH . 'Ajax/PublicAjax.php';
    }

    private function init_hooks() {
        register_activation_hook(MARKET_GOOGLE_LOCATION_FILE, [__NAMESPACE__ . '\Activator', 'activate']);
        register_deactivation_hook(MARKET_GOOGLE_LOCATION_FILE, [__NAMESPACE__ . '\Deactivator', 'deactivate']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    private function init_classes() {
        new Dashboard();
        new AdminAssets();
        new Shortcodes();
        new PublicAssets();
        new Payment();
        new SmsService();
        new AdminAjax();
        new PublicAjax();
    }

    public function load_textdomain() {
        load_plugin_textdomain(
            'market-google-location',
            false,
            dirname(plugin_basename(MARKET_GOOGLE_LOCATION_FILE)) . '/languages'
        );
    }
}
