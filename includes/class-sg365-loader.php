<?php
/**
 * Plugin loader.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Loader {
	private static $instance = null;
	private $settings = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->bootstrap();
		}

		return self::$instance;
	}

	public static function activate() {
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-installer.php';
		SG365_Installer::activate();
	}

	public static function deactivate() {
		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-cron.php';
		SG365_Cron::deactivate();
		flush_rewrite_rules();
	}

	private function bootstrap() {
		spl_autoload_register( array( $this, 'autoload' ) );

		require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-settings.php';
		$this->settings = new SG365_Settings();

		add_action( 'admin_notices', array( $this, 'maybe_show_woocommerce_notice' ) );

		$this->init_components();
	}

	public function autoload( $class ) {
		if ( 0 !== strpos( $class, 'SG365_' ) ) {
			return;
		}

		$map = array(
			'SG365_Installer'        => 'class-sg365-installer.php',
			'SG365_Capabilities'     => 'class-sg365-capabilities.php',
			'SG365_Encryption'       => 'class-sg365-encryption.php',
			'SG365_Audit'            => 'class-sg365-audit.php',
			'SG365_Rest'             => 'class-sg365-rest.php',
			'SG365_Notifications'    => 'class-sg365-notifications.php',
			'SG365_Search'           => 'class-sg365-search.php',
			'SG365_Cron'             => 'class-sg365-cron.php',
			'SG365_Settings'         => 'class-sg365-settings.php',
			'SG365_DB'               => 'db/class-sg365-db.php',
			'SG365_Admin'            => 'admin/class-sg365-admin.php',
			'SG365_Admin_UI'         => 'admin/class-sg365-admin-ui.php',
			'SG365_Client_Portal'    => 'portal/class-sg365-client-portal.php',
			'SG365_MyAccount_Bridge' => 'portal/class-sg365-myaccount-bridge.php',
			'SG365_Staff_App'        => 'portal/class-sg365-staff-app.php',
		);

		if ( isset( $map[ $class ] ) ) {
			require_once SG365_DS_PLUGIN_DIR . 'includes/' . $map[ $class ];
		}
	}

	private function init_components() {
		new SG365_Capabilities();
		new SG365_Rest();
		new SG365_Search();
		new SG365_Cron();
		new SG365_Notifications();
		new SG365_Client_Portal();
		new SG365_Staff_App();
		new SG365_MyAccount_Bridge();

		if ( is_admin() ) {
			new SG365_Admin();
			new SG365_Admin_UI();
		}
	}

	public function maybe_show_woocommerce_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( self::is_woocommerce_active() ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>' . esc_html__( 'SiteGuard365 Dashboard Suite: WooCommerce is not active. The My Account portal endpoint is disabled until WooCommerce is activated.', 'sg365-dashboard-suite' ) . '</p></div>';
	}

	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}
