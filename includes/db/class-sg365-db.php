<?php
/**
 * DB helpers.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_DB {
	/**
	 * Return table name with prefix.
	 *
	 * @param string $table Table slug.
	 * @return string
	 */
	public static function table( $table ) {
		global $wpdb;

		return $wpdb->prefix . 'sg365_' . $table;
	}

	/**
	 * Current client ID for user.
	 *
	 * @param int $user_id User ID.
	 * @return int|null
	 */
	public static function get_client_id_by_user( $user_id ) {
		global $wpdb;

		$table = self::table( 'clients' );
		$client_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND status = %s",
				$user_id,
				'active'
			)
		);

		return $client_id ? absint( $client_id ) : null;
	}

	/**
	 * Check staff assignment.
	 *
	 * @param int $staff_user_id Staff user.
	 * @param int $client_id Client ID.
	 * @return bool
	 */
	public static function has_staff_assignment( $staff_user_id, $client_id ) {
		global $wpdb;

		$table = self::table( 'staff_assignments' );
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE staff_user_id = %d AND client_id = %d",
				$staff_user_id,
				$client_id
			)
		);

		return ! empty( $exists );
	}

	/**
	 * Get IP address.
	 *
	 * @return string
	 */
	public static function get_ip() {
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return '';
	}
}
