<?php
/**
 * Cron scheduler.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Cron {
	const EVENT_DAILY = 'sg365_daily_events';

	public function __construct() {
		add_action( 'init', array( $this, 'schedule' ) );
		add_action( self::EVENT_DAILY, array( $this, 'handle_daily' ) );
	}

	public function schedule() {
		if ( ! wp_next_scheduled( self::EVENT_DAILY ) ) {
			wp_schedule_event( time(), 'daily', self::EVENT_DAILY );
		}
	}

	public function handle_daily() {
		// Placeholder for renewal reminders and due date notifications.
	}

	public static function deactivate() {
		$timestamp = wp_next_scheduled( self::EVENT_DAILY );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::EVENT_DAILY );
		}
	}
}
