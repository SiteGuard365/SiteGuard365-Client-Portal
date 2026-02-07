<?php
/**
 * Installer for SG365 Dashboard Suite.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Installer {
	/**
	 * Activation hook.
	 *
	 * @return void
	 */
	public static function activate() {
		require_once SG365_DS_PLUGIN_DIR . 'includes/db/schema.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-capabilities.php';

		SG365_Capabilities::add_roles();
		sg365_install_schema();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}
