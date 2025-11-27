<?php
/**
 * Database Migrator
 *
 * Handles database schema migrations in a version-controlled manner.
 * Migrations are tracked to prevent re-running and ensure consistency.
 *
 * @package Canvas
 */

namespace Canvas\Database;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Migrator class.
 *
 * Runs numbered migration files in order. Each migration should be:
 * - Idempotent (safe to run multiple times)
 * - Forward-only (no rollback support by default)
 * - Self-contained (no external dependencies)
 *
 * Migration files are in: includes/Database/migrations/
 * Format: NNN-description.php (e.g., 001-add-index.php)
 */
class Migrator {

	/**
	 * Option name for storing completed migrations.
	 *
	 * @var string
	 */
	private const MIGRATIONS_OPTION = 'canvas_completed_migrations';

	/**
	 * Directory containing migration files.
	 *
	 * @var string
	 */
	private string $migrations_dir;

	/**
	 * List of completed migration IDs.
	 *
	 * @var array<string>
	 */
	private array $completed = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->migrations_dir = CANVAS_PATH . 'includes/Database/migrations/';
		$this->completed      = $this->load_completed_migrations();
	}

	/**
	 * Run all pending migrations.
	 *
	 * Scans the migrations directory and runs any migrations that haven't
	 * been executed yet, in numerical order.
	 *
	 * @param bool $dry_run If true, only report what would run without executing.
	 * @return array<string, mixed> Results of migration run.
	 */
	public function run_migrations( bool $dry_run = false ): array {
		$results = array(
			'executed' => array(),
			'skipped'  => array(),
			'errors'   => array(),
		);

		// Get all migration files.
		$migrations = $this->get_migration_files();

		if ( empty( $migrations ) ) {
			return $results;
		}

		foreach ( $migrations as $migration_id => $migration_file ) {
			// Skip if already completed.
			if ( in_array( $migration_id, $this->completed, true ) ) {
				$results['skipped'][] = $migration_id;
				continue;
			}

			// Dry run - just report what would run.
			if ( $dry_run ) {
				$results['executed'][] = array(
					'id'      => $migration_id,
					'file'    => basename( $migration_file ),
					'dry_run' => true,
				);
				continue;
			}

			// Execute the migration.
			$result = $this->execute_migration( $migration_id, $migration_file );

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = array(
					'id'      => $migration_id,
					'message' => $result->get_error_message(),
				);
				// Stop on first error to prevent cascading issues.
				break;
			}

			$results['executed'][] = array(
				'id'   => $migration_id,
				'file' => basename( $migration_file ),
			);
		}

		return $results;
	}

	/**
	 * Get all migration files sorted by number.
	 *
	 * @return array<string, string> Migration ID => file path.
	 */
	private function get_migration_files(): array {
		if ( ! is_dir( $this->migrations_dir ) ) {
			return array();
		}

		$files = glob( $this->migrations_dir . '*.php' );

		if ( empty( $files ) ) {
			return array();
		}

		$migrations = array();

		foreach ( $files as $file ) {
			$filename = basename( $file );

			// Extract migration ID from filename (e.g., "001" from "001-add-index.php").
			if ( preg_match( '/^(\d{3})-/', $filename, $matches ) ) {
				$migration_id               = $matches[1];
				$migrations[ $migration_id ] = $file;
			}
		}

		// Sort by migration ID.
		ksort( $migrations );

		return $migrations;
	}

	/**
	 * Execute a single migration.
	 *
	 * @param string $migration_id The migration identifier.
	 * @param string $migration_file Path to the migration file.
	 * @return true|\WP_Error True on success, WP_Error on failure.
	 */
	private function execute_migration( string $migration_id, string $migration_file ): true|\WP_Error {
		global $wpdb;

		// Verify file exists.
		if ( ! file_exists( $migration_file ) ) {
			return new \WP_Error(
				'migration_not_found',
				sprintf( 'Migration file not found: %s', $migration_file )
			);
		}

		try {
			// Start transaction for safety.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'START TRANSACTION' );

			// Include and run the migration.
			// Migration files should contain a function or class that performs the migration.
			require_once $migration_file;

			// Look for a function named canvas_migration_NNN.
			$function_name = 'canvas_migration_' . $migration_id;

			if ( function_exists( $function_name ) ) {
				$result = call_user_func( $function_name );

				if ( is_wp_error( $result ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->query( 'ROLLBACK' );
					return $result;
				}
			}

			// Commit the transaction.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'COMMIT' );

			// Mark migration as completed.
			$this->mark_completed( $migration_id );

			return true;

		} catch ( \Exception $e ) {
			// Rollback on exception.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( 'ROLLBACK' );

			return new \WP_Error(
				'migration_failed',
				sprintf( 'Migration %s failed: %s', $migration_id, $e->getMessage() )
			);
		}
	}

	/**
	 * Load the list of completed migrations.
	 *
	 * @return array<string> Array of completed migration IDs.
	 */
	private function load_completed_migrations(): array {
		$completed = get_option( self::MIGRATIONS_OPTION, array() );

		if ( ! is_array( $completed ) ) {
			return array();
		}

		return $completed;
	}

	/**
	 * Mark a migration as completed.
	 *
	 * @param string $migration_id The migration ID to mark complete.
	 * @return void
	 */
	private function mark_completed( string $migration_id ): void {
		$this->completed[] = $migration_id;
		update_option( self::MIGRATIONS_OPTION, $this->completed );
	}

	/**
	 * Get list of pending migrations.
	 *
	 * Useful for admin UI to show what migrations will run.
	 *
	 * @return array<string> Array of pending migration IDs.
	 */
	public function get_pending_migrations(): array {
		$all_migrations = array_keys( $this->get_migration_files() );
		return array_diff( $all_migrations, $this->completed );
	}

	/**
	 * Check if there are pending migrations.
	 *
	 * @return bool True if migrations are pending.
	 */
	public function has_pending_migrations(): bool {
		return ! empty( $this->get_pending_migrations() );
	}

	/**
	 * Reset migration history.
	 *
	 * WARNING: Only use for development/testing. This will cause all
	 * migrations to run again on next activation.
	 *
	 * @return void
	 */
	public function reset_migration_history(): void {
		delete_option( self::MIGRATIONS_OPTION );
		$this->completed = array();
	}
}
