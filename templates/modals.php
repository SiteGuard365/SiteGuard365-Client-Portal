<?php
/**
 * Shared modals.
 *
 * @package SG365_Dashboard_Suite
 */

?>
<div class="sg365-modal" data-sg365-modal>
	<div class="sg365-modal__content">
		<h3><?php esc_html_e( 'Create worklog', 'sg365-dashboard-suite' ); ?></h3>
		<input type="number" min="1" placeholder="<?php esc_attr_e( 'Client ID', 'sg365-dashboard-suite' ); ?>" data-sg365-worklog-client>
		<input type="text" placeholder="<?php esc_attr_e( 'Title', 'sg365-dashboard-suite' ); ?>" data-sg365-worklog-title>
		<textarea rows="3" placeholder="<?php esc_attr_e( 'Details', 'sg365-dashboard-suite' ); ?>" data-sg365-worklog-details></textarea>
		<textarea rows="3" placeholder="<?php esc_attr_e( 'Internal notes', 'sg365-dashboard-suite' ); ?>" data-sg365-worklog-notes></textarea>
		<input type="number" min="1" placeholder="<?php esc_attr_e( 'Minutes', 'sg365-dashboard-suite' ); ?>" data-sg365-worklog-minutes>
		<label>
			<input type="checkbox" data-sg365-worklog-visible>
			<?php esc_html_e( 'Visible to client', 'sg365-dashboard-suite' ); ?>
		</label>
		<div class="sg365-modal__actions">
			<button type="button" class="button button-primary" data-sg365-submit-worklog><?php esc_html_e( 'Save worklog', 'sg365-dashboard-suite' ); ?></button>
			<button type="button" class="button" data-sg365-close-modal><?php esc_html_e( 'Close', 'sg365-dashboard-suite' ); ?></button>
		</div>
	</div>
</div>
