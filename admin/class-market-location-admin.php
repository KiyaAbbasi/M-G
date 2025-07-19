<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Market_Google_Location
 * @subpackage Market_Google_Location/admin
 * @author     Your Name <email@example.com>
 */
class Market_Location_Admin {

    private $plugin_name;
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     * @param string $hook The current admin page.
     */
    public function enqueue_styles( $hook ) {
        /**
         * An example of how to enqueue a stylesheet for a specific admin page.
         *
         * The $hook parameter is the same as $current_screen->id from get_current_screen() function.
         * All admin pages === 'toplevel_page_market-google-location'
         * Settings page === 'market-google-location_page_mgl-settings'
         * Locations page === 'market-google-location_page_mgl-locations' (or similar)
         * Stats page === 'market-google-location_page_mgl-stats' (or similar)
         */
        // if ( 'toplevel_page_market-google-location' !== $hook && strpos($hook, 'market-google-location_page_') === false ) {
        //  return;
        // }

        wp_enqueue_style( $this->plugin_name . '_admin', MARKET_GOOGLE_LOCATION_URL . 'admin/assets/css/admin-style.css', array(), $this->version, 'all' );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     * @param string $hook The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // if ( 'toplevel_page_market-google-location' !== $hook && strpos($hook, 'market-google-location_page_') === false ) {
        //  return;
        // }

        wp_enqueue_script( $this->plugin_name . '_admin', MARKET_GOOGLE_LOCATION_URL . 'admin/assets/js/admin-script.js', array( 'jquery' ), $this->version, false );
        
        // For media uploader (if needed for settings)
        // wp_enqueue_media();

        // For Chart.js (if loaded locally)
        // wp_enqueue_script( $this->plugin_name . '_chartjs', MARKET_GOOGLE_LOCATION_URL . 'vendor/chart.js/Chart.min.js', array(), '2.9.4', false );
    }

    /**
     * Adds the admin menu pages.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Locations', 'market-google-location' ), // Page title
            __( 'Business Locations', 'market-google-location' ), // Menu title
            'manage_options', // Capability
            'market-google-location', // Menu slug
            array( $this, 'display_locations_page' ), // Function
            'dashicons-location-alt', // Icon URL
            26 // Position
        );

        add_submenu_page(
            'market-google-location', // Parent slug
            __( 'All Locations', 'market-google-location' ), // Page title
            __( 'All Locations', 'market-google-location' ), // Menu title
            'manage_options', // Capability
            'market-google-location', // Menu slug (same as parent for the first item)
            array( $this, 'display_locations_page' ) // Function
        );
        
        add_submenu_page(
            'market-google-location',
            __( 'Add New Location', 'market-google-location' ),
            __( 'Add New', 'market-google-location' ),
            'manage_options', // Or a more specific capability like 'publish_posts' if non-admins can add
            'mgl-add-new',
            array( $this, 'display_add_new_location_page' )
        );

        add_submenu_page(
            'market-google-location',
            __( 'Statistics', 'market-google-location' ),
            __( 'Statistics', 'market-google-location' ),
            'manage_options',
            'mgl-stats',
            array( $this, 'display_stats_page' )
        );

        add_submenu_page(
            'market-google-location',
            __( 'Settings', 'market-google-location' ),
            __( 'Settings', 'market-google-location' ),
            'manage_options',
            'mgl-settings',
            array( $this, 'display_settings_page' )
        );
    }

    /**
     * Display callback for the main locations list page.
     *
     * @since    1.0.0
     */
    public function display_locations_page() {
        // We will require the WP_List_Table class and our custom table here.
        // require_once MARKET_GOOGLE_LOCATION_PATH . 'admin/partials/locations-list-table.php';
        // $list_table = new Market_Location_List_Table();
        // $list_table->prepare_items();
        echo '<div class="wrap">';
        echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
        // $list_table->display();
        echo '<p>Location list table will be displayed here.</p>'; // Placeholder
        echo '</div>';
    }

    /**
     * Display callback for the add new location page.
     *
     * @since    1.0.0
     */
    public function display_add_new_location_page() {
        // This page could reuse the public shortcode form or have its own admin-specific form.
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'Add New Location', 'market-google-location' ) . '</h1>';
        // Form HTML will go here
        // For now, we can put a placeholder or include the shortcode form if appropriate
        // echo do_shortcode('[market_location_form admin_context="true"]');
        echo '<p>Form to add a new location will be displayed here.</p>'; // Placeholder
        echo '</div>';
    }
    
    /**
     * Display callback for the statistics page.
     *
     * @since    1.0.0
     */
    public function display_stats_page() {
        require_once MARKET_GOOGLE_LOCATION_PATH . 'admin/views/stats-page.php';
    }

    /**
     * Display callback for the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        require_once MARKET_GOOGLE_LOCATION_PATH . 'admin/views/settings-page.php';
    }

    // Add other methods for saving settings, handling AJAX requests for admin, etc.
    // For example: public function save_settings() { ... }
}
