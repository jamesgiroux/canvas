<?php
/**
 * Item Model
 *
 * Example model showing how to extend Base_Model. Copy and modify this for your
 * own data models.
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
 * Item model.
 *
 * Demonstrates JSON columns, custom finders, an enum-backed status column, and
 * domain methods built on the Base_Model primitives.
 */
final class Item extends Base_Model {

	/**
	 * Table name without prefix.
	 *
	 * @var string
	 */
	protected static string $table = 'canvas_items';

	/**
	 * JSON columns.
	 *
	 * @var array<int, string>
	 */
	protected static array $json_columns = array( 'meta' );

	/**
	 * Query allowlist.
	 *
	 * @var array<int, string>
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
	 * @param Item_Status $status Status to filter by.
	 * @param int         $limit  Maximum items.
	 * @return array<int, object>
	 */
	public static function find_by_status( Item_Status $status, int $limit = 100 ): array {
		return self::find_all( array( 'status' => $status->value ), 'created_at', 'DESC', $limit );
	}

	/**
	 * Find items by author.
	 *
	 * @param int $author_id Author user ID.
	 * @param int $limit     Maximum items.
	 * @return array<int, object>
	 */
	public static function find_by_author( int $author_id, int $limit = 100 ): array {
		return self::find_all( array( 'author_id' => $author_id ), 'created_at', 'DESC', $limit );
	}

	/**
	 * Search items by title.
	 *
	 * @param string $search Search term.
	 * @param int    $limit  Maximum items.
	 * @return array<int, object>
	 */
	public static function search( string $search, int $limit = 100 ): array {
		return self::find_like( 'title', $search, 'created_at', 'DESC', $limit );
	}

	/**
	 * Get the most recent items.
	 *
	 * @param int $limit Maximum items.
	 * @return array<int, object>
	 */
	public static function get_recent( int $limit = 10 ): array {
		return self::find_all( array(), 'created_at', 'DESC', $limit );
	}

	/**
	 * Transition an item to a new status.
	 *
	 * @param int         $id     Item ID.
	 * @param Item_Status $status Target status.
	 * @return bool
	 */
	public static function set_status( int $id, Item_Status $status ): bool {
		return self::update( $id, array( 'status' => $status->value ) );
	}

	/**
	 * Archive an item.
	 *
	 * @param int $id Item ID.
	 * @return bool
	 */
	public static function archive( int $id ): bool {
		return self::set_status( $id, Item_Status::Archived );
	}

	/**
	 * Activate an item.
	 *
	 * @param int $id Item ID.
	 * @return bool
	 */
	public static function activate( int $id ): bool {
		return self::set_status( $id, Item_Status::Active );
	}
}
