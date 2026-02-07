<?php
/**
 * WooCommerce My Account bridge.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_MyAccount_Bridge {
	const ENDPOINT = 'sg365-portal';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'init', array( __CLASS__, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render_endpoint' ) );
	}

	/**
	 * Add endpoint.
	 *
	 * @return void
	 */
	public static function add_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Add menu item.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	public static function add_menu_item( $items ) {
		$items[ self::ENDPOINT ] = __( 'SiteGuard365 Portal', 'sg365-dashboard-suite' );
		return $items;
	}

	/**
	 * Render endpoint content.
	 *
	 * @return void
	 */
	public static function render_endpoint() {
		echo do_shortcode( '[sg365_client_app]' );
	}
}
