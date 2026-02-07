<?php
/**
 * Staff app shortcode.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Staff_App {
	public function __construct() {
		add_shortcode( 'sg365_staff_app', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style( 'sg365-staff-app', SG365_DS_PLUGIN_URL . 'assets/css/staff-app.css', array(), SG365_DS_VERSION );
		wp_register_script( 'sg365-staff-app', SG365_DS_PLUGIN_URL . 'assets/js/staff-app.js', array( 'jquery' ), SG365_DS_VERSION, true );
	}

	public function render_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please log in to view the staff dashboard.', 'sg365-dashboard-suite' ) . '</p>';
		}

		$settings = get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() );

		wp_enqueue_style( 'sg365-staff-app' );
		wp_enqueue_script( 'sg365-staff-app' );
		wp_localize_script(
			'sg365-staff-app',
			'sg365Staff',
			array(
				'root'  => esc_url_raw( rest_url( 'sg365/v1' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'interval' => absint( $settings['notification_poll_interval'] ?? 20 ),
			)
		);

		ob_start();
		include SG365_DS_PLUGIN_DIR . 'templates/staff-shell.php';
		return ob_get_clean();
	}
}
