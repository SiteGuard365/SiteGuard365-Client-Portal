<?php
/**
 * Search endpoint.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Search {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'sg365/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_auth' ),
				'callback'            => array( $this, 'search' ),
			)
		);
	}

	public function require_auth() {
		return is_user_logged_in();
	}

	public function search( WP_REST_Request $request ) {
		$query = sanitize_text_field( $request->get_param( 'q' ) );
		$results = array(
			'clients'  => array(),
			'sites'    => array(),
			'projects' => array(),
			'tickets'  => array(),
		);

		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $results,
				'meta'    => array( 'query' => $query ),
			)
		);
	}
}
