<?php
/**
 * Admin UI renderer.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SG365_Admin_UI {
	public function render() {
		$settings = get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() );
		?>
		<div class="wrap sg365-admin">
			<h1><?php esc_html_e( 'SG365 Dashboard Suite', 'sg365-dashboard-suite' ); ?></h1>
			<div class="sg365-card">
				<h2><?php esc_html_e( 'Branding', 'sg365-dashboard-suite' ); ?></h2>
				<label>
					<?php esc_html_e( 'Brand Name', 'sg365-dashboard-suite' ); ?>
					<input type="text" id="sg365-brand-name" value="<?php echo esc_attr( $settings['brand_name'] ); ?>" />
				</label>
			</div>

			<div class="sg365-card">
				<h2><?php esc_html_e( 'Notifications', 'sg365-dashboard-suite' ); ?></h2>
				<label>
					<?php esc_html_e( 'Poll Interval (seconds)', 'sg365-dashboard-suite' ); ?>
					<input type="number" min="5" id="sg365-notification-interval" value="<?php echo esc_attr( $settings['notification_poll_interval'] ); ?>" />
				</label>
			</div>

			<div class="sg365-card">
				<h2><?php esc_html_e( 'Menus', 'sg365-dashboard-suite' ); ?></h2>
				<p><?php esc_html_e( 'Configure the client and staff menu labels in the app shell.', 'sg365-dashboard-suite' ); ?></p>
				<div class="sg365-menu-preview" data-menu="client"></div>
				<div class="sg365-menu-preview" data-menu="staff"></div>
			</div>

			<div class="sg365-card">
				<h2><?php esc_html_e( 'Tools', 'sg365-dashboard-suite' ); ?></h2>
				<button class="button button-secondary" id="sg365-reset-settings">
					<?php esc_html_e( 'Reset to Defaults', 'sg365-dashboard-suite' ); ?>
				</button>
				<button class="button button-primary" id="sg365-save-settings">
					<?php esc_html_e( 'Save Settings', 'sg365-dashboard-suite' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
