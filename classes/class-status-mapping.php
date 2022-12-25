<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

if ( ! class_exists( 'Barq_Status_Mapping' ) ) {
    class Barq_Status_Mapping {
		public $mapping = array();

		public function __construct() {
			$this->mapping = array(
				'new_order' => array(
					'id'    => 1,
					'label' => __('New order', BARQ_PLUGIN_SLUG),
				),
				'processing' => array(
					'id'    => 2,
					'label' => __('processing', BARQ_PLUGIN_SLUG),
				),
				'ready_for_delivery' => array(
					'id'    => 3,
					'label' => __('Ready for delivery', BARQ_PLUGIN_SLUG),
				),
				'picked_up' => array(
					'id'    => 4,
					'label' => __('Picked up', BARQ_PLUGIN_SLUG),
				),
				'intransit' => array(
					'id'    => 5,
					'label' => __('Intransit', BARQ_PLUGIN_SLUG),
				),
				'completed' => array(
					'id'    => 6,
					'label' => __('Completed', BARQ_PLUGIN_SLUG),
				),
				'cancelled' => array(
					'id'    => 7,
					'label' => __('Cancelled', BARQ_PLUGIN_SLUG),
				),
				'exception' => array(
					'id'    => 8,
					'label' => __('Exception', BARQ_PLUGIN_SLUG),
				),
				'returned' => array(
					'id'    => 9,
					'label' => __('Returned', BARQ_PLUGIN_SLUG),
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
