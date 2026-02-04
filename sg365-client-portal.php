<?php
/**
 * Plugin Name: SiteGuard365 Client Portal (Work Logs & Salary)
 * Description: Manage client domains/sites, projects, work logs, and monthly salary sheets. Adds a client portal inside WooCommerce My Account.
 * Version: 1.0.0
 * Author: Site Guard 365
 * Author URI: https://siteguard365.com
 * Plugin URI: https://siteguard365.com
 * Text Domain: sg365-client-portal
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package SG365_Client_Portal
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'SG365_CP_VERSION', '1.0.0' );
define( 'SG365_CP_PLUGIN_FILE', __FILE__ );
define( 'SG365_CP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SG365_CP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SG365_CP_PLUGIN_DIR . 'includes/helpers.php';
require_once SG365_CP_PLUGIN_DIR . 'includes/class-sg365-cp-plugin.php';

add_action( 'plugins_loaded', array( 'SG365_CP_Plugin', 'instance' ) );

register_activation_hook( __FILE__, array( 'SG365_CP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SG365_CP_Plugin', 'deactivate' ) );
