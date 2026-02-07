<?php
/**
 * Notifications utilities.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Notifications {
	public function create( $user_id, $type, $title, $body = '', $link_url = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'sg365_notifications';
		$wpdb->insert(
			$table,
			array(
				'user_id'    => absint( $user_id ),
				'type'       => sanitize_text_field( $type ),
				'title'      => sanitize_text_field( $title ),
				'body'       => wp_kses_post( $body ),
				'link_url'   => esc_url_raw( $link_url ),
				'is_read'    => 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}
}
