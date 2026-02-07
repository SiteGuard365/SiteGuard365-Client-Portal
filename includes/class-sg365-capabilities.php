<?php
/**
 * Capabilities and roles.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Capabilities {
	const ROLE_STAFF = 'sg365_staff';
	const ROLE_MANAGER = 'sg365_manager';

	public function __construct() {
		add_action( 'init', array( $this, 'register_caps' ) );
	}

	public static function add_roles() {
		add_role(
			self::ROLE_STAFF,
			__( 'SG365 Staff', 'sg365-dashboard-suite' ),
			self::get_caps()
		);

		add_role(
			self::ROLE_MANAGER,
			__( 'SG365 Manager', 'sg365-dashboard-suite' ),
			array_merge( self::get_caps(), array( 'sg365_manage_settings' => true ) )
		);
	}

	public function register_caps() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( array_keys( self::get_caps() ) as $cap ) {
				$admin->add_cap( $cap );
			}
		}
	}

	public static function get_caps() {
		return array(
			'sg365_view_staff_app'    => true,
			'sg365_manage_clients'    => true,
			'sg365_manage_sites'      => true,
			'sg365_manage_projects'   => true,
			'sg365_manage_worklogs'   => true,
			'sg365_manage_tickets'    => true,
			'sg365_view_client_portal'=> true,
			'sg365_manage_settings'   => false,
		);
	}
}
