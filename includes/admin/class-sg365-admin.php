<?php
/**
 * Admin hooks.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Admin {
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_sg365_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_sg365_reset_settings', array( $this, 'reset_settings' ) );
	}

	public function register_menus() {
		add_menu_page(
			__( 'SG365 Dashboard', 'sg365-dashboard-suite' ),
			__( 'SG365 Dashboard', 'sg365-dashboard-suite' ),
			'manage_options',
			'sg365-dashboard',
			array( $this, 'render_page' ),
			'dashicons-shield'
		);

		$sections = array(
			'general'       => __( 'General', 'sg365-dashboard-suite' ),
			'branding'      => __( 'Branding', 'sg365-dashboard-suite' ),
			'staff-menu'    => __( 'Staff App Menu', 'sg365-dashboard-suite' ),
			'client-menu'   => __( 'Client Portal Menu', 'sg365-dashboard-suite' ),
			'notifications' => __( 'Notifications', 'sg365-dashboard-suite' ),
			'integrations'  => __( 'Integrations (Woo)', 'sg365-dashboard-suite' ),
			'security'      => __( 'Security', 'sg365-dashboard-suite' ),
			'tools'         => __( 'Tools', 'sg365-dashboard-suite' ),
		);

		foreach ( $sections as $slug => $label ) {
			add_submenu_page(
				'sg365-dashboard',
				$label,
				$label,
				'manage_options',
				'sg365-dashboard-' . $slug,
				array( $this, 'render_page' )
			);
		}
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'sg365-dashboard' ) ) {
			return;
		}

		wp_enqueue_style( 'sg365-admin', SG365_DS_PLUGIN_URL . 'assets/css/admin.css', array(), SG365_DS_VERSION );
		wp_enqueue_script( 'sg365-admin', SG365_DS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SG365_DS_VERSION, true );
		wp_localize_script(
			'sg365-admin',
			'sg365Admin',
			array(
				'nonce'    => wp_create_nonce( 'sg365_admin' ),
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'settings' => get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() ),
			)
		);
	}

	public function render_page() {
		$ui = new SG365_Admin_UI();
		$ui->render();
	}

	public function save_settings() {
		check_ajax_referer( 'sg365_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sg365-dashboard-suite' ) ), 403 );
		}

		$payload = json_decode( wp_unslash( $_POST['settings'] ?? '' ), true );
		if ( ! is_array( $payload ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings payload.', 'sg365-dashboard-suite' ) ), 400 );
		}

		update_option( SG365_Settings::OPTION_KEY, $payload );
		wp_send_json_success( array( 'settings' => $payload ) );
	}

	public function reset_settings() {
		check_ajax_referer( 'sg365_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sg365-dashboard-suite' ) ), 403 );
		}

		$defaults = SG365_Settings::default_settings();
		update_option( SG365_Settings::OPTION_KEY, $defaults );
		wp_send_json_success( array( 'settings' => $defaults ) );
	}
}
