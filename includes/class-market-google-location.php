<?php

class Market_Google_Location {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if ( defined( 'MARKET_GOOGLE_LOCATION_VERSION' ) ) {
            $this->version = MARKET_GOOGLE_LOCATION_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'market-google-location';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        // Core files
        require_once MARKET_GOOGLE_LOCATION_PATH . 'includes/class-market-location-loader.php';
        require_once MARKET_GOOGLE_LOCATION_PATH . 'includes/class-market-location-i18n.php';
        require_once MARKET_GOOGLE_LOCATION_PATH . 'includes/class-market-location-db.php'; // Will be created later
        require_once MARKET_GOOGLE_LOCATION_PATH . 'includes/class-market-location-shortcodes.php'; // Will be created later
        require_once MARKET_GOOGLE_LOCATION_PATH . 'includes/helpers.php'; // Will be created later

        // Admin specific
        require_once MARKET_GOOGLE_LOCATION_PATH . 'admin/class-market-location-admin.php'; // Will be created later

        // Public specific
        // require_once MARKET_GOOGLE_LOCATION_PATH . 'public/class-market-location-public.php'; // If needed

        $this->loader = new Market_Location_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Market_Location_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        if (is_admin()) {
            $plugin_admin = new Market_Location_Admin( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
            // Add other admin hooks here (e.g., for saving settings, handling ajax)
        }
    }

    private function define_public_hooks() {
        // $plugin_public = new Market_Location_Public( $this->get_plugin_name(), $this->get_version() );
        // $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        // $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

        // Shortcodes
        // The Shortcode class itself will handle add_shortcode via its constructor or an init method
        if (class_exists('Market_Location_Shortcodes')) {
             Market_Location_Shortcodes::init();
        }
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}
