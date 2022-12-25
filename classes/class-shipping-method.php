<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

if ( ! class_exists( 'Barq_Shipping_Method' ) ) {
    class Barq_Shipping_Method extends WC_Shipping_Method {
        /**
         * Constructor for your shipping class
         *
         * @return void
         */
        public function __construct() {
            parent::__construct();
            $this->id           = 'barq';
            $this->enabled      = $this->get_option('enabled');
            $this->method_title = __('Barq', BARQ_PLUGIN_SLUG);
            $this->init();
            $this->title = $this->settings['title'] ?? __('Barq Shipping', BARQ_PLUGIN_SLUG);
        }

        /**
         * Init your settings
         *
         * @return void
         */
        function init() {
            $this->init_form_fields();
            $this->init_settings();
            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }
        /**
         * Define settings field for this shipping
         * 
         * @return void
         */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'       => __('Enable', BARQ_PLUGIN_SLUG),
                    'type'        => 'checkbox',
                    'description' => __('Enable this shipping.', BARQ_PLUGIN_SLUG),
                    'default'     => 'yes'
                ),
    
                'title' => array(
                    'title'       => __('Title', BARQ_PLUGIN_SLUG),
                    'type'        => 'text',
                    'description' => __('Title to be display on site', BARQ_PLUGIN_SLUG),
                    'default'     => __('Barq Shipping', BARQ_PLUGIN_SLUG)
                ),
    
                'weight' => array(
                    'title'       => __('Weight (kg)', BARQ_PLUGIN_SLUG),
                    'type'        => 'number',
                    'description' => __('Maximum allowed weight', BARQ_PLUGIN_SLUG),
                    'default'     => 100
                ),
            );
        }

        /**
         * calculate_shipping function.
         *
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package = array() ) {
            if ( ! $this->enabled ) {
                return;
            }
            $weight = 0;
            $lat_1  = 0;
            $lng_1  = 0;
            $lat_2  = 24.9456402;
            $lng_2  = 46.5890501;

            foreach ( $package['contents'] as $item_id => $values ) {
                $_product = $values['data'];
                if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
                    $weight += intval( $values['quantity'] ) * floatval( $_product->get_weight() );
                }
            }
            $weight = wc_get_weight($weight, 'kg');

            $store_address = get_option('barq_merchant_store_address');
            if ( isset( $store_address['merchant_hub_lat'] ) && ! empty( $store_address['merchant_hub_lat'] ) ) {
                $lat_1  = floatval( $store_address['merchant_hub_lat'] );
            }
            if ( isset( $store_address['merchant_hub_lng'] ) && ! empty( $store_address['merchant_hub_lng'] ) ) {
                $lng_1  = floatval( $store_address['merchant_hub_lng'] );
            }

            $destination_country = '';
            $destination_city    = '';
            $destination_address = array();
            if ( isset( $package['destination']['country'] ) && ! empty( $package['destination']['country'] ) ) {
                $destination_country = $package['destination']['country'];
                $destination_country = WC()->countries->countries[ $destination_country ];
            }
            if ( isset( $package['destination']['city'] ) && ! empty( $package['destination']['city'] ) ) {
                $destination_city = $package['destination']['city'];
            }
            if ( isset( $package['destination']['address_1'] ) && ! empty( $package['destination']['address_1'] ) ) {
                $destination_address[] = $package['destination']['address_1'];
            }
            if ( isset( $package['destination']['address_2'] ) && ! empty( $package['destination']['address_2'] ) ) {
                $destination_address[] = $package['destination']['address_2'];
            }
            $destination_address = array_filter( $destination_address );
            $destination_address = implode(', ', $destination_address);
            $destination         = barq()->helpers['api-helper']->get_location_from_address( $destination_address, $destination_city, $destination_country );
            barq()->log->write( $lat_1 . $lat_2, 'map-api' );
            if ( ! empty( $destination ) && isset( $destination['lat'] ) && isset( $destination['lng'] ) ) {
                $lat_2 = $destination['lat'];
                $lng_2 = $destination['lng'];
            } else {
                barq()->log->write( 'Destination location: Failed to get the destination location coordinates.', 'orders-api' );
            }

            if ( ! empty( $lat_1 ) && ! empty( $lng_1 ) && ! empty( $lat_2 ) && ! empty( $lng_2 ) ) {
                $query = array(
                    'lat1'   => $lat_1,
                    'lng1'   => $lng_1,
                    'lat2'   => $lat_2,
                    'lng2'   => $lng_2,
                    'weight' => 3,
                );
                if ( ! empty( $weight ) ) {
                    $query['weight'] = $weight;
                }
                $shipment_methods = barq()->helpers['api-helper']->get_shipment_methods( $query );
                if ( ! empty( $shipment_methods ) ) {
                    foreach ( $shipment_methods as $shipment_method ) {
                        $rate = array(
                            'id'    => $this->id . '_' . $shipment_method['shipment_type'],
                            'label' => $shipment_method['name'] . ' - ' . $shipment_method['description'],
                            'cost'  => $shipment_method['price'],
                        );
                        $this->add_rate( $rate );
                    }
                }
            }
        }
    }
}