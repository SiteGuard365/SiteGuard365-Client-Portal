<?php
/**
 * Cron scheduling.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Cron {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_interval' ) );
		add_action( 'init', array( __CLASS__, 'schedule_events' ) );
	}

	/**
	 * Add custom interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function add_interval( $schedules ) {
		$schedules['sg365_daily'] = array(
			'interval' => DAY_IN_SECONDS,
			'display'  => __( 'SG365 Daily', 'sg365-dashboard-suite' ),
		);

		return $schedules;
	}

	/**
	 * Schedule events.
	 *
	 * @return void
	 */
	public static function schedule_events() {
		if ( ! wp_next_scheduled( 'sg365_cron_due_reminders' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'sg365_daily', 'sg365_cron_due_reminders' );
		}
	}
}
