<?php
/**
 * Base Model Class
 *
 * Abstract base for data models backed by a custom table. Provides CRUD,
 * multisite isolation, JSON column handling, a shared WHERE builder, and
 * object-cache-backed per-row caching.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Model.
 *
 * Extend and override the static configuration properties. All queries are
 * automatically scoped to the current blog via the `blog_id` column.
 */
abstract class Base_Model {

	/**
	 * Table name without the WordPress prefix.
	 *
	 * @var string
	 */
	protected static string $table = '';

	/**
	 * Primary key column.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Columns stored as JSON (encoded on write, decoded on read).
	 *
	 * @var array<int, string>
	 */
	protected static array $json_columns = array();

	/**
	 * Columns allowed in WHERE / ORDER BY clauses (SQL-injection allowlist).
	 *
	 * @var array<int, string>
	 */
	protected static array $allowed_columns = array( 'id', 'blog_id', 'created_at', 'updated_at' );

	/**
	 * Whether per-row caching is enabled.
	 *
	 * @var bool
	 */
	protected static bool $cache_enabled = true;

	/**
	 * Cache TTL in seconds.
	 *
	 * @var int
	 */
	protected static int $cache_ttl = HOUR_IN_SECONDS;

	/**
	 * Full table name including the WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . static::$table;
	}

	/**
	 * Current blog ID (1 on single site). Used for multisite isolation.
	 *
	 * @return int
	 */
	public static function get_blog_id(): int {
		return get_current_blog_id();
	}

	/**
	 * Find a record by primary key.
	 *
	 * @param int  $id         Primary key value.
	 * @param bool $skip_cache Bypass the cache when true.
	 * @return object|null
	 */
	public static function find( int $id, bool $skip_cache = false ): ?object {
		global $wpdb;

		if ( ! $skip_cache && static::$cache_enabled ) {
			$found  = false;
			$cached = wp_cache_get( static::cache_key( $id ), static::cache_group(), false, $found );
			if ( $found ) {
				return false === $cached ? null : $cached;
			}
		}

		$table = static::get_table_name();
		$pk    = static::$primary_key;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE {$pk} = %d AND blog_id = %d",
				$id,
				static::get_blog_id()
			)
		);

		$result = $row ? static::hydrate( $row ) : null;

		if ( static::$cache_enabled ) {
			wp_cache_set( static::cache_key( $id ), $result ?? false, static::cache_group(), static::$cache_ttl );
		}

		return $result;
	}

	/**
	 * Find records matching the given conditions.
	 *
	 * @param array<string, mixed> $where    Column => value conditions.
	 * @param string               $order_by Column to sort by.
	 * @param string               $order    ASC or DESC.
	 * @param int                  $limit    Maximum rows.
	 * @param int                  $offset   Rows to skip.
	 * @return array<int, object>
	 */
	public static function find_all(
		array $where = array(),
		string $order_by = 'id',
		string $order = 'DESC',
		int $limit = 100,
		int $offset = 0
	): array {
		global $wpdb;

		$table               = static::get_table_name();
		[ $clause, $values ] = static::build_where( $where );

		if ( ! static::is_valid_column( $order_by ) ) {
			$order_by = static::$primary_key;
		}
		$order = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		$values[] = $limit;
		$values[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE {$clause} ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d",
				$values
			)
		);

		return $rows ? array_map( array( static::class, 'hydrate' ), $rows ) : array();
	}

	/**
	 * Find records where a column matches a LIKE term.
	 *
	 * @param string $column   Column to match (must be allowlisted).
	 * @param string $term     Search term (wrapped in %…%).
	 * @param string $order_by Column to sort by.
	 * @param string $order    ASC or DESC.
	 * @param int    $limit    Maximum rows.
	 * @param int    $offset   Rows to skip.
	 * @return array<int, object>
	 */
	public static function find_like(
		string $column,
		string $term,
		string $order_by = 'id',
		string $order = 'DESC',
		int $limit = 100,
		int $offset = 0
	): array {
		global $wpdb;

		if ( ! static::is_valid_column( $column ) ) {
			return array();
		}

		$table = static::get_table_name();

		if ( ! static::is_valid_column( $order_by ) ) {
			$order_by = static::$primary_key;
		}
		$order = 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC';

		$like = '%' . $wpdb->esc_like( $term ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} WHERE blog_id = %d AND {$column} LIKE %s ORDER BY {$order_by} {$order} LIMIT %d OFFSET %d",
				static::get_blog_id(),
				$like,
				$limit,
				$offset
			)
		);

		return $rows ? array_map( array( static::class, 'hydrate' ), $rows ) : array();
	}

	/**
	 * Count records matching the given conditions.
	 *
	 * @param array<string, mixed> $where Column => value conditions.
	 * @return int
	 */
	public static function count( array $where = array() ): int {
		global $wpdb;

		$table               = static::get_table_name();
		[ $clause, $values ] = static::build_where( $where );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$table} WHERE {$clause}",
				$values
			)
		);
	}

	/**
	 * Insert a new record.
	 *
	 * @param array<string, mixed> $data Column => value pairs.
	 * @return int|false Inserted ID, or false on failure.
	 */
	public static function insert( array $data ): int|false {
		global $wpdb;

		$data['blog_id'] ??= static::get_blog_id();
		$data              = static::encode_json_columns( $data );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->insert( static::get_table_name(), $data );

		return false === $result ? false : (int) $wpdb->insert_id;
	}

	/**
	 * Update an existing record.
	 *
	 * @param int                  $id   Primary key value.
	 * @param array<string, mixed> $data Column => value pairs.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$data = static::encode_json_columns( $data );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update(
			static::get_table_name(),
			$data,
			array(
				static::$primary_key => $id,
				'blog_id'            => static::get_blog_id(),
			)
		);

		if ( false !== $result ) {
			static::clear_cache( $id );
		}

		return false !== $result;
	}

	/**
	 * Delete a record.
	 *
	 * @param int $id Primary key value.
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			static::get_table_name(),
			array(
				static::$primary_key => $id,
				'blog_id'            => static::get_blog_id(),
			)
		);

		if ( false !== $result ) {
			static::clear_cache( $id );
		}

		return false !== $result;
	}

	/**
	 * Encode/decode helpers for JSON columns.
	 *
	 * @param mixed $data Data to encode.
	 * @return string
	 */
	public static function encode_json( mixed $data ): string {
		if ( empty( $data ) ) {
			return '{}';
		}
		$encoded = wp_json_encode( $data );
		return false !== $encoded ? $encoded : '{}';
	}

	/**
	 * Decode a JSON string from storage.
	 *
	 * @param string $json  JSON string.
	 * @param bool   $assoc Return associative array when true.
	 * @return mixed
	 */
	public static function decode_json( string $json, bool $assoc = true ): mixed {
		if ( '' === $json ) {
			return $assoc ? array() : new \stdClass();
		}
		$decoded = json_decode( $json, $assoc );
		return null !== $decoded ? $decoded : ( $assoc ? array() : new \stdClass() );
	}

	/**
	 * Clear the cached copy of a single record.
	 *
	 * @param int $id Primary key value.
	 * @return void
	 */
	public static function clear_cache( int $id ): void {
		if ( static::$cache_enabled ) {
			wp_cache_delete( static::cache_key( $id ), static::cache_group() );
		}
	}

	/**
	 * Invalidate every cached record for this model on the current blog.
	 *
	 * Bumps the group's last_changed marker, orphaning all existing keys without
	 * touching other cache groups — safe on external object caches.
	 *
	 * @return void
	 */
	public static function clear_all_cache(): void {
		if ( static::$cache_enabled ) {
			wp_cache_set( 'last_changed', microtime(), static::cache_group() );
		}
	}

	/**
	 * Run a callback inside a database transaction.
	 *
	 * Commits on success, rolls back if the callback throws, then re-throws.
	 *
	 * @template T
	 * @param callable():T $callback Work to perform atomically.
	 * @return T
	 *
	 * @throws \Throwable Whatever the callback throws (after rollback).
	 */
	public static function transaction( callable $callback ): mixed {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( 'START TRANSACTION' );

		try {
			$result = $callback();
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'COMMIT' );
			return $result;
		} catch ( \Throwable $e ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ROLLBACK' );
			throw $e;
		}
	}

	/**
	 * Hydrate a raw database row into a model object (decodes JSON columns).
	 *
	 * @param object $row Database row.
	 * @return object
	 */
	protected static function hydrate( object $row ): object {
		foreach ( static::$json_columns as $column ) {
			if ( isset( $row->$column ) && is_string( $row->$column ) ) {
				$row->$column = static::decode_json( $row->$column );
			}
		}
		return $row;
	}

	/**
	 * Build a blog-scoped WHERE clause from an allowlisted condition map.
	 *
	 * @param array<string, mixed> $where Column => value conditions.
	 * @return array{0: string, 1: array<int, mixed>} [ clause, prepared values ]
	 */
	protected static function build_where( array $where ): array {
		$conditions = array( 'blog_id = %d' );
		$values     = array( static::get_blog_id() );

		foreach ( $where as $column => $value ) {
			if ( ! static::is_valid_column( $column ) ) {
				continue;
			}

			if ( null === $value ) {
				$conditions[] = "{$column} IS NULL";
			} elseif ( is_int( $value ) ) {
				$conditions[] = "{$column} = %d";
				$values[]     = $value;
			} else {
				$conditions[] = "{$column} = %s";
				$values[]     = $value;
			}
		}

		return array( implode( ' AND ', $conditions ), $values );
	}

	/**
	 * Whether a column is allowed in dynamic query fragments.
	 *
	 * @param string $column Column name.
	 * @return bool
	 */
	protected static function is_valid_column( string $column ): bool {
		return in_array( $column, static::$allowed_columns, true );
	}

	/**
	 * Encode all configured JSON columns present in the data array.
	 *
	 * @param array<string, mixed> $data Data to write.
	 * @return array<string, mixed>
	 */
	protected static function encode_json_columns( array $data ): array {
		foreach ( static::$json_columns as $column ) {
			if ( isset( $data[ $column ] ) && ! is_string( $data[ $column ] ) ) {
				$data[ $column ] = static::encode_json( $data[ $column ] );
			}
		}
		return $data;
	}

	/**
	 * Object-cache group for this model.
	 *
	 * @return string
	 */
	protected static function cache_group(): string {
		return 'canvas:' . static::$table;
	}

	/**
	 * Cache key for a record, namespaced by the group's last_changed marker so a
	 * single clear_all_cache() invalidates every key at once.
	 *
	 * @param int $id Primary key value.
	 * @return string
	 */
	protected static function cache_key( int $id ): string {
		return $id . ':' . wp_cache_get_last_changed( static::cache_group() );
	}
}
