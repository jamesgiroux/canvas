<?php
/**
 * Migration Runner
 *
 * Runs pending schema migrations when the stored DB version is behind the
 * code's DB version. Hooked on admin_init so upgrades apply on the next admin
 * request after a plugin update.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Database;

use Canvas\Contracts\Registrable;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migration runner registrar.
 */
final class Migration_Runner implements Registrable {

	/**
	 * Option storing the installed DB schema version.
	 *
	 * @var string
	 */
	public const VERSION_OPTION = 'canvas_db_version';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'maybe_migrate' ) );
	}

	/**
	 * Run migrations if the stored version is behind.
	 *
	 * @return void
	 */
	public function maybe_migrate(): void {
		$stored = (string) get_option( self::VERSION_OPTION, '0.0.0' );

		if ( version_compare( $stored, CANVAS_DB_VERSION, '>=' ) ) {
			return;
		}

		( new Migrator() )->run_migrations();

		update_option( self::VERSION_OPTION, CANVAS_DB_VERSION );
	}
}
