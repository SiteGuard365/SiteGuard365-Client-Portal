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
- Analytics-first SG365 Portal dashboard with AJAX-loaded tabs for overview, analytics, pending & due, this month, recent work logs, email center shortcuts, and settings quick links (no full page reloads).
- Email Center module with per-trigger enable/disable controls, subject/body templates, HTML + fallback bodies, test email button, and duplicate-send suppression.
- Service Types manager under SG365 Settings to add, edit, delete, and assign staff to service types stored in sg365_service_types.
- Plan-aware client portal menus to show work-related sections only for salary/project plans (maintenance-only plans see a reduced menu).
- Project progress tracking field (0–100) surfaced in admin lists, client portal views, and analytics calculations.
- Work log modal (AJAX) for quick additions on the dashboard with client/domain dependency and staff assignment (no hourly tracking).
Improved:
- WooCommerce My Account endpoint registration, query var handling, and one-time rewrite flush on upgrade to prevent 404s.
- Dashboard KPI cards are clickable and feed analytics filters for faster insight navigation.
- Pending & due tables now surface expected next update dates and status labels for overdue vs on-time work.
Fixed:
- Portal 404 issue by properly registering the sg365-portal endpoint and adding an admin “Fix Portal Now” action to flush rewrites securely.
Changed:
- Service type selections now pull from configurable options instead of hard-coded lists.
- Salary reminders are monthly only with fixed amounts (explicitly no hourly tracking logic included).

= 1.0.0 =
- Initial release.
