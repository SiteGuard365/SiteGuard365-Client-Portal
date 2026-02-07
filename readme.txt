=== SiteGuard365 Dashboard Suite ===
Contributors: siteguard365
Tags: client portal, staff dashboard, woocommerce, rest api, stripe style
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stripe-style client portal and staff dashboard with REST-first data storage, custom tables, and WooCommerce My Account integration.

== Installation ==
1. Upload the plugin folder to /wp-content/plugins/
2. Activate the plugin.
3. Ensure WooCommerce is active to enable the My Account endpoint.
4. Configure SG365 Dashboard settings in the admin menu.

== Demo Pages ==
1. Create a page named "Client Portal" and add the shortcode: [sg365_client_app]
2. Create a page named "Staff Dashboard" and add the shortcode: [sg365_staff_app]
3. The My Account endpoint is added automatically at /my-account/sg365-portal

== REST API ==
All endpoints are under /wp-json/sg365/v1 and require authentication and permissions.

== Changelog ==
= 1.0.0 =
- Initial release of SiteGuard365 Dashboard Suite.
