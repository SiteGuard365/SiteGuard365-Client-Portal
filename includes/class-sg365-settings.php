<?php
/**
 * Settings storage.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Settings {
	const OPTION_KEY = 'sg365_settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_post_sg365_export_settings', array( __CLASS__, 'export_settings' ) );
		add_action( 'wp_ajax_sg365_save_settings', array( __CLASS__, 'ajax_save' ) );
		add_action( 'wp_ajax_sg365_reset_settings', array( __CLASS__, 'ajax_reset' ) );
		add_action( 'wp_ajax_sg365_import_settings', array( __CLASS__, 'ajax_import' ) );
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'brand_name'       => 'SiteGuard365',
			'help_url'         => 'https://siteguard365.com/support',
			'polling_interval' => 20,
			'client_menu'      => array(
				array( 'label' => 'Dashboard', 'icon' => 'dashicons-dashboard', 'slug' => 'dashboard' ),
				array( 'label' => 'Sites', 'icon' => 'dashicons-admin-site', 'slug' => 'sites' ),
				array( 'label' => 'Projects', 'icon' => 'dashicons-clipboard', 'slug' => 'projects' ),
				array( 'label' => 'Support', 'icon' => 'dashicons-sos', 'slug' => 'support' ),
				array( 'label' => 'Notifications', 'icon' => 'dashicons-bell', 'slug' => 'notifications' ),
			),
			'staff_menu'       => array(
				array( 'label' => 'Dashboard', 'icon' => 'dashicons-dashboard', 'slug' => 'dashboard' ),
				array( 'label' => 'Clients', 'icon' => 'dashicons-groups', 'slug' => 'clients' ),
				array( 'label' => 'Worklogs', 'icon' => 'dashicons-clipboard', 'slug' => 'worklogs' ),
				array( 'label' => 'Support', 'icon' => 'dashicons-sos', 'slug' => 'support' ),
				array( 'label' => 'Notifications', 'icon' => 'dashicons-bell', 'slug' => 'notifications' ),
			),
		);
	}

	/**
	 * Get settings with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$settings = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( $settings, self::defaults() );
	}

	/**
	 * Export settings as JSON.
	 *
	 * @return void
	 */
	public static function export_settings() {
		if ( ! current_user_can( 'sg365_manage_settings' ) ) {
			wp_die( esc_html__( 'Access denied.', 'sg365-dashboard-suite' ) );
		}

		$settings = self::get();
		nocache_headers();
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename=sg365-settings.json' );
		echo wp_json_encode( $settings );
		exit;
	}

	/**
	 * Handle AJAX save.
	 *
	 * @return void
	 */
	public static function ajax_save() {
		check_ajax_referer( 'sg365_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'sg365_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-dashboard-suite' ) ) );
		}

		$payload = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
		$data    = json_decode( $payload, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid settings payload.', 'sg365-dashboard-suite' ) ) );
		}

		update_option( self::OPTION_KEY, self::sanitize_settings( $data ) );

		wp_send_json_success( array( 'message' => __( 'Settings saved.', 'sg365-dashboard-suite' ) ) );
	}

	/**
	 * Handle AJAX reset.
	 *
	 * @return void
	 */
	public static function ajax_reset() {
		check_ajax_referer( 'sg365_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'sg365_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-dashboard-suite' ) ) );
		}

		update_option( self::OPTION_KEY, self::defaults() );
		wp_send_json_success( array( 'message' => __( 'Settings reset.', 'sg365-dashboard-suite' ) ) );
	}

	/**
	 * Handle import.
	 *
	 * @return void
	 */
	public static function ajax_import() {
		check_ajax_referer( 'sg365_settings_nonce', 'nonce' );

		if ( ! current_user_can( 'sg365_manage_settings' ) ) {
			wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-dashboard-suite' ) ) );
		}

		$payload = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
		$data    = json_decode( $payload, true );

		if ( ! is_array( $data ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid import file.', 'sg365-dashboard-suite' ) ) );
		}

		update_option( self::OPTION_KEY, self::sanitize_settings( $data ) );
		wp_send_json_success( array( 'message' => __( 'Settings imported.', 'sg365-dashboard-suite' ) ) );
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings.
	 * @return array
	 */
	public static function sanitize_settings( $settings ) {
		$defaults = self::defaults();

		$sanitized = array(
			'brand_name'       => sanitize_text_field( $settings['brand_name'] ?? $defaults['brand_name'] ),
			'help_url'         => esc_url_raw( $settings['help_url'] ?? $defaults['help_url'] ),
			'polling_interval' => absint( $settings['polling_interval'] ?? $defaults['polling_interval'] ),
			'client_menu'      => self::sanitize_menu( $settings['client_menu'] ?? $defaults['client_menu'] ),
			'staff_menu'       => self::sanitize_menu( $settings['staff_menu'] ?? $defaults['staff_menu'] ),
		);

		return $sanitized;
	}

	/**
	 * Sanitize menu items.
	 *
	 * @param array $menu Menu items.
	 * @return array
	 */
	private static function sanitize_menu( $menu ) {
		$sanitized = array();
		foreach ( (array) $menu as $item ) {
			$sanitized[] = array(
				'label' => sanitize_text_field( $item['label'] ?? '' ),
				'icon'  => sanitize_text_field( $item['icon'] ?? '' ),
				'slug'  => sanitize_title( $item['slug'] ?? '' ),
				'hidden'=> ! empty( $item['hidden'] ) ? 1 : 0,
			);
		}

		return $sanitized;
	}
}
