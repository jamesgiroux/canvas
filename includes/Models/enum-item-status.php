<?php
/**
 * Item Status
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
 * The lifecycle states an Item can be in.
 *
 * Backed string enum so values map directly to the `status` column and to REST
 * `enum` schemas. Use Item_Status::values() wherever a list of valid statuses
 * is needed instead of hand-maintaining duplicate arrays.
 */
enum Item_Status: string {
	case Draft    = 'draft';
	case Active   = 'active';
	case Archived = 'archived';

	/**
	 * All status values as strings.
	 *
	 * @return array<int, string>
	 */
	public static function values(): array {
		return array_map( static fn ( self $status ): string => $status->value, self::cases() );
	}

	/**
	 * Resolve a raw value to a case, falling back to Draft.
	 *
	 * @param mixed $value Raw value.
	 * @return self
	 */
	public static function from_value( mixed $value ): self {
		return self::tryFrom( is_string( $value ) ? $value : '' ) ?? self::Draft;
	}
}
