<?php
/**
 * REST API endpoints.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_REST {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			'sg365/v1',
			'/me',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_me' ),
				'permission_callback' => array( __CLASS__, 'require_login' ),
			)
		);

		self::register_client_routes();
		self::register_staff_routes();
		self::register_search_route();
	}

	/**
	 * Register client routes.
	 *
	 * @return void
	 */
	private static function register_client_routes() {
		$routes = array(
			'/client/dashboard'          => array( 'GET', 'get_client_dashboard' ),
			'/client/sites'              => array( 'GET', 'get_client_sites' ),
			'/client/projects'           => array( 'GET', 'get_client_projects' ),
			'/client/worklogs'           => array( 'GET', 'get_client_worklogs' ),
			'/client/support'            => array( 'GET', 'get_client_support' ),
			'/client/support'            => array( 'POST', 'create_client_support' ),
			'/client/notifications'      => array( 'GET', 'get_client_notifications' ),
			'/client/notifications/read' => array( 'POST', 'mark_client_notification' ),
		);

		foreach ( $routes as $route => $config ) {
			register_rest_route(
				'sg365/v1',
				$route,
				array(
					'methods'             => $config[0],
					'callback'            => array( __CLASS__, $config[1] ),
					'permission_callback' => array( __CLASS__, 'require_client_access' ),
				)
			);
		}
	}

	/**
	 * Register staff routes.
	 *
	 * @return void
	 */
	private static function register_staff_routes() {
		$routes = array(
			'/staff/dashboard'          => array( 'GET', 'get_staff_dashboard' ),
			'/staff/clients'            => array( 'GET', 'get_staff_clients' ),
			'/staff/sites'              => array( 'GET', 'get_staff_sites' ),
			'/staff/worklogs'           => array( 'GET', 'get_staff_worklogs' ),
			'/staff/worklogs'           => array( 'POST', 'create_staff_worklog' ),
			'/staff/support'            => array( 'GET', 'get_staff_support' ),
			'/staff/support/assign'     => array( 'POST', 'assign_staff_support' ),
			'/staff/notifications'      => array( 'GET', 'get_staff_notifications' ),
			'/staff/notifications/read' => array( 'POST', 'mark_staff_notification' ),
		);

		foreach ( $routes as $route => $config ) {
			register_rest_route(
				'sg365/v1',
				$route,
				array(
					'methods'             => $config[0],
					'callback'            => array( __CLASS__, $config[1] ),
					'permission_callback' => array( __CLASS__, 'require_staff_access' ),
				)
			);
		}
	}

	/**
	 * Register search route.
	 *
	 * @return void
	 */
	private static function register_search_route() {
		register_rest_route(
			'sg365/v1',
			'/search',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => array( __CLASS__, 'require_staff_access' ),
			)
		);
	}

	/**
	 * Permission: logged in.
	 *
	 * @return bool
	 */
	public static function require_login() {
		return is_user_logged_in();
	}

	/**
	 * Permission: client access.
	 *
	 * @return bool
	 */
	public static function require_client_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'sg365_view_client_portal' ) ) {
			return true;
		}

		return (bool) SG365_DB::get_client_id_by_user( get_current_user_id() );
	}

	/**
	 * Permission: staff access.
	 *
	 * @return bool
	 */
	public static function require_staff_access() {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		return SG365_Capabilities::is_staff();
	}

	/**
	 * Response helper.
	 *
	 * @param mixed $data Data.
	 * @param array $meta Meta.
	 * @return WP_REST_Response
	 */
	private static function response( $data, $meta = array() ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
				'meta'    => $meta,
			)
		);
	}

	/**
	 * GET /me.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_me() {
		$user = wp_get_current_user();
		$role = $user->roles ? $user->roles[0] : 'guest';
		$scopes = array();

		foreach ( SG365_Capabilities::get_caps() as $cap ) {
			if ( current_user_can( $cap ) ) {
				$scopes[] = $cap;
			}
		}

		return self::response(
			array(
				'id'     => $user->ID,
				'name'   => $user->display_name,
				'email'  => $user->user_email,
				'role'   => $role,
				'scopes' => $scopes,
			)
		);
	}

	/**
	 * Client dashboard summary.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_dashboard() {
		global $wpdb;

		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array(), array( 'message' => 'Client record not found.' ) );
		}

		$sites = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sg365_sites WHERE client_id = %d AND archived_at IS NULL",
				$client_id
			)
		);
		$projects = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sg365_projects WHERE client_id = %d AND archived_at IS NULL",
				$client_id
			)
		);
		$tickets = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sg365_support_requests WHERE client_id = %d AND status IN ('open','inprogress')",
				$client_id
			)
		);

		return self::response(
			array(
				'sites'    => $sites,
				'projects' => $projects,
				'tickets'  => $tickets,
			)
		);
	}

	/**
	 * Client sites list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_sites() {
		global $wpdb;

		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array() );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, domain, service_type, included_services, plan, next_update_date, health_status, last_check_at
				FROM {$wpdb->prefix}sg365_sites WHERE client_id = %d AND archived_at IS NULL ORDER BY id DESC",
				$client_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Client projects.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_projects() {
		global $wpdb;
		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array() );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, name, description, status, eta_date
				FROM {$wpdb->prefix}sg365_projects WHERE client_id = %d AND archived_at IS NULL ORDER BY id DESC",
				$client_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Client worklogs.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_worklogs() {
		global $wpdb;
		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array() );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, site_id, project_id, title, details, log_date, time_minutes
				FROM {$wpdb->prefix}sg365_worklogs
				WHERE client_id = %d AND visibility_client = 1 AND archived_at IS NULL ORDER BY log_date DESC",
				$client_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Client support list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_support() {
		global $wpdb;
		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array() );
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, subject, message, status, priority, created_at, updated_at
				FROM {$wpdb->prefix}sg365_support_requests
				WHERE client_id = %d AND archived_at IS NULL ORDER BY created_at DESC",
				$client_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Create client support request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create_client_support( WP_REST_Request $request ) {
		global $wpdb;
		$client_id = SG365_DB::get_client_id_by_user( get_current_user_id() );
		if ( ! $client_id ) {
			return self::response( array(), array( 'message' => 'Client record not found.' ) );
		}

		$subject  = sanitize_text_field( $request->get_param( 'subject' ) );
		$message  = wp_kses_post( $request->get_param( 'message' ) );
		$priority = sanitize_text_field( $request->get_param( 'priority' ) );

		$wpdb->insert(
			$wpdb->prefix . 'sg365_support_requests',
			array(
				'client_id'           => $client_id,
				'created_by_user_id'  => get_current_user_id(),
				'subject'             => $subject,
				'message'             => $message,
				'status'              => 'open',
				'priority'            => $priority ? $priority : 'normal',
				'created_at'          => current_time( 'mysql' ),
				'updated_at'          => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		$request_id = (int) $wpdb->insert_id;
		SG365_Audit::log( 'create', 'support_request', $request_id, array( 'subject' => $subject ) );
		SG365_Notifications::notify_admins( 'new_support', 'New support request', $subject );

		return self::response( array( 'id' => $request_id ) );
	}

	/**
	 * Client notifications.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_client_notifications() {
		global $wpdb;
		$user_id = get_current_user_id();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, type, title, body, link_url, is_read, created_at
				FROM {$wpdb->prefix}sg365_notifications WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
				$user_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Mark client notification read.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function mark_client_notification( WP_REST_Request $request ) {
		global $wpdb;
		$user_id = get_current_user_id();
		$notification_id = absint( $request->get_param( 'id' ) );

		if ( $notification_id ) {
			$wpdb->update(
				$wpdb->prefix . 'sg365_notifications',
				array( 'is_read' => 1 ),
				array(
					'id'      => $notification_id,
					'user_id' => $user_id,
				),
				array( '%d' ),
				array( '%d', '%d' )
			);
		}

		return self::response( array( 'id' => $notification_id ) );
	}

	/**
	 * Staff dashboard summary.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_dashboard() {
		global $wpdb;
		$user_id = get_current_user_id();
		$client_ids = self::get_assigned_client_ids( $user_id );

		if ( empty( $client_ids ) ) {
			return self::response(
				array(
					'due_today' => 0,
					'overdue'   => 0,
					'new_tickets' => 0,
					'recent_activity' => array(),
				)
			);
		}

		$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}sg365_support_requests WHERE client_id IN ({$placeholders}) AND status = 'open'",
			$client_ids
		);
		$new_tickets = (int) $wpdb->get_var( $sql );

		return self::response(
			array(
				'due_today'       => 0,
				'overdue'         => 0,
				'new_tickets'     => $new_tickets,
				'recent_activity' => array(),
			)
		);
	}

	/**
	 * Staff clients list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_clients() {
		global $wpdb;
		$client_ids = self::get_assigned_client_ids( get_current_user_id() );
		if ( empty( $client_ids ) ) {
			return self::response( array() );
		}

		$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT id, company_name, email, phone, status FROM {$wpdb->prefix}sg365_clients WHERE id IN ({$placeholders})",
			$client_ids
		);
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::response( $results );
	}

	/**
	 * Staff sites list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_sites() {
		global $wpdb;
		$client_ids = self::get_assigned_client_ids( get_current_user_id() );
		if ( empty( $client_ids ) ) {
			return self::response( array() );
		}

		$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT id, client_id, domain, service_type, plan, health_status, last_check_at
			FROM {$wpdb->prefix}sg365_sites WHERE client_id IN ({$placeholders}) AND archived_at IS NULL",
			$client_ids
		);
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::response( $results );
	}

	/**
	 * Staff worklogs list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_worklogs() {
		global $wpdb;
		$user_id = get_current_user_id();

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, client_id, site_id, project_id, title, details, internal_notes, log_date, time_minutes, visibility_client
				FROM {$wpdb->prefix}sg365_worklogs
				WHERE staff_user_id = %d AND archived_at IS NULL ORDER BY log_date DESC",
				$user_id
			),
			ARRAY_A
		);

		return self::response( $results );
	}

	/**
	 * Create staff worklog.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function create_staff_worklog( WP_REST_Request $request ) {
		global $wpdb;
		$user_id   = get_current_user_id();
		$client_id = absint( $request->get_param( 'client_id' ) );

		if ( ! $client_id || ! SG365_DB::has_staff_assignment( $user_id, $client_id ) ) {
			return self::response( array(), array( 'message' => 'Not assigned to this client.' ) );
		}

		$data = array(
			'client_id'        => $client_id,
			'site_id'          => absint( $request->get_param( 'site_id' ) ),
			'project_id'       => absint( $request->get_param( 'project_id' ) ),
			'staff_user_id'    => $user_id,
			'title'            => sanitize_text_field( $request->get_param( 'title' ) ),
			'details'          => wp_kses_post( $request->get_param( 'details' ) ),
			'internal_notes'   => wp_kses_post( $request->get_param( 'internal_notes' ) ),
			'log_date'         => sanitize_text_field( $request->get_param( 'log_date' ) ),
			'time_minutes'     => absint( $request->get_param( 'time_minutes' ) ),
			'visibility_client'=> absint( $request->get_param( 'visibility_client' ) ),
		);

		$wpdb->insert(
			$wpdb->prefix . 'sg365_worklogs',
			$data,
			array( '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d' )
		);

		$worklog_id = (int) $wpdb->insert_id;
		SG365_Audit::log( 'create', 'worklog', $worklog_id, array( 'title' => $data['title'] ) );

		if ( $data['visibility_client'] ) {
			SG365_Notifications::notify_client( $client_id, 'worklog_added', 'Worklog added', $data['title'] );
		}

		return self::response( array( 'id' => $worklog_id ) );
	}

	/**
	 * Staff support list.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_support() {
		global $wpdb;
		$client_ids = self::get_assigned_client_ids( get_current_user_id() );
		if ( empty( $client_ids ) ) {
			return self::response( array() );
		}

		$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
		$sql = $wpdb->prepare(
			"SELECT id, client_id, subject, message, status, priority, assigned_staff_user_id, created_at
			FROM {$wpdb->prefix}sg365_support_requests WHERE client_id IN ({$placeholders}) AND archived_at IS NULL ORDER BY created_at DESC",
			$client_ids
		);
		$results = $wpdb->get_results( $sql, ARRAY_A );

		return self::response( $results );
	}

	/**
	 * Assign staff support ticket.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function assign_staff_support( WP_REST_Request $request ) {
		global $wpdb;
		$ticket_id = absint( $request->get_param( 'id' ) );
		$staff_id  = absint( $request->get_param( 'staff_user_id' ) );

		if ( ! $ticket_id || ! $staff_id ) {
			return self::response( array(), array( 'message' => 'Invalid ticket.' ) );
		}

		$wpdb->update(
			$wpdb->prefix . 'sg365_support_requests',
			array(
				'assigned_staff_user_id' => $staff_id,
				'status'                 => 'inprogress',
				'updated_at'             => current_time( 'mysql' ),
			),
			array( 'id' => $ticket_id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);

		SG365_Audit::log( 'assign', 'support_request', $ticket_id, array( 'assigned_staff_user_id' => $staff_id ) );
		SG365_Notifications::notify_user( $staff_id, 'ticket_assigned', 'Ticket assigned', 'A support ticket has been assigned to you.' );

		return self::response( array( 'id' => $ticket_id ) );
	}

	/**
	 * Staff notifications.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_staff_notifications() {
		return self::get_client_notifications();
	}

	/**
	 * Mark staff notification.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function mark_staff_notification( WP_REST_Request $request ) {
		return self::mark_client_notification( $request );
	}

	/**
	 * Search endpoint.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function search( WP_REST_Request $request ) {
		global $wpdb;
		$query = sanitize_text_field( $request->get_param( 'q' ) );
		if ( '' === $query ) {
			return self::response( array() );
		}

		$like = '%' . $wpdb->esc_like( $query ) . '%';
		$client_filter = '';
		$params = array( $like );

		if ( ! current_user_can( 'manage_options' ) ) {
			$client_ids = self::get_assigned_client_ids( get_current_user_id() );
			if ( empty( $client_ids ) ) {
				return self::response( array() );
			}
			$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
			$client_filter = " AND id IN ({$placeholders})";
			$params = array_merge( $params, $client_ids );
		}

		$clients = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, company_name FROM {$wpdb->prefix}sg365_clients WHERE company_name LIKE %s{$client_filter}",
				$params
			),
			ARRAY_A
		);

		$site_params = array( $like );
		$site_filter = '';
		if ( ! current_user_can( 'manage_options' ) ) {
			$client_ids = self::get_assigned_client_ids( get_current_user_id() );
			$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
			$site_filter = " AND client_id IN ({$placeholders})";
			$site_params = array_merge( $site_params, $client_ids );
		}
		$sites = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, domain FROM {$wpdb->prefix}sg365_sites WHERE domain LIKE %s{$site_filter}",
				$site_params
			),
			ARRAY_A
		);

		$project_params = array( $like );
		$project_filter = '';
		if ( ! current_user_can( 'manage_options' ) ) {
			$client_ids = self::get_assigned_client_ids( get_current_user_id() );
			$placeholders = implode( ',', array_fill( 0, count( $client_ids ), '%d' ) );
			$project_filter = " AND client_id IN ({$placeholders})";
			$project_params = array_merge( $project_params, $client_ids );
		}
		$projects = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, name FROM {$wpdb->prefix}sg365_projects WHERE name LIKE %s{$project_filter}",
				$project_params
			),
			ARRAY_A
		);

		return self::response(
			array(
				'clients'  => $clients,
				'sites'    => $sites,
				'projects' => $projects,
			)
		);
	}

	/**
	 * Get assigned client IDs.
	 *
	 * @param int $user_id User.
	 * @return int[]
	 */
	private static function get_assigned_client_ids( $user_id ) {
		global $wpdb;

		if ( current_user_can( 'manage_options' ) ) {
			$results = $wpdb->get_col( "SELECT id FROM {$wpdb->prefix}sg365_clients WHERE status = 'active'" );
			return array_map( 'absint', $results );
		}

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT client_id FROM {$wpdb->prefix}sg365_staff_assignments WHERE staff_user_id = %d",
				$user_id
			)
		);

		return array_map( 'absint', $results );
	}
}
