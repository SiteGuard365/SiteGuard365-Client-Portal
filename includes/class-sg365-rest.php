<?php
/**
 * REST API routes.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Rest {
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'sg365/v1',
			'/me',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_auth' ),
				'callback'            => array( $this, 'get_me' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/dashboard',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_dashboard' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/sites',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_sites' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/projects',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_projects' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/worklogs',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_worklogs' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/support',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_support_list' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/support',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'require_client_access' ),
				'callback'            => array( $this, 'client_support_create' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/notifications',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_auth' ),
				'callback'            => array( $this, 'client_notifications' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/client/notifications/read',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'require_auth' ),
				'callback'            => array( $this, 'notifications_read' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/dashboard',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_dashboard' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/clients',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_clients' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/sites',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_sites' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/worklogs',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_worklogs' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/worklogs',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_worklogs_create' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/support',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_support_list' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/support/assign',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_support_assign' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/notifications',
			array(
				'methods'             => 'GET',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'staff_notifications' ),
			)
		);

		register_rest_route(
			'sg365/v1',
			'/staff/notifications/read',
			array(
				'methods'             => 'POST',
				'permission_callback' => array( $this, 'require_staff_access' ),
				'callback'            => array( $this, 'notifications_read' ),
			)
		);
	}

	public function require_auth() {
		return is_user_logged_in();
	}

	public function require_client_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'sg365_view_client_portal' ) || current_user_can( 'manage_options' ) ) {
			return true;
		}

		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		return ! empty( $client_id );
	}

	public function require_staff_access() {
		return is_user_logged_in() && ( current_user_can( 'sg365_view_staff_app' ) || current_user_can( 'manage_options' ) );
	}

	private function respond( $data = array(), $meta = array() ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
				'meta'    => $meta,
			)
		);
	}

	public function get_me() {
		$user = wp_get_current_user();
		$role = $user ? implode( ',', $user->roles ) : '';
		return $this->respond(
			array(
				'user_id' => get_current_user_id(),
				'role'    => $role,
				'scopes'  => array(
					'client' => current_user_can( 'sg365_view_client_portal' ),
					'staff'  => current_user_can( 'sg365_view_staff_app' ),
				),
			)
		);
	}

	public function client_dashboard() {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		$data = array(
			'client_id' => $client_id,
			'sites'     => SG365_DB::get_client_sites( $client_id ),
			'projects'  => SG365_DB::get_client_projects( $client_id ),
		);
		return $this->respond( $data );
	}

	public function client_sites() {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		return $this->respond( SG365_DB::get_client_sites( $client_id ) );
	}

	public function client_projects() {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		return $this->respond( SG365_DB::get_client_projects( $client_id ) );
	}

	public function client_worklogs() {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		return $this->respond( SG365_DB::get_client_worklogs( $client_id ) );
	}

	public function client_support_list() {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		return $this->respond( SG365_DB::get_client_support_requests( $client_id ) );
	}

	public function client_support_create( WP_REST_Request $request ) {
		$client_id = SG365_DB::get_client_id_for_user( get_current_user_id() );
		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );
		$priority = $request->get_param( 'priority' ) ?: 'normal';

		$request_id = SG365_DB::create_support_request( $client_id, get_current_user_id(), $subject, $message, $priority );
		SG365_Audit::log( 'create', 'support_request', $request_id, array( 'subject' => $subject ) );
		return $this->respond( array( 'id' => $request_id ) );
	}

	public function client_notifications() {
		return $this->respond( SG365_DB::get_client_notifications( get_current_user_id() ) );
	}

	public function notifications_read( WP_REST_Request $request ) {
		$notification_id = absint( $request->get_param( 'id' ) );
		if ( $notification_id ) {
			SG365_DB::mark_notification_read( $notification_id, get_current_user_id() );
		}
		return $this->respond( array( 'id' => $notification_id ) );
	}

	public function staff_dashboard() {
		$user_id = get_current_user_id();
		$data = array(
			'due_today'  => array(),
			'overdue'    => array(),
			'new_tickets'=> array(),
			'activity'   => array(),
			'worklogs'   => SG365_DB::get_staff_worklogs( $user_id ),
		);
		return $this->respond( $data );
	}

	public function staff_clients() {
		return $this->respond( SG365_DB::get_staff_clients( get_current_user_id() ) );
	}

	public function staff_sites() {
		return $this->respond( SG365_DB::get_staff_sites( get_current_user_id() ) );
	}

	public function staff_worklogs() {
		return $this->respond( SG365_DB::get_staff_worklogs( get_current_user_id() ) );
	}

	public function staff_worklogs_create( WP_REST_Request $request ) {
		$data = array(
			'client_id'         => $request->get_param( 'client_id' ),
			'site_id'           => $request->get_param( 'site_id' ),
			'project_id'        => $request->get_param( 'project_id' ),
			'staff_user_id'     => get_current_user_id(),
			'title'             => $request->get_param( 'title' ),
			'details'           => $request->get_param( 'details' ),
			'internal_notes'    => $request->get_param( 'internal_notes' ),
			'log_date'          => $request->get_param( 'log_date' ) ?: current_time( 'Y-m-d' ),
			'time_minutes'      => $request->get_param( 'time_minutes' ) ?: 0,
			'visibility_client' => (bool) $request->get_param( 'visibility_client' ),
			'attachments'       => $request->get_param( 'attachments' ) ?: array(),
		);

		if ( ! SG365_DB::is_staff_assigned( get_current_user_id(), absint( $data['client_id'] ) ) && ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'sg365_forbidden', __( 'Not assigned to this client.', 'sg365-dashboard-suite' ), array( 'status' => 403 ) );
		}

		$worklog_id = SG365_DB::create_worklog( $data );
		SG365_Audit::log( 'create', 'worklog', $worklog_id, array( 'title' => $data['title'] ) );
		return $this->respond( array( 'id' => $worklog_id ) );
	}

	public function staff_support_list() {
		return $this->respond( SG365_DB::get_staff_support_requests( get_current_user_id() ) );
	}

	public function staff_support_assign( WP_REST_Request $request ) {
		$request_id = absint( $request->get_param( 'request_id' ) );
		$staff_id = absint( $request->get_param( 'staff_user_id' ) );
		SG365_DB::assign_support_request( $request_id, $staff_id );
		SG365_Audit::log( 'assign', 'support_request', $request_id, array( 'staff_user_id' => $staff_id ) );
		return $this->respond( array( 'id' => $request_id ) );
	}

	public function staff_notifications() {
		return $this->respond( SG365_DB::get_staff_notifications( get_current_user_id() ) );
	}
}
