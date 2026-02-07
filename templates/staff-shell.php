<?php
/**
 * Staff app shell template.
 *
 * @package SG365_Dashboard_Suite
 */

$settings = get_option( SG365_Settings::OPTION_KEY, SG365_Settings::default_settings() );
$brand = $settings['brand_name'] ?? 'SiteGuard365';
$menu = $settings['staff_menu'] ?? array();
?>
<div class="sg365-app sg365-staff" data-app="staff">
	<header class="sg365-topbar">
		<div class="sg365-brand">
			<?php echo esc_html( $brand ); ?>
		</div>
		<div class="sg365-search">
			<input type="search" placeholder="<?php esc_attr_e( 'Search', 'sg365-dashboard-suite' ); ?>" />
		</div>
		<div class="sg365-topbar-actions">
			<a href="<?php echo esc_url( $settings['help_url'] ?? '#' ); ?>" target="_blank" rel="noopener" class="sg365-icon">?</a>
			<button class="sg365-icon" type="button" data-action="notifications">🔔</button>
			<button class="sg365-icon" type="button" data-action="settings">⚙️</button>
			<button class="sg365-button" type="button" data-action="new-worklog"><?php esc_html_e( 'New Worklog', 'sg365-dashboard-suite' ); ?></button>
		</div>
	</header>
	<aside class="sg365-sidebar">
		<nav>
			<ul>
				<?php foreach ( $menu as $item ) : ?>
					<?php if ( empty( $item['enabled'] ) ) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<li>
						<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
						<?php echo esc_html( $item['label'] ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</aside>
	<main class="sg365-content">
		<div class="sg365-card">
			<h2><?php esc_html_e( 'Staff inbox', 'sg365-dashboard-suite' ); ?></h2>
			<p><?php esc_html_e( 'Track due work, tickets, and activity at a glance.', 'sg365-dashboard-suite' ); ?></p>
		</div>
	</main>
	<?php include SG365_DS_PLUGIN_DIR . 'templates/modals.php'; ?>
</div>
