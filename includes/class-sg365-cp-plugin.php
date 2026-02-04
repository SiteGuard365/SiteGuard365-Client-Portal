<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once SG365_CP_PLUGIN_DIR . 'includes/admin/class-sg365-cp-admin.php';
require_once SG365_CP_PLUGIN_DIR . 'includes/frontend/class-sg365-cp-myaccount.php';

final class SG365_CP_Plugin {

    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_caps' ) );
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );

        if ( is_admin() ) {
            SG365_CP_Admin::instance();
        }

        SG365_CP_MyAccount::instance();
    }

    public static function activate(): void {
        self::instance()->register_post_types();
        flush_rewrite_rules();
        self::instance()->register_caps();
        update_option( 'sg365_cp_version', SG365_CP_VERSION );
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public function maybe_upgrade(): void {
        $stored = (string) get_option( 'sg365_cp_version', '1.0.0' );
        if ( version_compare( $stored, SG365_CP_VERSION, '<' ) ) {
            flush_rewrite_rules();
            update_option( 'sg365_cp_version', SG365_CP_VERSION );
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'sg365-client-portal', false, dirname( plugin_basename( SG365_CP_PLUGIN_FILE ) ) . '/languages' );
    }

    public function register_caps(): void {
        $role = get_role( 'administrator' );
        if ( $role && ! $role->has_cap( 'sg365_cp_manage' ) ) {
            $role->add_cap( 'sg365_cp_manage', true );
        }
        $sm = get_role( 'shop_manager' );
        if ( $sm && ! $sm->has_cap( 'sg365_cp_manage' ) ) {
            $sm->add_cap( 'sg365_cp_manage', true );
        }
    }

    public function register_post_types(): void {
        $common = array(
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => array( 'title' ),
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
        );

        register_post_type( 'sg365_client', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Clients', 'sg365-client-portal' ),
                'singular_name' => __( 'Client', 'sg365-client-portal' ),
                'add_new_item'  => __( 'Add Client', 'sg365-client-portal' ),
                'edit_item'     => __( 'Edit Client', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-businessperson',
        )));

        register_post_type( 'sg365_site', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Sites / Domains', 'sg365-client-portal' ),
                'singular_name' => __( 'Site / Domain', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-admin-site',
            'supports'  => array( 'title' ),
        )));

        register_post_type( 'sg365_project', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Projects', 'sg365-client-portal' ),
                'singular_name' => __( 'Project', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-portfolio',
            'supports'  => array( 'title', 'editor' ),
        )));

        register_post_type( 'sg365_worklog', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Work Logs', 'sg365-client-portal' ),
                'singular_name' => __( 'Work Log', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-clipboard',
            'supports'  => array( 'title', 'editor' ),
        )));

        register_post_type( 'sg365_staff', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Staff', 'sg365-client-portal' ),
                'singular_name' => __( 'Staff Member', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-groups',
            'supports'  => array( 'title' ),
        )));

        register_post_type( 'sg365_salary', array_merge( $common, array(
            'labels' => array(
                'name'          => __( 'Salary Sheets', 'sg365-client-portal' ),
                'singular_name' => __( 'Salary Sheet', 'sg365-client-portal' ),
            ),
            'menu_icon' => 'dashicons-money-alt',
            'supports'  => array( 'title' ),
        )));
    }
}
