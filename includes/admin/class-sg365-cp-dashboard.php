<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SG365_CP_Dashboard {

    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'wp_ajax_sg365_cp_dashboard_tab', array( $this, 'ajax_tab' ) );
        add_action( 'wp_ajax_sg365_cp_add_worklog', array( $this, 'ajax_add_worklog' ) );
        add_action( 'wp_ajax_sg365_cp_client_sites', array( $this, 'ajax_client_sites' ) );
    }

    public function render_dashboard(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        ?>
        <div class="wrap sg365-cp-wrap sg365-cp-dashboard">
            <h1><?php echo esc_html__( 'SiteGuard365 Portal', 'sg365-client-portal' ); ?></h1>
            <div class="sg365-cp-tabs" data-nonce="<?php echo esc_attr( wp_create_nonce( 'sg365_cp_dashboard' ) ); ?>">
                <?php
                $tabs = array(
                    'overview'     => __( 'Overview', 'sg365-client-portal' ),
                    'analytics'    => __( 'Analytics', 'sg365-client-portal' ),
                    'pending_due'  => __( 'Pending & Due', 'sg365-client-portal' ),
                    'this_month'   => __( 'This Month', 'sg365-client-portal' ),
                    'recent_logs'  => __( 'Recent Work Logs', 'sg365-client-portal' ),
                    'email_center' => __( 'Email Center', 'sg365-client-portal' ),
                    'settings'     => __( 'Settings', 'sg365-client-portal' ),
                );
                foreach ( $tabs as $key => $label ) {
                    printf(
                        '<button class="sg365-cp-tab" data-tab="%s">%s</button>',
                        esc_attr( $key ),
                        esc_html( $label )
                    );
                }
                ?>
            </div>
            <div class="sg365-cp-tab-panel" id="sg365-cp-tab-panel">
                <div class="sg365-cp-loading"><?php echo esc_html__( 'Loading dashboard…', 'sg365-client-portal' ); ?></div>
            </div>

            <div class="sg365-cp-modal" id="sg365-cp-worklog-modal" aria-hidden="true">
                <div class="sg365-cp-modal__overlay" data-modal-close="1"></div>
                <div class="sg365-cp-modal__content">
                    <div class="sg365-cp-modal__header">
                        <h2><?php echo esc_html__( 'Add Work Log', 'sg365-client-portal' ); ?></h2>
                        <button class="button-link" type="button" data-modal-close="1">×</button>
                    </div>
                    <form id="sg365-cp-worklog-form">
                        <input type="hidden" name="action" value="sg365_cp_add_worklog" />
                        <input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'sg365_cp_add_worklog' ) ); ?>" />
                        <div class="sg365-cp-form-grid">
                            <label>
                                <?php echo esc_html__( 'Title', 'sg365-client-portal' ); ?>
                                <input type="text" name="title" required />
                            </label>
                            <label>
                                <?php echo esc_html__( 'Date', 'sg365-client-portal' ); ?>
                                <input type="date" name="log_date" value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>" required />
                            </label>
                            <label>
                                <?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?>
                                <?php $this->client_select( 0, 'client_id', true ); ?>
                            </label>
                            <label>
                                <?php echo esc_html__( 'Domain/Site', 'sg365-client-portal' ); ?>
                                <select name="site_id" required>
                                    <option value="0"><?php echo esc_html__( 'Select client first', 'sg365-client-portal' ); ?></option>
                                </select>
                            </label>
                            <label>
                                <?php echo esc_html__( 'Project (optional)', 'sg365-client-portal' ); ?>
                                <?php $this->project_select( 0, 0, 'project_id' ); ?>
                            </label>
                            <label>
                                <?php echo esc_html__( 'Service Type', 'sg365-client-portal' ); ?>
                                <?php $this->service_type_select( 'service_type' ); ?>
                            </label>
                            <label>
                                <?php echo esc_html__( 'Visibility', 'sg365-client-portal' ); ?>
                                <select name="visibility">
                                    <option value="client"><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></option>
                                    <option value="internal"><?php echo esc_html__( 'Internal', 'sg365-client-portal' ); ?></option>
                                </select>
                            </label>
                            <label>
                                <?php echo esc_html__( 'Staff Assigned', 'sg365-client-portal' ); ?>
                                <?php $this->staff_multiselect( array(), 'staff_ids[]' ); ?>
                            </label>
                        </div>
                        <label>
                            <?php echo esc_html__( 'Description', 'sg365-client-portal' ); ?>
                            <textarea name="description" rows="4"></textarea>
                        </label>
                        <label>
                            <?php echo esc_html__( 'Attachments (URLs, comma-separated)', 'sg365-client-portal' ); ?>
                            <input type="text" name="attachments" placeholder="https://..." />
                        </label>
                        <div class="sg365-cp-modal__actions">
                            <button class="button button-primary" type="submit"><?php echo esc_html__( 'Save Work Log', 'sg365-client-portal' ); ?></button>
                            <button class="button" type="button" data-modal-close="1"><?php echo esc_html__( 'Cancel', 'sg365-client-portal' ); ?></button>
                        </div>
                        <div class="sg365-cp-modal__notice" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    public function ajax_tab(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        check_ajax_referer( 'sg365_cp_dashboard', 'nonce' );

        $tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'overview';
        $filter = isset( $_POST['filter'] ) ? sanitize_key( wp_unslash( $_POST['filter'] ) ) : '';

        ob_start();
        switch ( $tab ) {
            case 'analytics':
                $this->render_analytics( $filter );
                break;
            case 'pending_due':
                $this->render_pending_due();
                break;
            case 'this_month':
                $this->render_this_month();
                break;
            case 'recent_logs':
                $this->render_recent_logs();
                break;
            case 'email_center':
                $this->render_email_center();
                break;
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'overview':
            default:
                $this->render_overview();
                break;
        }
        $html = ob_get_clean();
        wp_send_json_success( array( 'html' => $html ) );
    }

    public function ajax_add_worklog(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        check_ajax_referer( 'sg365_cp_add_worklog', 'nonce' );

        $title       = sg365_cp_clean_text( $_POST['title'] ?? '' );
        $desc        = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
        $client_id   = sg365_cp_clean_int( $_POST['client_id'] ?? 0 );
        $site_id     = sg365_cp_clean_int( $_POST['site_id'] ?? 0 );
        $project_id  = sg365_cp_clean_int( $_POST['project_id'] ?? 0 );
        $service     = sg365_cp_clean_text( $_POST['service_type'] ?? '' );
        $log_date    = sg365_cp_clean_text( $_POST['log_date'] ?? '' );
        $visibility  = sg365_cp_clean_text( $_POST['visibility'] ?? 'client' );
        $attachments = sg365_cp_clean_text( $_POST['attachments'] ?? '' );
        $staff_ids   = isset( $_POST['staff_ids'] ) ? array_map( 'intval', (array) $_POST['staff_ids'] ) : array();

        if ( ! $title || ! $client_id || ! $site_id ) {
            wp_send_json_error( array( 'message' => __( 'Please fill out the required fields.', 'sg365-client-portal' ) ), 400 );
        }

        $post_id = wp_insert_post( array(
            'post_type'    => 'sg365_worklog',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $desc,
        ), true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Failed to create work log.', 'sg365-client-portal' ) ), 500 );
        }

        update_post_meta( $post_id, '_sg365_client_id', $client_id );
        update_post_meta( $post_id, '_sg365_site_id', $site_id );
        update_post_meta( $post_id, '_sg365_project_id', $project_id );
        update_post_meta( $post_id, '_sg365_category', $service );
        update_post_meta( $post_id, '_sg365_log_date', $log_date ? $log_date : current_time( 'Y-m-d' ) );
        update_post_meta( $post_id, '_sg365_visible_client', $visibility === 'client' ? 1 : 0 );
        update_post_meta( $post_id, '_sg365_attachments', $attachments );
        update_post_meta( $post_id, '_sg365_staff_ids', $staff_ids );

        $client_user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
        update_post_meta( $post_id, '_sg365_client_user_id', (int) $client_user_id );

        do_action( 'sg365_cp_worklog_created', $post_id );

        wp_send_json_success( array( 'message' => __( 'Work log added successfully.', 'sg365-client-portal' ) ) );
    }

    public function ajax_client_sites(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        check_ajax_referer( 'sg365_cp_add_worklog', 'nonce' );

        $client_id = sg365_cp_clean_int( $_POST['client_id'] ?? 0 );
        $sites = get_posts( array(
            'post_type'   => 'sg365_site',
            'numberposts' => 200,
            'orderby'     => 'title',
            'order'       => 'ASC',
            'meta_query'  => array(
                array( 'key' => '_sg365_client_id', 'value' => $client_id ),
            ),
        ) );
        $options = array();
        foreach ( $sites as $site ) {
            $options[] = array( 'id' => (int) $site->ID, 'label' => $site->post_title );
        }
        wp_send_json_success( array( 'sites' => $options ) );
    }

    private function render_overview(): void {
        $counts = $this->get_counts();
        ?>
        <div class="sg365-cp-kpi-grid">
            <?php
            $cards = array(
                'total_clients' => array( __( 'Total Clients', 'sg365-client-portal' ), $counts['clients'] ),
                'total_sites'   => array( __( 'Total Domains/Sites', 'sg365-client-portal' ), $counts['sites'] ),
                'active_projects' => array( __( 'Total Active Projects', 'sg365-client-portal' ), $counts['active_projects'] ),
                'pending_work'  => array( __( 'Pending Work (Overdue)', 'sg365-client-portal' ), $counts['pending_work'] ),
                'on_time_work'  => array( __( 'On-Time Work', 'sg365-client-portal' ), $counts['on_time_work'] ),
                'salary_due'    => array( __( 'Salaries Due Soon', 'sg365-client-portal' ), $counts['salary_due'] ),
                'salary_overdue'=> array( __( 'Salaries Overdue', 'sg365-client-portal' ), $counts['salary_overdue'] ),
            );
            foreach ( $cards as $key => $data ) {
                printf(
                    '<button class="sg365-cp-kpi-card" data-filter="%s"><span class="sg365-cp-kpi-label">%s</span><span class="sg365-cp-kpi-value">%d</span></button>',
                    esc_attr( $key ),
                    esc_html( $data[0] ),
                    (int) $data[1]
                );
            }
            ?>
        </div>
        <div class="sg365-cp-actions">
            <button class="button button-primary" id="sg365-cp-open-worklog"><?php echo esc_html__( 'Add Work Log', 'sg365-client-portal' ); ?></button>
        </div>
        <?php
    }

    private function render_analytics( string $filter ): void {
        $counts = $this->get_counts();
        $total_domains = max( 1, $counts['sites'] );
        $performance = round( ( $counts['on_time_work'] / $total_domains ) * 100, 2 );

        $projects = $this->get_projects();
        $completed = 0;
        $remaining = 0;
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_project_progress', true );
            if ( $progress >= 100 ) {
                $completed++;
            } else {
                $remaining++;
            }
        }
        ?>
        <div class="sg365-cp-analytics">
            <div class="sg365-cp-analytics-grid">
                <div class="sg365-cp-analytics-card">
                    <h3><?php echo esc_html__( 'Client & Domain Metrics', 'sg365-client-portal' ); ?></h3>
                    <ul>
                        <li><?php echo esc_html__( 'Total Clients:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $counts['clients'] ); ?></strong></li>
                        <li><?php echo esc_html__( 'Total Domains:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $counts['sites'] ); ?></strong></li>
                        <li><?php echo esc_html__( 'Domains Worked On Time:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $counts['on_time_work'] ); ?></strong></li>
                        <li><?php echo esc_html__( 'Domains Not Worked On Time:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $counts['pending_work'] ); ?></strong></li>
                        <li><?php echo esc_html__( 'Monthly Performance %:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $performance ); ?>%</strong></li>
                    </ul>
                </div>
                <div class="sg365-cp-analytics-card">
                    <h3><?php echo esc_html__( 'Project Analytics', 'sg365-client-portal' ); ?></h3>
                    <ul>
                        <li><?php echo esc_html__( 'Active Projects:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $counts['active_projects'] ); ?></strong></li>
                        <li><?php echo esc_html__( 'Completed Projects:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $completed ); ?></strong></li>
                        <li><?php echo esc_html__( 'Projects With Remaining Work:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $remaining ); ?></strong></li>
                    </ul>
                </div>
            </div>
            <div class="sg365-cp-charts">
                <div class="sg365-cp-chart-card">
                    <h4><?php echo esc_html__( 'Domain Performance (Bar)', 'sg365-client-portal' ); ?></h4>
                    <div class="sg365-cp-bar">
                        <span style="width: <?php echo esc_attr( $performance ); ?>%"></span>
                    </div>
                    <div class="sg365-cp-chart-legend">
                        <span><?php echo esc_html__( 'On-time', 'sg365-client-portal' ); ?>: <?php echo esc_html( $counts['on_time_work'] ); ?></span>
                        <span><?php echo esc_html__( 'Overdue', 'sg365-client-portal' ); ?>: <?php echo esc_html( $counts['pending_work'] ); ?></span>
                    </div>
                </div>
                <div class="sg365-cp-chart-card">
                    <h4><?php echo esc_html__( 'On-time vs Overdue (Donut)', 'sg365-client-portal' ); ?></h4>
                    <div class="sg365-cp-donut" style="--sg365-on-time: <?php echo esc_attr( $performance ); ?>%;"></div>
                    <div class="sg365-cp-chart-legend">
                        <span><?php echo esc_html__( 'On-time', 'sg365-client-portal' ); ?>: <?php echo esc_html( $counts['on_time_work'] ); ?></span>
                        <span><?php echo esc_html__( 'Overdue', 'sg365-client-portal' ); ?>: <?php echo esc_html( $counts['pending_work'] ); ?></span>
                    </div>
                </div>
                <div class="sg365-cp-chart-card">
                    <h4><?php echo esc_html__( 'Project Progress', 'sg365-client-portal' ); ?></h4>
                    <?php foreach ( $projects as $project ) : ?>
                        <?php $progress = (int) get_post_meta( $project->ID, '_sg365_project_progress', true ); ?>
                        <div class="sg365-cp-progress-row">
                            <div class="sg365-cp-progress-label"><?php echo esc_html( $project->post_title ); ?></div>
                            <div class="sg365-cp-progress-bar">
                                <span style="width: <?php echo esc_attr( $progress ); ?>%"></span>
                            </div>
                            <div class="sg365-cp-progress-value"><?php echo esc_html( $progress ); ?>%</div>
                        </div>
                    <?php endforeach; ?>
                    <?php if ( empty( $projects ) ) : ?>
                        <p><?php echo esc_html__( 'No projects found yet.', 'sg365-client-portal' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ( $filter ) : ?>
                <div class="sg365-cp-filter-note">
                    <?php echo esc_html__( 'Filtered by KPI:', 'sg365-client-portal' ); ?> <strong><?php echo esc_html( $filter ); ?></strong>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function render_pending_due(): void {
        $overdue_sites = $this->get_overdue_sites();
        $projects = $this->get_projects();
        $due_lists = $this->get_salary_due_lists();
        ?>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Domains With Overdue Work', 'sg365-client-portal' ); ?></h2>
            <?php $this->render_table_header(); ?>
            <?php foreach ( $overdue_sites as $site ) : ?>
                <?php $this->render_site_row( $site ); ?>
            <?php endforeach; ?>
            <?php if ( empty( $overdue_sites ) ) : ?>
                <p><?php echo esc_html__( 'No overdue domains found.', 'sg365-client-portal' ); ?></p>
            <?php endif; ?>
        </div>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Projects With Remaining Progress', 'sg365-client-portal' ); ?></h2>
            <?php $this->render_table_header( true ); ?>
            <?php
            $has_project = false;
            foreach ( $projects as $project ) :
                $progress = (int) get_post_meta( $project->ID, '_sg365_project_progress', true );
                if ( $progress >= 100 ) {
                    continue;
                }
                $has_project = true;
                $this->render_project_row( $project, $progress );
            endforeach;
            ?>
            <?php if ( ! $has_project ) : ?>
                <p><?php echo esc_html__( 'No projects with remaining work.', 'sg365-client-portal' ); ?></p>
            <?php endif; ?>
        </div>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Salaries Due Soon', 'sg365-client-portal' ); ?></h2>
            <?php $this->render_salary_rows( $due_lists['due_soon'] ); ?>
        </div>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Salaries Overdue', 'sg365-client-portal' ); ?></h2>
            <?php $this->render_salary_rows( $due_lists['overdue'] ); ?>
        </div>
        <?php
    }

    private function render_this_month(): void {
        $month = gmdate( 'Y-m' );
        $worklogs = $this->get_worklogs_by_month( $month );
        $new_domains = $this->get_posts_by_month( 'sg365_site', $month );
        $projects_completed = $this->get_completed_projects_by_month( $month );
        $income = $this->get_salary_totals_by_month( $month, 'incoming' );
        $outgoing = $this->get_salary_totals_by_month( $month, 'outgoing' );
        ?>
        <div class="sg365-cp-kpi-grid">
            <div class="sg365-cp-kpi-card is-static">
                <span class="sg365-cp-kpi-label"><?php echo esc_html__( 'Work Logs Added', 'sg365-client-portal' ); ?></span>
                <span class="sg365-cp-kpi-value"><?php echo esc_html( count( $worklogs ) ); ?></span>
            </div>
            <div class="sg365-cp-kpi-card is-static">
                <span class="sg365-cp-kpi-label"><?php echo esc_html__( 'New Domains Added', 'sg365-client-portal' ); ?></span>
                <span class="sg365-cp-kpi-value"><?php echo esc_html( $new_domains ); ?></span>
            </div>
            <div class="sg365-cp-kpi-card is-static">
                <span class="sg365-cp-kpi-label"><?php echo esc_html__( 'Projects Completed', 'sg365-client-portal' ); ?></span>
                <span class="sg365-cp-kpi-value"><?php echo esc_html( $projects_completed ); ?></span>
            </div>
            <div class="sg365-cp-kpi-card is-static">
                <span class="sg365-cp-kpi-label"><?php echo esc_html__( 'Incoming Payments', 'sg365-client-portal' ); ?></span>
                <span class="sg365-cp-kpi-value"><?php echo esc_html( $income ); ?></span>
            </div>
            <div class="sg365-cp-kpi-card is-static">
                <span class="sg365-cp-kpi-label"><?php echo esc_html__( 'Outgoing Salaries', 'sg365-client-portal' ); ?></span>
                <span class="sg365-cp-kpi-value"><?php echo esc_html( $outgoing ); ?></span>
            </div>
        </div>
        <?php
    }

    private function render_recent_logs(): void {
        $logs = get_posts( array(
            'post_type'   => 'sg365_worklog',
            'numberposts' => 20,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        ?>
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
                <?php foreach ( $logs as $log ) : ?>
                    <?php
                    $date = (string) get_post_meta( $log->ID, '_sg365_log_date', true );
                    $client_id = (int) get_post_meta( $log->ID, '_sg365_client_id', true );
                    $site_id = (int) get_post_meta( $log->ID, '_sg365_site_id', true );
                    $service = (string) get_post_meta( $log->ID, '_sg365_category', true );
                    $visible = (int) get_post_meta( $log->ID, '_sg365_visible_client', true );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $date ); ?></td>
                        <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '-' ); ?></td>
                        <td><?php echo esc_html( $site_id ? get_the_title( $site_id ) : '-' ); ?></td>
                        <td><?php echo esc_html( ucfirst( $service ) ); ?></td>
                        <td><?php echo esc_html( $visible ? __( 'Client', 'sg365-client-portal' ) : __( 'Internal', 'sg365-client-portal' ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ( empty( $logs ) ) : ?>
                    <tr><td colspan="5"><?php echo esc_html__( 'No work logs yet.', 'sg365-client-portal' ); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="sg365-cp-actions">
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-worklogs' ) ); ?>"><?php echo esc_html__( 'View All Logs', 'sg365-client-portal' ); ?></a>
        </div>
        <?php
    }

    private function render_email_center(): void {
        $url = admin_url( 'admin.php?page=sg365-cp-email-center' );
        ?>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Email Center', 'sg365-client-portal' ); ?></h2>
            <p><?php echo esc_html__( 'Control all system notifications and templates from the Email Center.', 'sg365-client-portal' ); ?></p>
            <a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html__( 'Open Email Center', 'sg365-client-portal' ); ?></a>
        </div>
        <?php
    }

    private function render_settings_tab(): void {
        ?>
        <div class="sg365-cp-section">
            <h2><?php echo esc_html__( 'Settings & Service Types', 'sg365-client-portal' ); ?></h2>
            <p><?php echo esc_html__( 'Manage portal settings, service types, and staff assignments.', 'sg365-client-portal' ); ?></p>
            <div class="sg365-cp-actions">
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-settings' ) ); ?>"><?php echo esc_html__( 'Open Settings', 'sg365-client-portal' ); ?></a>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sg365-cp-service-types' ) ); ?>"><?php echo esc_html__( 'Manage Service Types', 'sg365-client-portal' ); ?></a>
            </div>
        </div>
        <?php
    }

    private function get_counts(): array {
        $clients = (int) wp_count_posts( 'sg365_client' )->publish;
        $sites = (int) wp_count_posts( 'sg365_site' )->publish;
        $projects = $this->get_projects();
        $active_projects = 0;
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_project_progress', true );
            if ( $progress < 100 ) {
                $active_projects++;
            }
        }
        $overdue_sites = $this->get_overdue_sites();
        $on_time_sites = $this->get_on_time_sites();
        $due_lists = $this->get_salary_due_lists();
        return array(
            'clients'         => $clients,
            'sites'           => $sites,
            'active_projects' => $active_projects,
            'pending_work'    => count( $overdue_sites ),
            'on_time_work'    => count( $on_time_sites ),
            'salary_due'      => count( $due_lists['due_soon'] ),
            'salary_overdue'  => count( $due_lists['overdue'] ),
        );
    }

    private function get_overdue_sites(): array {
        $today = current_time( 'Y-m-d' );
        return get_posts( array(
            'post_type'   => 'sg365_site',
            'numberposts' => 200,
            'meta_query'  => array(
                array(
                    'key'     => '_sg365_next_update_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
            ),
        ) );
    }

    private function get_on_time_sites(): array {
        $today = current_time( 'Y-m-d' );
        return get_posts( array(
            'post_type'   => 'sg365_site',
            'numberposts' => 200,
            'meta_query'  => array(
                array(
                    'key'     => '_sg365_next_update_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ),
            ),
        ) );
    }

    private function get_projects(): array {
        return get_posts( array(
            'post_type'   => 'sg365_project',
            'numberposts' => 200,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
    }

    private function render_table_header( bool $project = false ): void {
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( $project ? 'Project' : 'Domain / Project', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Last Activity', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Expected Next Update', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'sg365-client-portal' ); ?></th>
                </tr>
            </thead>
            <tbody>
        <?php
    }

    private function render_site_row( WP_Post $site ): void {
        $client_id = (int) get_post_meta( $site->ID, '_sg365_client_id', true );
        $last = (string) get_post_meta( $site->ID, '_sg365_last_activity_date', true );
        $next = (string) get_post_meta( $site->ID, '_sg365_next_update_date', true );
        ?>
        <tr>
            <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '-' ); ?></td>
            <td><?php echo esc_html( $site->post_title ); ?></td>
            <td><?php echo esc_html( $last ? $last : '-' ); ?></td>
            <td><?php echo esc_html( $next ? $next : '-' ); ?></td>
            <td><span class="sg365-cp-status is-overdue"><?php echo esc_html__( 'Overdue', 'sg365-client-portal' ); ?></span></td>
        </tr>
        <?php
    }

    private function render_project_row( WP_Post $project, int $progress ): void {
        $client_id = (int) get_post_meta( $project->ID, '_sg365_client_id', true );
        $last = (string) get_post_meta( $project->ID, '_sg365_last_activity_date', true );
        $next = (string) get_post_meta( $project->ID, '_sg365_next_update_date', true );
        ?>
        <tr>
            <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '-' ); ?></td>
            <td><?php echo esc_html( $project->post_title ); ?> (<?php echo esc_html( $progress ); ?>%)</td>
            <td><?php echo esc_html( $last ? $last : '-' ); ?></td>
            <td><?php echo esc_html( $next ? $next : '-' ); ?></td>
            <td><span class="sg365-cp-status is-pending"><?php echo esc_html__( 'Pending', 'sg365-client-portal' ); ?></span></td>
        </tr>
        <?php
    }

    private function render_salary_rows( array $salaries ): void {
        if ( empty( $salaries ) ) {
            echo '<p>' . esc_html__( 'No salary records found.', 'sg365-client-portal' ) . '</p>';
            return;
        }
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Client', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Domain / Project', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Last Activity', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Expected Next Update', 'sg365-client-portal' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'sg365-client-portal' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $salaries as $salary ) : ?>
                    <?php
                    $client_id = (int) get_post_meta( $salary->ID, '_sg365_client_id', true );
                    $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
                    $status = (string) get_post_meta( $salary->ID, '_sg365_payment_status', true );
                    $label = $status === 'overdue' ? __( 'Overdue', 'sg365-client-portal' ) : __( 'Due Soon', 'sg365-client-portal' );
                    ?>
                    <tr>
                        <td><?php echo esc_html( $client_id ? get_the_title( $client_id ) : '-' ); ?></td>
                        <td><?php echo esc_html__( 'Salary', 'sg365-client-portal' ); ?></td>
                        <td><?php echo esc_html( $due ); ?></td>
                        <td><?php echo esc_html( $due ); ?></td>
                        <td><span class="sg365-cp-status <?php echo esc_attr( $status === 'overdue' ? 'is-overdue' : 'is-due' ); ?>"><?php echo esc_html( $label ); ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function get_salary_due_lists(): array {
        $settings = SG365_CP_Email_Center::get_settings();
        $days = isset( $settings['salary_due_days'] ) ? (int) $settings['salary_due_days'] : 7;
        $today = current_time( 'Y-m-d' );
        $soon = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );

        $salaries = get_posts( array(
            'post_type'   => 'sg365_salary',
            'numberposts' => 200,
            'meta_query'  => array(
                array( 'key' => '_sg365_payment_status', 'value' => 'unpaid' ),
                array( 'key' => '_sg365_due_date', 'compare' => 'EXISTS' ),
            ),
        ) );

        $due_soon = array();
        $overdue = array();
        foreach ( $salaries as $salary ) {
            $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
            if ( ! $due ) {
                continue;
            }
            if ( $due < $today ) {
                update_post_meta( $salary->ID, '_sg365_payment_status', 'overdue' );
                $overdue[] = $salary;
            } elseif ( $due <= $soon ) {
                update_post_meta( $salary->ID, '_sg365_payment_status', 'due_soon' );
                $due_soon[] = $salary;
            }
        }

        return array(
            'due_soon' => $due_soon,
            'overdue'  => $overdue,
        );
    }

    private function get_worklogs_by_month( string $month ): array {
        $logs = get_posts( array(
            'post_type'   => 'sg365_worklog',
            'numberposts' => 500,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ) );
        return array_filter( $logs, function( $log ) use ( $month ) {
            $date = (string) get_post_meta( $log->ID, '_sg365_log_date', true );
            return strpos( $date, $month . '-' ) === 0;
        } );
    }

    private function get_posts_by_month( string $post_type, string $month ): int {
        $q = new WP_Query( array(
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'year'  => (int) substr( $month, 0, 4 ),
                    'month' => (int) substr( $month, 5, 2 ),
                ),
            ),
        ) );
        return (int) $q->found_posts;
    }

    private function get_completed_projects_by_month( string $month ): int {
        $projects = $this->get_projects();
        $count = 0;
        foreach ( $projects as $project ) {
            $progress = (int) get_post_meta( $project->ID, '_sg365_project_progress', true );
            if ( $progress < 100 ) {
                continue;
            }
            $date = get_the_date( 'Y-m', $project );
            if ( $date === $month ) {
                $count++;
            }
        }
        return $count;
    }

    private function get_salary_totals_by_month( string $month, string $direction ): string {
        $salaries = get_posts( array(
            'post_type'   => 'sg365_salary',
            'numberposts' => 500,
            'meta_query'  => array(
                array( 'key' => '_sg365_direction', 'value' => $direction ),
                array( 'key' => '_sg365_due_date', 'compare' => 'EXISTS' ),
            ),
        ) );
        $total = 0;
        foreach ( $salaries as $salary ) {
            $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
            if ( strpos( $due, $month . '-' ) !== 0 ) {
                continue;
            }
            $amount = (float) get_post_meta( $salary->ID, '_sg365_amount', true );
            $total += $amount;
        }
        return number_format_i18n( $total, 2 );
    }

    private function client_select( int $selected, string $name, bool $required = false ): void {
        $clients = get_posts( array( 'post_type' => 'sg365_client', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<select class="widefat" name="' . esc_attr( $name ) . '"' . ( $required ? ' required' : '' ) . '>';
        echo '<option value="0">' . esc_html__( '— Select —', 'sg365-client-portal' ) . '</option>';
        foreach ( $clients as $client ) {
            printf('<option value="%d"%s>%s</option>', (int) $client->ID, selected( $selected, (int) $client->ID, false ), esc_html( $client->post_title ) );
        }
        echo '</select>';
    }

    private function project_select( int $selected, int $client_id, string $name ): void {
        $args = array( 'post_type' => 'sg365_project', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' );
        if ( $client_id ) {
            $args['meta_query'] = array( array( 'key' => '_sg365_client_id', 'value' => $client_id ) );
        }
        $projects = get_posts( $args );
        echo '<select class="widefat" name="' . esc_attr( $name ) . '">';
        echo '<option value="0">' . esc_html__( '— Optional —', 'sg365-client-portal' ) . '</option>';
        foreach ( $projects as $project ) {
            printf('<option value="%d"%s>%s</option>', (int) $project->ID, selected( $selected, (int) $project->ID, false ), esc_html( $project->post_title ) );
        }
        echo '</select>';
    }

    private function service_type_select( string $name ): void {
        $types = sg365_cp_get_service_types();
        echo '<select class="widefat" name="' . esc_attr( $name ) . '">';
        foreach ( $types as $key => $data ) {
            $label = is_array( $data ) ? ( $data['label'] ?? $key ) : $data;
            printf('<option value="%s">%s</option>', esc_attr( $key ), esc_html( $label ) );
        }
        echo '</select>';
    }

    private function staff_multiselect( array $selected, string $name ): void {
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 300, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<select class="widefat" name="' . esc_attr( $name ) . '" multiple>';
        foreach ( $staff as $member ) {
            printf(
                '<option value="%d"%s>%s</option>',
                (int) $member->ID,
                in_array( (int) $member->ID, $selected, true ) ? ' selected' : '',
                esc_html( $member->post_title )
            );
        }
        echo '</select>';
    }
}
