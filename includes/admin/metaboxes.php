<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SG365_CP_Metaboxes {
    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'add_meta_boxes', array( $this, 'register' ) );
        add_action( 'save_post', array( $this, 'save' ), 10, 2 );
    }

    public function register(): void {
        add_meta_box( 'sg365_client_meta', __( 'Client Details', 'sg365-client-portal' ), array( $this, 'client_box' ), 'sg365_client', 'normal', 'default' );
        add_meta_box( 'sg365_site_meta', __( 'Site/Domain Details', 'sg365-client-portal' ), array( $this, 'site_box' ), 'sg365_site', 'normal', 'default' );
        add_meta_box( 'sg365_project_meta', __( 'Project Details', 'sg365-client-portal' ), array( $this, 'project_box' ), 'sg365_project', 'normal', 'default' );
        add_meta_box( 'sg365_worklog_meta', __( 'Work Log Details', 'sg365-client-portal' ), array( $this, 'worklog_box' ), 'sg365_worklog', 'normal', 'default' );
        add_meta_box( 'sg365_staff_meta', __( 'Staff Details', 'sg365-client-portal' ), array( $this, 'staff_box' ), 'sg365_staff', 'normal', 'default' );
        add_meta_box( 'sg365_salary_meta', __( 'Salary Sheet Details', 'sg365-client-portal' ), array( $this, 'salary_box' ), 'sg365_salary', 'normal', 'default' );
    }

    private function clients( int $selected = 0, string $name = '_sg365_client_id' ): void {
        $items = get_posts( array( 'post_type' => 'sg365_client', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<select class="widefat" name="' . esc_attr( $name ) . '">';
        echo '<option value="0">' . esc_html__( '— Select —', 'sg365-client-portal' ) . '</option>';
        foreach ( $items as $c ) {
            printf('<option value="%d"%s>%s</option>', (int)$c->ID, selected($selected,(int)$c->ID,false), esc_html($c->post_title));
        }
        echo '</select>';
    }

    private function sites( int $selected = 0, int $client_id = 0 ): void {
        $args = array( 'post_type'=>'sg365_site', 'numberposts'=>500, 'orderby'=>'title', 'order'=>'ASC' );
        if ( $client_id ) { $args['meta_query'] = array( array('key'=>'_sg365_client_id','value'=>$client_id) ); }
        $items = get_posts($args);
        echo '<select class="widefat" name="_sg365_site_id">';
        echo '<option value="0">' . esc_html__( '— Select —', 'sg365-client-portal' ) . '</option>';
        foreach ( $items as $s ) {
            printf('<option value="%d"%s>%s</option>', (int)$s->ID, selected($selected,(int)$s->ID,false), esc_html($s->post_title));
        }
        echo '</select>';
    }

    private function projects( int $selected = 0, int $client_id = 0 ): void {
        $args = array( 'post_type'=>'sg365_project', 'numberposts'=>500, 'orderby'=>'title', 'order'=>'ASC' );
        if ( $client_id ) { $args['meta_query'] = array( array('key'=>'_sg365_client_id','value'=>$client_id) ); }
        $items = get_posts($args);
        echo '<select class="widefat" name="_sg365_project_id">';
        echo '<option value="0">' . esc_html__( '— Optional —', 'sg365-client-portal' ) . '</option>';
        foreach ( $items as $p ) {
            printf('<option value="%d"%s>%s</option>', (int)$p->ID, selected($selected,(int)$p->ID,false), esc_html($p->post_title));
        }
        echo '</select>';
    }

    public function client_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $user_id = (int) get_post_meta( $post->ID, '_sg365_user_id', true );
        $phone = (string) get_post_meta( $post->ID, '_sg365_phone', true );
        $plan = (string) get_post_meta( $post->ID, '_sg365_plan_type', true );
        $staff_ids = (array) get_post_meta( $post->ID, '_sg365_staff_ids', true );
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<p><strong>' . esc_html__( 'Linked User', 'sg365-client-portal' ) . '</strong></p>';
        $users = get_users( array( 'fields'=>array('ID','user_login','user_email') ) );
        echo '<select class="widefat" name="_sg365_user_id"><option value="0">' . esc_html__( '— Select user —','sg365-client-portal') . '</option>';
        foreach($users as $u){
            printf('<option value="%d"%s>%s (%s)</option>', (int)$u->ID, selected($user_id,(int)$u->ID,false), esc_html($u->user_login), esc_html($u->user_email));
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Phone', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_phone" value="' . esc_attr($phone) . '" />';
        echo '<p><strong>' . esc_html__( 'Plan Type', 'sg365-client-portal' ) . '</strong></p>';
        $plans = array( 'salary' => __( 'Salary-based', 'sg365-client-portal' ), 'project' => __( 'Project-based', 'sg365-client-portal' ), 'maintenance' => __( 'Maintenance', 'sg365-client-portal' ) );
        echo '<select class="widefat" name="_sg365_plan_type">';
        foreach ( $plans as $k => $lbl ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $plan, $k, false ), esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Assigned Staff', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            printf( '<option value="%d"%s>%s</option>', (int) $member->ID, in_array( (int) $member->ID, $staff_ids, true ) ? ' selected' : '', esc_html( $member->post_title ) );
        }
        echo '</select>';
    }

    public function site_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $type = (string) get_post_meta( $post->ID, '_sg365_type', true );
        $plan = (string) get_post_meta( $post->ID, '_sg365_plan', true );
        $services = (array) get_post_meta( $post->ID, '_sg365_services', true );
        $last_activity = (string) get_post_meta( $post->ID, '_sg365_last_activity_date', true );
        $next_update = (string) get_post_meta( $post->ID, '_sg365_next_update_date', true );
        $staff_ids = (array) get_post_meta( $post->ID, '_sg365_staff_ids', true );
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Service Type', 'sg365-client-portal' ) . '</strong></p>';
        $types = sg365_cp_get_service_types();
        echo '<select class="widefat" name="_sg365_type">';
        foreach($types as $k=>$data){
            $lbl = is_array( $data ) ? ( $data['label'] ?? $k ) : $data;
            printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($type,$k,false), esc_html($lbl));
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Included Services', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_services[]" multiple>';
        foreach ( $types as $k => $data ) {
            $lbl = is_array( $data ) ? ( $data['label'] ?? $k ) : $data;
            printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), in_array( $k, $services, true ) ? ' selected' : '', esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Plan', 'sg365-client-portal' ) . '</strong></p>';
        $plans = array('monthly'=>'Monthly','one_time'=>'One-time');
        echo '<select class="widefat" name="_sg365_plan">';
        foreach($plans as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($plan,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Last Activity Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_last_activity_date" value="' . esc_attr( $last_activity ) . '" />';
        echo '<p><strong>' . esc_html__( 'Next Expected Update Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_next_update_date" value="' . esc_attr( $next_update ) . '" />';
        echo '<p><strong>' . esc_html__( 'Assigned Staff', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            printf( '<option value="%d"%s>%s</option>', (int) $member->ID, in_array( (int) $member->ID, $staff_ids, true ) ? ' selected' : '', esc_html( $member->post_title ) );
        }
        echo '</select>';
    }

    public function project_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $ptype = (string) get_post_meta( $post->ID, '_sg365_project_type', true );
        $amount = (string) get_post_meta( $post->ID, '_sg365_amount', true );
        $progress = (int) get_post_meta( $post->ID, '_sg365_project_progress', true );
        $last_activity = (string) get_post_meta( $post->ID, '_sg365_last_activity_date', true );
        $next_update = (string) get_post_meta( $post->ID, '_sg365_next_update_date', true );
        $assigned_sites = (array) get_post_meta( $post->ID, '_sg365_project_sites', true );
        $assigned_services = (array) get_post_meta( $post->ID, '_sg365_project_services', true );
        $sites = get_posts( array( 'post_type' => 'sg365_site', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        $types = sg365_cp_get_service_types();
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Project Type', 'sg365-client-portal' ) . '</strong></p>';
        $types = array('monthly'=>'Monthly (Plan)','one_time'=>'One-time');
        echo '<select class="widefat" name="_sg365_project_type">';
        foreach($types as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($ptype,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Amount (optional)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_amount" value="' . esc_attr($amount) . '" placeholder="15000" />';
        echo '<p><strong>' . esc_html__( 'Progress (0-100)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="number" name="_sg365_project_progress" min="0" max="100" value="' . esc_attr( $progress ) . '" />';
        echo '<p><strong>' . esc_html__( 'Assigned Domains', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_project_sites[]" multiple>';
        foreach ( $sites as $site ) {
            printf( '<option value="%d"%s>%s</option>', (int) $site->ID, in_array( (int) $site->ID, $assigned_sites, true ) ? ' selected' : '', esc_html( $site->post_title ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Assigned Service Types', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_project_services[]" multiple>';
        foreach ( $types as $k => $data ) {
            $lbl = is_array( $data ) ? ( $data['label'] ?? $k ) : $data;
            printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), in_array( $k, $assigned_services, true ) ? ' selected' : '', esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Last Activity Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_last_activity_date" value="' . esc_attr( $last_activity ) . '" />';
        echo '<p><strong>' . esc_html__( 'Next Expected Update Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_next_update_date" value="' . esc_attr( $next_update ) . '" />';
    }

    public function worklog_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $site_id = (int) get_post_meta( $post->ID, '_sg365_site_id', true );
        $project_id = (int) get_post_meta( $post->ID, '_sg365_project_id', true );
        $cat = (string) get_post_meta( $post->ID, '_sg365_category', true );
        $visible = (int) get_post_meta( $post->ID, '_sg365_visible_client', true );
        $date = (string) get_post_meta( $post->ID, '_sg365_log_date', true );
        $attachments = (string) get_post_meta( $post->ID, '_sg365_attachments', true );
        $staff_ids = (array) get_post_meta( $post->ID, '_sg365_staff_ids', true );
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        if(!$date){ $date = current_time('Y-m-d'); }
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Domain/Site (required)', 'sg365-client-portal' ) . '</strong></p>';
        $this->sites($site_id, $client_id);
        echo '<p><strong>' . esc_html__( 'Project (optional)', 'sg365-client-portal' ) . '</strong></p>';
        $this->projects($project_id, $client_id);
        echo '<p><strong>' . esc_html__( 'Category', 'sg365-client-portal' ) . '</strong></p>';
        $cats = sg365_cp_get_service_types();
        echo '<select class="widefat" name="_sg365_category">';
        foreach($cats as $k=>$data){
            $lbl = is_array( $data ) ? ( $data['label'] ?? $k ) : $data;
            printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($cat,$k,false), esc_html($lbl));
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_log_date" value="' . esc_attr($date) . '" />';
        echo '<p><label><input type="checkbox" name="_sg365_visible_client" value="1" ' . checked(1,$visible,false) . ' /> ' . esc_html__( 'Visible to client in My Account', 'sg365-client-portal' ) . '</label></p>';
        echo '<p><strong>' . esc_html__( 'Attachments (URLs, comma-separated)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_attachments" value="' . esc_attr( $attachments ) . '" placeholder="https://..." />';
        echo '<p><strong>' . esc_html__( 'Assigned Staff', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            printf( '<option value="%d"%s>%s</option>', (int) $member->ID, in_array( (int) $member->ID, $staff_ids, true ) ? ' selected' : '', esc_html( $member->post_title ) );
        }
        echo '</select>';
    }

    public function staff_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $role = (string) get_post_meta( $post->ID, '_sg365_role', true );
        $salary = (string) get_post_meta( $post->ID, '_sg365_monthly_salary', true );
        $email = (string) get_post_meta( $post->ID, '_sg365_staff_email', true );
        echo '<p><strong>' . esc_html__( 'Role', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_role" value="' . esc_attr($role) . '" placeholder="Developer" />';
        echo '<p><strong>' . esc_html__( 'Monthly Salary', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_monthly_salary" value="' . esc_attr($salary) . '" placeholder="25000" />';
        echo '<p><strong>' . esc_html__( 'Email', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_staff_email" value="' . esc_attr($email) . '" placeholder="staff@example.com" />';
    }

    public function salary_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $month = (string) get_post_meta( $post->ID, '_sg365_month', true );
        if(!$month){ $month = gmdate('Y-m'); }
        $staff_id = (int) get_post_meta( $post->ID, '_sg365_staff_id', true );
        $base = (string) get_post_meta( $post->ID, '_sg365_base', true );
        $bonus = (string) get_post_meta( $post->ID, '_sg365_bonus', true );
        $ded = (string) get_post_meta( $post->ID, '_sg365_deduction', true );
        $paid = (int) get_post_meta( $post->ID, '_sg365_paid', true );
        $note = (string) get_post_meta( $post->ID, '_sg365_note', true );
        $direction = (string) get_post_meta( $post->ID, '_sg365_direction', true );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $due_date = (string) get_post_meta( $post->ID, '_sg365_due_date', true );
        $amount = (string) get_post_meta( $post->ID, '_sg365_amount', true );
        $status = (string) get_post_meta( $post->ID, '_sg365_payment_status', true );

        $staff = get_posts(array('post_type'=>'sg365_staff','numberposts'=>300,'orderby'=>'title','order'=>'ASC'));
        $clients = get_posts(array('post_type'=>'sg365_client','numberposts'=>300,'orderby'=>'title','order'=>'ASC'));

        echo '<p><strong>' . esc_html__( 'Month', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="month" name="_sg365_month" value="' . esc_attr($month) . '" />';
        echo '<p><strong>' . esc_html__( 'Direction', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_direction">';
        $dirs = array( 'incoming' => __( 'Incoming (from clients)', 'sg365-client-portal' ), 'outgoing' => __( 'Outgoing (to staff)', 'sg365-client-portal' ) );
        foreach ( $dirs as $k => $lbl ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $direction, $k, false ), esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Client (for incoming)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_client_id"><option value="0">—</option>';
        foreach($clients as $c){ printf('<option value="%d"%s>%s</option>', (int)$c->ID, selected($client_id,(int)$c->ID,false), esc_html($c->post_title)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Staff', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_id"><option value="0">—</option>';
        foreach($staff as $s){ printf('<option value="%d"%s>%s</option>', (int)$s->ID, selected($staff_id,(int)$s->ID,false), esc_html($s->post_title)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Due Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_due_date" value="' . esc_attr( $due_date ) . '" />';
        echo '<p><strong>' . esc_html__( 'Amount', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_amount" value="' . esc_attr( $amount ) . '" placeholder="25000" />';

        echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:10px">';
        echo '<div><strong>Base</strong><input class="widefat" name="_sg365_base" value="' . esc_attr($base) . '"/></div>';
        echo '<div><strong>Bonus</strong><input class="widefat" name="_sg365_bonus" value="' . esc_attr($bonus) . '"/></div>';
        echo '<div><strong>Deduction/Advance</strong><input class="widefat" name="_sg365_deduction" value="' . esc_attr($ded) . '"/></div>';
        echo '</div>';

        echo '<p style="margin-top:10px"><label><input type="checkbox" name="_sg365_paid" value="1" ' . checked(1,$paid,false) . ' /> ' . esc_html__('Marked as paid','sg365-client-portal') . '</label></p>';
        echo '<p><strong>' . esc_html__( 'Payment Status', 'sg365-client-portal' ) . '</strong></p>';
        $statuses = array( 'unpaid' => __( 'Unpaid', 'sg365-client-portal' ), 'due_soon' => __( 'Due Soon', 'sg365-client-portal' ), 'overdue' => __( 'Overdue', 'sg365-client-portal' ), 'paid' => __( 'Paid', 'sg365-client-portal' ) );
        echo '<select class="widefat" name="_sg365_payment_status">';
        foreach ( $statuses as $k => $lbl ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $status, $k, false ), esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Payment Note / UTR', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_note" value="' . esc_attr($note) . '" placeholder="UTR / Cash / Bank..." />';
    }

    public function save( int $post_id, WP_Post $post ): void {
        if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) { return; }
        if ( empty($_POST['sg365_cp_nonce']) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['sg365_cp_nonce'])), 'sg365_cp_meta' ) ) { return; }
        if ( ! current_user_can('sg365_cp_manage') ) { return; }

        $map = array(
            'sg365_client' => array('_sg365_user_id'=>'int','_sg365_phone'=>'text','_sg365_plan_type'=>'text','_sg365_staff_ids'=>'array_int'),
            'sg365_site' => array('_sg365_client_id'=>'int','_sg365_type'=>'text','_sg365_plan'=>'text','_sg365_services'=>'array_text','_sg365_last_activity_date'=>'text','_sg365_next_update_date'=>'text','_sg365_staff_ids'=>'array_int'),
            'sg365_project' => array('_sg365_client_id'=>'int','_sg365_project_type'=>'text','_sg365_amount'=>'money','_sg365_project_progress'=>'int','_sg365_project_sites'=>'array_int','_sg365_project_services'=>'array_text','_sg365_last_activity_date'=>'text','_sg365_next_update_date'=>'text'),
            'sg365_worklog' => array('_sg365_client_id'=>'int','_sg365_site_id'=>'int','_sg365_project_id'=>'int','_sg365_category'=>'text','_sg365_visible_client'=>'bool','_sg365_log_date'=>'text','_sg365_attachments'=>'text','_sg365_staff_ids'=>'array_int'),
            'sg365_staff' => array('_sg365_role'=>'text','_sg365_monthly_salary'=>'money','_sg365_staff_email'=>'text'),
            'sg365_salary' => array('_sg365_month'=>'text','_sg365_staff_id'=>'int','_sg365_base'=>'money','_sg365_bonus'=>'money','_sg365_deduction'=>'money','_sg365_paid'=>'bool','_sg365_note'=>'text','_sg365_direction'=>'text','_sg365_client_id'=>'int','_sg365_due_date'=>'text','_sg365_amount'=>'money','_sg365_payment_status'=>'text'),
        );

        if ( empty($map[$post->post_type]) ) { return; }

        foreach($map[$post->post_type] as $key=>$type){
            $val = $_POST[$key] ?? null;
            if ( $type === 'array_text' ) {
                $vals = isset( $_POST[ $key ] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ $key ] ) ) : array();
                update_post_meta( $post_id, $key, $vals );
            } elseif ( $type === 'array_int' ) {
                $vals = isset( $_POST[ $key ] ) ? array_map( 'intval', (array) wp_unslash( $_POST[ $key ] ) ) : array();
                update_post_meta( $post_id, $key, $vals );
            } elseif($type==='int'){ update_post_meta($post_id,$key, sg365_cp_clean_int($val)); }
            elseif($type==='bool'){ update_post_meta($post_id,$key, sg365_cp_clean_bool($val)); }
            elseif($type==='money'){ update_post_meta($post_id,$key, sg365_cp_clean_money($val)); }
            else { update_post_meta($post_id,$key, sg365_cp_clean_text($val)); }
        }

        if ( 'sg365_worklog' === $post->post_type ) {
            $client_id = (int) get_post_meta($post_id,'_sg365_client_id',true);
            $client_user_id = $client_id ? sg365_cp_get_user_id_for_client($client_id) : 0;
            update_post_meta($post_id,'_sg365_client_user_id',(int)$client_user_id);
        }
    }
}
