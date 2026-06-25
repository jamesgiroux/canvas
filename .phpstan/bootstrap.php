<?php
/**
 * PHPStan bootstrap.
 *
 * Defines the runtime constants that the plugin sets in its main file so static
 * analysis can resolve them without executing WordPress.
 *
 * @package Canvas
 */

declare(strict_types=1);

define( 'CANVAS_VERSION', '1.0.0' );
define( 'CANVAS_DB_VERSION', '1.0.0' );
define( 'CANVAS_FILE', __FILE__ );
define( 'CANVAS_PATH', __DIR__ . '/' );
define( 'CANVAS_URL', 'https://example.com/wp-content/plugins/canvas/' );
define( 'CANVAS_MIN_PHP', '8.3' );
define( 'CANVAS_MIN_WP', '7.0' );
