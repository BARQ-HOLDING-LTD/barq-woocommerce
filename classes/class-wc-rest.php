<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class WC_REST_Barq_Controller {
    /**
     * WC Namespace
     * @var string
     */
    protected $namespace = 'wc/v3';
    protected $rest_base = 'barq';

    /**
     * Barq Status Update Endpoint Path
     * @var string
     */
    protected $order_status_update = 'order/status/update';

    /**
     *  WC Register Routes
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace, "/" . $this->rest_base . "/" . $this->order_status_update,
            array(
                'methods' => 'POST',
                'callback' => array( $this, 'update_order_status' ),
            )
        );
    }

    /**
     * Order Status Update from Server
     * @param $request
     * @return string[]
     */
    public function update_order_status( $request ) {
        $params = $request->get_params();
		$tracking_number = 0;
		$status          = '';
		if ( isset( $params['tracking_number'] ) && ! empty( $params['tracking_number'] ) ) {
			$tracking_number = trim( sanitize_text_field( $params['tracking_number'] ) );
		}
		if ( isset( $params['status'] ) && ! empty( $params['status'] ) ) {
			$status = trim( sanitize_text_field( $params['status'] ) );
		}
        if ( ! empty( $tracking_number ) && ! empty( $status ) ) {
            if ( $status == 'completed' || $status == 'cancelled' ) {
				$order_id = $this->get_order_id_by_tracking_number( $tracking_number );
				if ( ! empty( $order_id ) ) {
					$wc_order = wc_get_order( $order_id );
					if ( ! empty( $wc_order ) && is_a( $wc_order, 'WC_Order' ) ) {
						$message = __('Barq: Order Status has been auto updated.', BARQ_PLUGIN_SLUG);
						$wc_order->update_status( $status, $message );
						return array(
							'success' => true
						);
					}
				}
            }
        }
        return array(
			'success' => false,
		);
    }

    /**
     * Get WC order by tracking number
     * @param $tracking_number int 
     * @return int
     */
    public function get_order_id_by_tracking_number( $tracking_number ) {
        global $wpdb;
		$order_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key LIKE '_tracking_number'
				  AND meta_value = %s
				LIMIT 1",
				esc_attr( $tracking_number )
			)
		);
        return (int) esc_attr( $order_id );
    }
}