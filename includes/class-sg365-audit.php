<?php
/**
 * Audit logger.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Audit {
	public static function log( $action, $object_type, $object_id, $changes = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_audit_log';

		$wpdb->insert(
			$table,
			array(
				'actor_user_id' => get_current_user_id(),
				'action'        => sanitize_text_field( $action ),
				'object_type'   => sanitize_text_field( $object_type ),
				'object_id'     => absint( $object_id ),
				'changes_json'  => wp_json_encode( $changes ),
				'ip'            => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
				'user_agent'    => sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
				'created_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}
}
