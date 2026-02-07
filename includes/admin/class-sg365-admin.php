<?php
/**
 * Admin bootstrap.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Admin {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_ajax_sg365_rebuild_indexes', array( __CLASS__, 'rebuild_indexes' ) );
		}
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'SG365 Dashboard', 'sg365-dashboard-suite' ),
			__( 'SG365 Dashboard', 'sg365-dashboard-suite' ),
			'sg365_manage_settings',
			'sg365-dashboard',
			array( 'SG365_Admin_UI', 'render' ),
			'dashicons-shield-alt',
			56
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_sg365-dashboard' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'sg365-admin', SG365_DS_PLUGIN_URL . 'assets/css/admin.css', array(), SG365_DS_VERSION );
		wp_enqueue_script( 'sg365-admin', SG365_DS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SG365_DS_VERSION, true );
		wp_localize_script(
			'sg365-admin',
			'SG365Admin',
			array(
				'nonce'   => wp_create_nonce( 'sg365_settings_nonce' ),
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Rebuild DB indexes.
	 *
	 * @return void
	 */
	public static function rebuild_indexes() {
		check_ajax_referer( 'sg365_settings_nonce', 'nonce' );
		if ( ! current_user_can( 'sg365_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-dashboard-suite' ) ) );
		}

		require_once SG365_DS_PLUGIN_DIR . 'includes/db/schema.php';
		sg365_install_schema();

		wp_send_json_success( array( 'message' => __( 'Indexes rebuilt.', 'sg365-dashboard-suite' ) ) );
	}
}
