<?php
/**
 * Capabilities and roles.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Capabilities {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'register_caps' ) );
	}

	/**
	 * Register capabilities for administrators.
	 *
	 * @return void
	 */
	public static function register_caps() {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}

		foreach ( self::get_caps() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Add SG365 roles.
	 *
	 * @return void
	 */
	public static function add_roles() {
		add_role(
			'sg365_staff',
			__( 'SG365 Staff', 'sg365-dashboard-suite' ),
			array(
				'read'                 => true,
				'sg365_view_staff_app' => true,
			)
		);

		add_role(
			'sg365_manager',
			__( 'SG365 Manager', 'sg365-dashboard-suite' ),
			array(
				'read'                 => true,
				'sg365_view_staff_app' => true,
				'sg365_manage_clients' => true,
				'sg365_manage_sites'   => true,
				'sg365_manage_projects'=> true,
				'sg365_manage_worklogs'=> true,
				'sg365_manage_tickets' => true,
			)
		);
	}

	/**
	 * Capability list.
	 *
	 * @return string[]
	 */
	public static function get_caps() {
		return array(
			'sg365_view_staff_app',
			'sg365_manage_clients',
			'sg365_manage_sites',
			'sg365_manage_projects',
			'sg365_manage_worklogs',
			'sg365_manage_tickets',
			'sg365_view_client_portal',
			'sg365_manage_settings',
		);
	}

	/**
	 * Check if current user is staff/manager.
	 *
	 * @return bool
	 */
	public static function is_staff() {
		return current_user_can( 'sg365_view_staff_app' ) || current_user_can( 'sg365_manage_clients' );
	}
}
