<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

final class BARQ {
    // A reference to an instance of this class.
    protected static $_instance = null;
    public $api     = null;
    public $log     = null;
    public $helpers = array();
    public $mapping = array();

    /**
	 * Returns an instance of this class. 
     * @since   1.0
     * @return null|BARQ object     an instance of this class.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Barq constructor.
     * @since   1.0
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_classes();
        $this->init_hooks();
    }

    /**
     * Define Barq Constants.
     * @since   1.0
     */
    private function define_constants() {
        $upload_dir = wp_upload_dir(null, false);
        $this->define( 'BARQ_ABSPATH', dirname( BARQ_PLUGIN_FILE ) . '/' );
        $this->define( 'BARQ_LOGS', $upload_dir['basedir'] . '/barq-logs/' );
    }

    /**
     * Define constant if not already set.
     * @since   1.0
     * @param string $name Constant name.
     * @param string|bool $value Constant value.
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     *  Include Barq plugin files
     * @since   1.0
     */
    private function includes() {
        include_once( BARQ_ABSPATH . 'includes/inc-logs.php' );
        include_once( BARQ_ABSPATH . 'includes/inc-install.php' );
        include_once( BARQ_ABSPATH . 'includes/inc-api-helper.php' );
        include_once( BARQ_ABSPATH . 'includes/inc-frontend-actions.php' );
        include_once( BARQ_ABSPATH . 'includes/inc-backend-actions.php' );
        
        include_once( BARQ_ABSPATH . 'classes/class-api-client.php' );
        include_once( BARQ_ABSPATH . 'classes/class-options.php' );
        include_once( BARQ_ABSPATH . 'classes/class-cron-job.php' );
        include_once( BARQ_ABSPATH . 'classes/class-wc-rest.php' );
        include_once( BARQ_ABSPATH . 'classes/class-status-mapping.php' );
        include_once( BARQ_ABSPATH . 'classes/class-payment-type-mapping.php' );
        include_once( BARQ_ABSPATH . 'classes/class-shipment-type-mapping.php' );
    }

    /**
     *  Init Barq plugin classes
     * @since   1.0
     */
    private function init_classes() {
        $this->api = new Barq_Api_Client();
        $this->log = new Barq_Log();
        $this->helpers['api-helper']    = new Barq_API_helper();
        $this->mapping['status']        = new Barq_Status_Mapping();
        $this->mapping['payment-type']  = new Barq_Payment_Type_Mapping();
        $this->mapping['shipment-type'] = new Barq_Shipment_Type_Mapping();
        new Barq_Cron_Job();
    }

    /**
     * Hook into actions and filters.
     * @since   1.0
     */
    private function init_hooks() {
        register_activation_hook( BARQ_PLUGIN_FILE, array( 'Barq_Install', 'install' ) );
        $this->init();
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    /**
     *  Init Barq WooCommerce related settings
     * @since   1.0
     */
    public function init() {
        add_action( 'woocommerce_shipping_init', array( $this, 'init_shipping_method' ) );
        add_action( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
        add_filter( 'woocommerce_rest_api_get_rest_namespaces', array( $this, 'init_wc_rest_api' ) );
    }

    /**
     *  Include Barq shipping method class
     * @since   1.0
     */
    public function init_shipping_method() {
        if ( ! class_exists('Barq_Shipping_Method') ) {
            include_once( BARQ_ABSPATH . 'classes/class-shipping-method.php' );
        }
    }

    /**
     *  Add Barq Shipping Method
     * @since   1.0
     * @param $methods
     * @return array
     */
    function add_shipping_method( $methods ) {
        if ( class_exists('Barq_Shipping_Method') ) {
            $methods["Barq_Shipping_Method"] = 'Barq_Shipping_Method';
        }
        return $methods;
    }

    /**
     * Init Barq WC Rest API
     * @param $controllers
     * @return mixed
     */
    public function init_wc_rest_api( $controllers ) {
        if ( class_exists('WC_REST_Barq_Controller') ) {
            $controllers['wc/v3']['barq/order/status/update'] = 'WC_REST_Barq_Controller';
        }
        return $controllers;
    }

    /**
     *  Enqueue Admin assets (styles and scripts)
     */
    public function admin_enqueue_scripts() {
        // styles
        wp_enqueue_style( 'admin-styles', BARQ_PLUGIN_URL . 'assets/css/admin-style.css', array(), barq_get_version() );
        // scripts
        wp_enqueue_script('admin-scripts', BARQ_PLUGIN_URL . 'assets/js/scripts.js', array('jquery'), '1.0.0', true);
        wp_localize_script('admin-scripts', 'Barq', array('ajax_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce('barq-fleet-woocommerse-plugin')));
    }
}
