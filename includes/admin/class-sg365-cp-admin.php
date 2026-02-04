<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once SG365_CP_PLUGIN_DIR . 'includes/admin/metaboxes.php';

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
        add_filter( 'manage_sg365_project_posts_columns', array( $this, 'project_columns' ) );
        add_action( 'manage_sg365_project_posts_custom_column', array( $this, 'project_column_content' ), 10, 2 );

        add_action( 'wp_ajax_sg365_cp_dashboard_tab', array( $this, 'ajax_dashboard_tab' ) );
        add_action( 'wp_ajax_sg365_cp_add_worklog', array( $this, 'ajax_add_worklog' ) );
        add_action( 'wp_ajax_sg365_cp_sites_for_client', array( $this, 'ajax_sites_for_client' ) );
        add_action( 'wp_ajax_sg365_cp_send_test_email', array( $this, 'ajax_send_test_email' ) );
        add_action( 'wp_ajax_sg365_cp_fix_portal_rewrites', array( $this, 'ajax_fix_portal_rewrites' ) );

        SG365_CP_Metaboxes::instance();
    }

    public function register_menu(): void {
        add_menu_page(
            __( 'SG365 Portal', 'sg365-client-portal' ),
            __( 'SG365 Portal', 'sg365-client-portal' ),
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
        add_submenu_page( 'sg365-cp-dashboard', __( 'All Work Logs', 'sg365-client-portal' ), __( 'All Work Logs', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-logs', array( $this, 'render_logs' ) );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Staff', 'sg365-client-portal' ), __( 'Staff', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_staff' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Salary', 'sg365-client-portal' ), __( 'Salary', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_salary' );

        add_submenu_page( 'sg365-cp-dashboard', __( 'Email Center', 'sg365-client-portal' ), __( 'Email Center', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-email-center', array( $this, 'render_email_center' ) );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Settings', 'sg365-client-portal' ), __( 'Settings', 'sg365-client-portal' ), 'sg365_cp_manage', 'sg365-cp-settings', array( $this, 'render_settings' ) );
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
        register_setting( 'sg365_cp_settings', 'sg365_service_types', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_service_types' ),
            'default'           => sg365_cp_get_service_types(),
        ));
        register_setting( 'sg365_cp_email_center', 'sg365_cp_email_settings', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_email_settings' ),
            'default'           => sg365_cp_get_email_settings(),
        ));
    }

    public function assets( $hook ): void {
        if ( strpos( (string) $hook, 'sg365-cp' ) !== false ) {
            wp_enqueue_style( 'sg365-cp-admin', SG365_CP_PLUGIN_URL . 'assets/css/admin.css', array(), SG365_CP_VERSION );
            wp_enqueue_script( 'sg365-cp-admin', SG365_CP_PLUGIN_URL . 'assets/js/admin-dashboard.js', array( 'jquery' ), SG365_CP_VERSION, true );
            wp_localize_script( 'sg365-cp-admin', 'sg365CpAdmin', array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'sg365_cp_admin' ),
            ) );
        }
    }

    public function render_dashboard(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        ?>
        <div class="wrap sg365-cp-wrap">
            <div class="sg365-dashboard-header">
                <div>
                    <h1><?php echo esc_html__( 'SiteGuard365 Portal', 'sg365-client-portal' ); ?></h1>
                    <p class="description"><?php echo esc_html__( 'Analytics-first dashboard with live KPIs, work logs, and email alerts.', 'sg365-client-portal' ); ?></p>
                </div>
                <button class="button button-primary" id="sg365-add-worklog"><?php echo esc_html__( 'Add Work Log', 'sg365-client-portal' ); ?></button>
            </div>
            <div class="sg365-tabs-nav" data-default="overview">
                <button class="sg365-tab-btn is-active" data-tab="overview"><?php echo esc_html__( 'Overview', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="analytics"><?php echo esc_html__( 'Analytics', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="pending"><?php echo esc_html__( 'Pending & Due', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="month"><?php echo esc_html__( 'This Month', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="recent"><?php echo esc_html__( 'Recent Work Logs', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="email"><?php echo esc_html__( 'Email Center', 'sg365-client-portal' ); ?></button>
                <button class="sg365-tab-btn" data-tab="settings"><?php echo esc_html__( 'Settings', 'sg365-client-portal' ); ?></button>
            </div>
            <div id="sg365-tab-content" class="sg365-tab-content">
                <div class="sg365-loading"><?php echo esc_html__( 'Loading dashboard…', 'sg365-client-portal' ); ?></div>
            </div>
        </div>
        <?php $this->render_worklog_modal(); ?>
        <?php
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
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Service Types', 'sg365-client-portal' ); ?></th>
                        <td>
                            <div id="sg365-service-types">
                                <?php $this->render_service_types_table(); ?>
                            </div>
                            <p class="description"><?php echo esc_html__( 'Add, edit, or remove service types used across projects, domains, and work logs.', 'sg365-client-portal' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_email_center(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $settings = sg365_cp_get_email_settings();
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'SG365 Email Center', 'sg365-client-portal' ); ?></h1>
            <form method="post" action="options.php" class="sg365-email-center">
                <?php settings_fields( 'sg365_cp_email_center' ); ?>
                <div class="sg365-email-grid">
                    <?php $this->render_email_card( 'worklog_client', __( 'New work log added → client email', 'sg365-client-portal' ), $settings['worklog_client'] ); ?>
                    <?php $this->render_email_card( 'site_client', __( 'New domain/site added → client email', 'sg365-client-portal' ), $settings['site_client'] ); ?>
                    <?php $this->render_email_card( 'salary_due_soon', __( 'Salary due soon → admin email', 'sg365-client-portal' ), $settings['salary_due_soon'], true ); ?>
                    <?php $this->render_email_card( 'salary_overdue', __( 'Salary overdue → admin email', 'sg365-client-portal' ), $settings['salary_overdue'] ); ?>
                    <?php $this->render_email_card( 'salary_status', __( 'Client salary status updated → client + admin', 'sg365-client-portal' ), $settings['salary_status'] ); ?>
                </div>
                <?php submit_button( __( 'Save Email Settings', 'sg365-client-portal' ) ); ?>
            </form>
            <div class="sg365-email-test">
                <h2><?php echo esc_html__( 'Send Test Email', 'sg365-client-portal' ); ?></h2>
                <p><?php echo esc_html__( 'Send a test email using the current template settings.', 'sg365-client-portal' ); ?></p>
                <button class="button" id="sg365-send-test-email"><?php echo esc_html__( 'Send Test Email', 'sg365-client-portal' ); ?></button>
                <span class="sg365-email-test-status" id="sg365-email-test-status"></span>
            </div>
        </div>
        <?php
    }

    public function render_logs(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
        $per_page = 50;
        $client_filter = (int) ( $_GET['client_id'] ?? 0 );
        $site_filter = (int) ( $_GET['site_id'] ?? 0 );
        $project_filter = (int) ( $_GET['project_id'] ?? 0 );
        $service_filter = sanitize_key( $_GET['service_type'] ?? '' );
        $date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
        $date_to = sanitize_text_field( $_GET['date_to'] ?? '' );
        $meta_query = array();
        if ( $client_filter ) { $meta_query[] = array( 'key' => '_sg365_client_id', 'value' => $client_filter ); }
        if ( $site_filter ) { $meta_query[] = array( 'key' => '_sg365_site_id', 'value' => $site_filter ); }
        if ( $project_filter ) { $meta_query[] = array( 'key' => '_sg365_project_id', 'value' => $project_filter ); }
        if ( $service_filter ) { $meta_query[] = array( 'key' => '_sg365_service_type', 'value' => $service_filter ); }
        $args = array(
            'post_type'      => 'sg365_worklog',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        );
        $query = new WP_Query( $args );
        $clients = get_posts( array( 'post_type' => 'sg365_client', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $sites = get_posts( array( 'post_type' => 'sg365_site', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $service_types = sg365_cp_get_service_types();
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'All Work Logs', 'sg365-client-portal' ); ?></h1>
            <form method="get" class="sg365-log-filters">
                <input type="hidden" name="page" value="sg365-cp-logs" />
                <select name="client_id">
                    <option value="0"><?php echo esc_html__( 'All Clients', 'sg365-client-portal' ); ?></option>
                    <?php foreach ( $clients as $client ) : ?>
                        <option value="<?php echo esc_attr( $client->ID ); ?>" <?php selected( $client_filter, $client->ID ); ?>><?php echo esc_html( $client->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="site_id">
                    <option value="0"><?php echo esc_html__( 'All Domains', 'sg365-client-portal' ); ?></option>
                    <?php foreach ( $sites as $site ) : ?>
                        <option value="<?php echo esc_attr( $site->ID ); ?>" <?php selected( $site_filter, $site->ID ); ?>><?php echo esc_html( $site->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="project_id">
                    <option value="0"><?php echo esc_html__( 'All Projects', 'sg365-client-portal' ); ?></option>
                    <?php foreach ( $projects as $project ) : ?>
                        <option value="<?php echo esc_attr( $project->ID ); ?>" <?php selected( $project_filter, $project->ID ); ?>><?php echo esc_html( $project->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="service_type">
                    <option value=""><?php echo esc_html__( 'All Service Types', 'sg365-client-portal' ); ?></option>
                    <?php foreach ( $service_types as $type ) : ?>
                        <option value="<?php echo esc_attr( $type['key'] ); ?>" <?php selected( $service_filter, $type['key'] ); ?>><?php echo esc_html( $type['label'] ); ?></option>
                    <?php endforeach; ?>
                </select>
                <label><?php echo esc_html__( 'From', 'sg365-client-portal' ); ?> <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" /></label>
                <label><?php echo esc_html__( 'To', 'sg365-client-portal' ); ?> <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" /></label>
                <button class="button" type="submit"><?php echo esc_html__( 'Filter', 'sg365-client-portal' ); ?></button>
            </form>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Date', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Domain', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Service Type', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Title', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Visibility', 'sg365-client-portal' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ( $query->have_posts() ) :
                    while ( $query->have_posts() ) :
                        $query->the_post();
                        $log_id = get_the_ID();
                        $client_id = (int) get_post_meta( $log_id, '_sg365_client_id', true );
                        $site_id = (int) get_post_meta( $log_id, '_sg365_site_id', true );
                        $service_type = (string) get_post_meta( $log_id, '_sg365_service_type', true );
                        $visible = (int) get_post_meta( $log_id, '_sg365_visible_client', true );
                        $date = (string) get_post_meta( $log_id, '_sg365_log_date', true );
                        if ( $date_from && $date < $date_from ) {
                            continue;
                        }
                        if ( $date_to && $date > $date_to ) {
                            continue;
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $date ); ?></td>
                            <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '—' ); ?></td>
                            <td><?php echo esc_html( $site_id ? get_the_title( $site_id ) : '—' ); ?></td>
                            <td><?php echo esc_html( $service_type ? ucfirst( $service_type ) : '—' ); ?></td>
                            <td><?php echo esc_html( get_the_title( $log_id ) ); ?></td>
                            <td><?php echo esc_html( $visible ? __( 'Client', 'sg365-client-portal' ) : __( 'Internal', 'sg365-client-portal' ) ); ?></td>
                        </tr>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    ?>
                    <tr><td colspan="6"><?php echo esc_html__( 'No work logs found.', 'sg365-client-portal' ); ?></td></tr>
                    <?php
                endif;
                ?>
                </tbody>
            </table>
            <?php
            $total_pages = (int) $query->max_num_pages;
            if ( $total_pages > 1 ) {
                echo '<div class="tablenav"><div class="tablenav-pages">';
                echo paginate_links( array(
                    'base'    => add_query_arg( 'paged', '%#%' ),
                    'format'  => '',
                    'current' => $paged,
                    'total'   => $total_pages,
                ) );
                echo '</div></div>';
            }
            ?>
        </div>
        <?php
    }

    public function portal_notice(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { return; }
        $rules = get_option( 'rewrite_rules', array() );
        if ( is_array( $rules ) && preg_grep( '/sg365-portal/', array_keys( $rules ) ) ) {
            return;
        }
        $nonce = wp_create_nonce( 'sg365_cp_admin' );
        ?>
        <div class="notice notice-warning">
            <p><?php echo esc_html__( 'SG365 Portal endpoint is not registered. Clients might see a 404 in My Account.', 'sg365-client-portal' ); ?></p>
            <p><a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=sg365_cp_fix_portal_rewrites&_wpnonce=' . $nonce ) ); ?>" class="button button-primary"><?php echo esc_html__( 'Fix Portal Now', 'sg365-client-portal' ); ?></a></p>
        </div>
        <?php
    }

    public function project_columns( array $columns ): array {
        $columns['sg365_progress'] = __( 'Progress', 'sg365-client-portal' );
        return $columns;
    }

    public function project_column_content( string $column, int $post_id ): void {
        if ( 'sg365_progress' === $column ) {
            $progress = (int) get_post_meta( $post_id, '_sg365_progress', true );
            echo esc_html( $progress ) . '%';
        }
    }

    public function sanitize_service_types( $value ): array {
        $clean = array();
        if ( is_array( $value ) ) {
            foreach ( $value as $row ) {
                if ( empty( $row['key'] ) || empty( $row['label'] ) ) {
                    continue;
                }
                $staff = array();
                if ( ! empty( $row['staff'] ) && is_array( $row['staff'] ) ) {
                    foreach ( $row['staff'] as $staff_id ) {
                        $staff[] = (int) $staff_id;
                    }
                }
                $clean[] = array(
                    'key'   => sanitize_key( $row['key'] ),
                    'label' => sanitize_text_field( $row['label'] ),
                    'staff' => $staff,
                );
            }
        }
        return $clean;
    }

    public function sanitize_email_settings( $value ): array {
        $settings = sg365_cp_get_email_settings();
        if ( ! is_array( $value ) ) {
            return $settings;
        }
        foreach ( $settings as $key => $setting ) {
            if ( isset( $value[ $key ]['enabled'] ) ) {
                $settings[ $key ]['enabled'] = ! empty( $value[ $key ]['enabled'] ) ? 1 : 0;
            }
            if ( isset( $value[ $key ]['days'] ) ) {
                $settings[ $key ]['days'] = (int) $value[ $key ]['days'];
            }
            if ( isset( $value[ $key ]['subject'] ) ) {
                $settings[ $key ]['subject'] = sanitize_text_field( $value[ $key ]['subject'] );
            }
            if ( isset( $value[ $key ]['body'] ) ) {
                $settings[ $key ]['body'] = wp_kses_post( $value[ $key ]['body'] );
            }
        }
        return $settings;
    }

    private function render_service_types_table(): void {
        $types = sg365_cp_get_service_types();
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        ?>
        <table class="widefat sg365-service-types">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Key', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Label', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Assigned Staff', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Remove', 'sg365-client-portal' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $types as $index => $type ) : ?>
                    <tr>
                        <td><input type="text" name="sg365_service_types[<?php echo esc_attr( $index ); ?>][key]" value="<?php echo esc_attr( $type['key'] ); ?>" /></td>
                        <td><input type="text" name="sg365_service_types[<?php echo esc_attr( $index ); ?>][label]" value="<?php echo esc_attr( $type['label'] ); ?>" /></td>
                        <td>
                            <select multiple name="sg365_service_types[<?php echo esc_attr( $index ); ?>][staff][]" class="sg365-multiselect">
                                <?php foreach ( $staff as $member ) : ?>
                                    <option value="<?php echo esc_attr( $member->ID ); ?>" <?php echo in_array( $member->ID, $type['staff'], true ) ? 'selected' : ''; ?>>
                                        <?php echo esc_html( $member->post_title ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><button type="button" class="button-link sg365-remove-row">×</button></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <template id="sg365-staff-options">
            <?php foreach ( $staff as $member ) : ?>
                <option value="<?php echo esc_attr( $member->ID ); ?>"><?php echo esc_html( $member->post_title ); ?></option>
            <?php endforeach; ?>
        </template>
        <button type="button" class="button" id="sg365-add-service-type"><?php echo esc_html__( 'Add Service Type', 'sg365-client-portal' ); ?></button>
        <?php
    }

    private function render_email_card( string $key, string $title, array $settings, bool $show_days = false ): void {
        ?>
        <div class="sg365-email-card">
            <h3><?php echo esc_html( $title ); ?></h3>
            <p>
                <label>
                    <input type="checkbox" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
                    <?php echo esc_html__( 'Enable this email', 'sg365-client-portal' ); ?>
                </label>
            </p>
            <?php if ( $show_days ) : ?>
                <p>
                    <label><?php echo esc_html__( 'Days before salary due', 'sg365-client-portal' ); ?>
                        <input type="number" min="0" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][days]" value="<?php echo esc_attr( $settings['days'] ?? 0 ); ?>" />
                    </label>
                </p>
            <?php endif; ?>
            <p>
                <label><?php echo esc_html__( 'Subject template', 'sg365-client-portal' ); ?>
                    <input type="text" class="widefat" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][subject]" value="<?php echo esc_attr( $settings['subject'] ?? '' ); ?>" />
                </label>
            </p>
            <p>
                <label><?php echo esc_html__( 'Body template (HTML allowed)', 'sg365-client-portal' ); ?></label>
                <textarea class="widefat" rows="5" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][body]"><?php echo esc_textarea( $settings['body'] ?? '' ); ?></textarea>
            </p>
            <p class="description"><?php echo esc_html__( 'Allowed variables: {client_name}, {domain}, {project_name}, {log_title}, {log_date}, {month}, {amount}, {due_date}, {portal_link}', 'sg365-client-portal' ); ?></p>
        </div>
        <?php
    }

    private function render_worklog_modal(): void {
        $clients = get_posts( array( 'post_type' => 'sg365_client', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $service_types = sg365_cp_get_service_types();
        ?>
        <div class="sg365-modal" id="sg365-worklog-modal" style="display:none;">
            <div class="sg365-modal-content">
                <h2><?php echo esc_html__( 'Add Work Log', 'sg365-client-portal' ); ?></h2>
                <form id="sg365-worklog-form" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="sg365_cp_add_worklog" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'sg365_cp_admin' ) ); ?>" />
                    <p>
                        <label><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></label>
                        <select name="client_id" id="sg365-worklog-client" required>
                            <option value=""><?php echo esc_html__( 'Select client', 'sg365-client-portal' ); ?></option>
                            <?php foreach ( $clients as $client ) : ?>
                                <option value="<?php echo esc_attr( $client->ID ); ?>"><?php echo esc_html( $client->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Domain/Site', 'sg365-client-portal' ); ?></label>
                        <select name="site_id" id="sg365-worklog-site" required>
                            <option value=""><?php echo esc_html__( 'Select site', 'sg365-client-portal' ); ?></option>
                        </select>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Project (optional)', 'sg365-client-portal' ); ?></label>
                        <select name="project_id">
                            <option value="0"><?php echo esc_html__( 'None', 'sg365-client-portal' ); ?></option>
                            <?php foreach ( $projects as $project ) : ?>
                                <option value="<?php echo esc_attr( $project->ID ); ?>"><?php echo esc_html( $project->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Service Type', 'sg365-client-portal' ); ?></label>
                        <select name="service_type" required>
                            <option value=""><?php echo esc_html__( 'Select service type', 'sg365-client-portal' ); ?></option>
                            <?php foreach ( $service_types as $type ) : ?>
                                <option value="<?php echo esc_attr( $type['key'] ); ?>"><?php echo esc_html( $type['label'] ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Title', 'sg365-client-portal' ); ?></label>
                        <input type="text" name="title" required />
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Description', 'sg365-client-portal' ); ?></label>
                        <textarea name="description" rows="4" required></textarea>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Date', 'sg365-client-portal' ); ?></label>
                        <input type="date" name="log_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" />
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Staff Assigned', 'sg365-client-portal' ); ?></label>
                        <select name="staff_ids[]" multiple class="sg365-multiselect">
                            <?php foreach ( $staff as $member ) : ?>
                                <option value="<?php echo esc_attr( $member->ID ); ?>"><?php echo esc_html( $member->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label><input type="checkbox" name="visible_client" value="1" checked /> <?php echo esc_html__( 'Visible to client', 'sg365-client-portal' ); ?></label>
                    </p>
                    <p>
                        <label><?php echo esc_html__( 'Attachment (optional)', 'sg365-client-portal' ); ?></label>
                        <input type="file" name="attachment" />
                    </p>
                    <div class="sg365-modal-actions">
                        <button type="button" class="button" id="sg365-worklog-cancel"><?php echo esc_html__( 'Cancel', 'sg365-client-portal' ); ?></button>
                        <button type="submit" class="button button-primary"><?php echo esc_html__( 'Save Log', 'sg365-client-portal' ); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public function ajax_dashboard_tab(): void {
        check_ajax_referer( 'sg365_cp_admin' );
        if ( ! sg365_cp_current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-client-portal' ) ) );
        }
        $tab = sanitize_key( $_POST['tab'] ?? '' );
        switch ( $tab ) {
            case 'analytics':
                $html = $this->get_tab_analytics();
                break;
            case 'pending':
                $html = $this->get_tab_pending();
                break;
            case 'month':
                $html = $this->get_tab_month();
                break;
            case 'recent':
                $html = $this->get_tab_recent();
                break;
            case 'email':
                $html = $this->get_tab_email_summary();
                break;
            case 'settings':
                $html = $this->get_tab_settings_summary();
                break;
            case 'overview':
            default:
                $html = $this->get_tab_overview();
                break;
        }
        wp_send_json_success( array( 'html' => $html ) );
    }

    public function ajax_sites_for_client(): void {
        check_ajax_referer( 'sg365_cp_admin' );
        if ( ! sg365_cp_current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-client-portal' ) ) );
        }
        $client_id = (int) ( $_POST['client_id'] ?? 0 );
        $sites = get_posts( array(
            'post_type'   => 'sg365_site',
            'numberposts' => 200,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'meta_query'  => array(
                array(
                    'key'   => '_sg365_client_id',
                    'value' => $client_id,
                ),
            ),
        ) );
        $options = array();
        foreach ( $sites as $site ) {
            $options[] = array( 'id' => $site->ID, 'label' => $site->post_title );
        }
        wp_send_json_success( array( 'options' => $options ) );
    }

    public function ajax_add_worklog(): void {
        check_ajax_referer( 'sg365_cp_admin' );
        if ( ! sg365_cp_current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-client-portal' ) ) );
        }
        $client_id = (int) ( $_POST['client_id'] ?? 0 );
        $site_id = (int) ( $_POST['site_id'] ?? 0 );
        $project_id = (int) ( $_POST['project_id'] ?? 0 );
        $service_type = sanitize_key( $_POST['service_type'] ?? '' );
        $title = sanitize_text_field( $_POST['title'] ?? '' );
        $description = wp_kses_post( $_POST['description'] ?? '' );
        $log_date = sanitize_text_field( $_POST['log_date'] ?? current_time( 'Y-m-d' ) );
        $visible = ! empty( $_POST['visible_client'] ) ? 1 : 0;
        $staff_ids = array();
        if ( ! empty( $_POST['staff_ids'] ) && is_array( $_POST['staff_ids'] ) ) {
            foreach ( $_POST['staff_ids'] as $staff_id ) {
                $staff_ids[] = (int) $staff_id;
            }
        }
        if ( ! $client_id || ! $site_id || ! $title ) {
            wp_send_json_error( array( 'message' => __( 'Client, site, and title are required.', 'sg365-client-portal' ) ) );
        }
        $log_id = wp_insert_post( array(
            'post_type'   => 'sg365_worklog',
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_content'=> $description,
        ) );
        if ( is_wp_error( $log_id ) ) {
            wp_send_json_error( array( 'message' => $log_id->get_error_message() ) );
        }
        update_post_meta( $log_id, '_sg365_client_id', $client_id );
        update_post_meta( $log_id, '_sg365_site_id', $site_id );
        update_post_meta( $log_id, '_sg365_project_id', $project_id );
        update_post_meta( $log_id, '_sg365_service_type', $service_type );
        update_post_meta( $log_id, '_sg365_category', $service_type );
        update_post_meta( $log_id, '_sg365_visible_client', $visible );
        update_post_meta( $log_id, '_sg365_log_date', $log_date );
        update_post_meta( $log_id, '_sg365_staff_ids', $staff_ids );
        $client_user_id = sg365_cp_get_user_id_for_client( $client_id );
        update_post_meta( $log_id, '_sg365_client_user_id', (int) $client_user_id );

        if ( ! empty( $_FILES['attachment']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attachment_id = media_handle_upload( 'attachment', $log_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                update_post_meta( $log_id, '_sg365_attachment_id', (int) $attachment_id );
            }
        }

        do_action( 'sg365_cp_worklog_created', $log_id );

        wp_send_json_success( array( 'message' => __( 'Work log created.', 'sg365-client-portal' ) ) );
    }

    public function ajax_send_test_email(): void {
        check_ajax_referer( 'sg365_cp_admin' );
        if ( ! sg365_cp_current_user_can_manage() ) {
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'sg365-client-portal' ) ) );
        }
        $settings = sg365_cp_get_email_settings();
        $sample = array(
            'client_name'  => 'Sample Client',
            'domain'       => 'example.com',
            'project_name' => 'Sample Project',
            'log_title'    => 'Sample Work Log',
            'log_date'     => current_time( 'Y-m-d' ),
            'month'        => gmdate( 'F' ),
            'amount'       => '₹10,000',
            'due_date'     => gmdate( 'Y-m-d' ),
            'portal_link'  => admin_url( 'admin.php?page=sg365-cp-dashboard' ),
        );
        $subject = sg365_cp_email_template_replace( $settings['worklog_client']['subject'], $sample );
        $body = sg365_cp_email_template_replace( $settings['worklog_client']['body'], $sample );
        $sent = wp_mail( get_option( 'admin_email' ), $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
        if ( ! $sent ) {
            wp_send_json_error( array( 'message' => __( 'Test email failed to send.', 'sg365-client-portal' ) ) );
        }
        wp_send_json_success( array( 'message' => __( 'Test email sent.', 'sg365-client-portal' ) ) );
    }

    public function ajax_fix_portal_rewrites(): void {
        check_ajax_referer( 'sg365_cp_admin' );
        if ( ! sg365_cp_current_user_can_manage() ) {
            wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) );
        }
        flush_rewrite_rules();
        wp_safe_redirect( admin_url( 'admin.php?page=sg365-cp-dashboard&sg365_portal_fixed=1' ) );
        exit;
    }

    private function get_tab_overview(): string {
        $client_count = (int) wp_count_posts( 'sg365_client' )->publish;
        $site_count   = (int) wp_count_posts( 'sg365_site' )->publish;
        $project_count= (int) wp_count_posts( 'sg365_project' )->publish;
        $worklog_count= (int) wp_count_posts( 'sg365_worklog' )->publish;
        $pending_work = $this->count_pending_work();
        $on_time = max( 0, $site_count - $pending_work );
        $salary_due = $this->count_salary_due_soon();
        $salary_overdue = $this->count_salary_overdue();

        ob_start();
        ?>
        <div class="sg365-kpi-grid">
            <?php $this->render_kpi_card( 'clients', __( 'Total Clients', 'sg365-client-portal' ), $client_count ); ?>
            <?php $this->render_kpi_card( 'sites', __( 'Total Domains/Sites', 'sg365-client-portal' ), $site_count ); ?>
            <?php $this->render_kpi_card( 'projects', __( 'Total Active Projects', 'sg365-client-portal' ), $project_count ); ?>
            <?php $this->render_kpi_card( 'pending', __( 'Pending Work (overdue)', 'sg365-client-portal' ), $pending_work ); ?>
            <?php $this->render_kpi_card( 'ontime', __( 'On-Time Work', 'sg365-client-portal' ), $on_time ); ?>
            <?php $this->render_kpi_card( 'salary_due', __( 'Salaries Due Soon', 'sg365-client-portal' ), $salary_due ); ?>
            <?php $this->render_kpi_card( 'salary_overdue', __( 'Salaries Overdue', 'sg365-client-portal' ), $salary_overdue ); ?>
            <?php $this->render_kpi_card( 'worklogs', __( 'Work Logs Logged', 'sg365-client-portal' ), $worklog_count ); ?>
        </div>
        <div class="sg365-overview-note">
            <?php echo esc_html__( 'Click any KPI to filter analytics.', 'sg365-client-portal' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tab_analytics(): string {
        $filter = sanitize_key( $_POST['filter'] ?? '' );
        $client_count = (int) wp_count_posts( 'sg365_client' )->publish;
        $site_count   = (int) wp_count_posts( 'sg365_site' )->publish;
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
        $projects_total = count( $projects );
        $projects_completed = 0;
        $projects_remaining = 0;
        $project_progress = array();
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_progress', true );
            $status = (string) get_post_meta( $project->ID, '_sg365_project_status', true );
            if ( $status === 'completed' || $progress >= 100 ) {
                $projects_completed++;
            } else {
                $projects_remaining++;
            }
            $project_progress[] = array(
                'name' => $project->post_title,
                'progress' => min( 100, max( 0, $progress ) ),
            );
        }
        $pending_work = $this->count_pending_work();
        $on_time = max( 0, $site_count - $pending_work );
        $performance = $site_count ? round( ( $on_time / $site_count ) * 100, 1 ) : 0;

        ob_start();
        ?>
        <div class="sg365-analytics-grid">
            <div class="sg365-analytics-card">
                <h3><?php echo esc_html__( 'Client & Domain Metrics', 'sg365-client-portal' ); ?></h3>
                <?php if ( $filter ) : ?>
                    <p class="description"><?php echo esc_html( sprintf( __( 'Filtered by KPI: %s', 'sg365-client-portal' ), ucfirst( str_replace( '_', ' ', $filter ) ) ) ); ?></p>
                <?php endif; ?>
                <ul class="sg365-metric-list">
                    <li><span><?php echo esc_html__( 'Total clients', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $client_count ); ?></strong></li>
                    <li><span><?php echo esc_html__( 'Total domains', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $site_count ); ?></strong></li>
                    <li><span><?php echo esc_html__( 'Domains worked on time', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $on_time ); ?></strong></li>
                    <li><span><?php echo esc_html__( 'Domains not worked on time', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $pending_work ); ?></strong></li>
                </ul>
                <div class="sg365-bar-chart">
                    <?php $total = max( 1, $site_count ); ?>
                    <div class="sg365-bar">
                        <span><?php echo esc_html__( 'On time', 'sg365-client-portal' ); ?></span>
                        <div><i style="width: <?php echo esc_attr( ( $on_time / $total ) * 100 ); ?>%;"></i></div>
                    </div>
                    <div class="sg365-bar">
                        <span><?php echo esc_html__( 'Overdue', 'sg365-client-portal' ); ?></span>
                        <div><i style="width: <?php echo esc_attr( ( $pending_work / $total ) * 100 ); ?>%;"></i></div>
                    </div>
                </div>
                <div class="sg365-donut" style="--percent:<?php echo esc_attr( $performance ); ?>;">
                    <div class="sg365-donut-center">
                        <strong><?php echo esc_html( $performance ); ?>%</strong>
                        <span><?php echo esc_html__( 'Monthly performance', 'sg365-client-portal' ); ?></span>
                    </div>
                </div>
            </div>
            <div class="sg365-analytics-card">
                <h3><?php echo esc_html__( 'Project Analytics', 'sg365-client-portal' ); ?></h3>
                <ul class="sg365-metric-list">
                    <li><span><?php echo esc_html__( 'Active projects', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $projects_total ); ?></strong></li>
                    <li><span><?php echo esc_html__( 'Completed projects', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $projects_completed ); ?></strong></li>
                    <li><span><?php echo esc_html__( 'Projects with remaining work', 'sg365-client-portal' ); ?></span><strong><?php echo esc_html( $projects_remaining ); ?></strong></li>
                </ul>
                <div class="sg365-progress-list">
                    <?php foreach ( $project_progress as $project ) : ?>
                        <div class="sg365-progress-item">
                            <span><?php echo esc_html( $project['name'] ); ?></span>
                            <div class="sg365-progress-bar">
                                <div style="width: <?php echo esc_attr( $project['progress'] ); ?>%;"></div>
                            </div>
                            <em><?php echo esc_html( $project['progress'] ); ?>%</em>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tab_pending(): string {
        $overdue_sites = $this->get_overdue_sites();
        $projects = $this->get_projects_remaining();
        $salary_due = $this->get_salaries_due_soon();
        $salary_overdue = $this->get_salaries_overdue();

        ob_start();
        ?>
        <div class="sg365-pending-grid">
            <div class="sg365-list-card">
                <h3><?php echo esc_html__( 'Domains with overdue work', 'sg365-client-portal' ); ?></h3>
                <?php echo $this->render_pending_table( $overdue_sites, array( 'client', 'item', 'last', 'next', 'status' ) ); ?>
            </div>
            <div class="sg365-list-card">
                <h3><?php echo esc_html__( 'Projects with remaining progress', 'sg365-client-portal' ); ?></h3>
                <?php echo $this->render_pending_table( $projects, array( 'client', 'item', 'last', 'next', 'status' ) ); ?>
            </div>
            <div class="sg365-list-card">
                <h3><?php echo esc_html__( 'Salaries due soon', 'sg365-client-portal' ); ?></h3>
                <?php echo $this->render_pending_table( $salary_due, array( 'client', 'item', 'last', 'next', 'status' ) ); ?>
            </div>
            <div class="sg365-list-card">
                <h3><?php echo esc_html__( 'Salaries overdue', 'sg365-client-portal' ); ?></h3>
                <?php echo $this->render_pending_table( $salary_overdue, array( 'client', 'item', 'last', 'next', 'status' ) ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tab_month(): string {
        $month_key = gmdate( 'Y-m' );
        $work_logs = $this->count_posts_in_month( 'sg365_worklog', $month_key, '_sg365_log_date' );
        $new_domains = $this->count_posts_in_month( 'sg365_site', $month_key, 'post_date' );
        $projects_completed = $this->count_projects_completed_in_month( $month_key );
        $incoming = $this->sum_salary_by_direction( 'incoming', $month_key );
        $outgoing = $this->sum_salary_by_direction( 'outgoing', $month_key );

        ob_start();
        ?>
        <div class="sg365-month-grid">
            <?php $this->render_kpi_card( 'month_logs', __( 'Work logs added this month', 'sg365-client-portal' ), $work_logs ); ?>
            <?php $this->render_kpi_card( 'month_domains', __( 'New domains added', 'sg365-client-portal' ), $new_domains ); ?>
            <?php $this->render_kpi_card( 'month_projects', __( 'Projects completed', 'sg365-client-portal' ), $projects_completed ); ?>
            <?php $this->render_kpi_card( 'month_incoming', __( 'Incoming payments', 'sg365-client-portal' ), $incoming ); ?>
            <?php $this->render_kpi_card( 'month_outgoing', __( 'Outgoing salaries', 'sg365-client-portal' ), $outgoing ); ?>
        </div>
        <p class="description"><?php echo esc_html__( 'Totals only. Hours are not tracked.', 'sg365-client-portal' ); ?></p>
        <?php
        return ob_get_clean();
    }

    private function get_tab_recent(): string {
        $logs = get_posts( array(
            'post_type'   => 'sg365_worklog',
            'numberposts' => 20,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        ob_start();
        ?>
        <div class="sg365-list-card">
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Date', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Domain', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Service Type', 'sg365-client-portal' ); ?></th>
                        <th><?php echo esc_html__( 'Visibility', 'sg365-client-portal' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $logs ) : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <?php
                            $client_id = (int) get_post_meta( $log->ID, '_sg365_client_id', true );
                            $site_id = (int) get_post_meta( $log->ID, '_sg365_site_id', true );
                            $service_type = (string) get_post_meta( $log->ID, '_sg365_service_type', true );
                            $visible = (int) get_post_meta( $log->ID, '_sg365_visible_client', true );
                            $date = (string) get_post_meta( $log->ID, '_sg365_log_date', true );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $date ); ?></td>
                                <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '—' ); ?></td>
                                <td><?php echo esc_html( $site_id ? get_the_title( $site_id ) : '—' ); ?></td>
                                <td><?php echo esc_html( $service_type ? ucfirst( $service_type ) : '—' ); ?></td>
                                <td><?php echo esc_html( $visible ? __( 'Client', 'sg365-client-portal' ) : __( 'Internal', 'sg365-client-portal' ) ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr><td colspan="5"><?php echo esc_html__( 'No recent work logs found.', 'sg365-client-portal' ); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-logs' ) ); ?>"><?php echo esc_html__( 'View All Logs', 'sg365-client-portal' ); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tab_email_summary(): string {
        $settings = sg365_cp_get_email_settings();
        ob_start();
        ?>
        <div class="sg365-list-card">
            <h3><?php echo esc_html__( 'Email Center Summary', 'sg365-client-portal' ); ?></h3>
            <ul class="sg365-metric-list">
                <?php foreach ( $settings as $key => $setting ) : ?>
                    <li><span><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span><strong><?php echo esc_html( ! empty( $setting['enabled'] ) ? __( 'Enabled', 'sg365-client-portal' ) : __( 'Disabled', 'sg365-client-portal' ) ); ?></strong></li>
                <?php endforeach; ?>
            </ul>
            <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-email-center' ) ); ?>"><?php echo esc_html__( 'Manage Email Center', 'sg365-client-portal' ); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_tab_settings_summary(): string {
        $types = sg365_cp_get_service_types();
        ob_start();
        ?>
        <div class="sg365-list-card">
            <h3><?php echo esc_html__( 'Service Types', 'sg365-client-portal' ); ?></h3>
            <ul class="sg365-chip-list">
                <?php foreach ( $types as $type ) : ?>
                    <li><?php echo esc_html( $type['label'] ); ?></li>
                <?php endforeach; ?>
            </ul>
            <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-settings' ) ); ?>"><?php echo esc_html__( 'Edit Settings', 'sg365-client-portal' ); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_kpi_card( string $key, string $label, $value ): void {
        ?>
        <button class="sg365-kpi-card" data-filter="<?php echo esc_attr( $key ); ?>">
            <span><?php echo esc_html( $label ); ?></span>
            <strong><?php echo esc_html( $value ); ?></strong>
        </button>
        <?php
    }

    private function render_pending_table( array $rows, array $columns ): string {
        ob_start();
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Domain / Project', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Last activity', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Expected next update', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'sg365-client-portal' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( $rows ) : ?>
                    <?php foreach ( $rows as $row ) : ?>
                        <tr>
                            <td><?php echo esc_html( $row['client'] ); ?></td>
                            <td><?php echo esc_html( $row['item'] ); ?></td>
                            <td><?php echo esc_html( $row['last'] ); ?></td>
                            <td><?php echo esc_html( $row['next'] ); ?></td>
                            <?php $status_slug = strtolower( str_replace( ' ', '-', $row['status'] ) ); ?>
                            <td><span class="sg365-status sg365-status-<?php echo esc_attr( $status_slug ); ?>"><?php echo esc_html( $row['status'] ); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php echo esc_html__( 'No records found.', 'sg365-client-portal' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
        return ob_get_clean();
    }

    private function count_pending_work(): int {
        return count( $this->get_overdue_sites() );
    }

    private function count_salary_due_soon(): int {
        return count( $this->get_salaries_due_soon() );
    }

    private function count_salary_overdue(): int {
        return count( $this->get_salaries_overdue() );
    }

    private function get_overdue_sites(): array {
        $sites = get_posts( array( 'post_type' => 'sg365_site', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $rows = array();
        foreach ( $sites as $site ) {
            $client_id = (int) get_post_meta( $site->ID, '_sg365_client_id', true );
            $next_update = (string) get_post_meta( $site->ID, '_sg365_next_update', true );
            if ( ! $next_update ) {
                continue;
            }
            $is_overdue = strtotime( $next_update ) < strtotime( current_time( 'Y-m-d' ) );
            if ( ! $is_overdue ) {
                continue;
            }
            $last_activity = $this->get_last_activity_date_for_site( $site->ID );
            $rows[] = array(
                'client' => $client_id ? get_the_title( $client_id ) : '—',
                'item'   => $site->post_title,
                'last'   => $last_activity ?: '—',
                'next'   => $next_update,
                'status' => 'Overdue',
            );
        }
        return $rows;
    }

    private function get_projects_remaining(): array {
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
        $rows = array();
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_progress', true );
            if ( $progress >= 100 ) {
                continue;
            }
            $client_id = (int) get_post_meta( $project->ID, '_sg365_client_id', true );
            $next_update = (string) get_post_meta( $project->ID, '_sg365_next_update', true );
            $rows[] = array(
                'client' => $client_id ? get_the_title( $client_id ) : '—',
                'item'   => $project->post_title,
                'last'   => get_the_modified_date( 'Y-m-d', $project ),
                'next'   => $next_update ?: '—',
                'status' => $progress ? 'In progress' : 'Pending',
            );
        }
        return $rows;
    }

    private function get_salaries_due_soon(): array {
        $settings = sg365_cp_get_email_settings();
        $days = (int) ( $settings['salary_due_soon']['days'] ?? 5 );
        $rows = array();
        $salaries = get_posts( array( 'post_type' => 'sg365_salary', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
        foreach ( $salaries as $salary ) {
            $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
            $paid = (int) get_post_meta( $salary->ID, '_sg365_paid', true );
            if ( ! $due || $paid ) {
                continue;
            }
            $diff = ( strtotime( $due ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS;
            if ( $diff < 0 || $diff > $days ) {
                continue;
            }
            $client_id = (int) get_post_meta( $salary->ID, '_sg365_client_id', true );
            $staff_id = (int) get_post_meta( $salary->ID, '_sg365_staff_id', true );
            $rows[] = array(
                'client' => $client_id ? get_the_title( $client_id ) : ( $staff_id ? get_the_title( $staff_id ) : '—' ),
                'item'   => $salary->post_title,
                'last'   => get_the_modified_date( 'Y-m-d', $salary ),
                'next'   => $due,
                'status' => 'On time',
            );
        }
        return $rows;
    }

    private function get_salaries_overdue(): array {
        $rows = array();
        $salaries = get_posts( array( 'post_type' => 'sg365_salary', 'numberposts' => 200, 'orderby' => 'date', 'order' => 'DESC' ) );
        foreach ( $salaries as $salary ) {
            $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
            $paid = (int) get_post_meta( $salary->ID, '_sg365_paid', true );
            if ( ! $due || $paid ) {
                continue;
            }
            if ( strtotime( $due ) >= strtotime( current_time( 'Y-m-d' ) ) ) {
                continue;
            }
            $client_id = (int) get_post_meta( $salary->ID, '_sg365_client_id', true );
            $staff_id = (int) get_post_meta( $salary->ID, '_sg365_staff_id', true );
            $rows[] = array(
                'client' => $client_id ? get_the_title( $client_id ) : ( $staff_id ? get_the_title( $staff_id ) : '—' ),
                'item'   => $salary->post_title,
                'last'   => get_the_modified_date( 'Y-m-d', $salary ),
                'next'   => $due,
                'status' => 'Overdue',
            );
        }
        return $rows;
    }

    private function count_posts_in_month( string $post_type, string $month_key, string $meta_key ): int {
        $posts = get_posts( array( 'post_type' => $post_type, 'numberposts' => 500 ) );
        $count = 0;
        foreach ( $posts as $post ) {
            $date = 'post_date' === $meta_key ? $post->post_date : (string) get_post_meta( $post->ID, $meta_key, true );
            if ( strpos( $date, $month_key . '-' ) === 0 ) {
                $count++;
            }
        }
        return $count;
    }

    private function count_projects_completed_in_month( string $month_key ): int {
        $projects = get_posts( array( 'post_type' => 'sg365_project', 'numberposts' => 500 ) );
        $count = 0;
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_progress', true );
            $completed_date = (string) get_post_meta( $project->ID, '_sg365_completed_date', true );
            if ( $progress >= 100 && $completed_date && strpos( $completed_date, $month_key . '-' ) === 0 ) {
                $count++;
            }
        }
        return $count;
    }

    private function sum_salary_by_direction( string $direction, string $month_key ): string {
        $salaries = get_posts( array( 'post_type' => 'sg365_salary', 'numberposts' => 500 ) );
        $sum = 0;
        foreach ( $salaries as $salary ) {
            $type = (string) get_post_meta( $salary->ID, '_sg365_salary_direction', true );
            if ( $type !== $direction ) {
                continue;
            }
            $month = (string) get_post_meta( $salary->ID, '_sg365_month', true );
            if ( $month !== $month_key ) {
                continue;
            }
            $base = (float) get_post_meta( $salary->ID, '_sg365_base', true );
            $bonus = (float) get_post_meta( $salary->ID, '_sg365_bonus', true );
            $deduction = (float) get_post_meta( $salary->ID, '_sg365_deduction', true );
            $sum += ( $base + $bonus - $deduction );
        }
        return number_format_i18n( $sum, 2 );
    }

    private function get_last_activity_date_for_site( int $site_id ): string {
        $logs = get_posts( array(
            'post_type'   => 'sg365_worklog',
            'numberposts' => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'meta_query'  => array(
                array( 'key' => '_sg365_site_id', 'value' => $site_id ),
            ),
        ) );
        if ( $logs ) {
            return (string) get_post_meta( $logs[0]->ID, '_sg365_log_date', true );
        }
        return '';
    }
}
