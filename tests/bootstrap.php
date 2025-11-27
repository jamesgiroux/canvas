<?php
/**
 * PHPUnit Bootstrap
 *
 * Loads WordPress test environment and plugin.
 *
 * @package Canvas
 */

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Determine tests directory.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Check for WordPress test suite.
if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	// Allow running unit tests without WordPress.
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );

	// Mock WordPress functions for unit tests.
	require_once __DIR__ . '/mocks/wordpress-functions.php';

	return;
}

// Load WordPress test functions.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Load the plugin before WordPress loads.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/canvas.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Load WordPress test suite.
require "{$_tests_dir}/includes/bootstrap.php";
