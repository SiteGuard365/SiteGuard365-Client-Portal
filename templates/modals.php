<?php
/**
 * Shared modals.
 *
 * @package SG365_Dashboard_Suite
 */
?>
<div class="sg365-modal" data-modal="worklog">
	<div class="sg365-modal-content">
		<h3><?php esc_html_e( 'Log Work', 'sg365-dashboard-suite' ); ?></h3>
		<label>
			<?php esc_html_e( 'Title', 'sg365-dashboard-suite' ); ?>
			<input type="text" name="worklog_title" />
		</label>
		<label>
			<?php esc_html_e( 'Details', 'sg365-dashboard-suite' ); ?>
			<textarea name="worklog_details"></textarea>
		</label>
		<div class="sg365-modal-actions">
			<button type="button" class="sg365-button" data-action="save-worklog">
				<?php esc_html_e( 'Save', 'sg365-dashboard-suite' ); ?>
			</button>
			<button type="button" class="sg365-button sg365-button-ghost" data-action="close-modal">
				<?php esc_html_e( 'Cancel', 'sg365-dashboard-suite' ); ?>
			</button>
		</div>
	</div>
</div>
