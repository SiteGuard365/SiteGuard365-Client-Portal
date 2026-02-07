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
		add_action( 'wp_ajax_sg365_reveal_secret', array( $this, 'reveal_secret' ) );
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

		$existing = get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() );
		$encryption = new SG365_Encryption();
		$masked_value = '••••••';

		foreach ( array( 'smtp_password', 'api_token', 'webhook_secret' ) as $secret_key ) {
			if ( empty( $payload[ $secret_key ] ) || $masked_value === $payload[ $secret_key ] ) {
				$payload[ $secret_key ] = $existing[ $secret_key ] ?? '';
				continue;
			}
			$payload[ $secret_key ] = $encryption->encrypt( sanitize_text_field( $payload[ $secret_key ] ) );
		}

		$payload['brand_name'] = sanitize_text_field( $payload['brand_name'] ?? '' );
		$payload['help_url'] = esc_url_raw( $payload['help_url'] ?? '' );
		$payload['notification_poll_interval'] = max( 5, absint( $payload['notification_poll_interval'] ?? 20 ) );

		update_option( SG365_Settings::OPTION_KEY, $payload );
		SG365_Audit::log( 'update', 'settings', 0, $existing, $payload );
		wp_send_json_success( array( 'settings' => $payload ) );
	}

	public function reset_settings() {
		check_ajax_referer( 'sg365_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sg365-dashboard-suite' ) ), 403 );
		}

		$defaults = SG365_Settings::default_settings();
		update_option( SG365_Settings::OPTION_KEY, $defaults );
		SG365_Audit::log( 'update', 'settings', 0, array(), $defaults );
		wp_send_json_success( array( 'settings' => $defaults ) );
	}

	public function reveal_secret() {
		check_ajax_referer( 'sg365_admin', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'sg365-dashboard-suite' ) ), 403 );
		}

		$key = sanitize_key( wp_unslash( $_POST['key'] ?? '' ) );
		if ( ! in_array( $key, array( 'smtp_password', 'api_token', 'webhook_secret' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid secret key.', 'sg365-dashboard-suite' ) ), 400 );
		}

		$settings = get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() );
		$encryption = new SG365_Encryption();
		$value = $settings[ $key ] ? $encryption->decrypt( $settings[ $key ] ) : '';
		wp_send_json_success( array( 'value' => $value ) );
	}
}
