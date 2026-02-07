<?php
/**
 * Settings helper.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Settings {
	const OPTION_KEY = 'sg365_settings';

	public function get( $key, $default = null ) {
		$settings = get_option( self::OPTION_KEY, self::default_settings() );
		return $settings[ $key ] ?? $default;
	}

	public static function default_settings() {
		return array(
			'brand_name'                => 'SiteGuard365',
			'help_url'                  => 'https://siteguard365.com/support',
			'notification_poll_interval'=> 20,
			'client_menu'               => array(
				array( 'label' => 'Dashboard', 'icon' => 'dashicons-dashboard', 'key' => 'dashboard', 'enabled' => true ),
				array( 'label' => 'Sites', 'icon' => 'dashicons-admin-site', 'key' => 'sites', 'enabled' => true ),
				array( 'label' => 'Projects', 'icon' => 'dashicons-portfolio', 'key' => 'projects', 'enabled' => true ),
				array( 'label' => 'Support', 'icon' => 'dashicons-sos', 'key' => 'support', 'enabled' => true ),
			),
			'staff_menu'                => array(
				array( 'label' => 'Dashboard', 'icon' => 'dashicons-dashboard', 'key' => 'dashboard', 'enabled' => true ),
				array( 'label' => 'Clients', 'icon' => 'dashicons-groups', 'key' => 'clients', 'enabled' => true ),
				array( 'label' => 'Worklogs', 'icon' => 'dashicons-clipboard', 'key' => 'worklogs', 'enabled' => true ),
				array( 'label' => 'Support', 'icon' => 'dashicons-sos', 'key' => 'support', 'enabled' => true ),
			),
		);
	}

	public function update( $settings ) {
		update_option( self::OPTION_KEY, $settings );
	}
}
