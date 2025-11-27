<?php
/**
 * Canvas - WordPress Plugin Starter Framework
 *
 * A reusable starting point for WordPress plugins with React admin interfaces,
 * REST API patterns, and WordPress data store integration.
 *
 * @package     Canvas
 * @author      Your Name
 * @copyright   2025 Your Company
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: Canvas
 * Plugin URI:  https://example.com/canvas
 * Description: A starter framework for WordPress plugins with React admin UI.
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://example.com
 * Text Domain: canvas
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.4
 * Requires PHP: 8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version.
 * Update this when releasing new versions.
 */
define( 'CANVAS_VERSION', '1.0.0' );

/**
 * Database schema version.
 * Increment when database schema changes require migrations.
 */
define( 'CANVAS_DB_VERSION', '1.0.0' );

/**
 * Plugin file path.
 * Use for activation/deactivation hooks and file references.
 */
define( 'CANVAS_FILE', __FILE__ );

/**
 * Plugin directory path.
 * Use for including PHP files.
 */
define( 'CANVAS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL.
 * Use for enqueuing assets.
 */
define( 'CANVAS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'CANVAS_MIN_PHP', '8.0' );

/**
 * Minimum WordPress version required.
 */
define( 'CANVAS_MIN_WP', '6.4' );

/**
 * Load Composer autoloader if available.
 *
 * Composer handles PSR-4 autoloading for dependencies like Action Scheduler.
 * If composer autoload isn't available, we fall back to manual loading.
 */
$composer_autoload = CANVAS_PATH . 'vendor/autoload.php';
if ( file_exists( $composer_autoload ) ) {
	require_once $composer_autoload;
}

/**
 * PSR-4 Autoloader for Canvas namespace.
 *
 * Maps Canvas\ClassName to includes/class-classname.php
 * Maps Canvas\Namespace\ClassName to includes/Namespace/class-classname.php
 *
 * Follows WordPress file naming conventions (lowercase with hyphens).
 */
spl_autoload_register(
	function ( $class ) {
		// Only handle classes in our namespace.
		$prefix   = 'Canvas\\';
		$base_dir = CANVAS_PATH . 'includes/';

		// Check if the class uses our namespace prefix.
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Convert namespace separators to directory separators.
		$parts      = explode( '\\', $relative_class );
		$class_name = array_pop( $parts );

		// Convert class name to WordPress file naming convention.
		// ClassName becomes class-classname.php
		// Class_Name becomes class-class-name.php
		$class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		// Build the file path.
		if ( ! empty( $parts ) ) {
			$file = $base_dir . implode( '/', $parts ) . '/' . $class_file;
		} else {
			$file = $base_dir . $class_file;
		}

		// Include the file if it exists.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Initialize the plugin.
 *
 * We use plugins_loaded to ensure WordPress and other plugins are ready.
 * Priority 10 is standard - adjust if you need to load before/after other plugins.
 */
add_action(
	'plugins_loaded',
	function () {
		// Load text domain for translations.
		load_plugin_textdomain( 'canvas', false, dirname( plugin_basename( CANVAS_FILE ) ) . '/languages' );

		// Initialize the main plugin instance.
		Canvas\Plugin::get_instance();
	}
);

/**
 * Activation hook.
 *
 * Runs when the plugin is activated. Handles:
 * - Requirements checking (PHP version, WordPress version)
 * - Database table creation
 * - Default options setup
 * - Capability assignment
 */
register_activation_hook( CANVAS_FILE, array( 'Canvas\\Installer', 'activate' ) );

/**
 * Deactivation hook.
 *
 * Runs when the plugin is deactivated. Handles:
 * - Cleanup of scheduled events
 * - Optional: Remove capabilities (uncomment in Installer if needed)
 *
 * Note: Does NOT remove database tables or options.
 * Those are handled by uninstall.php for complete removal.
 */
register_deactivation_hook( CANVAS_FILE, array( 'Canvas\\Installer', 'deactivate' ) );
