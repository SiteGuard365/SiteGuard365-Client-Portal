<?php
/**
 * Notifications manager.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Notifications {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'sg365_cron_due_reminders', array( __CLASS__, 'send_due_reminders' ) );
	}

	/**
	 * Notify admins.
	 *
	 * @param string $type Type.
	 * @param string $title Title.
	 * @param string $body Body.
	 * @return void
	 */
	public static function notify_admins( $type, $title, $body ) {
		$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'ID' ) ) );
		foreach ( $admins as $admin ) {
			self::notify_user( $admin->ID, $type, $title, $body );
		}
	}

	/**
	 * Notify client.
	 *
	 * @param int    $client_id Client ID.
	 * @param string $type Type.
	 * @param string $title Title.
	 * @param string $body Body.
	 * @return void
	 */
	public static function notify_client( $client_id, $type, $title, $body ) {
		global $wpdb;
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->prefix}sg365_clients WHERE id = %d",
				$client_id
			)
		);
		if ( $user_id ) {
			self::notify_user( (int) $user_id, $type, $title, $body );
		}
	}

	/**
	 * Notify user.
	 *
	 * @param int    $user_id User ID.
	 * @param string $type Type.
	 * @param string $title Title.
	 * @param string $body Body.
	 * @return void
	 */
	public static function notify_user( $user_id, $type, $title, $body ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'sg365_notifications',
			array(
				'user_id'   => absint( $user_id ),
				'type'      => sanitize_text_field( $type ),
				'title'     => sanitize_text_field( $title ),
				'body'      => wp_kses_post( $body ),
				'link_url'  => '',
				'is_read'   => 0,
				'created_at'=> current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}

	/**
	 * Send due reminders.
	 *
	 * @return void
	 */
	public static function send_due_reminders() {
		// Placeholder for due reminders.
	}
}
