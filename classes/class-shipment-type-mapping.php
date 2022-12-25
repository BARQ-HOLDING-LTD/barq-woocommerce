<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

if ( ! class_exists( 'Barq_Shipment_Type_Mapping' ) ) {
    class Barq_Shipment_Type_Mapping {
		public $mapping = array();

		public function __construct() {
			$this->mapping = array(
				'instant_delivery' => array(
					'id'    => 0,
					'label' => __('Instant delivery', BARQ_PLUGIN_SLUG),
				),
				'same_day' => array(
					'id'    => 1,
					'label' => __('Same day', BARQ_PLUGIN_SLUG),
				),
				'next_day' => array(
					'id'    => 2,
					'label' => __('Next day', BARQ_PLUGIN_SLUG),
				),
				'pick_up' => array(
					'id'    => 3,
					'label' => __('Pick up', BARQ_PLUGIN_SLUG),
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
