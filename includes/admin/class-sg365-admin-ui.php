<?php
/**
 * Admin UI renderer.
 *
 * @package SG365_Dashboard_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SG365_Admin_UI {
	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// Placeholder for future admin hooks.
	}

	/**
	 * Render admin screen.
	 *
	 * @return void
	 */
	public static function render() {
		$settings = SG365_Settings::get();
		?>
		<div class="sg365-admin">
			<header class="sg365-admin__header">
				<div>
					<h1><?php esc_html_e( 'SG365 Dashboard Suite', 'sg365-dashboard-suite' ); ?></h1>
					<p><?php esc_html_e( 'Configure branding, menus, notifications, and integrations.', 'sg365-dashboard-suite' ); ?></p>
				</div>
				<div class="sg365-admin__actions">
					<a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=sg365_export_settings' ) ); ?>">
						<?php esc_html_e( 'Export Settings', 'sg365-dashboard-suite' ); ?>
					</a>
					<button class="button button-secondary" data-sg365-reset><?php esc_html_e( 'Reset to defaults', 'sg365-dashboard-suite' ); ?></button>
					<button class="button button-primary" data-sg365-save><?php esc_html_e( 'Save Changes', 'sg365-dashboard-suite' ); ?></button>
				</div>
			</header>

			<section class="sg365-card">
				<h2><?php esc_html_e( 'General', 'sg365-dashboard-suite' ); ?></h2>
				<div class="sg365-grid">
					<label>
						<span><?php esc_html_e( 'Brand name', 'sg365-dashboard-suite' ); ?></span>
						<input type="text" data-sg365-field="brand_name" value="<?php echo esc_attr( $settings['brand_name'] ); ?>">
					</label>
					<label>
						<span><?php esc_html_e( 'Help URL', 'sg365-dashboard-suite' ); ?></span>
						<input type="url" data-sg365-field="help_url" value="<?php echo esc_url( $settings['help_url'] ); ?>">
					</label>
					<label>
						<span><?php esc_html_e( 'Notification polling (seconds)', 'sg365-dashboard-suite' ); ?></span>
						<input type="number" min="5" data-sg365-field="polling_interval" value="<?php echo esc_attr( $settings['polling_interval'] ); ?>">
					</label>
				</div>
			</section>

			<section class="sg365-card">
				<h2><?php esc_html_e( 'Client Portal Menu', 'sg365-dashboard-suite' ); ?></h2>
				<div class="sg365-menu-editor" data-sg365-menu="client_menu">
					<?php foreach ( $settings['client_menu'] as $item ) : ?>
						<div class="sg365-menu-item">
							<input type="text" value="<?php echo esc_attr( $item['label'] ); ?>" data-sg365-item="label">
							<input type="text" value="<?php echo esc_attr( $item['icon'] ); ?>" data-sg365-item="icon">
							<input type="text" value="<?php echo esc_attr( $item['slug'] ); ?>" data-sg365-item="slug">
							<label class="sg365-toggle">
								<input type="checkbox" data-sg365-item="hidden" <?php checked( ! empty( $item['hidden'] ) ); ?>>
								<span><?php esc_html_e( 'Hidden', 'sg365-dashboard-suite' ); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sg365-card">
				<h2><?php esc_html_e( 'Staff App Menu', 'sg365-dashboard-suite' ); ?></h2>
				<div class="sg365-menu-editor" data-sg365-menu="staff_menu">
					<?php foreach ( $settings['staff_menu'] as $item ) : ?>
						<div class="sg365-menu-item">
							<input type="text" value="<?php echo esc_attr( $item['label'] ); ?>" data-sg365-item="label">
							<input type="text" value="<?php echo esc_attr( $item['icon'] ); ?>" data-sg365-item="icon">
							<input type="text" value="<?php echo esc_attr( $item['slug'] ); ?>" data-sg365-item="slug">
							<label class="sg365-toggle">
								<input type="checkbox" data-sg365-item="hidden" <?php checked( ! empty( $item['hidden'] ) ); ?>>
								<span><?php esc_html_e( 'Hidden', 'sg365-dashboard-suite' ); ?></span>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sg365-card">
				<h2><?php esc_html_e( 'Tools', 'sg365-dashboard-suite' ); ?></h2>
				<div class="sg365-tools">
					<button class="button" data-sg365-import><?php esc_html_e( 'Import Settings', 'sg365-dashboard-suite' ); ?></button>
					<button class="button" data-sg365-rebuild><?php esc_html_e( 'Rebuild DB Indexes', 'sg365-dashboard-suite' ); ?></button>
				</div>
				<textarea class="sg365-import-area" data-sg365-import-area placeholder="<?php esc_attr_e( 'Paste settings JSON here...', 'sg365-dashboard-suite' ); ?>"></textarea>
			</section>
		</div>
		<?php
	}
}
