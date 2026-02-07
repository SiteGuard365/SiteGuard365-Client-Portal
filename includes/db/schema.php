<?php
/**
 * Database schema definitions.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sg365_get_schema() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	$tables = array();

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_clients (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		company_name VARCHAR(190) NOT NULL,
		phone VARCHAR(50) DEFAULT '',
		email VARCHAR(190) DEFAULT '',
		plan VARCHAR(30) DEFAULT 'basic',
		report_emails LONGTEXT NULL,
		status VARCHAR(20) DEFAULT 'active',
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_sites (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		domain VARCHAR(190) NOT NULL,
		service_type VARCHAR(50) DEFAULT '',
		included_services LONGTEXT NULL,
		plan VARCHAR(20) DEFAULT 'monthly',
		next_update_date DATE NULL,
		health_status VARCHAR(20) DEFAULT 'ok',
		last_check_at DATETIME NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY (id),
		KEY domain (domain)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_projects (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		name VARCHAR(190) NOT NULL,
		description LONGTEXT NULL,
		status VARCHAR(30) DEFAULT 'assigned',
		eta_date DATE NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_staff_assignments (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		staff_user_id BIGINT UNSIGNED NOT NULL,
		client_id BIGINT UNSIGNED NOT NULL,
		role VARCHAR(30) DEFAULT 'staff',
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

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
		time_minutes INT UNSIGNED DEFAULT 0,
		visibility_client TINYINT(1) DEFAULT 0,
		attachments LONGTEXT NULL,
		archived_at DATETIME NULL,
		PRIMARY KEY (id),
		KEY client_id (client_id),
		KEY staff_user_id (staff_user_id)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_support_requests (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		created_by_user_id BIGINT UNSIGNED NOT NULL,
		subject VARCHAR(190) NOT NULL,
		message LONGTEXT NULL,
		status VARCHAR(30) DEFAULT 'open',
		priority VARCHAR(20) DEFAULT 'normal',
		attachments LONGTEXT NULL,
		assigned_staff_user_id BIGINT UNSIGNED NULL,
		archived_at DATETIME NULL,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY client_id (client_id),
		KEY status (status)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_notifications (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT UNSIGNED NOT NULL,
		type VARCHAR(50) DEFAULT '',
		title VARCHAR(190) NOT NULL,
		body LONGTEXT NULL,
		link_url VARCHAR(255) DEFAULT '',
		is_read TINYINT(1) DEFAULT 0,
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY is_read (is_read)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_chat_threads (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		client_id BIGINT UNSIGNED NOT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_chat_messages (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		thread_id BIGINT UNSIGNED NOT NULL,
		sender_user_id BIGINT UNSIGNED NOT NULL,
		message LONGTEXT NULL,
		attachments LONGTEXT NULL,
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	$tables[] = "CREATE TABLE {$wpdb->prefix}sg365_audit_log (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		actor_user_id BIGINT UNSIGNED NOT NULL,
		action VARCHAR(60) NOT NULL,
		object_type VARCHAR(60) NOT NULL,
		object_id BIGINT UNSIGNED NOT NULL,
		old_data_json LONGTEXT NULL,
		new_data_json LONGTEXT NULL,
		ip VARCHAR(64) DEFAULT '',
		user_agent VARCHAR(255) DEFAULT '',
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	return $tables;
}
