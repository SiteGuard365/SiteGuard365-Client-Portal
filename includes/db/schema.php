<?php
/**
 * Database schema installer.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Install DB tables.
 *
 * @return void
 */
function sg365_install_schema() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset = $wpdb->get_charset_collate();

	$tables = array();

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_clients (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		company_name VARCHAR(190) NOT NULL,
		phone VARCHAR(50) NOT NULL DEFAULT '',
		email VARCHAR(190) NOT NULL,
		plan VARCHAR(20) NOT NULL DEFAULT 'basic',
		client_notes LONGTEXT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'active',
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_sites (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		domain VARCHAR(190) NOT NULL,
		service_type VARCHAR(50) NOT NULL DEFAULT '',
		included_services LONGTEXT NULL,
		plan VARCHAR(20) NOT NULL DEFAULT 'monthly',
		next_update_date DATE NULL,
		health_status VARCHAR(20) NOT NULL DEFAULT 'ok',
		last_check_at DATETIME NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY domain (domain)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_projects (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		name VARCHAR(190) NOT NULL,
		description LONGTEXT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'assigned',
		eta_date DATE NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_staff_assignments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		staff_user_id BIGINT UNSIGNED NOT NULL,
		client_id BIGINT UNSIGNED NOT NULL,
		role VARCHAR(20) NOT NULL DEFAULT 'staff',
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_worklogs (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		site_id BIGINT UNSIGNED NULL,
		project_id BIGINT UNSIGNED NULL,
		staff_user_id BIGINT UNSIGNED NOT NULL,
		title VARCHAR(190) NOT NULL,
		details LONGTEXT NULL,
		internal_notes LONGTEXT NULL,
		log_date DATE NOT NULL,
		time_minutes INT NOT NULL DEFAULT 0,
		visibility_client TINYINT(1) NOT NULL DEFAULT 0,
		attachments LONGTEXT NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY  (id),
		KEY client_id (client_id),
		KEY staff_user_id (staff_user_id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_support_requests (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		created_by_user_id BIGINT UNSIGNED NOT NULL,
		subject VARCHAR(190) NOT NULL,
		message LONGTEXT NOT NULL,
		status VARCHAR(20) NOT NULL DEFAULT 'open',
		priority VARCHAR(20) NOT NULL DEFAULT 'normal',
		attachments LONGTEXT NULL,
		assigned_staff_user_id BIGINT UNSIGNED NULL,
		archived_at DATETIME NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY client_status (client_id, status)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_notifications (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(50) NOT NULL,
		title VARCHAR(190) NOT NULL,
		body LONGTEXT NOT NULL,
		link_url VARCHAR(255) NOT NULL DEFAULT '',
		is_read TINYINT(1) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY user_read (user_id, is_read)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_chat_threads (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_chat_messages (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		thread_id BIGINT UNSIGNED NOT NULL,
		sender_user_id BIGINT UNSIGNED NOT NULL,
		message LONGTEXT NOT NULL,
		attachments LONGTEXT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_audit_log (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		actor_user_id BIGINT UNSIGNED NOT NULL,
		action VARCHAR(50) NOT NULL,
		object_type VARCHAR(50) NOT NULL,
		object_id BIGINT UNSIGNED NOT NULL,
		changes_json LONGTEXT NULL,
		ip VARCHAR(50) NOT NULL DEFAULT '',
		user_agent VARCHAR(255) NOT NULL DEFAULT '',
		created_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) {$charset};";

	foreach ( $tables as $sql ) {
		dbDelta( $sql );
	}
}
