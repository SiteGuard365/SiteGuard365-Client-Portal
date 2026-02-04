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
        $plan_type = (string) get_post_meta( $post->ID, '_sg365_plan_type', true );
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
        $plans = array(
            'salary'      => __( 'Salary-based plan', 'sg365-client-portal' ),
            'project'     => __( 'Project-based plan', 'sg365-client-portal' ),
            'maintenance' => __( 'Maintenance plan', 'sg365-client-portal' ),
        );
        echo '<select class="widefat" name="_sg365_plan_type">';
        foreach ( $plans as $key => $label ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $plan_type, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Assigned Staff (reporting only)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            $selected = in_array( $member->ID, $staff_ids, true ) ? 'selected' : '';
            printf( '<option value="%d" %s>%s</option>', (int) $member->ID, $selected, esc_html( $member->post_title ) );
        }
        echo '</select>';
    }

    public function site_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $type = (string) get_post_meta( $post->ID, '_sg365_type', true );
        $plan = (string) get_post_meta( $post->ID, '_sg365_plan', true );
        $next_update = (string) get_post_meta( $post->ID, '_sg365_next_update', true );
        $services = (array) get_post_meta( $post->ID, '_sg365_services', true );
        $staff_ids = (array) get_post_meta( $post->ID, '_sg365_staff_ids', true );
        $service_types = sg365_cp_get_service_types();
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Service Type', 'sg365-client-portal' ) . '</strong></p>';
        $types = array();
        foreach ( $service_types as $item ) {
            $types[ $item['key'] ] = $item['label'];
        }
        echo '<select class="widefat" name="_sg365_type">';
        foreach($types as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($type,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Included Services', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_services[]" multiple>';
        foreach ( $types as $k => $lbl ) {
            $selected = in_array( $k, $services, true ) ? 'selected' : '';
            printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), $selected, esc_html( $lbl ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Plan', 'sg365-client-portal' ) . '</strong></p>';
        $plans = array('monthly'=>'Monthly','one_time'=>'One-time');
        echo '<select class="widefat" name="_sg365_plan">';
        foreach($plans as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($plan,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Next expected update date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_next_update" value="' . esc_attr( $next_update ) . '" />';
        echo '<p><strong>' . esc_html__( 'Assigned Staff (reporting only)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            $selected = in_array( $member->ID, $staff_ids, true ) ? 'selected' : '';
            printf( '<option value="%d" %s>%s</option>', (int) $member->ID, $selected, esc_html( $member->post_title ) );
        }
        echo '</select>';
    }

    public function project_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $ptype = (string) get_post_meta( $post->ID, '_sg365_project_type', true );
        $amount = (string) get_post_meta( $post->ID, '_sg365_amount', true );
        $progress = (int) get_post_meta( $post->ID, '_sg365_progress', true );
        $status = (string) get_post_meta( $post->ID, '_sg365_project_status', true );
        $next_update = (string) get_post_meta( $post->ID, '_sg365_next_update', true );
        $completed_date = (string) get_post_meta( $post->ID, '_sg365_completed_date', true );
        $service_types = sg365_cp_get_service_types();
        $services = (array) get_post_meta( $post->ID, '_sg365_services', true );
        $assigned_sites = (array) get_post_meta( $post->ID, '_sg365_site_ids', true );
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Project Type', 'sg365-client-portal' ) . '</strong></p>';
        $types = array('monthly'=>'Monthly (Plan)','one_time'=>'One-time');
        echo '<select class="widefat" name="_sg365_project_type">';
        foreach($types as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($ptype,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Assigned Domains', 'sg365-client-portal' ) . '</strong></p>';
        $sites = get_posts( array( 'post_type'=>'sg365_site', 'numberposts'=>500, 'orderby'=>'title', 'order'=>'ASC' ) );
        echo '<select class="widefat" name="_sg365_site_ids[]" multiple>';
        foreach ( $sites as $site ) {
            $selected = in_array( $site->ID, $assigned_sites, true ) ? 'selected' : '';
            printf( '<option value="%d" %s>%s</option>', (int) $site->ID, $selected, esc_html( $site->post_title ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Assigned Service Types', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_services[]" multiple>';
        foreach ( $service_types as $service ) {
            $selected = in_array( $service['key'], $services, true ) ? 'selected' : '';
            printf( '<option value="%s" %s>%s</option>', esc_attr( $service['key'] ), $selected, esc_html( $service['label'] ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Amount (optional)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_amount" value="' . esc_attr($amount) . '" placeholder="15000" />';
        echo '<p><strong>' . esc_html__( 'Progress (0–100)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="number" min="0" max="100" name="_sg365_progress" value="' . esc_attr( $progress ) . '" />';
        echo '<p><strong>' . esc_html__( 'Status', 'sg365-client-portal' ) . '</strong></p>';
        $statuses = array( 'active' => __( 'Active', 'sg365-client-portal' ), 'completed' => __( 'Completed', 'sg365-client-portal' ) );
        echo '<select class="widefat" name="_sg365_project_status">';
        foreach ( $statuses as $key => $label ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $status, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Next expected update date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_next_update" value="' . esc_attr( $next_update ) . '" />';
        echo '<p><strong>' . esc_html__( 'Completed date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_completed_date" value="' . esc_attr( $completed_date ) . '" />';
    }

    public function worklog_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $site_id = (int) get_post_meta( $post->ID, '_sg365_site_id', true );
        $project_id = (int) get_post_meta( $post->ID, '_sg365_project_id', true );
        $cat = (string) get_post_meta( $post->ID, '_sg365_category', true );
        $service_type = (string) get_post_meta( $post->ID, '_sg365_service_type', true );
        $visible = (int) get_post_meta( $post->ID, '_sg365_visible_client', true );
        $date = (string) get_post_meta( $post->ID, '_sg365_log_date', true );
        $staff_ids = (array) get_post_meta( $post->ID, '_sg365_staff_ids', true );
        $attachment_id = (int) get_post_meta( $post->ID, '_sg365_attachment_id', true );
        if(!$date){ $date = current_time('Y-m-d'); }
        $service_types = sg365_cp_get_service_types();
        $staff = get_posts( array( 'post_type' => 'sg365_staff', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
        echo '<p><strong>' . esc_html__( 'Client', 'sg365-client-portal' ) . '</strong></p>';
        $this->clients($client_id);
        echo '<p><strong>' . esc_html__( 'Domain/Site (required)', 'sg365-client-portal' ) . '</strong></p>';
        $this->sites($site_id, $client_id);
        echo '<p><strong>' . esc_html__( 'Project (optional)', 'sg365-client-portal' ) . '</strong></p>';
        $this->projects($project_id, $client_id);
        echo '<p><strong>' . esc_html__( 'Service Type', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_service_type">';
        foreach ( $service_types as $service ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $service['key'] ), selected( $service_type, $service['key'], false ), esc_html( $service['label'] ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Category', 'sg365-client-portal' ) . '</strong></p>';
        $cats = array('development'=>'Development','security'=>'Security','bugfix'=>'Bug Fix','support'=>'Support','seo'=>'SEO','content'=>'Content');
        echo '<select class="widefat" name="_sg365_category">';
        foreach($cats as $k=>$lbl){ printf('<option value="%s"%s>%s</option>', esc_attr($k), selected($cat,$k,false), esc_html($lbl)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_log_date" value="' . esc_attr($date) . '" />';
        echo '<p><strong>' . esc_html__( 'Staff Assigned', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_ids[]" multiple>';
        foreach ( $staff as $member ) {
            $selected = in_array( $member->ID, $staff_ids, true ) ? 'selected' : '';
            printf( '<option value="%d" %s>%s</option>', (int) $member->ID, $selected, esc_html( $member->post_title ) );
        }
        echo '</select>';
        if ( $attachment_id ) {
            echo '<p>' . esc_html__( 'Attachment:', 'sg365-client-portal' ) . ' <a href="' . esc_url( wp_get_attachment_url( $attachment_id ) ) . '" target="_blank">' . esc_html__( 'View', 'sg365-client-portal' ) . '</a></p>';
        }
        echo '<p><label><input type="checkbox" name="_sg365_visible_client" value="1" ' . checked(1,$visible,false) . ' /> ' . esc_html__( 'Visible to client in My Account', 'sg365-client-portal' ) . '</label></p>';
    }

    public function staff_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $role = (string) get_post_meta( $post->ID, '_sg365_role', true );
        $salary = (string) get_post_meta( $post->ID, '_sg365_monthly_salary', true );
        echo '<p><strong>' . esc_html__( 'Role', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_role" value="' . esc_attr($role) . '" placeholder="Developer" />';
        echo '<p><strong>' . esc_html__( 'Monthly Salary', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_monthly_salary" value="' . esc_attr($salary) . '" placeholder="25000" />';
    }

    public function salary_box( WP_Post $post ): void {
        wp_nonce_field( 'sg365_cp_meta', 'sg365_cp_nonce' );
        $month = (string) get_post_meta( $post->ID, '_sg365_month', true );
        if(!$month){ $month = gmdate('Y-m'); }
        $staff_id = (int) get_post_meta( $post->ID, '_sg365_staff_id', true );
        $client_id = (int) get_post_meta( $post->ID, '_sg365_client_id', true );
        $direction = (string) get_post_meta( $post->ID, '_sg365_salary_direction', true );
        $due_date = (string) get_post_meta( $post->ID, '_sg365_due_date', true );
        $base = (string) get_post_meta( $post->ID, '_sg365_base', true );
        $bonus = (string) get_post_meta( $post->ID, '_sg365_bonus', true );
        $ded = (string) get_post_meta( $post->ID, '_sg365_deduction', true );
        $paid = (int) get_post_meta( $post->ID, '_sg365_paid', true );
        $note = (string) get_post_meta( $post->ID, '_sg365_note', true );

        $staff = get_posts(array('post_type'=>'sg365_staff','numberposts'=>300,'orderby'=>'title','order'=>'ASC'));
        $clients = get_posts(array('post_type'=>'sg365_client','numberposts'=>300,'orderby'=>'title','order'=>'ASC'));

        echo '<p><strong>' . esc_html__( 'Month', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="month" name="_sg365_month" value="' . esc_attr($month) . '" />';
        echo '<p><strong>' . esc_html__( 'Salary Direction', 'sg365-client-portal' ) . '</strong></p>';
        $directions = array( 'incoming' => __( 'Incoming (from client)', 'sg365-client-portal' ), 'outgoing' => __( 'Outgoing (to staff)', 'sg365-client-portal' ) );
        echo '<select class="widefat" name="_sg365_salary_direction">';
        foreach ( $directions as $key => $label ) {
            printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $direction, $key, false ), esc_html( $label ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Client (incoming only)', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_client_id"><option value="0">—</option>';
        foreach ( $clients as $client ) {
            printf( '<option value="%d"%s>%s</option>', (int) $client->ID, selected( $client_id, (int) $client->ID, false ), esc_html( $client->post_title ) );
        }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Staff', 'sg365-client-portal' ) . '</strong></p>';
        echo '<select class="widefat" name="_sg365_staff_id"><option value="0">—</option>';
        foreach($staff as $s){ printf('<option value="%d"%s>%s</option>', (int)$s->ID, selected($staff_id,(int)$s->ID,false), esc_html($s->post_title)); }
        echo '</select>';
        echo '<p><strong>' . esc_html__( 'Due date', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input type="date" name="_sg365_due_date" value="' . esc_attr( $due_date ) . '" />';

        echo '<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:10px">';
        echo '<div><strong>Base</strong><input class="widefat" name="_sg365_base" value="' . esc_attr($base) . '"/></div>';
        echo '<div><strong>Bonus</strong><input class="widefat" name="_sg365_bonus" value="' . esc_attr($bonus) . '"/></div>';
        echo '<div><strong>Deduction/Advance</strong><input class="widefat" name="_sg365_deduction" value="' . esc_attr($ded) . '"/></div>';
        echo '</div>';

        echo '<p style="margin-top:10px"><label><input type="checkbox" name="_sg365_paid" value="1" ' . checked(1,$paid,false) . ' /> ' . esc_html__('Marked as paid','sg365-client-portal') . '</label></p>';
        echo '<p><strong>' . esc_html__( 'Payment Note / UTR', 'sg365-client-portal' ) . '</strong></p>';
        echo '<input class="widefat" name="_sg365_note" value="' . esc_attr($note) . '" placeholder="UTR / Cash / Bank..." />';
    }

    public function save( int $post_id, WP_Post $post ): void {
        if ( wp_is_post_autosave($post_id) || wp_is_post_revision($post_id) ) { return; }
        if ( empty($_POST['sg365_cp_nonce']) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_POST['sg365_cp_nonce'])), 'sg365_cp_meta' ) ) { return; }
        if ( ! current_user_can('sg365_cp_manage') ) { return; }

        $map = array(
            'sg365_client' => array('_sg365_user_id'=>'int','_sg365_phone'=>'text','_sg365_plan_type'=>'text','_sg365_staff_ids'=>'array'),
            'sg365_site' => array('_sg365_client_id'=>'int','_sg365_type'=>'text','_sg365_plan'=>'text','_sg365_next_update'=>'text','_sg365_services'=>'array','_sg365_staff_ids'=>'array'),
            'sg365_project' => array('_sg365_client_id'=>'int','_sg365_project_type'=>'text','_sg365_amount'=>'money','_sg365_progress'=>'int','_sg365_project_status'=>'text','_sg365_next_update'=>'text','_sg365_completed_date'=>'text','_sg365_services'=>'array','_sg365_site_ids'=>'array'),
            'sg365_worklog' => array('_sg365_client_id'=>'int','_sg365_site_id'=>'int','_sg365_project_id'=>'int','_sg365_category'=>'text','_sg365_service_type'=>'text','_sg365_visible_client'=>'bool','_sg365_log_date'=>'text','_sg365_staff_ids'=>'array','_sg365_attachment_id'=>'int'),
            'sg365_staff' => array('_sg365_role'=>'text','_sg365_monthly_salary'=>'money'),
            'sg365_salary' => array('_sg365_month'=>'text','_sg365_staff_id'=>'int','_sg365_client_id'=>'int','_sg365_salary_direction'=>'text','_sg365_due_date'=>'text','_sg365_base'=>'money','_sg365_bonus'=>'money','_sg365_deduction'=>'money','_sg365_paid'=>'bool','_sg365_note'=>'text'),
        );

        if ( empty($map[$post->post_type]) ) { return; }

        foreach($map[$post->post_type] as $key=>$type){
            $val = $_POST[$key] ?? null;
            if($type==='int'){ update_post_meta($post_id,$key, sg365_cp_clean_int($val)); }
            elseif($type==='bool'){ update_post_meta($post_id,$key, sg365_cp_clean_bool($val)); }
            elseif($type==='money'){ update_post_meta($post_id,$key, sg365_cp_clean_money($val)); }
            elseif($type==='array'){
                $items = array();
                if ( is_array( $val ) ) {
                    foreach ( $val as $item ) {
                        $items[] = sanitize_text_field( wp_unslash( (string) $item ) );
                    }
                }
                update_post_meta( $post_id, $key, $items );
            } else { update_post_meta($post_id,$key, sg365_cp_clean_text($val)); }
        }

        if ( 'sg365_worklog' === $post->post_type ) {
            $client_id = (int) get_post_meta($post_id,'_sg365_client_id',true);
            $client_user_id = $client_id ? sg365_cp_get_user_id_for_client($client_id) : 0;
            update_post_meta($post_id,'_sg365_client_user_id',(int)$client_user_id);
        }
    }
}
