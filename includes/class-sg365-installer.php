<?php
/**
 * Installer routines.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Installer {
	public static function activate() {
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-capabilities.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-settings.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/db/class-sg365-db.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/db/schema.php';

		SG365_Capabilities::add_roles();
		SG365_DB::install();

		add_option( 'sg365_settings', SG365_Settings::default_settings() );

		flush_rewrite_rules();
	}
}
