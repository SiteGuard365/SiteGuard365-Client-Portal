<?php
/**
 * Uninstall cleanup.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$tables = array(
	$wpdb->prefix . 'sg365_clients',
	$wpdb->prefix . 'sg365_sites',
	$wpdb->prefix . 'sg365_projects',
	$wpdb->prefix . 'sg365_staff_assignments',
	$wpdb->prefix . 'sg365_worklogs',
	$wpdb->prefix . 'sg365_support_requests',
	$wpdb->prefix . 'sg365_notifications',
	$wpdb->prefix . 'sg365_chat_threads',
	$wpdb->prefix . 'sg365_chat_messages',
	$wpdb->prefix . 'sg365_audit_log',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

remove_role( 'sg365_staff' );
remove_role( 'sg365_manager' );

delete_option( 'sg365_settings' );
