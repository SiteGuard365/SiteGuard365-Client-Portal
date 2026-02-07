<?php
/**
 * Staff app shell.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Staff_App {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_shortcode( 'sg365_staff_app', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style( 'sg365-staff', SG365_DS_PLUGIN_URL . 'assets/css/staff-app.css', array(), SG365_DS_VERSION );
		wp_register_script( 'sg365-staff', SG365_DS_PLUGIN_URL . 'assets/js/staff-app.js', array( 'jquery' ), SG365_DS_VERSION, true );
		wp_localize_script(
			'sg365-staff',
			'SG365Staff',
			array(
				'root'  => esc_url_raw( rest_url( 'sg365/v1' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'settings' => SG365_Settings::get(),
			)
		);
	}

	/**
	 * Render shortcode.
	 *
	 * @return string
	 */
	public static function render_shortcode() {
		wp_enqueue_style( 'sg365-staff' );
		wp_enqueue_script( 'sg365-staff' );

		ob_start();
		$settings = SG365_Settings::get();
		include SG365_DS_PLUGIN_DIR . 'templates/staff-shell.php';
		return ob_get_clean();
	}
}
