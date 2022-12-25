<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_Api_Client {
	private $base_url = array(
		'test' => 'https://staging.barqfleet.com',
		'live' => 'https://live.barqfleet.com',
	);

	private $merchant_login_url   = "/api/v1/merchants/login";
	private $merchant_profile_url = "/api/v1/merchants/profile";
	private $shipment_methods_url = "/api/v1/merchants/shipping_methods";
	private $orders_url           = "/api/v1/merchants/orders";

    private $merchant_update_url = "/api/v1/merchants/webhook";
	private $merchant_credentials = array();
	private $merchant_profile     = array();

	/**
	 * Barq_Api_Client constructor.
	 */
	function __construct() {
		$this->init();
	}

	/**
	 * Initialize the API settings
	 */
	private function init() {
		$this->merchant_credentials = get_option('barq_merchant_credentials');
		$this->merchant_profile     = get_option('barq_merchant_profile');
	}

	/**
	 * Based on the "testing mode" setting return the live or the test base url
	 * @return string
	 */
	public function get_base_url() {
		// TBD: based on the "testing mode" setting return the live or the test base url
		return $this->base_url['test'];
	}

	/**
	 * Login to the API and get the token
	 * @param null $data
	 * @return null
	 */
	public function merchant_login( $data = null ) {
		if ( $data == null ) {
			$data = array(
				'email'    => $this->merchant_credentials['merchant_username'],
				'password' => $this->merchant_credentials['merchant_password'],
			);
		}
		$method     = 'post';
		$need_token = false;
		$response   = $this->send_request( $this->merchant_login_url, $method, $data, $need_token );
		return $response;
	}

	/**
	 * Check if the token is expired or not, if expired re-login and refresh the token
	 * @return bool
	 */
	public function is_token_valid() {
		if ( ! isset( $this->merchant_credentials['last_login_date'] ) || empty( $this->merchant_credentials['last_login_date'] ) ) {
			$this->merchant_credentials['last_login_date'] = 0;
		}
		if ( 1 || ( time() - $this->merchant_credentials['last_login_date'] ) > 60 * 60 ) {
			if ( ! isset( $this->merchant_credentials['merchant_username'] ) || empty( $this->merchant_credentials['merchant_username'] ) || ! isset( $this->merchant_credentials['merchant_password'] ) || empty( $this->merchant_credentials['merchant_password'] ) ) {
				barq()->log->write('Check token validity: Merchant credentials are empty.', 'error');
				return false;
			}
			$response = $this->merchant_login();
			if ( $response['success'] ) {
				$options['merchant_token']    = $response['data']['token'];
				$options['merchant_username'] = $this->merchant_credentials['merchant_username'];
				$options['merchant_password'] = $this->merchant_credentials['merchant_password'];
				$options['last_login_date']   = time();
				update_option('barq_merchant_credentials', $options);
				$this->init();
				return true;
			}
			barq()->log->write( $response['code'] . ": " . $response['message'], 'error' );
			return false;
		}
		return true;
	}


	/**
	 * Send request to Barq API system
	 * @param $url
	 * @param string $request_method
	 * @param null $data
	 * @param bool $need_token
	 * @return array
	 */
	public function send_request( $url, $request_method = 'get', $data = null, $need_token = true ) {
		$response = array();
		$response['success'] = false;

		if ( $need_token ) {
			$is_token_valid = $this->is_token_valid();
			if ( ! $is_token_valid ) {
				$response['code'] = 'error.token';
				$response['message'] = __('Token is not valid and cannot re-login to the System', BARQ_PLUGIN_SLUG);
				return $response;
			}
		}


		$base_url    = $this->get_base_url();
		$request_url = $base_url . $url;
		$body        = ! empty( $data ) ? json_encode( $data ) : '';
		$curl_header = array(
			'x-app-type'     => 'wordpress_plugin',
			'Content-Type'   => 'application/json',
			'Content-Length' => strlen( $body ),
			'RemoteAddr'     => $_SERVER['REMOTE_ADDR'],
			'Accept'         => '*/*'
		);

		if ( in_array( strtolower( $request_method ), array('post', 'get', 'put', 'delete') ) ) {
			$method = strtoupper( $request_method );
		}

		if ( $need_token ) {
			$token = $this->merchant_credentials['merchant_token'];
			if ( ! empty( $token ) ) {
				$curl_header['Authorization'] = $token;
			}
		}

		$wp_remote_response = wp_remote_request(
			$request_url,
			array(
				'method'  => $method,
				'headers' => $curl_header,
				'body'    => $body,
				'timeout' => 30
			)
		);


		$http_code           = wp_remote_retrieve_response_code( $wp_remote_response );
		$response_message    = wp_remote_retrieve_response_message( $wp_remote_response );
		$response_body       = json_decode( wp_remote_retrieve_body( $wp_remote_response ), true );
		$response['code']    = $http_code;
		$response['message'] = $response_message;
		$response['data']    = $response_body;
		$log_status          = 'curl-api';
		if ( $http_code < 200 || $http_code >= 300 ) {
			/**
			 * http_code is not between 200 - 299
			 * http codes reference: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status
			 */
			$log_status = 'curl-error';
		} elseif( $http_code > 201 ) {
			/**
			 * http_codes that should not return from API
			 */
			$log_status = 'api-error';
		}
		if ( in_array( $http_code, array(200, 201) ) ) {
			/**
			 * acceptable https codes
			 */
			$response['success'] = true;
		}
		barq()->log->write( 'Request URL: ' . $request_url, $log_status );
		barq()->log->write( 'Request headers: ' . json_encode( $curl_header ), $log_status );
		barq()->log->write( 'Request method: ' . $method, $log_status );
		barq()->log->write( 'Request body: ' . $body, $log_status );
		barq()->log->write( 'Response HTTP code: ' . $http_code, $log_status );
		barq()->log->write( 'Response message: ' . $response_message, $log_status );
		barq()->log->write( 'Response body: ' . json_encode( $response_body ), $log_status );
		return $response;
	}

	/**
	 * Get merchant profile
	 */
	public function get_merchant_profile() {
		$method     = 'get';
		$data       = null;
		$need_token = true;
		$response   = $this->send_request( $this->merchant_profile_url, $method, $data, $need_token );
		return $response;
	}

	/**
	 * Get merchant profile
	 * 
     * @param $query
	 */
	public function get_shipment_methods( $query ) {
		$url        = $this->shipment_methods_url . '?' . http_build_query( $query );
		$method     = 'get';
		$data       = null;
		$need_token = true;
		$response   = $this->send_request( $url, $method, $data, $need_token );
		return $response;
	}

	/**
	 * Set callback to Barq system
	 * 
     * @param $query
	 */
	public function push_order_to_barq_system( $query ) {
		$url        = $this->orders_url;
		$method     = 'post';
		$data       = $query;
		$need_token = true;
		$response   = $this->send_request( $url, $method, $data, $need_token );
		return $response;
	}

    /**
     * Push order to Barq system
     *
     * @param $query
     */
    public function update_callback_url( $query ) {
        $url        = $this->merchant_update_url;
        $method     = 'put';
        $data       = $query;
        $need_token = true;
        $response   = $this->send_request( $url, $method, $data, $need_token );
        return $response;
    }

	/**
	 * Get order information from Barq system
	 * 
     * @param $query
	 */
	public function get_order_information_from_barq_system( $order_id ) {
		$url        = $this->orders_url . '/' . esc_attr( $order_id );
		$method     = 'get';
		$data       = null;
		$need_token = true;
		$response   = $this->send_request( $url, $method, $data, $need_token );
		return $response;
	}

	/**
	 * Cancel order on Barq system
	 * 
     * @param $query
	 */
	public function cancel_order_on_barq_system( $order_id ) {
		$url        = $this->orders_url . '/' . esc_attr( $order_id ) . '/cancellation';
		$method     = 'get';
		$data       = null;
		$need_token = true;
		$response   = $this->send_request( $url, $method, $data, $need_token );
		return $response;
	}

}