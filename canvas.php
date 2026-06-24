<?php
/**
 * Canvas - WordPress Plugin Starter Framework
 *
 * A reusable starting point for WordPress plugins with React admin interfaces,
 * REST API patterns, and WordPress data store integration.
 *
 * @package     Canvas
 * @author      Your Name
 * @copyright   2026 Your Company
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
 * Requires at least: 7.0
 * Requires PHP: 8.3
 */

declare(strict_types=1);

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version. Update this when releasing new versions.
 */
define( 'CANVAS_VERSION', '1.0.0' );

/**
 * Database schema version. Increment when schema changes require migrations.
 */
define( 'CANVAS_DB_VERSION', '1.0.0' );

/**
 * Plugin file path. Use for activation/deactivation hooks and file references.
 */
define( 'CANVAS_FILE', __FILE__ );

/**
 * Plugin directory path. Use for including PHP files.
 */
define( 'CANVAS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Plugin URL. Use for enqueuing assets.
 */
define( 'CANVAS_URL', plugin_dir_url( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'CANVAS_MIN_PHP', '8.3' );

/**
 * Minimum WordPress version required.
 */
define( 'CANVAS_MIN_WP', '7.0' );

/**
 * Load the Composer autoloader if dependencies are installed.
 *
 * When present, Composer's PSR-4 map handles the Canvas\ namespace (and any
 * third-party dependencies). The hand-rolled autoloader below is a fallback
 * for environments where `composer install` has not been run.
 */
$canvas_composer_autoload = CANVAS_PATH . 'vendor/autoload.php';
if ( is_readable( $canvas_composer_autoload ) ) {
	require_once $canvas_composer_autoload;
}

/**
 * Fallback PSR-4 autoloader for the Canvas namespace.
 *
 * Maps Canvas\Sub\Thing_Name to includes/Sub/<prefix>-thing-name.php, following
 * WordPress file-naming conventions. Because the symbol kind (class, interface,
 * trait, enum) cannot be derived from the name alone, each known prefix is tried
 * in turn and the first matching file wins.
 */
spl_autoload_register(
	static function ( string $classname ): void {
		$prefix   = 'Canvas\\';
		$base_dir = CANVAS_PATH . 'includes/';

		if ( ! str_starts_with( $classname, $prefix ) ) {
			return;
		}

		$relative   = substr( $classname, strlen( $prefix ) );
		$parts      = explode( '\\', $relative );
		$class_name = array_pop( $parts );
		$slug       = strtolower( str_replace( '_', '-', $class_name ) );
		$sub_dir    = $parts ? implode( '/', $parts ) . '/' : '';

		foreach ( array( 'class', 'interface', 'trait', 'enum' ) as $kind ) {
			$file = $base_dir . $sub_dir . $kind . '-' . $slug . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
);

/**
 * Boot the plugin once WordPress and other plugins are ready.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'canvas', false, dirname( plugin_basename( CANVAS_FILE ) ) . '/languages' );
		Canvas\Plugin::get_instance()->register();
	}
);

/**
 * Activation/deactivation lifecycle. Registered at top level, as WordPress requires.
 */
register_activation_hook( CANVAS_FILE, array( 'Canvas\\Installer', 'activate' ) );
register_deactivation_hook( CANVAS_FILE, array( 'Canvas\\Installer', 'deactivate' ) );
