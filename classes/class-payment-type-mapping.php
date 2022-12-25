<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

if ( ! class_exists( 'Barq_Payment_Type_Mapping' ) ) {
    class Barq_Payment_Type_Mapping {
		public $mapping = array();

		public function __construct() {
			$this->mapping = array(
				'credit_card' => array(
					'id'    => 0,
					'label' => __('Credit card', BARQ_PLUGIN_SLUG),
				),
				'cash_on_delivery' => array(
					'id'    => 1,
					'label' => __('Cash on delivery', BARQ_PLUGIN_SLUG),
				),
			);
		}

		public function get_label( $key ) {
			$label = '';
			if ( isset( $this->mapping[ $key ] ) && ! empty( ( $this->mapping[ $key ] ) ) ) {
				if ( isset( $this->mapping[ $key ]['label'] ) && ! empty( ( $this->mapping[ $key ]['label'] ) ) ) {
					$label = $this->mapping[ $key ]['label'];
				}
			}
			return esc_attr( $label );
		}
	}
}
