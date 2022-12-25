<?php

if ( ! defined('ABSPATH') ) {
    return; // Exit if accessed directly.
}

class Barq_Cron_Job {
    private $interval = 60 * 60;

    /**
     * Barq_Cron_Job contructor.
     */
    public function __construct() {
        add_filter( 'cron_schedules', array( $this, 'every_n_minutes_cron_schedules' ) );
        if ( ! wp_next_scheduled('barq_every_n_minutes') ) {
            wp_schedule_event( time(), 'every_n_minutes', 'barq_every_n_minutes' );
        }
        add_action( 'barq_every_n_minutes', array( $this, 'every_n_minutes_event_handler' ) );
    }

    /**
	 * Add Barq cron job schedules
     * @param $schedules
     * @return mixed
     */
    public function every_n_minutes_cron_schedules( $schedules ) {
        $schedules['every_n_minutes'] = array(
            'interval' => $this->interval,
            'display'  => __( 'Every N Minutes', BARQ_PLUGIN_SLUG )
        );
        return $schedules;
    }

    /**
     *  Handle Barq cron job event
     */
    public function every_n_minutes_event_handler() {
        barq()->api->is_token_valid();
        barq()->helpers['api-helper']->update_merchant_hub();
        barq()->helpers['api-helper']->update_merchant_profile();
    }
}