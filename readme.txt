=== SiteGuard365 Client Portal (Work Logs & Salary) ===
Contributors: siteguard365
Tags: client portal, work logs, salary, woocommerce my account
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Admin utility plugin to manage Clients, Sites/Domains, Projects, Work Logs, and Monthly Salary Sheets. Includes a WooCommerce My Account portal tab for clients to view their sites, visible work logs, projects, and payments.

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/
2. Activate the plugin.
3. Create a Client and link it to a WooCommerce user.
4. Add Sites/Domains and Work Logs for that client.
5. Clients can view the portal in WooCommerce My Account (Settings -> enable if needed).

== Changelog ==
= 1.1.0 =
Added:
- New analytics-first SiteGuard365 Portal admin dashboard with AJAX tabs for Overview, Analytics, Pending & Due, This Month, Recent Work Logs, Email Center, and Settings (no full page reloads). 
- Email Center module with per-trigger enable/disable toggles, subject/body templates, day-based salary reminders, and a test email action.
- Service Types settings to add/edit/remove service categories and assign staff for reporting.
- AJAX work log modal with client-based domain loading, staff assignment, and optional attachments.
- Project progress tracking (0–100) with status, assigned domains, and service type mapping for dashboards and client portal.
Improved:
- Client portal menus now adapt to plan type (salary/project vs. maintenance) and add a dedicated dashboard landing view.
- My Active Sites now displays included services, next expected update date, and quick access to logs when available.
Fixed:
- WooCommerce My Account endpoint registration now includes query vars, with one-time rewrite flush on upgrade and an admin “Fix Portal Now” action for 404 recovery.
Changed:
- Monthly summaries and salary tracking remain totals-only (explicitly no hourly tracking).
