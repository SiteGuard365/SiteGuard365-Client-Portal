<?php
/**
 * Database helpers.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_DB {
	public static function install() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$schema = sg365_get_schema();
		foreach ( $schema as $sql ) {
			dbDelta( $sql );
		}
	}

	public static function get_client_id_for_user( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_clients';
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE user_id = %d AND status = %s", $user_id, 'active' ) );
	}

	public static function is_staff_assigned( $staff_user_id, $client_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_staff_assignments';
		$assignment = $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE staff_user_id = %d AND client_id = %d", $staff_user_id, $client_id )
		);
		return ! empty( $assignment );
	}

	public static function get_client_sites( $client_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_sites';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE client_id = %d AND archived_at IS NULL", $client_id ) );
	}

	public static function get_client_projects( $client_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_projects';
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE client_id = %d AND archived_at IS NULL", $client_id ) );
	}

	public static function get_client_worklogs( $client_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_worklogs';
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE client_id = %d AND archived_at IS NULL AND visibility_client = 1", $client_id )
		);
	}

	public static function get_client_support_requests( $client_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_support_requests';
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE client_id = %d AND archived_at IS NULL ORDER BY created_at DESC", $client_id )
		);
	}

	public static function get_client_notifications( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_notifications';
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC", $user_id )
		);
	}

	public static function create_support_request( $client_id, $user_id, $subject, $message, $priority ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_support_requests';
		$wpdb->insert(
			$table,
			array(
				'client_id'          => $client_id,
				'created_by_user_id' => $user_id,
				'subject'            => sanitize_text_field( $subject ),
				'message'            => wp_kses_post( $message ),
				'status'             => 'open',
				'priority'           => sanitize_text_field( $priority ),
				'created_at'         => current_time( 'mysql' ),
				'updated_at'         => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		return $wpdb->insert_id;
	}

	public static function get_staff_clients( $staff_user_id ) {
		global $wpdb;
		$assignments = $wpdb->prefix . 'sg365_staff_assignments';
		$clients = $wpdb->prefix . 'sg365_clients';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT c.* FROM {$clients} c INNER JOIN {$assignments} a ON c.id = a.client_id WHERE a.staff_user_id = %d",
				$staff_user_id
			)
		);
	}

	public static function get_staff_sites( $staff_user_id ) {
		global $wpdb;
		$assignments = $wpdb->prefix . 'sg365_staff_assignments';
		$sites = $wpdb->prefix . 'sg365_sites';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.* FROM {$sites} s INNER JOIN {$assignments} a ON s.client_id = a.client_id WHERE a.staff_user_id = %d AND s.archived_at IS NULL",
				$staff_user_id
			)
		);
	}

	public static function get_staff_worklogs( $staff_user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_worklogs';
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE staff_user_id = %d AND archived_at IS NULL ORDER BY log_date DESC", $staff_user_id )
		);
	}

	public static function create_worklog( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_worklogs';
		$wpdb->insert(
			$table,
			array(
				'client_id'         => absint( $data['client_id'] ),
				'site_id'           => $data['site_id'] ? absint( $data['site_id'] ) : null,
				'project_id'        => $data['project_id'] ? absint( $data['project_id'] ) : null,
				'staff_user_id'     => absint( $data['staff_user_id'] ),
				'title'             => sanitize_text_field( $data['title'] ),
				'details'           => wp_kses_post( $data['details'] ),
				'internal_notes'    => wp_kses_post( $data['internal_notes'] ),
				'log_date'          => sanitize_text_field( $data['log_date'] ),
				'time_minutes'      => absint( $data['time_minutes'] ),
				'visibility_client' => $data['visibility_client'] ? 1 : 0,
				'attachments'       => wp_json_encode( $data['attachments'] ),
			),
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
		);
		return $wpdb->insert_id;
	}

	public static function get_staff_support_requests( $staff_user_id ) {
		global $wpdb;
		$assignments = $wpdb->prefix . 'sg365_staff_assignments';
		$table = $wpdb->prefix . 'sg365_support_requests';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.* FROM {$table} r INNER JOIN {$assignments} a ON r.client_id = a.client_id WHERE a.staff_user_id = %d AND r.archived_at IS NULL",
				$staff_user_id
			)
		);
	}

	public static function assign_support_request( $request_id, $staff_user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_support_requests';
		$wpdb->update(
			$table,
			array(
				'assigned_staff_user_id' => absint( $staff_user_id ),
				'status'                 => 'inprogress',
				'updated_at'             => current_time( 'mysql' ),
			),
			array( 'id' => absint( $request_id ) ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function get_staff_notifications( $user_id ) {
		return self::get_client_notifications( $user_id );
	}

	public static function mark_notification_read( $notification_id, $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_notifications';
		$wpdb->update(
			$table,
			array( 'is_read' => 1 ),
			array(
				'id'      => absint( $notification_id ),
				'user_id' => absint( $user_id ),
			),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}
}
