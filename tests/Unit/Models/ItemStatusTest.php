<?php
/**
 * Item_Status Enum Unit Tests
 *
 * Pure-PHP tests for the status enum; no WordPress required.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Tests\Unit\Models;

use Canvas\Models\Item_Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test the Item_Status enum.
 */
final class ItemStatusTest extends TestCase {

	/**
	 * values() returns every backing string.
	 */
	public function test_values_lists_all_cases(): void {
		$this->assertSame( array( 'draft', 'active', 'archived' ), Item_Status::values() );
	}

	/**
	 * from_value() resolves known values.
	 */
	public function test_from_value_resolves_known(): void {
		$this->assertSame( Item_Status::Active, Item_Status::from_value( 'active' ) );
		$this->assertSame( Item_Status::Archived, Item_Status::from_value( 'archived' ) );
	}

	/**
	 * from_value() falls back to Draft for unknown/invalid input.
	 *
	 * @param mixed $input Raw value.
	 */
	#[DataProvider( 'invalid_provider' )]
	public function test_from_value_falls_back_to_draft( mixed $input ): void {
		$this->assertSame( Item_Status::Draft, Item_Status::from_value( $input ) );
	}

	/**
	 * Invalid inputs for from_value().
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public static function invalid_provider(): array {
		return array(
			'unknown string' => array( 'nope' ),
			'empty string'   => array( '' ),
			'integer'        => array( 5 ),
			'null'           => array( null ),
		);
	}
}
