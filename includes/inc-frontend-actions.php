<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_Frontend_Actions {
    /**
     * Barq_Frontend_Actions constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_review_order_before_cart_contents', array( $this, 'validate_order' ), 10 );
        add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_order' ), 10 );
        add_action( 'woocommerce_thankyou', array( $this, 'order_processed' ), 10, 1 );
        add_action( 'woocommerce_view_order', array( $this, 'woocommerce_view_order' ), 10, 1 );
	}

    /**
	 * Check if the current order weight exceeds the weight limit of Barq shipping methods
	 * 
     * @param $data
     */
	public function validate_order( $data ) {
        $packages       = WC()->shipping->get_packages();
        $chosen_methods = WC()->session->get('chosen_shipping_methods', null);
        $is_barq_chosen = false;
        if ( is_array( $chosen_methods ) ) {
            foreach ( $chosen_methods as $method ) {
                if ( strpos( $method, 'barq_' ) !== false ) {
                    $is_barq_chosen = true;
                    break;
                }
            }
        }
		if ( is_array( $chosen_methods ) && $is_barq_chosen ) {
			foreach ( $packages as $key => $package ) {
                if ( strpos( $chosen_methods[ $key ], 'barq_' ) === false ) {
                    continue;
                }
                $barq_shipping_method = new Barq_Shipping_Method();
                $weight_limit = intval( $barq_shipping_method->settings['weight'] );
                $weight       = 0;
				foreach ( $package['contents'] as $item_id => $values ) {
					$_product = $values['data'];
					if ( $values['quantity'] > 0 && $_product->needs_shipping() ) {
						$weight += intval( $values['quantity'] ) * floatval( $_product->get_weight() );
					}
				}
				$weight = wc_get_weight($weight, 'kg');
                if ( $weight > $weight_limit ) {
                    $message      = sprintf( __('Sorry, %d kg exceeds the maximum weight of %d kg for %s', BARQ_PLUGIN_SLUG), $weight, $weight_limit, $barq_shipping_method->title );
                    $message_type = 'error';
                    if ( ! wc_has_notice( $message, $message_type ) ) {
                        wc_add_notice( $message, $message_type );
                    }
                }
			}
		}
	}

    /**
	 * Push order to barq system when it is placed and its status is processing
	 * 
     * @param $order_id
     */
	public function order_processed( $order_id ) {
        if ( ! $order_id ) {
            return;
		}
		if( ! get_post_meta( $order_id, '_barq_thankyou_action_done', true ) ) {
            $order = new WC_Order( $order_id );
            $shipping_country = trim( get_post_meta( $order_id, '_shipping_country', true ) );
            $shipping_methods = WC()->session->get( 'chosen_shipping_methods', array() );
            foreach ( $shipping_methods as $chosen_method ) {
                if ( strpos( $chosen_method, 'barq_' ) !== false ) {
                    if ( $order->get_status() == 'processing' ) {
						barq()->helpers['api-helper']->push_order_to_barq_system( $order, $chosen_method );
                    } else {
                        barq()->log->write( sprintf( __('Payment Title %s and Order status: %s for Order ID: %s', BARQ_PLUGIN_SLUG), $order->get_payment_method_title(), $order->get_status(), $order_id ), 'payment-hold' );
                        $order->add_order_note( sprintf( __('Barq: Order is not pushed to Barq system because the Payment (Method: %s) is not paid yet.', BARQ_PLUGIN_SLUG), $order->get_payment_method_title() ), 0 );
                    }
                }
            }
            update_post_meta( $order_id, '_barq_thankyou_action_done', true );
		}
	}

    /**
     * 
     * 
     * @param $order_id
     */
    public function woocommerce_view_order( $order_id ) {
        if ( ! $order_id ) {
            return;
		}
        barq()->helpers['api-helper']->update_order_meta_fields_from_barq( $order_id );
        $status       = get_post_meta( $order_id, 'barq_order_status', true );
        $status_label = barq()->mapping['status']->get_label( $status );
        ?>
        <h2>
            <?php echo __('Barq order information', BARQ_PLUGIN_SLUG); ?>
        </h2>
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <tbody>
                <tr>
                    <td class="woocommerce-table__barq-order-status-label">
                        <?php echo __('Order status', BARQ_PLUGIN_SLUG); ?>
                    </td>
                    <td class="woocommerce-table__barq-order-status">
                        <?php echo esc_attr( $status_label ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}

new Barq_Frontend_Actions();