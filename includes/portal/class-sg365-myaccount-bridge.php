<?php
/**
 * WooCommerce My Account endpoint bridge.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_MyAccount_Bridge {
	const ENDPOINT = 'sg365-portal';

	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_item' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render_endpoint' ) );
	}

	public function add_endpoint() {
		if ( ! SG365_Loader::is_woocommerce_active() ) {
			return;
		}

		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	public function add_query_vars( $vars ) {
		$vars[] = self::ENDPOINT;
		return $vars;
	}

	public function add_menu_item( $items ) {
		if ( ! SG365_Loader::is_woocommerce_active() ) {
			return $items;
		}

		$items[ self::ENDPOINT ] = __( 'Dashboard', 'sg365-dashboard-suite' );
		return $items;
	}

	public function render_endpoint() {
		echo do_shortcode( '[sg365_client_app]' );
	}
}
