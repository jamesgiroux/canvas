=== Canvas ===
Contributors: yourname
Tags: framework, starter, react, rest-api, admin
Requires at least: 7.0
Tested up to: 7.0
Requires PHP: 8.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A starter framework for WordPress plugins with a React admin UI, REST API patterns, and a custom-table data layer.

== Description ==

Canvas is a scaffold for building admin-area WordPress plugins. It provides:

* A full-page React admin interface (dark sidebar, white content area).
* Single-responsibility PHP components wired through a small loader.
* A REST API base controller with auth, pagination, and sanitization helpers.
* An abstract model layer with CRUD, multisite isolation, JSON columns, an SQL-injection allowlist, and object-cache-backed caching.
* Version-controlled database migrations.
* A one-command initialization script that renames the scaffold to your plugin.

It is intended as a starting point, not an end-user plugin. After copying it,
run the initialization script to rebrand every identifier to your own plugin.

== Installation ==

1. Copy the plugin into `wp-content/plugins/`.
2. Run `python3 bin/canvas-init.py` to rename the scaffold to your plugin.
3. Run `composer install` and `npm install && npm run build`.
4. Activate the plugin in the WordPress admin.

== Frequently Asked Questions ==

= Is this an end-user plugin? =

No. It is a developer scaffold. Use the initialization script to turn it into
your own plugin.

== Changelog ==

= 1.0.0 =
* Initial release: React admin, REST API, model layer, migrations, and tooling.
