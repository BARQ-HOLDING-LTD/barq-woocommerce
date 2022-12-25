<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_API_Helper {
    /**
     * Barq_API_Helper constructor.
     */
    public function __construct() {
        add_action('wp_ajax_get_barq_token', array($this, 'get_barq_token_ajax_handler'));
        add_action('wp_ajax_set_barq_callback', array($this, 'set_barq_callback_ajax_handler'));
        add_action('wp_ajax_set_reload_hubs', array($this, 'set_reload_hubs_ajax_handler'));

    }

	/**
	 * Get token via AJAX call and return it
	 */
	public function get_barq_token_ajax_handler() {
        check_ajax_referer('barq-fleet-woocommerse-plugin', 'nonce');
        $merchant_username = sanitize_text_field( $_POST['username'] );
        $merchant_password = sanitize_text_field( $_POST['password'] );
		$message = '';
		if ( ! empty( $merchant_username ) && ! empty( $merchant_password ) ) {
			$request = array(
				'email'    => $merchant_username,
				'password' => $merchant_password,
			);
			$response = barq()->api->merchant_login( $request );
			if ( $response['success'] ) {
				$options['merchant_token']    = $response['data']['token'];
				$options['merchant_username'] = $merchant_username;
				$options['merchant_password'] = $merchant_password;
				$options['last_login_date']   = time();
				update_option('barq_merchant_credentials', $options);
				// $response = barq()->api->get_merchant_profile();
				// if ( $response['success'] ) {
				// 	$options  = array(
				// 		'merchant_id'      => $response['id'],
				// 		'merchant_name'    => $response['name'],
				// 		'merchant_code'    => $response['code'],
				// 		'merchant_cod_fee' => $response['cod_fee'],
				// 	);
				// 	update_option('barq_merchant_profile', $options);
				// }
				$response = array(
					'message' => __('You are successfully authorized.', BARQ_PLUGIN_SLUG),
					'token'   => $response['data']['token'],
				);
				barq()->log->write( json_encode( $response ), 'ajax-success' );
				wp_send_json_success( $response );
			} else {
				delete_option( 'barq_merchant_credentials' );
				$message = __('Process incomplete. Please check your credentials and try again.', BARQ_PLUGIN_SLUG);
			}
		} else {
			$message = __('Username or password is empty.', BARQ_PLUGIN_SLUG);
		}
		barq()->log->write( 'AJAX, get token: ' . $message, 'ajax-error' );
		$response = array(
			'message' => $message,
		);
		wp_send_json_error( $response );
    }

    /**
     * Get token via AJAX call and return it
     */
    public function set_reload_hubs_ajax_handler() {
        check_ajax_referer('barq-fleet-woocommerse-plugin', 'nonce');
        $message = 'OK';
        barq()->helpers['api-helper']->update_merchant_hub();
        barq()->log->write( 'AJAX, get token: ' . $message, 'ajax-error' );
        $response = array(
            'message' => $message,
        );
        wp_send_json_error( $response );
    }

    /**
     * Get token via AJAX call and return it
     */
    public function set_barq_callback_ajax_handler() {
        check_ajax_referer('barq-fleet-woocommerse-plugin', 'nonce');
        $callback_url = sanitize_text_field( $_POST['callback_url'] );
        $message = '';
        if ( ! empty( $callback_url ) ) {
            $request = array(
                'url'    => $callback_url
            );
            $response = barq()->api->update_callback_url( $request );
            if ( $response['success'] ) {
                $response = array(
                    'message' => __('You are successfully set.', BARQ_PLUGIN_SLUG),
                );
                barq()->log->write( json_encode( $response ), 'ajax-success' );
                wp_send_json_success( $response );
            } else {
                $message = __('Process incomplete. Please check your credentials and try again.', BARQ_PLUGIN_SLUG);
            }
        } else {
            $message = __('Callback is empty.', BARQ_PLUGIN_SLUG);
        }
        barq()->log->write( 'AJAX, set callback: ' . $message, 'ajax-error' );
        $response = array(
            'message' => $message,
        );
        wp_send_json_error( $response );
    }

	/**
	 * Get Merchant Hubs
	 */
	public function get_merchant_hubs() {
		$hubs = array();
		$response = barq()->api->get_merchant_profile();
		if ( isset( $response['success'] ) && $response['success'] && isset( $response['data']['hubs'] ) && ! empty( $response['data']['hubs'] ) && is_array( $response['data']['hubs'] ) ) {
			foreach ( $response['data']['hubs'] as $hub ) {
				if ( isset( $hub['is_active'] ) && $hub['is_active'] ) {
					$hubs[] = $hub;
				}
			}
		}
		return $hubs;
	}

	/**
	 * Get Shipment methods
	 */
	public function get_shipment_methods( $query ) {
		$shipment_methods = array();
		$response = barq()->api->get_shipment_methods( $query );
		if ( isset( $response['success'] ) && $response['success'] && isset( $response['data'] ) && ! empty( $response['data'] ) && is_array( $response['data'] ) ) {
			$shipment_methods = $response['data'];
		}
		return $shipment_methods;
	}

	/**
	 * Update Merchant hub info
	 */
	public function update_merchant_hub() {
        $store_address = get_option('barq_merchant_store_address');
        $merchant_hub  = isset( $store_address['merchant_hub'] ) ? $store_address['merchant_hub'] : '';
		$hubs          = $this->get_merchant_hubs();
		if ( ! empty( $merchant_hub ) && ! empty( $hubs ) ) {
			foreach ( $hubs as $hub ) {
				if ( $hub['id'] == $merchant_hub ) {
					$store_address['merchant_hub_lat']  = $hub['latitude'];
					$store_address['merchant_hub_lng']  = $hub['longitude'];
					$store_address['merchant_hub_code'] = $hub['code'];
					break;
				}
			}
			update_option( 'barq_merchant_store_address', $store_address );
		} else {
			delete_option( 'barq_merchant_store_address' );
		}
	}

	/**
	 * Update Merchant profile info
	 */
	public function update_merchant_profile() {
		$response = barq()->api->get_merchant_profile();
		if ( $response['success'] ) {
			$profile = $response['data'];
			$options = array(
				'merchant_id'      => $profile['id'],
				'merchant_name'    => $profile['name'],
				'merchant_code'    => $profile['code'],
				'merchant_cod_fee' => $profile['cod_fee'],
			);
			update_option('barq_merchant_profile', $options);
		}
	}

	/**
	 * Get location from address using Google maps API
	 * 
	 * @param $address
	 * @param $city
	 * @param $country
	 */
	public function get_location_from_address( $address, $city, $country ) {
		$location      = false;
		$configuration = get_option('barq_service_configuration');
		$gmaps_api_key = isset( $configuration['gmaps_api_key'] ) ? $configuration['gmaps_api_key'] : '';
		if ( ! empty( $gmaps_api_key ) ) {
			$full_address  = array(
				$address,
				$city,
				$country
			);
			$full_address  = implode( ', ', $full_address );
			$response      = wp_remote_get( 'https://maps.googleapis.com/maps/api/geocode/json?address=' . esc_attr( $full_address ) . '&key=' . esc_attr( $gmaps_api_key ) );
			if ( ! is_wp_error( $response ) && isset( $response['response']['code'] ) && $response['response']['code'] == 200 ) {
				$response_body = $response['body'];
				$response_body = json_decode( $response_body, true );
				if ( isset( $response_body['results'][0]['geometry']['location'] ) ) {
					$location = $response_body['results'][0]['geometry']['location'];
				}
				if ( ! isset( $location['lat'] ) || ! isset( $location['lng'] ) || empty( $location['lat'] ) || empty( $location['lng'] ) ) {
					return false;
				}
			}
		}
		return $location;
	}

	/**
	 * Push order to Barq system
	 * 
     * @param $order_wc
	 */
	public function push_order_to_barq_system( $order_wc, $shipping_method ) {
		$store_address    = get_option('barq_merchant_store_address');
		$merchant_profile = get_option('barq_merchant_profile');
		$products         = array();
		$order_items      = $order_wc->get_items();
		if ( ! empty( $order_items ) && is_array( $order_items ) ) {
			foreach ( $order_items as $order_item ) {
				$_product = $order_item->get_product();
				$products[] = array(
					'sku'       => $_product->get_sku(),
					'serial_no' => $_product->get_sku(), //'APL16252417283',
					'name'      => $_product->get_name(),
					'color'     => '',
					'brand'     => '',
					'price'     => $order_item->get_total(),
					'weight_kg' => floatval($_product->get_weight()) * $order_item->get_quantity(),
					'qty'       => $order_item->get_quantity(),
				);
			}
		}
		$customer_first_name = $order_wc->get_shipping_first_name();
		$customer_last_name  = $order_wc->get_shipping_last_name();
		$customer_country    = $order_wc->get_shipping_country();
		$customer_city       = $order_wc->get_shipping_city();
		$customer_mobile     = $order_wc->get_shipping_phone();
		$customer_address    = array(
			$order_wc->get_shipping_address_1(),
			$order_wc->get_shipping_address_2()
		);
		$customer_address    = array_filter( $customer_address );
		if ( empty( $customer_first_name ) ) {
			$customer_first_name = $order_wc->get_billing_first_name();
		}
		if ( empty( $customer_last_name ) ) {
			$customer_last_name = $order_wc->get_billing_last_name();
		}
		if ( empty( $customer_country ) ) {
			$customer_country = $order_wc->get_billing_country();
		}
		if ( empty( $customer_city ) ) {
			$customer_city = $order_wc->get_billing_city();
		}
		if ( empty( $customer_mobile ) ) {
			$customer_mobile = $order_wc->get_billing_phone();
		}
		if ( empty( $customer_address ) ) {
			$customer_address = array(
				$order_wc->get_billing_address_1(),
				$order_wc->get_billing_address_2()
			);
		}
		$customer_country = WC()->countries->countries[ $customer_country ];
		$customer_address = array_filter( $customer_address );
		$customer_address = implode(', ', $customer_address);
		$shipping_method  = str_replace('barq_', '', $shipping_method);
		$payment_method   = $order_wc->get_payment_method();
		switch ( $payment_method ) {
			case 'cod':
				$payment_method = 'cash_on_delivery';
				break;
			default:
				$payment_method = 'credit_card';
				break;
		}
		$destination = $this->get_location_from_address( $customer_address, $customer_city, $customer_country );
		if ( empty( $destination ) ) {
			barq()->log->write( 'Destination location: Failed to get the destination location coordinates.', 'orders-api' );
			return;
		}
		if ( ! isset( $store_address['merchant_hub'] ) || ! isset( $store_address['merchant_hub_code'] ) || empty( $store_address['merchant_hub'] ) || empty( $store_address['merchant_hub_code'] ) ) {
			barq()->log->write( 'Merchant hub: merchant hub not set correctly.', 'orders-api' );
			return;
		}
		$query = array(
			'payment_type'      => $payment_method,
			'shipment_type'     => $shipping_method,
			'hub_id'            => $store_address['merchant_hub'],
			'hub_code'          => $store_address['merchant_hub_code'],
			'merchant_order_id' => 'wc-' . $merchant_profile['merchant_id'] . '-' . $order_wc->get_id(),
			'invoice_total'     => $order_wc->get_total(),
			'products'          => $products,
			'destination'       => array(
				'latitude'  => esc_attr( $destination['lat'] ),
				'longitude' => esc_attr( $destination['lng'] ),
			),
			'customer_details' => array(
				'first_name' => $customer_first_name,
				'last_name'  => $customer_last_name,
				'country'    => $customer_country,
				'city'       => $customer_city,
				'mobile'     => $customer_mobile,
				'address'    => $customer_address,
			),
		);
		$response = barq()->api->push_order_to_barq_system( $query );
		barq()->log->write( 'Create order query: ' . json_encode( $query ), 'orders-api' );
		barq()->log->write( 'Create order response: ' . json_encode( $response ), 'orders-api' );
		if ( isset( $response['success'] ) && $response['success'] && isset( $response['data'] ) && ! empty( $response['data'] ) ) {
			$this->update_order_meta_fields( $order_wc, $response['data'] );
		}
	}

	public function update_order_meta_fields_from_barq( $order_id ) {
        if ( ! $order_id ) {
            return;
		}
		$order_wc = new WC_Order( $order_id );
		if ( ! is_a( $order_wc, 'WC_Order' ) ) {
			return;
		}
		$barq_order_id = get_post_meta( $order_id, 'barq_order_id', true );
		if ( ! empty( $barq_order_id ) && intval( $barq_order_id ) ) {
			$response = barq()->api->get_order_information_from_barq_system( $barq_order_id );
			if ( isset( $response['success'] ) && $response['success'] && isset( $response['data'] ) && ! empty( $response['data'] ) ) {
				$this->update_order_meta_fields( $order_wc, $response['data'] );
			}
		}
	}

	public function update_order_meta_fields( $order_wc, $barq_response ) {
		$order_wc_id = $order_wc->get_id();
		$response_fields = array(
			'id',
			'tracking_no',
			'order_status',
			'payment_type',
			'shipment_type',
			'is_assigned',
			'origin',
			'destination',
			'courier',
			'shipment',
		);
		foreach ( $response_fields as $field ) {
			if ( isset( $barq_response[ $field ] ) && ! empty( $barq_response[ $field ] ) ) {
				$meta_key = 'barq_' . $field;
				if ( $field == 'id' ) {
					$meta_key = 'barq_order_' . $field;
				}
				$meta_value = $barq_response[ $field ];
				if ( is_array( $meta_value ) ) {
					$meta_value = array_map( 'esc_attr', $meta_value );
					$meta_value = json_encode( $meta_value );
				} else {
					$meta_value = esc_attr( $meta_value );
				}
				update_post_meta( $order_wc_id, $meta_key, $meta_value );
			}
		}
	}
}