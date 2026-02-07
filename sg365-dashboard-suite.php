<?php
/**
 * Plugin Name: SiteGuard365 Dashboard Suite
 * Description: Stripe-style client portal and staff dashboard for SiteGuard365 with REST-first architecture.
 * Version: 1.0.0
 * Author: Site Guard 365
 * Author URI: https://siteguard365.com
 * Plugin URI: https://siteguard365.com
 * Text Domain: sg365-dashboard-suite
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SG365_DS_VERSION', '1.0.0' );
define( 'SG365_DS_PLUGIN_FILE', __FILE__ );
define( 'SG365_DS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SG365_DS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SG365_DS_PLUGIN_DIR . 'includes/class-sg365-loader.php';

add_action( 'plugins_loaded', array( 'SG365_Loader', 'init' ) );

register_activation_hook( __FILE__, array( 'SG365_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SG365_Installer', 'deactivate' ) );
