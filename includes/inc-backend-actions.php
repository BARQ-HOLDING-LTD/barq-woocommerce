<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_Backend_Actions {
    /**
     * Barq_Backend_Actions constructor.
     */
    public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );
        add_action( 'add_meta_boxes', array( $this, 'add_order_metabox' ) );
		add_filter( 'wc_order_statuses', array( $this, 'filter_wc_order_statuses' ), 10, 1 );
	}

	/**
     *  Add Barq Order Meta Box
     */
    public function add_order_metabox() {
        add_meta_box(
            'barq-order-meta-box',
            __('Barq', BARQ_PLUGIN_SLUG),
            array($this, 'render_order_metabox'),
            'shop_order',
            'advanced',
            'high'
        );
    }

    /**
     * Render Barq Order Metabox
     * @param $post
     */
    public function render_order_metabox( $post ) {
		$order_id = $post->ID;
		$barq_order_id = get_post_meta( $order_id, 'barq_order_id', true );
		if ( empty( $barq_order_id ) || ! intval( $barq_order_id ) ) {
			echo '<div class="barq-error">' . __('Barq: This order is not using Barq.', BARQ_PLUGIN_SLUG) . '</div>';
			return;
		}
		barq()->helpers['api-helper']->update_order_meta_fields_from_barq( $order_id );
		$order_meta_fields  = array(
			'order_id'      => __('Barq order ID', BARQ_PLUGIN_SLUG),
			'tracking_no'   => __('Barq tracking number', BARQ_PLUGIN_SLUG),
			'order_status'  => __('Barq order status', BARQ_PLUGIN_SLUG),
			'payment_type'  => __('Barq payment type', BARQ_PLUGIN_SLUG),
			'shipment_type' => __('Barq shipment type', BARQ_PLUGIN_SLUG),
			'is_assigned'   => __('Barq order is assigned', BARQ_PLUGIN_SLUG),
			'origin'        => __('Barq order origin location', BARQ_PLUGIN_SLUG),
			'destination'   => __('Barq order destination location', BARQ_PLUGIN_SLUG),
			'courier'       => __('Barq order courier information', BARQ_PLUGIN_SLUG),
			'shipment'      => __('Barq order shipment information', BARQ_PLUGIN_SLUG),
		);
		$location_fields = array(
			'latitude'  => __('Latitude', BARQ_PLUGIN_SLUG),
			'longitude' => __('Longitude', BARQ_PLUGIN_SLUG),
		);
		$courier_fields = array(
			'name'        => __('Name', BARQ_PLUGIN_SLUG),
			'mobile'      => __('Mobile', BARQ_PLUGIN_SLUG),
			'is_online'   => __('Is online', BARQ_PLUGIN_SLUG),
			'nationality' => __('Nationality', BARQ_PLUGIN_SLUG),
		);
		$shipment_fields = array(
			'id'           => __('ID', BARQ_PLUGIN_SLUG),
			'tracking_no'  => __('Tracking number', BARQ_PLUGIN_SLUG),
			'is_assigned'  => __('Is assigned', BARQ_PLUGIN_SLUG),
			'is_completed' => __('Is completed', BARQ_PLUGIN_SLUG),
			'is_cancelled' => __('Is cancelled', BARQ_PLUGIN_SLUG),
		);

		echo '<div class="barq-order-information-wrapper">';
		foreach ( $order_meta_fields as $meta_key => $meta_label ) {
			$meta_value = get_post_meta( $order_id, 'barq_' . $meta_key, true );
			echo '<div class="barq-order-information-row">';
				echo '<div class="barq-order-information-label">';
					echo esc_attr( $meta_label );
				echo '</div>';
				echo '<div class="barq-order-information-value">';
					switch ( $meta_key ) {
						case 'order_status':
							echo barq()->mapping['status']->get_label( $meta_value );
							break;
						case 'payment_type':
							echo barq()->mapping['payment-type']->get_label( $meta_value );
							break;
						case 'shipment_type':
							echo barq()->mapping['shipment-type']->get_label( $meta_value );
							break;
						case 'is_assigned':
							if ( ! empty( $meta_value ) ) {
								echo __('Order assigned', BARQ_PLUGIN_SLUG);
							} else {
								echo __('Order still not assigned', BARQ_PLUGIN_SLUG);
							}
							break;
						case 'origin':
						case 'destination':
						case 'courier':
						case 'shipment':
							$meta_value = json_decode( $meta_value, true );
							$fields     = $location_fields;
							if ( $meta_key == 'courier' ) {
								$fields = $courier_fields;
							} elseif ( $meta_key == 'shipment' ) {
								$fields = $shipment_fields;
							}
							foreach ( $fields as $field_key => $field_label ) {
									echo '<div class="barq-order-information-row">';
										echo '<div class="barq-order-information-label">';
											echo $field_label;
										echo '</div>';
										echo '<div class="barq-order-information-value">';
											if ( isset( $meta_value[ $field_key ] ) && ! empty( $meta_value[ $field_key ] ) ) {
												echo esc_attr( $meta_value[ $field_key ] ); 
											}
										echo '</div>';
									echo '</div>';
							}
							break;
						default:
							echo esc_attr( $meta_value ); 
							break;
					}
				echo '</div>';
			echo '</div>';
		}
		echo '</div>';
	}

	/**
	 * Handle Order status changed action
	 */
	public function order_status_changed( $order_id, $from_status, $to_status ) {
        if ( ! $order_id ) {
            return;
		}
		$order_wc = new WC_Order( $order_id );
		if ( ! is_a( $order_wc, 'WC_Order' ) ) {
			return;
		}
		$barq_order_id = get_post_meta( $order_id, 'barq_order_id', true );
		if ( $to_status == 'cancelled' && ! empty( $barq_order_id ) && intval( $barq_order_id ) ) {
			$response = barq()->api->cancel_order_on_barq_system( $barq_order_id );
			if ( isset( $response['success'] ) && $response['success'] && isset( $response['data'] ) && ! empty( $response['data'] ) ) {
				$code        = $response['data']['code'];
				$response_id = $response['data']['response_id'];
				$message     = $response['data']['message'];
				$order_wc->add_order_note( 'Barq: ' . $message , 1 );
				update_post_meta( $order_id, 'barq_cancellation_response_id', $response_id );
			}
		}
	}

	/**
	 * Filter the order statuses based on barq order status
	 * 
	 * @param $order_statuses
	 */
	public function filter_wc_order_statuses( $order_statuses ) {
		global $pagenow;
		if ( $pagenow == 'post.php' && isset( $_GET['post'] ) && intval( $_GET['post'] ) ) {
			$order_id = intval( $_GET['post'] );
			$order_wc = new WC_Order( $order_id );
			if ( is_a( $order_wc, 'WC_Order' ) ) {
				$current_status = $order_wc->get_status();
				if ( $current_status != 'cancelled' ) {
					$barq_order_status = get_post_meta( $order_id, 'barq_order_status', true );
					$statuses_allowed_to_cancel = array(
						'new_order',
						'ready_for_delivery',
						'processing',
					);
					if ( ! in_array( $barq_order_status, $statuses_allowed_to_cancel ) ) {
						unset( $order_statuses['wc-cancelled'] );
					}
				}
			}
		}
		return $order_statuses;
	}
}

new Barq_Backend_Actions();
