<?php
/**
 * Client portal shell.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Client_Portal {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_shortcode( 'sg365_client_app', array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	public static function register_assets() {
		wp_register_style( 'sg365-portal', SG365_DS_PLUGIN_URL . 'assets/css/portal.css', array(), SG365_DS_VERSION );
		wp_register_script( 'sg365-portal', SG365_DS_PLUGIN_URL . 'assets/js/portal.js', array( 'jquery' ), SG365_DS_VERSION, true );
		wp_localize_script(
			'sg365-portal',
			'SG365Portal',
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
		wp_enqueue_style( 'sg365-portal' );
		wp_enqueue_script( 'sg365-portal' );

		ob_start();
		$settings = SG365_Settings::get();
		include SG365_DS_PLUGIN_DIR . 'templates/portal-shell.php';
		return ob_get_clean();
	}
}
