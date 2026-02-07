<?php
/**
 * Core loader for SG365 Dashboard Suite.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Loader {
	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public static function init() {
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-installer.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-capabilities.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-encryption.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-audit.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/db/class-sg365-db.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-settings.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-rest.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-notifications.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-search.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-cron.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/portal/class-sg365-client-portal.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/portal/class-sg365-staff-app.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/portal/class-sg365-myaccount-bridge.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/admin/class-sg365-admin.php';
		require_once SG365_DS_PLUGIN_DIR . 'includes/admin/class-sg365-admin-ui.php';

		SG365_Capabilities::register_hooks();
		SG365_Settings::register_hooks();
		SG365_REST::register_hooks();
		SG365_Notifications::register_hooks();
		SG365_Search::register_hooks();
		SG365_Cron::register_hooks();
		SG365_Client_Portal::register_hooks();
		SG365_Staff_App::register_hooks();
		SG365_Admin::register_hooks();
		SG365_Admin_UI::register_hooks();

		if ( SG365_Installer::is_woocommerce_active() ) {
			SG365_MyAccount_Bridge::register_hooks();
		} else {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
		}
	}

	/**
	 * Display notice when WooCommerce is not active.
	 *
	 * @return void
	 */
	public static function woocommerce_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'SiteGuard365 Dashboard Suite requires WooCommerce to enable the My Account portal endpoint. The shortcodes remain available.', 'sg365-dashboard-suite' );
		echo '</p></div>';
	}
}
