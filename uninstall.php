<?php
/**
 * Uninstall cleanup.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sg365_settings' );
