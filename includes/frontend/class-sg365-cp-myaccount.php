<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SG365_CP_MyAccount {
    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init(): void {
        add_action( 'init', array( $this, 'endpoint' ) );
        add_filter( 'query_vars', array( $this, 'query_vars' ) );
        add_filter( 'woocommerce_get_query_vars', array( $this, 'woo_query_vars' ) );
        add_filter( 'woocommerce_account_menu_items', array( $this, 'menu' ) );
        add_action( 'woocommerce_account_sg365-portal_endpoint', array( $this, 'render' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
    }

    public function endpoint(): void {
        if ( ! sg365_cp_is_woocommerce_active() ) { return; }
        add_rewrite_endpoint( 'sg365-portal', EP_ROOT | EP_PAGES );
    }

    public function query_vars( array $vars ): array {
        $vars[] = 'sg365-portal';
        return $vars;
    }

    public function woo_query_vars( array $vars ): array {
        $vars['sg365-portal'] = 'sg365-portal';
        return $vars;
    }

    public function menu( array $items ): array {
        if ( ! sg365_cp_is_woocommerce_active() ) { return $items; }
        if ( ! (bool) get_option( 'sg365_cp_enable_myaccount', true ) ) { return $items; }

        $client_id = sg365_cp_get_client_id_for_user( get_current_user_id() );
        $has_work_access = $client_id ? sg365_cp_client_has_work_access( $client_id, get_current_user_id() ) : false;

        $label = (string) get_option( 'sg365_cp_portal_label', 'SG365 Portal' );
        $new = array();
        foreach ( $items as $k => $v ) {
            $new[$k] = $v;
            if ( 'dashboard' === $k ) { $new['sg365-portal'] = $label; }
        }
        if ( ! isset($new['sg365-portal']) ) { $new['sg365-portal'] = $label; }

        return $new;
    }

    public function assets(): void {
        if ( function_exists('is_account_page') && is_account_page() ) {
            wp_enqueue_style( 'sg365-cp-frontend', SG365_CP_PLUGIN_URL . 'assets/css/frontend.css', array(), SG365_CP_VERSION );
        }
    }

    public function render(): void {
        if ( ! is_user_logged_in() ) { echo esc_html__('Please login.','sg365-client-portal'); return; }
        $user_id = get_current_user_id();
        $client_id = sg365_cp_get_client_id_for_user($user_id);
        if ( ! $client_id ) {
            echo '<div class="woocommerce-message">' . esc_html__('Your account is not linked to a client profile yet.','sg365-client-portal') . '</div>';
            return;
        }

        $has_work_access = sg365_cp_client_has_work_access( $client_id, $user_id );
        $tab = isset($_GET['tab']) ? sanitize_key(wp_unslash($_GET['tab'])) : 'dashboard';
        $allowed = $has_work_access ? array('dashboard','sites','logs','projects','payments') : array('dashboard','sites','payments');
        if(!in_array($tab,$allowed,true)) $tab='dashboard';

        $base = wc_get_account_endpoint_url('sg365-portal');

        echo '<div class="sg365-portal"><h3>' . esc_html__('SG365 Client Portal','sg365-client-portal') . '</h3>';
        echo '<div class="sg365-tabs">';
        $tabs = array('dashboard'=>__('Dashboard','sg365-client-portal'),'sites'=>__('My Active Sites','sg365-client-portal'));
        if ( $has_work_access ) {
            $tabs['logs'] = __('Work Logs','sg365-client-portal');
            $tabs['projects'] = __('Projects','sg365-client-portal');
        }
        $tabs['payments'] = __('Payments','sg365-client-portal');
        foreach($tabs as $k=>$lbl){
            $url = add_query_arg('tab',$k,$base);
            printf('<a class="sg365-tab %s" href="%s">%s</a>', $tab===$k?'is-active':'', esc_url($url), esc_html($lbl));
        }
        echo '</div>';

        if($tab==='dashboard'){ $this->dashboard($client_id, $has_work_access); }
        elseif($tab==='sites'){ $this->sites($client_id, $has_work_access); }
        elseif($tab==='logs' && $has_work_access){ $this->logs($client_id,$user_id); }
        elseif($tab==='projects' && $has_work_access){ $this->projects($client_id); }
        else { $this->payments(); }

        echo '</div>';
    }

    private function sites(int $client_id, bool $has_work_access): void {
        $sites = get_posts(array(
            'post_type'=>'sg365_site',
            'numberposts'=>200,
            'orderby'=>'title',
            'order'=>'ASC',
            'meta_query'=>array(array('key'=>'_sg365_client_id','value'=>$client_id)),
        ));
        $service_type_map = array();
        foreach ( sg365_cp_get_service_types() as $type ) {
            $service_type_map[ $type['key'] ] = $type['label'];
        }
        echo '<div class="sg365-cards">';
        if(!$sites){ echo '<p>'.esc_html__('No sites/domains found yet.','sg365-client-portal').'</p>'; }
        foreach($sites as $s){
            $type = (string) get_post_meta($s->ID,'_sg365_type',true);
            $plan = (string) get_post_meta($s->ID,'_sg365_plan',true);
            $services = (array) get_post_meta($s->ID,'_sg365_services',true);
            $next_update = (string) get_post_meta($s->ID,'_sg365_next_update',true);
            echo '<div class="sg365-card">';
            echo '<div class="sg365-card-title">'.esc_html($s->post_title).'</div>';
            echo '<div class="sg365-badges">';
            echo '<span class="sg365-badge">'.esc_html(ucfirst($type)).'</span>';
            echo '<span class="sg365-badge sg365-badge-soft">'.esc_html($plan==='one_time'?__('One-time','sg365-client-portal'):__('Monthly','sg365-client-portal')).'</span>';
            echo '</div></div>';
            echo '<div class="sg365-card-meta">';
            if ( $services ) {
                $labels = array();
                foreach ( $services as $service ) {
                    $labels[] = $service_type_map[ $service ] ?? ucfirst( $service );
                }
                echo '<p><strong>'.esc_html__('Included services','sg365-client-portal').':</strong> '.esc_html( implode( ', ', $labels ) ).'</p>';
            }
            if ( $next_update ) {
                echo '<p><strong>'.esc_html__('Next expected update','sg365-client-portal').':</strong> '.esc_html( $next_update ).'</p>';
            }
            if ( $has_work_access ) {
                echo '<p><a class="button" href="'.esc_url( add_query_arg( array( 'tab' => 'logs', 'site_id' => $s->ID ), wc_get_account_endpoint_url('sg365-portal') ) ).'">'.esc_html__('View Work Logs','sg365-client-portal').'</a></p>';
            }
            echo '</div>';
        }
        echo '</div>';
    }

    private function logs(int $client_id, int $user_id): void {
        $month = isset($_GET['month']) ? sg365_cp_month_key_from_input(sanitize_text_field(wp_unslash($_GET['month']))) : gmdate('Y-m');
        $site_filter = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;

        $meta = array(
            array('key'=>'_sg365_client_user_id','value'=>$user_id),
            array('key'=>'_sg365_visible_client','value'=>1),
        );
        if($site_filter){ $meta[] = array('key'=>'_sg365_site_id','value'=>$site_filter); }

        $logs = get_posts(array(
            'post_type'=>'sg365_worklog',
            'numberposts'=>200,
            'orderby'=>'date',
            'order'=>'DESC',
            'meta_query'=>$meta,
        ));
        $logs = array_filter($logs, function($p) use ($month){
            $d = (string) get_post_meta($p->ID,'_sg365_log_date',true);
            return strpos($d,$month.'-')===0;
        });

        $sites = get_posts(array('post_type'=>'sg365_site','numberposts'=>200,'orderby'=>'title','order'=>'ASC','meta_query'=>array(array('key'=>'_sg365_client_id','value'=>$client_id))));
        echo '<form class="sg365-filters" method="get">';
        echo '<input type="hidden" name="tab" value="logs" />';
        echo '<label>'.esc_html__('Month','sg365-client-portal').' <input type="month" name="month" value="'.esc_attr($month).'" /></label>';
        echo '<label>'.esc_html__('Site','sg365-client-portal').' <select name="site_id"><option value="0">'.esc_html__('All','sg365-client-portal').'</option>';
        foreach($sites as $s){ printf('<option value="%d"%s>%s</option>', (int)$s->ID, selected($site_filter,(int)$s->ID,false), esc_html($s->post_title)); }
        echo '</select></label>';
        echo '<button class="button" type="submit">'.esc_html__('Filter','sg365-client-portal').'</button></form>';

        if(!$logs){ echo '<p>'.esc_html__('No work logs found for the selected filters.','sg365-client-portal').'</p>'; return; }

        echo '<div class="sg365-list">';
        foreach($logs as $l){
            $date = (string) get_post_meta($l->ID,'_sg365_log_date',true);
            $cat  = (string) get_post_meta($l->ID,'_sg365_service_type',true);
            $site_id = (int) get_post_meta($l->ID,'_sg365_site_id',true);
            $site_title = $site_id ? get_the_title($site_id) : '';
            echo '<div class="sg365-list-item">';
            echo '<div class="sg365-li-head"><strong>'.esc_html(get_the_title($l)).'</strong>';
            echo '<span class="sg365-li-meta">'.esc_html($date).' • '.esc_html(ucfirst($cat)).($site_title?' • '.esc_html($site_title):'').'</span></div>';
            echo '<div class="sg365-li-body">'.wp_kses_post(wpautop($l->post_content)).'</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function projects(int $client_id): void {
        $projects = get_posts(array('post_type'=>'sg365_project','numberposts'=>200,'orderby'=>'date','order'=>'DESC','meta_query'=>array(array('key'=>'_sg365_client_id','value'=>$client_id))));
        if(!$projects){ echo '<p>'.esc_html__('No projects found yet.','sg365-client-portal').'</p>'; return; }
        echo '<div class="sg365-list">';
        foreach($projects as $p){
            $ptype = (string) get_post_meta($p->ID,'_sg365_project_type',true);
            $amount = (string) get_post_meta($p->ID,'_sg365_amount',true);
            $progress = (int) get_post_meta($p->ID,'_sg365_progress',true);
            echo '<div class="sg365-list-item"><div class="sg365-li-head"><strong>'.esc_html($p->post_title).'</strong>';
            echo '<span class="sg365-li-meta">'.esc_html(strtoupper($ptype)).($amount?' • ₹'.esc_html($amount):'').'</span></div>';
            if($p->post_content){ echo '<div class="sg365-li-body">'.wp_kses_post(wpautop($p->post_content)).'</div>'; }
            echo '<div class="sg365-progress-bar"><span style="width:'.esc_attr($progress).'%;"></span></div>';
            echo '<div class="sg365-progress-label">'.esc_html($progress).'%</div>';
            echo '</div>';
        }
        echo '</div>';
    }

    private function dashboard(int $client_id, bool $has_work_access): void {
        $sites = get_posts(array(
            'post_type'=>'sg365_site',
            'numberposts'=>10,
            'orderby'=>'title',
            'order'=>'ASC',
            'meta_query'=>array(array('key'=>'_sg365_client_id','value'=>$client_id)),
        ));
        $logs = get_posts(array(
            'post_type'=>'sg365_worklog',
            'numberposts'=>5,
            'orderby'=>'date',
            'order'=>'DESC',
            'meta_query'=>array(
                array('key'=>'_sg365_client_id','value'=>$client_id),
                array('key'=>'_sg365_visible_client','value'=>1),
            ),
        ));
        echo '<div class="sg365-dashboard-grid">';
        echo '<div class="sg365-card"><h4>'.esc_html__('Active Sites','sg365-client-portal').'</h4><p>'.esc_html( count( $sites ) ).'</p></div>';
        if ( $has_work_access ) {
            echo '<div class="sg365-card"><h4>'.esc_html__('Recent Work Logs','sg365-client-portal').'</h4><p>'.esc_html( count( $logs ) ).'</p></div>';
        }
        echo '</div>';
    }

    private function payments(): void {
        if(!sg365_cp_is_woocommerce_active()){ echo '<p>'.esc_html__('WooCommerce is required to show payments.','sg365-client-portal').'</p>'; return; }
        $orders = wc_get_orders(array('customer_id'=>get_current_user_id(),'limit'=>20,'orderby'=>'date','order'=>'DESC'));
        if(!$orders){ echo '<p>'.esc_html__('No orders found.','sg365-client-portal').'</p>'; return; }
        echo '<table class="shop_table shop_table_responsive my_account_orders"><thead><tr><th>'.esc_html__('Order','sg365-client-portal').'</th><th>'.esc_html__('Date','sg365-client-portal').'</th><th>'.esc_html__('Status','sg365-client-portal').'</th><th>'.esc_html__('Total','sg365-client-portal').'</th></tr></thead><tbody>';
        foreach($orders as $order){
            echo '<tr>';
            echo '<td><a href="'.esc_url($order->get_view_order_url()).'">#'.esc_html($order->get_order_number()).'</a></td>';
            echo '<td>'.esc_html(wc_format_datetime($order->get_date_created())).'</td>';
            echo '<td>'.esc_html(wc_get_order_status_name($order->get_status())).'</td>';
            echo '<td>'.wp_kses_post($order->get_formatted_order_total()).'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
}
