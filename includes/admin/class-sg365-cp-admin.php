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
        add_submenu_page( 'sg365-cp-dashboard', __( 'Staff', 'sg365-client-portal' ), __( 'Staff', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_staff' );
        add_submenu_page( 'sg365-cp-dashboard', __( 'Salary', 'sg365-client-portal' ), __( 'Salary', 'sg365-client-portal' ), 'sg365_cp_manage', 'edit.php?post_type=sg365_salary' );

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
    }

    public function assets( $hook ): void {
        if ( strpos( (string) $hook, 'sg365-cp' ) !== false ) {
            wp_enqueue_style( 'sg365-cp-admin', SG365_CP_PLUGIN_URL . 'assets/css/admin.css', array(), SG365_CP_VERSION );
        }
    }

    public function render_dashboard(): void {
        if ( ! sg365_cp_current_user_can_manage() ) { wp_die( esc_html__( 'Access denied.', 'sg365-client-portal' ) ); }
        $client_count = (int) wp_count_posts( 'sg365_client' )->publish;
        $site_count   = (int) wp_count_posts( 'sg365_site' )->publish;
        $log_count    = (int) wp_count_posts( 'sg365_worklog' )->publish;
        $project_count= (int) wp_count_posts( 'sg365_project' )->publish;
        ?>
        <div class="wrap sg365-cp-wrap">
            <h1><?php echo esc_html__( 'SiteGuard365 Portal', 'sg365-client-portal' ); ?></h1>
            <div class="sg365-cp-grid">
                <div class="sg365-cp-card"><h3><?php echo esc_html__( 'Clients', 'sg365-client-portal' ); ?></h3><p class="sg365-cp-stat"><?php echo esc_html( $client_count ); ?></p></div>
                <div class="sg365-cp-card"><h3><?php echo esc_html__( 'Sites/Domains', 'sg365-client-portal' ); ?></h3><p class="sg365-cp-stat"><?php echo esc_html( $site_count ); ?></p></div>
                <div class="sg365-cp-card"><h3><?php echo esc_html__( 'Projects', 'sg365-client-portal' ); ?></h3><p class="sg365-cp-stat"><?php echo esc_html( $project_count ); ?></p></div>
                <div class="sg365-cp-card"><h3><?php echo esc_html__( 'Work Logs', 'sg365-client-portal' ); ?></h3><p class="sg365-cp-stat"><?php echo esc_html( $log_count ); ?></p></div>
            </div>
            <p class="description"><?php echo esc_html__( 'Tip: Link a Client to a WooCommerce user to enable the My Account portal.', 'sg365-client-portal' ); ?></p>
        </div>
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
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
