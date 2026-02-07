<?php
/**
 * Client portal shell template.
 *
 * @package SG365_Dashboard_Suite
 */

?>
<div class="sg365-shell">
	<aside class="sg365-sidebar">
		<div class="sg365-sidebar__brand">
			<img src="<?php echo esc_url( SG365_DS_PLUGIN_URL . 'assets/img/logo.svg' ); ?>" alt="<?php echo esc_attr( $settings['brand_name'] ); ?>">
		</div>
		<ul class="sg365-sidebar__menu">
			<?php foreach ( $settings['client_menu'] as $item ) : ?>
				<?php if ( ! empty( $item['hidden'] ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<li>
					<button type="button">
						<span class="<?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php echo esc_html( $item['label'] ); ?>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>
	<div class="sg365-content">
		<div class="sg365-topbar">
			<div class="sg365-topbar__search">
				<input type="search" placeholder="<?php esc_attr_e( 'Search...', 'sg365-dashboard-suite' ); ?>">
			</div>
			<div class="sg365-topbar__actions">
				<button type="button" aria-label="<?php esc_attr_e( 'App grid', 'sg365-dashboard-suite' ); ?>">▦</button>
				<a href="<?php echo esc_url( $settings['help_url'] ); ?>" target="_blank" rel="noopener noreferrer" class="button"><?php esc_html_e( 'Help', 'sg365-dashboard-suite' ); ?></a>
				<div class="sg365-notifications">
					<button type="button">🔔</button>
					<span class="sg365-notifications__badge"></span>
				</div>
				<button type="button" aria-label="<?php esc_attr_e( 'Settings', 'sg365-dashboard-suite' ); ?>">⚙️</button>
			</div>
		</div>
		<div class="sg365-content__inner">
			<div class="sg365-card">
				<h2><?php esc_html_e( 'Your overview', 'sg365-dashboard-suite' ); ?></h2>
				<p><?php esc_html_e( 'Sites', 'sg365-dashboard-suite' ); ?>: <strong class="sg365-dashboard__sites">0</strong></p>
				<p><?php esc_html_e( 'Projects', 'sg365-dashboard-suite' ); ?>: <strong class="sg365-dashboard__projects">0</strong></p>
				<p><?php esc_html_e( 'Open tickets', 'sg365-dashboard-suite' ); ?>: <strong class="sg365-dashboard__tickets">0</strong></p>
			</div>
			<?php include SG365_DS_PLUGIN_DIR . 'templates/modals.php'; ?>
		</div>
	</div>
</div>
