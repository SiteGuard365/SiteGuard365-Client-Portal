<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SG365_CP_Email_Center {

    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    public static function get_settings(): array {
        $defaults = array(
            'new_worklog' => array(
                'enabled' => 1,
                'subject' => 'New work log added for {domain}',
                'body'    => '<p>Hi {client_name},</p><p>A new work log was added: <strong>{log_title}</strong> on {log_date}.</p><p>View it here: {portal_link}</p>',
                'body_text' => 'Hi {client_name}, A new work log was added: {log_title} on {log_date}. View it here: {portal_link}',
            ),
            'new_domain' => array(
                'enabled' => 1,
                'subject' => 'New domain added: {domain}',
                'body'    => '<p>Hi {client_name},</p><p>Your new domain/site {domain} has been added to the portal.</p><p>View it here: {portal_link}</p>',
                'body_text' => 'Hi {client_name}, Your new domain/site {domain} has been added to the portal. View it here: {portal_link}',
            ),
            'salary_due_soon' => array(
                'enabled' => 1,
                'subject' => 'Salary due soon for {client_name}',
                'body'    => '<p>Reminder: Salary of {amount} is due on {due_date} for {client_name}.</p>',
                'body_text' => 'Reminder: Salary of {amount} is due on {due_date} for {client_name}.',
            ),
            'salary_overdue' => array(
                'enabled' => 1,
                'subject' => 'Salary overdue for {client_name}',
                'body'    => '<p>Alert: Salary of {amount} was due on {due_date} for {client_name}.</p>',
                'body_text' => 'Alert: Salary of {amount} was due on {due_date} for {client_name}.',
            ),
            'salary_status_updated' => array(
                'enabled' => 1,
                'subject' => 'Salary status updated for {client_name}',
                'body'    => '<p>The salary status for {client_name} has been updated. Amount: {amount}. Due date: {due_date}.</p>',
                'body_text' => 'The salary status for {client_name} has been updated. Amount: {amount}. Due date: {due_date}.',
            ),
            'salary_due_days' => 7,
        );

        $settings = get_option( 'sg365_cp_email_settings', array() );
        return wp_parse_args( $settings, $defaults );
    }

    private function init(): void {
        add_action( 'admin_post_sg365_cp_send_test_email', array( $this, 'send_test_email' ) );
        add_action( 'wp_ajax_sg365_cp_send_test_email', array( $this, 'send_test_email_ajax' ) );
        add_action( 'save_post_sg365_worklog', array( $this, 'send_worklog_email' ), 20, 3 );
        add_action( 'save_post_sg365_site', array( $this, 'send_domain_email' ), 20, 3 );
        add_action( 'save_post_sg365_salary', array( $this, 'handle_salary_updates' ), 20, 3 );
        add_action( 'admin_init', array( $this, 'maybe_run_salary_checks' ) );
    }

    public function render_page(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $settings = self::get_settings();
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'Email Center', 'sg365-client-portal' ); ?></h1>
            <form method="post" action="options.php" class="sg365-cp-email-form">
                <?php settings_fields( 'sg365_cp_email_settings' ); ?>
                <input type="hidden" name="sg365_cp_email_settings[salary_due_days]" value="<?php echo esc_attr( $settings['salary_due_days'] ); ?>" />
                <?php $this->render_trigger_block( 'new_worklog', __( 'New work log added → client email', 'sg365-client-portal' ), $settings ); ?>
                <?php $this->render_trigger_block( 'new_domain', __( 'New domain/site added → client email', 'sg365-client-portal' ), $settings ); ?>
                <?php $this->render_trigger_block( 'salary_due_soon', __( 'Salary due soon → admin email', 'sg365-client-portal' ), $settings, true ); ?>
                <?php $this->render_trigger_block( 'salary_overdue', __( 'Salary overdue → admin email', 'sg365-client-portal' ), $settings, true ); ?>
                <?php $this->render_trigger_block( 'salary_status_updated', __( 'Client salary status updated → client + admin', 'sg365-client-portal' ), $settings, true ); ?>

                <h2><?php echo esc_html__( 'Global Settings', 'sg365-client-portal' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Days before salary due', 'sg365-client-portal' ); ?></th>
                        <td>
                            <input type="number" name="sg365_cp_email_settings[salary_due_days]" min="1" max="60" value="<?php echo esc_attr( $settings['salary_due_days'] ); ?>" />
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Email Settings', 'sg365-client-portal' ) ); ?>
            </form>

            <form method="post" class="sg365-cp-test-email">
                <?php wp_nonce_field( 'sg365_cp_send_test_email', 'sg365_cp_test_nonce' ); ?>
                <input type="hidden" name="action" value="sg365_cp_send_test_email" />
                <button class="button" type="submit"><?php echo esc_html__( 'Send Test Email', 'sg365-client-portal' ); ?></button>
                <span class="description"><?php echo esc_html__( 'Sends a test email to the site admin address.', 'sg365-client-portal' ); ?></span>
            </form>
        </div>
        <?php
    }

    private function render_trigger_block( string $key, string $label, array $settings, bool $show_vars = false ): void {
        $data = $settings[ $key ];
        $enabled = ! empty( $data['enabled'] );
        ?>
        <div class="sg365-cp-email-block">
            <h2><?php echo esc_html( $label ); ?></h2>
            <p>
                <label>
                    <input type="checkbox" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][enabled]" value="1" <?php checked( $enabled ); ?> />
                    <?php echo esc_html__( 'Enable this email trigger', 'sg365-client-portal' ); ?>
                </label>
            </p>
            <p>
                <label><?php echo esc_html__( 'Subject template', 'sg365-client-portal' ); ?></label>
                <input type="text" class="widefat" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][subject]" value="<?php echo esc_attr( $data['subject'] ); ?>" />
            </p>
            <p>
                <label><?php echo esc_html__( 'HTML body template', 'sg365-client-portal' ); ?></label>
                <textarea class="widefat" rows="4" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][body]"><?php echo esc_textarea( $data['body'] ); ?></textarea>
            </p>
            <p>
                <label><?php echo esc_html__( 'Plain text fallback', 'sg365-client-portal' ); ?></label>
                <textarea class="widefat" rows="3" name="sg365_cp_email_settings[<?php echo esc_attr( $key ); ?>][body_text]"><?php echo esc_textarea( $data['body_text'] ); ?></textarea>
            </p>
            <?php if ( $show_vars ) : ?>
                <p class="description"><?php echo esc_html__( 'Allowed variables: {client_name}, {domain}, {project_name}, {log_title}, {log_date}, {month}, {amount}, {due_date}, {portal_link}', 'sg365-client-portal' ); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function send_test_email(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        check_admin_referer( 'sg365_cp_send_test_email', 'sg365_cp_test_nonce' );
        $sent = wp_mail( get_option( 'admin_email' ), 'SG365 Test Email', 'This is a test email from SG365 Email Center.' );
        wp_safe_redirect( add_query_arg( 'sg365_test_email', $sent ? 'sent' : 'failed', wp_get_referer() ?: admin_url( 'admin.php?page=sg365-cp-email-center' ) ) );
        exit;
    }

    public function send_test_email_ajax(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_send_json_error( array( 'message' => 'Access denied.' ), 403 ); }
        check_ajax_referer( 'sg365_cp_send_test_email', 'nonce' );
        $sent = wp_mail( get_option( 'admin_email' ), 'SG365 Test Email', 'This is a test email from SG365 Email Center.' );
        wp_send_json_success( array( 'sent' => $sent ) );
    }

    public function send_worklog_email( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        if ( $update ) { return; }
        $settings = self::get_settings();
        if ( empty( $settings['new_worklog']['enabled'] ) ) { return; }

        $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
        $user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
        if ( ! $user_id ) { return; }

        $last_sent = (int) get_post_meta( $post_id, '_sg365_email_worklog_sent', true );
        if ( $last_sent ) { return; }

        $to = get_userdata( $user_id )->user_email;
        $vars = $this->build_vars( array(
            'client_id' => $client_id,
            'domain'    => (int) get_post_meta( $post_id, '_sg365_site_id', true ),
            'log_title' => $post->post_title,
            'log_date'  => (string) get_post_meta( $post_id, '_sg365_log_date', true ),
        ) );

        $this->send_templated_email( $to, $settings['new_worklog'], $vars );
        update_post_meta( $post_id, '_sg365_email_worklog_sent', time() );
    }

    public function send_domain_email( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        if ( $update ) { return; }
        $settings = self::get_settings();
        if ( empty( $settings['new_domain']['enabled'] ) ) { return; }

        $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
        $user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
        if ( ! $user_id ) { return; }

        $last_sent = (int) get_post_meta( $post_id, '_sg365_email_domain_sent', true );
        if ( $last_sent ) { return; }

        $to = get_userdata( $user_id )->user_email;
        $vars = $this->build_vars( array(
            'client_id' => $client_id,
            'domain'    => $post->post_title,
        ) );

        $this->send_templated_email( $to, $settings['new_domain'], $vars );
        update_post_meta( $post_id, '_sg365_email_domain_sent', time() );
    }

    public function handle_salary_updates( int $post_id, WP_Post $post, bool $update ): void {
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
        $settings = self::get_settings();

        if ( ! empty( $settings['salary_status_updated']['enabled'] ) ) {
            $sent = (int) get_post_meta( $post_id, '_sg365_email_salary_status_sent', true );
            if ( ! $sent ) {
                $client_id = (int) get_post_meta( $post_id, '_sg365_client_id', true );
                $user_id = $client_id ? sg365_cp_get_user_id_for_client( $client_id ) : 0;
                $vars = $this->build_vars( array(
                    'client_id' => $client_id,
                    'amount'    => (string) get_post_meta( $post_id, '_sg365_amount', true ),
                    'due_date'  => (string) get_post_meta( $post_id, '_sg365_due_date', true ),
                ) );
                if ( $user_id ) {
                    $this->send_templated_email( get_userdata( $user_id )->user_email, $settings['salary_status_updated'], $vars );
                }
                $this->send_templated_email( get_option( 'admin_email' ), $settings['salary_status_updated'], $vars );
                update_post_meta( $post_id, '_sg365_email_salary_status_sent', time() );
            }
        }
    }

    public function maybe_run_salary_checks(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { return; }
        $last_run = (int) get_option( 'sg365_cp_salary_email_run', 0 );
        if ( $last_run && ( time() - $last_run ) < DAY_IN_SECONDS ) {
            return;
        }
        $settings = self::get_settings();
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

        foreach ( $salaries as $salary ) {
            $due = (string) get_post_meta( $salary->ID, '_sg365_due_date', true );
            $vars = $this->build_vars( array(
                'client_id' => (int) get_post_meta( $salary->ID, '_sg365_client_id', true ),
                'amount'    => (string) get_post_meta( $salary->ID, '_sg365_amount', true ),
                'due_date'  => $due,
            ) );
            if ( $due < $today && ! empty( $settings['salary_overdue']['enabled'] ) ) {
                $sent = (int) get_post_meta( $salary->ID, '_sg365_email_overdue_sent', true );
                if ( ! $sent ) {
                    $this->send_templated_email( get_option( 'admin_email' ), $settings['salary_overdue'], $vars );
                    update_post_meta( $salary->ID, '_sg365_email_overdue_sent', time() );
                }
            } elseif ( $due <= $soon && ! empty( $settings['salary_due_soon']['enabled'] ) ) {
                $sent = (int) get_post_meta( $salary->ID, '_sg365_email_due_soon_sent', true );
                if ( ! $sent ) {
                    $this->send_templated_email( get_option( 'admin_email' ), $settings['salary_due_soon'], $vars );
                    update_post_meta( $salary->ID, '_sg365_email_due_soon_sent', time() );
                }
            }
        }

        update_option( 'sg365_cp_salary_email_run', time() );
    }

    private function build_vars( array $data ): array {
        $client_id = $data['client_id'] ?? 0;
        $client_name = $client_id ? get_the_title( $client_id ) : '';
        $domain = '';
        if ( ! empty( $data['domain'] ) ) {
            $domain = is_numeric( $data['domain'] ) ? get_the_title( (int) $data['domain'] ) : (string) $data['domain'];
        }

        return array(
            '{client_name}' => $client_name,
            '{domain}'      => $domain,
            '{project_name}'=> $data['project_name'] ?? '',
            '{log_title}'   => $data['log_title'] ?? '',
            '{log_date}'    => $data['log_date'] ?? '',
            '{month}'       => $data['month'] ?? gmdate( 'F Y' ),
            '{amount}'      => $data['amount'] ?? '',
            '{due_date}'    => $data['due_date'] ?? '',
            '{portal_link}' => function_exists( 'wc_get_account_endpoint_url' ) ? wc_get_account_endpoint_url( 'sg365-portal' ) : home_url( '/' ),
        );
    }

    private function send_templated_email( string $to, array $template, array $vars ): void {
        $subject = strtr( $template['subject'], $vars );
        $body = strtr( $template['body'], $vars );
        $body_text = strtr( $template['body_text'], $vars );
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        $sent = wp_mail( $to, $subject, $body, $headers );
        if ( ! $sent ) {
            wp_mail( $to, $subject, $body_text );
        }
    }
}
