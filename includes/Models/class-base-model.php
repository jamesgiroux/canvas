<?php
/**
 * Base Model Class
 *
 * Abstract base class for all data models. Provides common functionality
 * for database operations including multisite support.
 *
 * @package Canvas
 */

namespace Canvas\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Model class.
 *
 * Extend this class to create data models with built-in:
 * - Table name management
 * - Multisite blog_id handling
 * - JSON encoding/decoding helpers
 * - Common query patterns
 */
abstract class Base_Model {

	/**
	 * The table name without prefix.
	 *
	 * Override this in child classes.
	 *
	 * @var string
	 */
	protected static string $table = '';

	/**
	 * Primary key column name.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Columns that contain JSON data.
	 *
	 * Override in child classes to specify which columns should be
	 * automatically encoded/decoded as JSON.
	 *
	 * @var array<string>
	 */
	protected static array $json_columns = array();

	/**
	 * Allowed columns for queries.
	 *
	 * Override in child classes to whitelist columns that can be used
	 * in WHERE clauses and ORDER BY. This prevents SQL injection.
	 *
	 * @var array<string>
	 */
	protected static array $allowed_columns = array( 'id', 'blog_id', 'created_at', 'updated_at' );

	/**
	 * Enable caching for this model.
	 *
	 * Override in child classes to enable transient caching.
	 * Set to false to disable caching for specific models.
	 *
	 * @var bool
	 */
	protected static bool $cache_enabled = true;

	/**
	 * Cache TTL in seconds.
	 *
	 * Override in child classes to customize cache duration.
	 * Default: 1 hour (3600 seconds).
	 *
	 * @var int
	 */
	protected static int $cache_ttl = HOUR_IN_SECONDS;

	/**
	 * Validate that a column name is allowed.
	 *
	 * @param string $column The column name to validate.
	 * @return bool True if column is allowed, false otherwise.
	 */
	protected static function is_valid_column( string $column ): bool {
		return in_array( $column, static::$allowed_columns, true );
	}

	/**
	 * Generate a cache key for a record.
	 *
	 * @param int $id The record ID.
	 * @return string The cache key.
	 */
	protected static function get_cache_key( int $id ): string {
		return sprintf(
			'%s_%d_%d',
			static::$table,
			static::get_blog_id(),
			$id
		);
	}

	/**
	 * Get a cached record.
	 *
	 * @param int $id The record ID.
	 * @return object|null|false Cached object, null if not found, false if not cached.
	 */
	protected static function get_cached( int $id ): object|null|false {
		if ( ! static::$cache_enabled ) {
			return false;
		}

		$cache_key = static::get_cache_key( $id );
		$cached    = get_transient( $cache_key );

		// Return false if not in cache (transient returns false when not found).
		if ( false === $cached ) {
			return false;
		}

		// Return null if explicitly cached as not found.
		if ( 'not_found' === $cached ) {
			return null;
		}

		return $cached;
	}

	/**
	 * Set a cached record.
	 *
	 * @param int         $id   The record ID.
	 * @param object|null $data The data to cache, or null if not found.
	 * @return void
	 */
	protected static function set_cached( int $id, ?object $data ): void {
		if ( ! static::$cache_enabled ) {
			return;
		}

		$cache_key = static::get_cache_key( $id );
		$value     = null === $data ? 'not_found' : $data;

		set_transient( $cache_key, $value, static::$cache_ttl );
	}

	/**
	 * Clear cache for a record.
	 *
	 * @param int $id The record ID.
	 * @return void
	 */
	protected static function clear_cache( int $id ): void {
		if ( ! static::$cache_enabled ) {
			return;
		}

		delete_transient( static::get_cache_key( $id ) );
	}

	/**
	 * Clear all cache for this model on the current blog.
	 *
	 * Note: This uses a prefix-based approach which may not delete all
	 * transients on all caching backends. For complete cache clearing,
	 * consider using object cache groups instead.
	 *
	 * @return void
	 */
	public static function clear_all_cache(): void {
		global $wpdb;

		if ( ! static::$cache_enabled ) {
			return;
		}

		$prefix = sprintf( '_transient_%s_%d_', static::$table, static::get_blog_id() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $prefix ) . '%'
			)
		);
	}

	/**
	 * Get the full table name with WordPress prefix.
	 *
	 * @return string The prefixed table name.
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . static::$table;
	}

	/**
	 * Get the current blog ID.
	 *
	 * Used for multisite isolation - each site's data is segregated.
	 *
	 * @return int The current blog ID (1 for single-site).
	 */
	public static function get_blog_id(): int {
		return get_current_blog_id();
	}

	/**
	 * Encode data as JSON for storage.
	 *
	 * @param mixed $data The data to encode.
	 * @return string JSON string, or empty string on failure.
	 */
	public static function encode_json( mixed $data ): string {
		if ( empty( $data ) ) {
			return '{}';
		}

		$encoded = wp_json_encode( $data );
		return false !== $encoded ? $encoded : '{}';
	}

	/**
	 * Decode JSON string from storage.
	 *
	 * @param string $json The JSON string to decode.
	 * @param bool   $assoc Whether to return associative array (default true).
	 * @return mixed Decoded data, or empty array on failure.
	 */
	public static function decode_json( string $json, bool $assoc = true ): mixed {
		if ( empty( $json ) ) {
			return $assoc ? array() : new \stdClass();
		}

		$decoded = json_decode( $json, $assoc );
		return null !== $decoded ? $decoded : ( $assoc ? array() : new \stdClass() );
	}

	/**
	 * Find a record by primary key.
	 *
	 * @param int  $id          The primary key value.
	 * @param bool $skip_cache  Whether to bypass cache (default false).
	 * @return object|null The record or null if not found.
	 */
	public static function find( int $id, bool $skip_cache = false ): ?object {
		global $wpdb;

		// Check cache first.
		if ( ! $skip_cache ) {
			$cached = static::get_cached( $id );
			if ( false !== $cached ) {
				return $cached;
			}
		}

		$table = static::get_table_name();
		$pk    = static::$primary_key;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$pk} = %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id,
				static::get_blog_id()
			)
		);

		if ( ! $row ) {
			// Cache the not-found result to prevent repeated queries.
			static::set_cached( $id, null );
			return null;
		}

		$hydrated = static::hydrate( $row );

		// Cache the result.
		static::set_cached( $id, $hydrated );

		return $hydrated;
	}

	/**
	 * Find all records matching criteria.
	 *
	 * @param array<string, mixed> $where Column => value pairs for WHERE clause.
	 * @param string               $order_by Column to order by.
	 * @param string               $order ASC or DESC.
	 * @param int                  $limit Maximum records to return.
	 * @param int                  $offset Records to skip.
	 * @return array<object> Array of records.
	 */
	public static function find_all(
		array $where = array(),
		string $order_by = 'id',
		string $order = 'DESC',
		int $limit = 100,
		int $offset = 0
	): array {
		global $wpdb;

		$table = static::get_table_name();

		// Build WHERE clause.
		$conditions = array( 'blog_id = %d' );
		$values     = array( static::get_blog_id() );

		foreach ( $where as $column => $value ) {
			// Validate column name against whitelist to prevent SQL injection.
			if ( ! static::is_valid_column( $column ) ) {
				continue; // Skip invalid columns.
			}

			if ( is_null( $value ) ) {
				$conditions[] = "{$column} IS NULL";
			} elseif ( is_int( $value ) ) {
				$conditions[] = "{$column} = %d";
				$values[]     = $value;
			} else {
				$conditions[] = "{$column} = %s";
				$values[]     = $value;
			}
		}

		$where_clause = implode( ' AND ', $conditions );

		// Validate and sanitize order parameters.
		if ( ! static::is_valid_column( $order_by ) ) {
			$order_by = static::$primary_key; // Default to primary key if invalid.
		}
		$order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';

		// Build query.
		$sql = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d";
		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $values ) );

		if ( ! $rows ) {
			return array();
		}

		return array_map( array( static::class, 'hydrate' ), $rows );
	}

	/**
	 * Count records matching criteria.
	 *
	 * @param array<string, mixed> $where Column => value pairs for WHERE clause.
	 * @return int The count.
	 */
	public static function count( array $where = array() ): int {
		global $wpdb;

		$table = static::get_table_name();

		// Build WHERE clause.
		$conditions = array( 'blog_id = %d' );
		$values     = array( static::get_blog_id() );

		foreach ( $where as $column => $value ) {
			// Validate column name against whitelist to prevent SQL injection.
			if ( ! static::is_valid_column( $column ) ) {
				continue; // Skip invalid columns.
			}

			if ( is_null( $value ) ) {
				$conditions[] = "{$column} IS NULL";
			} elseif ( is_int( $value ) ) {
				$conditions[] = "{$column} = %d";
				$values[]     = $value;
			} else {
				$conditions[] = "{$column} = %s";
				$values[]     = $value;
			}
		}

		$where_clause = implode( ' AND ', $conditions );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$values
			)
		);
	}

	/**
	 * Insert a new record.
	 *
	 * @param array<string, mixed> $data Column => value pairs to insert.
	 * @return int|false The inserted ID or false on failure.
	 */
	public static function insert( array $data ): int|false {
		global $wpdb;

		// Add blog_id if not present.
		if ( ! isset( $data['blog_id'] ) ) {
			$data['blog_id'] = static::get_blog_id();
		}

		// Encode JSON columns.
		foreach ( static::$json_columns as $column ) {
			if ( isset( $data[ $column ] ) && ! is_string( $data[ $column ] ) ) {
				$data[ $column ] = static::encode_json( $data[ $column ] );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert( static::get_table_name(), $data );

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update an existing record.
	 *
	 * @param int                  $id The primary key value.
	 * @param array<string, mixed> $data Column => value pairs to update.
	 * @return bool True on success, false on failure.
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		// Encode JSON columns.
		foreach ( static::$json_columns as $column ) {
			if ( isset( $data[ $column ] ) && ! is_string( $data[ $column ] ) ) {
				$data[ $column ] = static::encode_json( $data[ $column ] );
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->update(
			static::get_table_name(),
			$data,
			array(
				static::$primary_key => $id,
				'blog_id'            => static::get_blog_id(),
			)
		);

		if ( false !== $result ) {
			// Clear cache on successful update.
			static::clear_cache( $id );
		}

		return false !== $result;
	}

	/**
	 * Delete a record.
	 *
	 * @param int $id The primary key value.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->delete(
			static::get_table_name(),
			array(
				static::$primary_key => $id,
				'blog_id'            => static::get_blog_id(),
			)
		);

		if ( false !== $result ) {
			// Clear cache on successful delete.
			static::clear_cache( $id );
		}

		return false !== $result;
	}

	/**
	 * Hydrate a database row into a model object.
	 *
	 * Override this method in child classes to add custom hydration logic.
	 * By default, decodes JSON columns.
	 *
	 * @param object $row The database row.
	 * @return object The hydrated object.
	 */
	protected static function hydrate( object $row ): object {
		// Decode JSON columns.
		foreach ( static::$json_columns as $column ) {
			if ( isset( $row->$column ) && is_string( $row->$column ) ) {
				$row->$column = static::decode_json( $row->$column );
			}
		}

		return $row;
	}

	/**
	 * Begin a database transaction.
	 *
	 * Use with commit() or rollback() for atomic operations.
	 *
	 * @return void
	 */
	public static function begin_transaction(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Commit a database transaction.
	 *
	 * @return void
	 */
	public static function commit(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'COMMIT' );
	}

	/**
	 * Rollback a database transaction.
	 *
	 * @return void
	 */
	public static function rollback(): void {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'ROLLBACK' );
	}
}
