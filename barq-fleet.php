<?php
/**
 * Plugin Name: BARQ Fleet for WooCommerce
 * Plugin URI: https://barqfleet.com/
 * Description: BARQ Fleet for pickup and delivery. Integration with WooCommerce.
 * Version: 1.0.0
 * Requires at least: 4.4
 * Tested up to: 5.9.1
 * WC requires at least: 3.0.0
 * WC tested up to: 6.2.1
 *
 * Text Domain: barq-fleet
 * Domain Path: /languages/
 * License: GPL v3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WooCommerce\Barq
 * @category Core
 * @author BARQ Fleet
 * @internal This file is only used when running as a feature plugin.
 */

if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}

// Define BARQ_PLUGIN_FILE.
if ( ! defined('BARQ_PLUGIN_FILE') ) {
    define( 'BARQ_PLUGIN_FILE', __FILE__ );
}

// Define BARQ_PLUGIN_URL.
if ( ! defined('BARQ_PLUGIN_URL') ) {
    define( 'BARQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Define BARQ_PLUGIN_SLUG.
if ( ! defined('BARQ_PLUGIN_SLUG') ) {
    define( 'BARQ_PLUGIN_SLUG', 'barq-fleet' );
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // Include the main WooCommerce class.
    if ( ! class_exists('BARQ') ) {
        include_once dirname(__FILE__) . '/classes/class-barq.php';
    }

    /**
     * Get plugin version.
     * @since  1.0.0
     * @return string   plugin version.
     */
    function barq_get_version() {
        $data = get_file_data( __FILE__ , array( 'Version' => 'Version' ) );
        if ( ! empty( $data['Version'] ) ) {
            return $data['Version'];
        }
        if ( ! function_exists('get_plugin_data') ) {
            require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugin_data = get_plugin_data( __FILE__ );
        if ( ! empty( $plugin_data['Version'] ) ) {
            return $plugin_data['Version'];
        }
        return false;
    }

    /**
     * Main instance of BARQ.
     *
     * Returns the main instance of BARQ to prevent the need to use globals.
     *
     * @since  2.0
     * @return BARQ
     */
    function barq() {
        return BARQ::instance();
    }

    // Global for backwards compatibility.
    $GLOBALS['barq'] = barq();

    // $profile = barq()->api->get_merchant_profile();
    // var_dump( $profile );
    // die();
} else {
    add_action( 'admin_notices', 'barq_admin_notice__error' );
    function barq_admin_notice__error() {
        $class   = 'notice notice-error';
        $message = __( 'Please make sure that the WooCommerce is installed and active in order to use "Barq for WooCommerce" plugin.', BARQ_PLUGIN_SLUG );
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
    }
}

// if ( isset( $_GET['create-order'] ) ) {
//     add_action( 'init', function() {
//         barq()->helpers['api-helper']->push_order_to_barq_system( null );
//     } );
// }
