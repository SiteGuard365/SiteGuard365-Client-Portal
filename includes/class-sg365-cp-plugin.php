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
        add_action( 'save_post_sg365_worklog', array( $this, 'maybe_send_worklog_email' ), 10, 3 );
        add_action( 'save_post_sg365_site', array( $this, 'maybe_send_site_email' ), 10, 3 );
        add_action( 'save_post_sg365_salary', array( $this, 'maybe_send_salary_emails' ), 10, 3 );

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
        $stored_version = (string) get_option( 'sg365_cp_version', '0.0.0' );
        if ( version_compare( $stored_version, '1.1.0', '<' ) ) {
            $flushed_flag = (int) get_option( 'sg365_cp_rewrite_flushed_110', 0 );
            if ( ! $flushed_flag ) {
                flush_rewrite_rules();
                update_option( 'sg365_cp_rewrite_flushed_110', time() );
            }
        }
        if ( $stored_version !== SG365_CP_VERSION ) {
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

    public function maybe_send_worklog_email( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        if ( $update ) {
            return;
        }
        $visible = (int) get_post_meta( $post_id, '_sg365_visible_client', true );
        if ( ! $visible ) {
            return;
        }
        $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
        $user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
        if ( ! $user_id ) {
            return;
        }
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return;
        }
        $site_id = (int) get_post_meta( $post_id, '_sg365_site_id', true );
        $data = array(
            'client_name'  => $client_id ? get_the_title( $client_id ) : $user->display_name,
            'domain'       => $site_id ? get_the_title( $site_id ) : '',
            'project_name' => $this->get_project_name_for_log( $post_id ),
            'log_title'    => $post->post_title,
            'log_date'     => (string) get_post_meta( $post_id, '_sg365_log_date', true ),
            'month'        => gmdate( 'F' ),
            'amount'       => '',
            'due_date'     => '',
            'portal_link'  => sg365_cp_get_portal_link( $client_id ),
        );
        sg365_cp_send_email( 'worklog_client', $user->user_email, $data, (string) $post_id );
    }

    public function maybe_send_site_email( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        if ( $update ) {
            return;
        }
        $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
        if ( ! $client_id ) {
            return;
        }
        $user_id = sg365_cp_get_user_id_for_client( $client_id );
        $user = $user_id ? get_user_by( 'id', $user_id ) : null;
        if ( ! $user ) {
            return;
        }
        $data = array(
            'client_name'  => $client_id ? get_the_title( $client_id ) : $user->display_name,
            'domain'       => $post->post_title,
            'project_name' => '',
            'log_title'    => '',
            'log_date'     => '',
            'month'        => gmdate( 'F' ),
            'amount'       => '',
            'due_date'     => '',
            'portal_link'  => sg365_cp_get_portal_link( $client_id ),
        );
        sg365_cp_send_email( 'site_client', $user->user_email, $data, (string) $post_id );
    }

    public function maybe_send_salary_emails( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        $settings = sg365_cp_get_email_settings();
        $due_date = (string) get_post_meta( $post_id, '_sg365_due_date', true );
        $paid = (int) get_post_meta( $post_id, '_sg365_paid', true );
        $direction = (string) get_post_meta( $post_id, '_sg365_salary_direction', true );
        $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
        $user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
        $user = $user_id ? get_user_by( 'id', $user_id ) : null;
        $amount = (float) get_post_meta( $post_id, '_sg365_base', true );
        $bonus = (float) get_post_meta( $post_id, '_sg365_bonus', true );
        $deduction = (float) get_post_meta( $post_id, '_sg365_deduction', true );
        $amount_total = number_format_i18n( $amount + $bonus - $deduction, 2 );
        $data = array(
            'client_name'  => $client_id ? get_the_title( $client_id ) : $post->post_title,
            'domain'       => '',
            'project_name' => '',
            'log_title'    => '',
            'log_date'     => '',
            'month'        => (string) get_post_meta( $post_id, '_sg365_month', true ),
            'amount'       => $amount_total,
            'due_date'     => $due_date,
            'portal_link'  => sg365_cp_get_portal_link( $client_id ),
        );
        $admin_email = get_option( 'admin_email' );

        if ( $due_date && ! $paid ) {
            $days = (int) ( $settings['salary_due_soon']['days'] ?? 5 );
            $diff = ( strtotime( $due_date ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS;
            if ( $diff >= 0 && $diff <= $days ) {
                sg365_cp_send_email( 'salary_due_soon', $admin_email, $data, (string) $post_id );
            }
            if ( $diff < 0 ) {
                sg365_cp_send_email( 'salary_overdue', $admin_email, $data, (string) $post_id );
            }
        }

        $status_key = 'salary_status_' . $post_id;
        if ( ! sg365_cp_email_already_sent( $status_key ) && $update ) {
            sg365_cp_send_email( 'salary_status', $admin_email, $data, (string) $post_id . '_admin' );
            if ( $user ) {
                sg365_cp_send_email( 'salary_status', $user->user_email, $data, (string) $post_id . '_client' );
            }
            sg365_cp_record_email_sent( $status_key );
        }
    }

    private function get_project_name_for_log( int $log_id ): string {
        $project_id = (int) get_post_meta( $log_id, '_sg365_project_id', true );
        return $project_id ? get_the_title( $project_id ) : '';
    }
}
