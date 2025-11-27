<?php
/**
 * Plugin Installer
 *
 * Handles plugin activation, deactivation, and requirements checking.
 * This class provides a clean pattern for setting up database tables,
 * capabilities, and default options.
 *
 * @package Canvas
 */

namespace Canvas;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installer class.
 *
 * Static methods for activation and deactivation hooks.
 * Supports both single-site and multisite WordPress installations.
 */
class Installer {

	/**
	 * Custom capabilities to register.
	 *
	 * Format: 'capability_name' => 'Description for documentation'
	 *
	 * These capabilities are assigned to administrators on activation.
	 * Customize this array for your plugin's permission model.
	 *
	 * @var array<string, string>
	 */
	private static array $capabilities = array(
		'manage_canvas'       => 'Full access to Canvas plugin settings',
		'view_canvas'         => 'View Canvas dashboard and reports',
		'edit_canvas_content' => 'Create and edit Canvas content',
	);

	/**
	 * Activate the plugin.
	 *
	 * Called by register_activation_hook(). Handles both single-site
	 * and network-wide activation for multisite installations.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 * @return void
	 */
	public static function activate( bool $network_wide = false ): void {
		// Check minimum requirements first.
		self::check_requirements();

		if ( is_multisite() && $network_wide ) {
			// Network-wide activation: run for each site.
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				self::single_site_activate();
				restore_current_blog();
			}
		} else {
			// Single site activation.
			self::single_site_activate();
		}
	}

	/**
	 * Check plugin requirements.
	 *
	 * Verifies PHP and WordPress versions meet minimum requirements.
	 * Kills activation with a user-friendly message if requirements aren't met.
	 *
	 * @return void
	 */
	private static function check_requirements(): void {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, CANVAS_MIN_PHP, '<' ) ) {
			wp_die(
				sprintf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'Canvas requires PHP %1$s or higher. You are running PHP %2$s.', 'canvas' ),
					esc_html( CANVAS_MIN_PHP ),
					esc_html( PHP_VERSION )
				),
				esc_html__( 'Plugin Activation Error', 'canvas' ),
				array( 'back_link' => true )
			);
		}

		// Check WordPress version.
		global $wp_version;

		// Strip any suffix (like -RC1, -beta1) for comparison.
		$clean_wp_version = preg_replace( '/-.+$/', '', $wp_version );

		if ( version_compare( $clean_wp_version, CANVAS_MIN_WP, '<' ) ) {
			wp_die(
				sprintf(
					/* translators: 1: Required WordPress version, 2: Current WordPress version */
					esc_html__( 'Canvas requires WordPress %1$s or higher. You are running WordPress %2$s.', 'canvas' ),
					esc_html( CANVAS_MIN_WP ),
					esc_html( $wp_version )
				),
				esc_html__( 'Plugin Activation Error', 'canvas' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Single site activation tasks.
	 *
	 * Performs all activation tasks for a single site:
	 * - Creates database tables
	 * - Runs migrations
	 * - Sets up capabilities
	 * - Initializes default options
	 *
	 * @return void
	 */
	private static function single_site_activate(): void {
		// Create database tables.
		self::create_tables();

		// Run any pending migrations.
		self::run_migrations();

		// Add capabilities to administrator role.
		self::add_capabilities();

		// Set default options.
		self::set_default_options();

		// Store activation timestamp for reference.
		update_option( 'canvas_activated', time() );

		// Store current DB version.
		update_option( 'canvas_db_version', CANVAS_DB_VERSION );

		// Flush rewrite rules (if plugin registers custom post types or endpoints).
		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 *
	 * Uses WordPress dbDelta() for safe table creation and updates.
	 * Tables are only created/modified if they differ from the schema.
	 *
	 * @return void
	 */
	private static function create_tables(): void {
		global $wpdb;

		// Include WordPress upgrade functions for dbDelta().
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Example table: canvas_items
		// Customize this for your plugin's data model.
		$table_name = $wpdb->prefix . 'canvas_items';

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
			title varchar(255) NOT NULL DEFAULT '',
			content longtext NOT NULL,
			status varchar(50) NOT NULL DEFAULT 'draft',
			meta text NOT NULL,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY blog_id (blog_id),
			KEY status (status),
			KEY author_id (author_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );

		// Example table: canvas_audit_log
		// Append-only audit log for compliance tracking.
		$audit_table = $wpdb->prefix . 'canvas_audit_log';

		$audit_sql = "CREATE TABLE {$audit_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			action varchar(100) NOT NULL,
			object_type varchar(100) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			details text NOT NULL,
			ip_address varchar(45) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY blog_id (blog_id),
			KEY user_id (user_id),
			KEY action (action),
			KEY object_type_id (object_type, object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $audit_sql );
	}

	/**
	 * Run database migrations.
	 *
	 * Delegates to the Migrator class for version-based migrations.
	 * Only runs migrations if DB version has changed.
	 *
	 * @return void
	 */
	private static function run_migrations(): void {
		$current_version = get_option( 'canvas_db_version', '0.0.0' );

		// Only run migrations if version has changed.
		if ( version_compare( $current_version, CANVAS_DB_VERSION, '<' ) ) {
			// Migrator class handles individual migration files.
			if ( class_exists( 'Canvas\\Database\\Migrator' ) ) {
				$migrator = new Database\Migrator();
				$migrator->run_migrations();
			}
		}
	}

	/**
	 * Add custom capabilities to administrator role.
	 *
	 * Capabilities are only added if they don't already exist.
	 * This is idempotent - safe to run multiple times.
	 *
	 * @return void
	 */
	private static function add_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role ) {
			return;
		}

		foreach ( self::$capabilities as $capability => $description ) {
			if ( ! $admin_role->has_cap( $capability ) ) {
				$admin_role->add_cap( $capability );
			}
		}
	}

	/**
	 * Set default plugin options.
	 *
	 * Only sets options if they don't already exist.
	 * Customize this for your plugin's default configuration.
	 *
	 * @return void
	 */
	private static function set_default_options(): void {
		// Default settings - only set if not already present.
		$defaults = array(
			'canvas_settings' => array(
				'enabled'            => true,
				'notifications'      => true,
				'retention_days'     => 90,
				'items_per_page'     => 20,
				'enable_audit_log'   => true,
				'enable_api'         => true,
			),
		);

		foreach ( $defaults as $option_name => $default_value ) {
			if ( false === get_option( $option_name ) ) {
				add_option( $option_name, $default_value );
			}
		}
	}

	/**
	 * Deactivate the plugin.
	 *
	 * Called by register_deactivation_hook(). Handles cleanup tasks
	 * that should occur when the plugin is deactivated.
	 *
	 * Note: This does NOT remove data. That's handled by uninstall.php.
	 *
	 * @param bool $network_wide Whether deactivating network-wide.
	 * @return void
	 */
	public static function deactivate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $sites as $site_id ) {
				switch_to_blog( $site_id );
				self::single_site_deactivate();
				restore_current_blog();
			}
		} else {
			self::single_site_deactivate();
		}
	}

	/**
	 * Single site deactivation tasks.
	 *
	 * Performs cleanup for a single site:
	 * - Clears scheduled events
	 * - Flushes rewrite rules
	 *
	 * Does NOT remove capabilities or data by default.
	 *
	 * @return void
	 */
	private static function single_site_deactivate(): void {
		// Clear any scheduled cron events.
		wp_clear_scheduled_hook( 'canvas_daily_cleanup' );
		wp_clear_scheduled_hook( 'canvas_hourly_tasks' );

		// If using Action Scheduler, unschedule pending actions.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'canvas_scheduled_task' );
		}

		// Flush rewrite rules.
		flush_rewrite_rules();

		// Uncomment to remove capabilities on deactivation:
		// self::remove_capabilities();
	}

	/**
	 * Remove custom capabilities.
	 *
	 * Only use this if you want to clean up capabilities on deactivation.
	 * By default, capabilities are preserved to maintain user permissions
	 * if the plugin is reactivated.
	 *
	 * @return void
	 */
	private static function remove_capabilities(): void {
		$admin_role = get_role( 'administrator' );

		if ( ! $admin_role ) {
			return;
		}

		foreach ( array_keys( self::$capabilities ) as $capability ) {
			$admin_role->remove_cap( $capability );
		}
	}

	/**
	 * Get registered capabilities.
	 *
	 * Useful for documentation or admin UI displaying available permissions.
	 *
	 * @return array<string, string> Capability name => description pairs.
	 */
	public static function get_capabilities(): array {
		return self::$capabilities;
	}
}
