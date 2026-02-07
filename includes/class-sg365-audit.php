<?php
/**
 * Audit logging.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Audit {
	/**
	 * Log an action.
	 *
	 * @param string $action Action name.
	 * @param string $object_type Object type.
	 * @param int    $object_id Object ID.
	 * @param array  $changes Changes array.
	 * @return void
	 */
	public static function log( $action, $object_type, $object_id, $changes ) {
		global $wpdb;

		$table = SG365_DB::table( 'audit_log' );

		$wpdb->insert(
			$table,
			array(
				'actor_user_id' => get_current_user_id(),
				'action'        => sanitize_text_field( $action ),
				'object_type'   => sanitize_text_field( $object_type ),
				'object_id'     => absint( $object_id ),
				'changes_json'  => wp_json_encode( $changes ),
				'ip'            => sanitize_text_field( SG365_DB::get_ip() ),
				'user_agent'    => sanitize_text_field( isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '' ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}
}
