<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once SG365_CP_PLUGIN_DIR . 'includes/admin/metaboxes.php';
require_once SG365_CP_PLUGIN_DIR . 'includes/admin/class-sg365-cp-dashboard.php';
require_once SG365_CP_PLUGIN_DIR . 'includes/admin/class-sg365-cp-email-center.php';

final class SG365_CP_Admin {

    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_notices', array( $this, 'portal_notice' ) );
        add_action( 'admin_post_sg365_cp_fix_portal', array( $this, 'fix_portal' ) );
        add_action( 'admin_post_sg365_cp_save_service_types', array( $this, 'save_service_types' ) );
        add_filter( 'manage_sg365_project_posts_columns', array( $this, 'project_columns' ) );
        add_action( 'manage_sg365_project_posts_custom_column', array( $this, 'project_column_content' ), 10, 2 );

        SG365_CP_Metaboxes::instance();
        SG365_CP_Dashboard::instance();
        SG365_CP_Email_Center::instance();
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'SiteGuard365 Portal', 'sg365-client-portal' ),
            __( 'SiteGuard365 Portal', 'sg365-client-portal' ),
            'sg365_cp_manage',
            'sg365-cp-dashboard',
            array( $this, 'render_dashboard' ),
            'dashicons-shield',
            56
        );

        add_submenu_page( 'sg365-cp-dashboard', __( 'Clients', 'sg365-client-portal' ), __( 'Clients', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_client' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Sites / Domains', 'sg365-client-portal' ), __( 'Sites / Domains', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_site' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Projects', 'sg365-client-portal' ), __( 'Projects', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_project' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Work Logs', 'sg365-client-portal' ), __( 'Work Logs', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_worklog' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'All Work Logs', 'sg365-client-portal' ), __( 'All Work Logs', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-worklogs', array( $this, 'render_worklogs' ) );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Staff', 'sg365-client-portal' ), __( 'Staff', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_staff' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Salary', 'sg365-client-portal' ), __( 'Salary', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_salary' );

        add_submenu_page( 'sg365-cp-dashboard', __( 'Email Center', 'sg365-client-portal' ), __( 'Email Center', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-email-center', array( $this, 'render_email_center' ) );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Settings', 'sg365-client-portal' ), __( 'Settings', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-settings', array( $this, 'render_settings' ) );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Service Types', 'sg365-client-portal' ), __( 'Service Types', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-service-types', array( $this, 'render_service_types' ) );
    }

    public function register_settings(): void {
        register_setting( 'sg365_cp_settings', 'sg365_cp_enable_myaccount', array(
            'type'              => 'boolean',
            'sanitize_callback' => function( $v ){ return (bool) $v; },
            'default'           => true,
        ));
        register_setting( 'sg365_cp_settings', 'sg365_cp_portal_label', array(
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'SG365 Portal',
        ));
        register_setting( 'sg365_cp_email_settings', 'sg365_cp_email_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_email_settings' ),
            'default'           => SG365_CP_Email_Center::get_settings(),
        ));
    }

    public function assets( $hook ): void {
        if ( strpos( (string) $hook, 'sg365-cp' ) !== false ) {
            wp_enqueue_style( 'sg365-cp-admin', SG365_CP_PLUGIN_URL . 'assets/css/admin.css', array(), SG365_CP_VERSION );
            if ( strpos( (string) $hook, 'sg365-cp-dashboard' ) !== false ) {
                wp_enqueue_style( 'sg365-cp-dashboard', SG365_CP_PLUGIN_URL . 'assets/css/admin-dashboard.css', array(), SG365_CP_VERSION );
                wp_enqueue_script( 'sg365-cp-dashboard', SG365_CP_PLUGIN_URL . 'assets/js/admin-dashboard.js', array( 'jquery' ), SG365_CP_VERSION, true );
            }
        }
    }

    public function render_dashboard(): void {
        SG365_CP_Dashboard::instance()->render_dashboard();
    }

    public function render_settings(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'SG365 Portal Settings', 'sg365-client-portal' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'sg365_cp_settings' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Enable WooCommerce My Account Portal', 'sg365-client-portal' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sg365_cp_enable_myaccount" value="1" <?php checked( (bool) get_option('sg365_cp_enable_myaccount', true) ); ?> />
                                <?php echo esc_html__( 'Show SG365 Portal inside WooCommerce My Account.', 'sg365-client-portal' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'My Account Menu Label', 'sg365-client-portal' ); ?></th>
                        <td>
                            <input type="text" class="regular-text" name="sg365_cp_portal_label" value="<?php echo esc_attr( get_option('sg365_cp_portal_label', 'SG365 Portal') ); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_email_center(): void {
        SG365_CP_Email_Center::instance()->render_page();
    }

    public function render_service_types(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $types = sg365_cp_get_service_types();
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'Service Types', 'sg365-client-portal' ); ?></h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'sg365_cp_save_service_types', 'sg365_cp_service_nonce' ); ?>
                <input type="hidden" name="action" value="sg365_cp_save_service_types" />
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Slug', 'sg365-client-portal' ); ?></th>
                            <th><?php echo esc_html__( 'Label', 'sg365-client-portal' ); ?></th>
                            <th><?php echo esc_html__( 'Assigned Staff', 'sg365-client-portal' ); ?></th>
                            <th><?php echo esc_html__( 'Delete', 'sg365-client-portal' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $types as $slug => $data ) : ?>
                            <?php $staff_ids = isset( $data['staff'] ) ? (array) $data['staff'] : array(); ?>
                            <tr>
                                <td><input type="text" name="service_types[<?php echo esc_attr( $slug ); ?>][slug]" value="<?php echo esc_attr( $slug ); ?>" /></td>
                                <td><input type="text" name="service_types[<?php echo esc_attr( $slug ); ?>][label]" value="<?php echo esc_attr( $data['label'] ?? $slug ); ?>" /></td>
                                <td>
                                    <select name="service_types[<?php echo esc_attr( $slug ); ?>][staff][]" multiple>
                                        <?php foreach ( $staff as $member ) : ?>
                                            <option value="<?php echo esc_attr( $member->ID ); ?>" <?php selected( in_array( (int) $member->ID, $staff_ids, true ) ); ?>>
                                                <?php echo esc_html( $member->post_title ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><input type="checkbox" name="service_types[<?php echo esc_attr( $slug ); ?>][delete]" value="1" /></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="service_types[new][slug]" placeholder="support" /></td>
                            <td><input type="text" name="service_types[new][label]" placeholder="<?php echo esc_attr__( 'Support', 'sg365-client-portal' ); ?>" /></td>
                            <td>
                                <select name="service_types[new][staff][]" multiple>
                                    <?php foreach ( $staff as $member ) : ?>
                                        <option value="<?php echo esc_attr( $member->ID ); ?>"><?php echo esc_html( $member->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><?php echo esc_html__( 'New', 'sg365-client-portal' ); ?></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( __( 'Save Service Types', 'sg365-client-portal' ) ); ?>
            </form>
        </div>
        <?php
    }

    public function render_worklogs(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $paged = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
        $client_id = isset( $_GET['client_id'] ) ? (int) $_GET['client_id'] : 0;
        $site_id = isset( $_GET['site_id'] ) ? (int) $_GET['site_id'] : 0;
        $project_id = isset( $_GET['project_id'] ) ? (int) $_GET['project_id'] : 0;
        $service = isset( $_GET['service_type'] ) ? sanitize_key( wp_unslash( $_GET['service_type'] ) ) : '';
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';

        $meta_query = array();
        if ( $client_id ) { $meta_query[] = array( 'key' => '_sg365_client_id', 'value' => $client_id ); }
        if ( $site_id ) { $meta_query[] = array( 'key' => '_sg365_site_id', 'value' => $site_id ); }
        if ( $project_id ) { $meta_query[] = array( 'key' => '_sg365_project_id', 'value' => $project_id ); }
        if ( $service ) { $meta_query[] = array( 'key' => '_sg365_category', 'value' => $service ); }
        if ( $date_from ) { $meta_query[] = array( 'key' => '_sg365_log_date', 'value' => $date_from, 'compare' => '>=', 'type' => 'DATE' ); }
        if ( $date_to ) { $meta_query[] = array( 'key' => '_sg365_log_date', 'value' => $date_to, 'compare' => '<=', 'type' => 'DATE' ); }

        $query = new WP_Query( array(
            'post_type'      => 'sg365_worklog',
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'paged'          => $paged,
            'meta_query'     => $meta_query,
        ) );
        $clients = get_posts( array( 'post_type' => 'sg365_client', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $sites = get_posts( array( 'post_type' => 'sg365_site', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $types = sg365_cp_get_service_types();
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'All Work Logs', 'sg365-client-portal' ); ?></h1>
            <form method="get">
                <input type="hidden" name="page" value="sg365-cp-worklogs" />
                <div class="sg365-cp-filters">
                    <select name="client_id">
                        <option value="0"><?php echo esc_html__( 'All Clients', 'sg365-client-portal' ); ?></option>
                        <?php foreach ( $clients as $client ) : ?>
                            <option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $client_id, (int) $client->ID ); ?>><?php echo esc_html( $client->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="site_id">
                        <option value="0"><?php echo esc_html__( 'All Domains', 'sg365-client-portal' ); ?></option>
                        <?php foreach ( $sites as $site ) : ?>
                            <option value="<?php echo esc_attr( $site->ID ); ?>" <?php selected( $site_id, (int) $site->ID ); ?>><?php echo esc_html( $site->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="project_id">
                        <option value="0"><?php echo esc_html__( 'All Projects', 'sg365-client-portal' ); ?></option>
                        <?php foreach ( $projects as $project ) : ?>
                            <option value="<?php echo esc_attr( $project->ID ); ?>" <?php selected( $project_id, (int) $project->ID ); ?>><?php echo esc_html( $project->post_title ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="service_type">
                        <option value=""><?php echo esc_html__( 'All Service Types', 'sg365-client-portal' ); ?></option>
                        <?php foreach ( $types as $key => $data ) : ?>
                            <?php $label = is_array( $data ) ? ( $data['label'] ?? $key ) : $data; ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $service, $key ); ?>><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>
                        <?php echo esc_html__( 'From', 'sg365-client-portal' ); ?>
                        <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
                    </label>
                    <label>
                        <?php echo esc_html__( 'To', 'sg365-client-portal' ); ?>
                        <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
                    </label>
                    <button class="button" type="submit"><?php echo esc_html__( 'Filter', 'sg365-client-portal' ); ?></button>
                </div>
            </form>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Date', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Domain', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Project', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Service Type', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Visibility', 'sg365-client-portal' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $query->posts as $log ) : ?>
                        <?php
                        $date = (string) get_post_meta( $log->ID, '_sg365_log_date', true );
                        $client = (int) get_post_meta( $log->ID, '_sg365_client_id', true );
                        $site = (int) get_post_meta( $log->ID, '_sg365_site_id', true );
                        $project = (int) get_post_meta( $log->ID, '_sg365_project_id', true );
                        $service_type = (string) get_post_meta( $log->ID, '_sg365_category', true );
                        $visible = (int) get_post_meta( $log->ID, '_sg365_visible_client', true );
                        ?>
                        <tr>
                            <td><?php echo esc_html( $date ); ?></td>
                            <td><?php echo esc_html( $client ? get_the_title( $client ) : '-' ); ?></td>
                            <td><?php echo esc_html( $site ? get_the_title( $site ) : '-' ); ?></td>
                            <td><?php echo esc_html( $project ? get_the_title( $project ) : '-' ); ?></td>
                            <td><?php echo esc_html( ucfirst( $service_type ) ); ?></td>
                            <td><?php echo esc_html( $visible ? __( 'Client', 'sg365-client-portal' ) : __( 'Internal', 'sg365-client-portal' ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ( empty( $query->posts ) ) : ?>
                        <tr><td colspan="6"><?php echo esc_html__( 'No work logs found.', 'sg365-client-portal' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php
            $total_pages = (int) $query->max_num_pages;
            if ( $total_pages > 1 ) :
                $base = add_query_arg( $_GET, admin_url( 'admin.php?page=sg365-cp-worklogs' ) );
                ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links( array(
                            'base'      => add_query_arg( 'paged', '%#%', $base ),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $total_pages,
                            'prev_text' => '«',
                            'next_text' => '»',
                        ) );
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function project_columns( array $columns ): array {
        $columns['sg365_progress'] = __( 'Progress', 'sg365-client-portal' );
        return $columns;
    }

    public function project_column_content( string $column, int $post_id ): void {
        if ( 'sg365_progress' === $column ) {
            $progress = (int) get_post_meta( $post_id, '_sg365_project_progress', true );
            echo esc_html( $progress . '%' );
        }
    }

    public function portal_notice(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { return; }
        if ( ! sg365_cp_is_woocommerce_active() ) { return; }
        $rules = get_option( 'rewrite_rules', array() );
        $has_endpoint = false;
        foreach ( $rules as $rule => $rewrite ) {
            if ( strpos( $rule, 'sg365-portal' ) !== false ) {
                $has_endpoint = true;
                break;
            }
        }
        if ( $has_endpoint ) { return; }
        $url = wp_nonce_url( admin_url( 'admin-post.php?action=sg365_cp_fix_portal' ), 'sg365_cp_fix_portal' );
        ?>
        <div class="notice notice-warning">
            <p><?php echo esc_html__( 'The SG365 Portal endpoint may still be returning 404. Click below to flush rewrite rules.', 'sg365-client-portal' ); ?></p>
            <p><a class="button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html__( 'Fix Portal Now', 'sg365-client-portal' ); ?></a></p>
        </div>
        <?php
    }

    public function fix_portal(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        check_admin_referer( 'sg365_cp_fix_portal' );
        flush_rewrite_rules();
        wp_safe_redirect( admin_url( 'admin.php?page=sg365-cp-dashboard' ) );
        exit;
    }

    public function save_service_types(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        check_admin_referer( 'sg365_cp_save_service_types', 'sg365_cp_service_nonce' );
        $incoming = isset( $_POST['service_types'] ) ? (array) $_POST['service_types'] : array();
        $types = array();
        foreach ( $incoming as $key => $data ) {
            $slug = sanitize_key( $data['slug'] ?? $key );
            if ( ! $slug ) { continue; }
            if ( ! empty( $data['delete'] ) ) { continue; }
            $label = sanitize_text_field( $data['label'] ?? $slug );
            $staff_ids = isset( $data['staff'] ) ? array_map( 'intval', (array) $data['staff'] ) : array();
            $types[ $slug ] = array(
                'label' => $label,
                'staff' => $staff_ids,
            );
        }
        update_option( 'sg365_service_types', $types );
        wp_safe_redirect( admin_url( 'admin.php?page=sg365-cp-service-types&updated=1' ) );
        exit;
    }

    public function sanitize_email_settings( $settings ): array {
        $defaults = SG365_CP_Email_Center::get_settings();
        $settings = is_array( $settings ) ? $settings : array();
        $clean = $defaults;
        foreach ( $defaults as $key => $data ) {
            if ( $key === 'salary_due_days' ) {
                $clean['salary_due_days'] = isset( $settings['salary_due_days'] ) ? (int) $settings['salary_due_days'] : $defaults['salary_due_days'];
                continue;
            }
            $incoming = $settings[ $key ] ?? array();
            $clean[ $key ] = array(
                'enabled'   => ! empty( $incoming['enabled'] ) ? 1 : 0,
                'subject'   => sanitize_text_field( $incoming['subject'] ?? $data['subject'] ),
                'body'      => wp_kses_post( $incoming['body'] ?? $data['body'] ),
                'body_text' => sanitize_text_field( $incoming['body_text'] ?? $data['body_text'] ),
            );
        }
        return $clean;
    }
}
