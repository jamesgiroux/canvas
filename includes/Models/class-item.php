<?php
/**
 * Item Model
 *
 * Example model showing how to extend Base_Model.
 * Copy and modify this for your own data models.
 *
 * @package Canvas
 */

namespace Canvas\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Item Model class.
 *
 * Represents items stored in the canvas_items table.
 * Demonstrates the model pattern with JSON column handling.
 */
class Item extends Base_Model {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = 'canvas_items';

	/**
	 * Primary key column.
	 *
	 * @var string
	 */
	protected static string $primary_key = 'id';

	/**
	 * Columns that contain JSON data.
	 *
	 * These will be automatically encoded on insert/update
	 * and decoded on retrieval.
	 *
	 * @var array<string>
	 */
	protected static array $json_columns = array( 'meta' );

	/**
	 * Allowed columns for queries.
	 *
	 * Whitelist of columns that can be used in WHERE and ORDER BY clauses.
	 *
	 * @var array<string>
	 */
	protected static array $allowed_columns = array(
		'id',
		'blog_id',
		'title',
		'content',
		'status',
		'author_id',
		'meta',
		'created_at',
		'updated_at',
	);

	/**
	 * Find items by status.
	 *
	 * Example of a custom finder method.
	 *
	 * @param string $status The status to filter by.
	 * @param int    $limit Maximum items to return.
	 * @return array<object> Array of items.
	 */
	public static function find_by_status( string $status, int $limit = 100 ): array {
		return self::find_all(
			array( 'status' => $status ),
			'created_at',
			'DESC',
			$limit
		);
	}

	/**
	 * Find items by author.
	 *
	 * @param int $author_id The author user ID.
	 * @param int $limit Maximum items to return.
	 * @return array<object> Array of items.
	 */
	public static function find_by_author( int $author_id, int $limit = 100 ): array {
		return self::find_all(
			array( 'author_id' => $author_id ),
			'created_at',
			'DESC',
			$limit
		);
	}

	/**
	 * Search items by title.
	 *
	 * Example of a custom query method.
	 *
	 * @param string $search The search term.
	 * @param int    $limit Maximum items to return.
	 * @return array<object> Array of matching items.
	 */
	public static function search( string $search, int $limit = 100 ): array {
		global $wpdb;

		$table   = self::get_table_name();
		$blog_id = self::get_blog_id();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE blog_id = %d
				AND title LIKE %s
				ORDER BY created_at DESC
				LIMIT %d",
				$blog_id,
				'%' . $wpdb->esc_like( $search ) . '%',
				$limit
			)
		);

		if ( ! $rows ) {
			return array();
		}

		return array_map( array( self::class, 'hydrate' ), $rows );
	}

	/**
	 * Get recent items.
	 *
	 * @param int $limit Maximum items to return.
	 * @return array<object> Array of recent items.
	 */
	public static function get_recent( int $limit = 10 ): array {
		return self::find_all(
			array(),
			'created_at',
			'DESC',
			$limit
		);
	}

	/**
	 * Archive an item.
	 *
	 * Example of a domain-specific method.
	 *
	 * @param int $id The item ID.
	 * @return bool True on success.
	 */
	public static function archive( int $id ): bool {
		return self::update( $id, array( 'status' => 'archived' ) );
	}

	/**
	 * Activate an item.
	 *
	 * @param int $id The item ID.
	 * @return bool True on success.
	 */
	public static function activate( int $id ): bool {
		return self::update( $id, array( 'status' => 'active' ) );
	}
}
