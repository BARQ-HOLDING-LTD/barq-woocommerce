<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_Options {
    /**
     * Barq_Options constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'init' ) );
    }

    /**
     *   Add menu to Admin Menu Container
     */
    public function add_admin_menu() {
        $dir = plugin_dir_url( __FILE__ );
        $dir = str_replace( 'classes', 'assets', $dir );
        add_menu_page(
			__('Barq', BARQ_PLUGIN_SLUG),
			__('Barq', BARQ_PLUGIN_SLUG),
			'manage_options',
			BARQ_PLUGIN_SLUG,
			array( $this, 'render_options' ),
			BARQ_PLUGIN_URL . 'assets/images/logo.png'
		);
    }

    /**
     *   Init Options
     */
    public function init() {
        $this->init_barq_service_configuration_fields();
        $this->init_merchant_credentials_fields();
        $this->init_merchant_store_address_fields();
        $this->init_order_configuration_fields();
    }

    /**
     * Barq service configuration fields
     */
    public function init_barq_service_configuration_fields() {
        register_setting(
            'barq_service_configuration_section', // Option group
            'barq_service_configuration'
        );

        add_settings_section(
            'barq_service_configuration_section_tab',
            __('Barq service configuration', BARQ_PLUGIN_SLUG),
            array( $this, 'barq_service_configuration_section_callback' ),
            'page_barq_service_configuration'
        );
        
        add_settings_field(
            'staging_mode',
            __('Staging mode', BARQ_PLUGIN_SLUG),
            array($this, 'staging_mode_render'),
            'page_barq_service_configuration',
            'barq_service_configuration_section_tab'
        );
        
        add_settings_field(
            'gmaps_api_key',
            __('Google maps API key', BARQ_PLUGIN_SLUG),
            array($this, 'gmaps_api_key_render'),
            'page_barq_service_configuration',
            'barq_service_configuration_section_tab'
        );
    }

    /**
     *   Merchant credentials fields
     */
    public function init_merchant_credentials_fields() {
        register_setting(
            'merchant_credentials_section', // Option group
            'barq_merchant_credentials'
        );

        add_settings_section(
            'merchant_credentials_section_tab',
            __('Merchant credentials', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_credentials_section_callback' ),
            'page_merchant_credentials'
        );

        add_settings_field(
            'merchant_username',
            __('Merchant Email', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_username_render' ),
            'page_merchant_credentials',
            'merchant_credentials_section_tab'
        );

        add_settings_field(
            'merchant_password',
            __('Merchant Password', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_password_render' ),
            'page_merchant_credentials',
            'merchant_credentials_section_tab'
        );

        add_settings_field(
            'merchant_get_token',
            '',
            array( $this, 'merchant_get_token_render' ),
            'page_merchant_credentials',
            'merchant_credentials_section_tab'
        );

        add_settings_field(
            'merchant_token',
            '',
            array( $this, 'merchant_token_render' ),
            'page_merchant_credentials',
            'merchant_credentials_section_tab'
        );
    }

    /**
     *   Merchant store address fields
     */
    public function init_merchant_store_address_fields() {
        register_setting(
            'merchant_store_address_section', // Option group
            'barq_merchant_store_address'
        );

        add_settings_section(
            'merchant_store_address_section_tab',
            __('Merchant store address', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_store_address_section_callback' ),
            'page_merchant_store_address'
        );

        add_settings_field(
            'merchant_hub',
            __('Merchant Hub', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_hub_render' ),
            'page_merchant_store_address',
            'merchant_store_address_section_tab'
        );

        add_settings_field(
            'merchant_hub_lat',
            __('Merchant Hub Latitude', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_hub_lat_render' ),
            'page_merchant_store_address',
            'merchant_store_address_section_tab'
        );


        add_settings_field(
            'merchant_hub_lng',
            __('Merchant Hub Longitude', BARQ_PLUGIN_SLUG),
            array( $this, 'merchant_hub_lng_render' ),
            'page_merchant_store_address',
            'merchant_store_address_section_tab'
        );

        add_settings_field(
            'merchant_get_token',
            '',
            array( $this, 'hub_reload_locations' ),
            'page_merchant_store_address',
            'merchant_store_address_section_tab'
        );
    }

    /**
     *   Order configuration fields
     */
    public function init_order_configuration_fields() {
        register_setting(
            'order_configuration_section', // Option group
            'barq_order_configuration'
        );

        add_settings_section(
            'order_configuration_section_tab',
            __('Merchant store address', BARQ_PLUGIN_SLUG),
            array( $this, 'order_configuration_section_callback' ),
            'page_order_configuration'
        );


        add_settings_field(
            'woo_webhook',
            __('WooCommerce webhook link', BARQ_PLUGIN_SLUG),
            array( $this, 'woo_webhook_render' ),
            'page_order_configuration',
            'order_configuration_section_tab'
        );

        add_settings_field(
            'merchant_get_token',
            '',
            array( $this, 'merchant_set_callback_render' ),
            'page_order_configuration',
            'order_configuration_section_tab'
        );

    }

    /**
     *   Barq service configuration section
     */
    public function barq_service_configuration_section_callback() {
        echo '';
    }

    /**
     *  Staging mode field
     */
    public function staging_mode_render() {
        $configuration = get_option('barq_service_configuration');
        $option_value  = isset( $configuration['staging_mode'] ) ? $configuration['staging_mode'] : '';
        ?>
            <select id='barq-staging-mode' name='barq_service_configuration[staging_mode]' class="barq-options-input" title="Staging mode">
                <option value='1' <?php selected($option_value, 1); ?>><?php _e('Yes', BARQ_PLUGIN_SLUG); ?></option>
                <option value='2' <?php selected($option_value, 2); ?>><?php _e('No', BARQ_PLUGIN_SLUG); ?></option>
            </select>
        <?php
    }

    /**
     *  Google maps API key field
     */
    public function gmaps_api_key_render() {
        $configuration = get_option('barq_service_configuration');
        $option_value  = isset( $configuration['gmaps_api_key'] ) ? $configuration['gmaps_api_key'] : '';
        ?>
        	<input id='barq-google-maps-api-key' class='barq-options-input' type='text' required title='<?php _e('Google maps API key', BARQ_PLUGIN_SLUG); ?>' name='barq_service_configuration[gmaps_api_key]' value='<?php echo esc_attr( $option_value ); ?>' />
        <?php
    }

    /**
     *   Merchant credentials section
     */
    public function merchant_credentials_section_callback() {
        echo '';
    }

    /**
     *  Merchant userName field
     */
    public function merchant_username_render() {
        $credentials  = get_option('barq_merchant_credentials');
        $option_value = isset( $credentials['merchant_username'] ) ? $credentials['merchant_username'] : '';
        ?>
        	<input id='barq-merchant-username' class='barq-options-input' type='text' required title='<?php _e('Merchant Username', BARQ_PLUGIN_SLUG); ?>' name='barq_merchant_credentials[merchant_username]' value='<?php echo esc_attr( $option_value ); ?>' />
        <?php
    }

    /**
     *  Merchant password render
     */
    function merchant_password_render() {
        $credentials  = get_option('barq_merchant_credentials');
        $option_value = isset( $credentials['merchant_password'] ) ? $credentials['merchant_password'] : '';
        ?>
        	<input id='barq-merchant-password' class='barq-options-input' type='password' required title='<?php _e('Merchant Password', BARQ_PLUGIN_SLUG); ?>' name='barq_merchant_credentials[merchant_password]' value='<?php echo esc_attr( $option_value ); ?>' />
        <?php
    }

    /**
     *  Merchant get token render
     */
    function merchant_get_token_render() {
        ?>
            <button id="barq-get-token" class="button button-primary barq-button"><?php _e('Get Token', BARQ_PLUGIN_SLUG); ?></button>
            <span class="spinner"></span>
        <?php
    }

    /**
     *  Merchant Token Hidden Field
     */
    function merchant_token_render() {
        $credentials  = get_option('barq_merchant_credentials');
        $option_value = isset( $credentials['merchant_token'] ) ? $credentials['merchant_token'] : '';
        $alert_style  = 'display: none;';
        if ( barq()->api->is_token_valid() ) {
            $alert_style = '';
            // echo '<div class="barq-alert barq-alert-success">' . __('You are successfully authorized.', BARQ_PLUGIN_SLUG) . '</div>';
        }
        ?>
            <div class="barq-alert barq-alert-success" style="<?php echo esc_attr( $alert_style ); ?>"><?php echo __('You are successfully authorized.', BARQ_PLUGIN_SLUG); ?></div>
        	<input id='barq-merchant-token' type='hidden' title='<?php _e('Merchant Token', BARQ_PLUGIN_SLUG); ?>' name='barq_merchant_credentials[merchant_token]' value='<?php echo esc_attr( $option_value ); ?>' />
        <?php
    }

    /**
     *   Merchant store address section
     */
    public function merchant_store_address_section_callback() {
        echo '';
    }

    /**
     *  Merchant Hub field
     */
    public function merchant_hub_render() {
        $store_address = get_option('barq_merchant_store_address');
        $option_value  = isset( $store_address['merchant_hub'] ) ? $store_address['merchant_hub'] : '';
        $merchant_hubs = barq()->helpers['api-helper']->get_merchant_hubs();
        ?>
            <select id='barq-staging-mode' name='barq_merchant_store_address[merchant_hub]' class="barq-options-input" title="Merchant hub" required>
                <option value=''>---</option>
                <?php
                    foreach ( $merchant_hubs as $hub ) {
                        echo '<option value="' . esc_attr( $hub['id'] ) . '" ' . selected($option_value, esc_attr( $hub['id'] ), false) . '>';
                        printf( __('City: %s, Manager: %s', BARQ_PLUGIN_SLUG) , esc_attr( $hub['city']['name'] ), esc_attr( $hub['manager'] ) );
                        echo '</option>';
                    }
                ?>
            </select>
        <?php
    }

    /**
     *  Merchant Hub Lat field
     */
    public function merchant_hub_lat_render() {
        $store_address = get_option('barq_merchant_store_address');
        if ( isset( $store_address['merchant_hub_lat'] ) && ! empty( $store_address['merchant_hub_lat'] ) ) {
            echo floatval( $store_address['merchant_hub_lat'] );
        }
    }

    /**
     *  Merchant Hub Lng field
     */
    public function merchant_hub_lng_render() {
        $store_address = get_option('barq_merchant_store_address');
        if ( isset( $store_address['merchant_hub_lng'] ) && ! empty( $store_address['merchant_hub_lng'] ) ) {
            echo floatval( $store_address['merchant_hub_lng'] );
        }
    }

    /**
     *   Merchant store address section
     */
    public function order_configuration_section_callback() {
        echo '';
    }
    /**
     *  Woo webhook field
     */
    public function woo_webhook_render() {
        echo '<span id="barq-webhook-callback-url" class="barq-options-input">' . site_url( '/' ) . 'wp-json/wc/v3/barq/order/status/update</span>';
    }

    /**
     *  Merchant get token render
     */
    function merchant_set_callback_render() {
        ?>
        <button id="barq-set-callback" class="button button-primary barq-button"><?php _e('Set Callback', BARQ_PLUGIN_SLUG); ?></button>
        <span class="spinner"></span>
        <?php
    }

    function hub_reload_locations() {
        ?>
        <button id="barq-get-hubs" type="button" class="button button-primary barq-button"><?php _e('Reload locations', BARQ_PLUGIN_SLUG); ?></button>
        <span class="spinner"></span>
        <?php
    }

    /**
     *  Rendering Options Fields
     */
    public function render_options() {
        if ( isset( $_GET['tab'] ) ) {
            $active_tab = sanitize_text_field( $_GET['tab'] );
        } else {
            $active_tab = 'barq_service_configuration';
        }
        ?>
            <form action='options.php' method='post'>
                <h1><?php _e( 'Barq Merchant Settings', BARQ_PLUGIN_SLUG); ?></h1>
                <hr/>
                <div class="nav-tab-wrapper">
                    <a href="?page=<?php echo BARQ_PLUGIN_SLUG; ?>&tab=barq_service_configuration" class="nav-tab <?php echo $active_tab == 'barq_service_configuration' ? 'nav-tab-active' : ''; ?>"><?php _e('Barq service configuration', BARQ_PLUGIN_SLUG); ?></a>
                    <a href="?page=<?php echo BARQ_PLUGIN_SLUG; ?>&tab=merchant_credentials"       class="nav-tab <?php echo $active_tab == 'merchant_credentials'       ? 'nav-tab-active' : ''; ?>"><?php _e('Merchant credentials', BARQ_PLUGIN_SLUG); ?></a>
                    <a href="?page=<?php echo BARQ_PLUGIN_SLUG; ?>&tab=merchant_store_address"     class="nav-tab <?php echo $active_tab == 'merchant_store_address'     ? 'nav-tab-active' : ''; ?>"><?php _e('Merchant store Address Details', BARQ_PLUGIN_SLUG); ?></a>
                    <a href="?page=<?php echo BARQ_PLUGIN_SLUG; ?>&tab=order_configuration"        class="nav-tab <?php echo $active_tab == 'order_configuration'        ? 'nav-tab-active' : ''; ?>"><?php _e('Order configuration', BARQ_PLUGIN_SLUG); ?></a>
                </div>
                <?php
                    if ($active_tab == 'barq_service_configuration') {
                        settings_fields( 'barq_service_configuration_section' );
                        do_settings_sections( 'page_barq_service_configuration' );
                    } else if ($active_tab == 'merchant_credentials') {
                        settings_fields('merchant_credentials_section');
                        do_settings_sections('page_merchant_credentials');
                    } else if ($active_tab == 'merchant_store_address') {
                        settings_fields('merchant_store_address_section');
                        do_settings_sections('page_merchant_store_address');
                    } else if ($active_tab == 'order_configuration') {
                        settings_fields('order_configuration_section');
                        do_settings_sections('page_order_configuration');
                    }
                    submit_button();
                ?>
            </form>
        <?php
	}
}

new Barq_Options();