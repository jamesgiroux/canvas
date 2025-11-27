<?php
/**
 * Item Model Integration Tests
 *
 * Integration tests that require WordPress test environment.
 * These tests interact with the actual database.
 *
 * To run integration tests:
 * 1. Install WordPress test suite (see README)
 * 2. Set WP_TESTS_DIR environment variable
 * 3. Run: composer test
 *
 * @package Canvas
 */

namespace Canvas\Tests\Integration;

use WP_UnitTestCase;
use Canvas\Models\Item;

/**
 * Test Item model with database integration.
 *
 * Note: This test requires WordPress test environment.
 * It will be skipped if WP_UnitTestCase is not available.
 *
 * @group integration
 * @group database
 */
class ItemModelIntegrationTest extends WP_UnitTestCase {

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();

		// Ensure tables exist.
		// In a real scenario, run migrations here.
	}

	/**
	 * Clean up after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();

		// Clean up test data.
		global $wpdb;
		$table = $wpdb->prefix . 'canvas_items';
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore
	}

	/**
	 * Test creating an item.
	 *
	 * @covers \Canvas\Models\Item::insert
	 */
	public function test_create_item(): void {
		$data = array(
			'title'   => 'Test Item',
			'content' => 'Test content',
			'status'  => 'draft',
		);

		$id = Item::insert( $data );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );
	}

	/**
	 * Test finding an item by ID.
	 *
	 * @covers \Canvas\Models\Item::find
	 */
	public function test_find_item(): void {
		// Create item first.
		$id = Item::insert(
			array(
				'title'  => 'Find Test',
				'status' => 'active',
			)
		);

		$item = Item::find( $id );

		$this->assertIsObject( $item );
		$this->assertEquals( 'Find Test', $item->title );
		$this->assertEquals( 'active', $item->status );
	}

	/**
	 * Test finding non-existent item returns null.
	 *
	 * @covers \Canvas\Models\Item::find
	 */
	public function test_find_nonexistent_item_returns_null(): void {
		$item = Item::find( 99999 );

		$this->assertNull( $item );
	}

	/**
	 * Test updating an item.
	 *
	 * @covers \Canvas\Models\Item::update
	 */
	public function test_update_item(): void {
		$id = Item::insert(
			array(
				'title'  => 'Original Title',
				'status' => 'draft',
			)
		);

		$result = Item::update(
			$id,
			array(
				'title'  => 'Updated Title',
				'status' => 'active',
			)
		);

		$this->assertTrue( $result );

		$item = Item::find( $id );
		$this->assertEquals( 'Updated Title', $item->title );
		$this->assertEquals( 'active', $item->status );
	}

	/**
	 * Test deleting an item.
	 *
	 * @covers \Canvas\Models\Item::delete
	 */
	public function test_delete_item(): void {
		$id = Item::insert(
			array(
				'title'  => 'Delete Test',
				'status' => 'draft',
			)
		);

		$result = Item::delete( $id );

		$this->assertTrue( $result );
		$this->assertNull( Item::find( $id ) );
	}

	/**
	 * Test find_all with filters.
	 *
	 * @covers \Canvas\Models\Item::find_all
	 */
	public function test_find_all_with_status_filter(): void {
		// Create items with different statuses.
		Item::insert( array( 'title' => 'Active 1', 'status' => 'active' ) );
		Item::insert( array( 'title' => 'Active 2', 'status' => 'active' ) );
		Item::insert( array( 'title' => 'Draft 1', 'status' => 'draft' ) );

		$active_items = Item::find_all( array( 'status' => 'active' ) );
		$draft_items  = Item::find_all( array( 'status' => 'draft' ) );

		$this->assertCount( 2, $active_items );
		$this->assertCount( 1, $draft_items );
	}

	/**
	 * Test count method.
	 *
	 * @covers \Canvas\Models\Item::count
	 */
	public function test_count_items(): void {
		Item::insert( array( 'title' => 'Item 1', 'status' => 'active' ) );
		Item::insert( array( 'title' => 'Item 2', 'status' => 'active' ) );
		Item::insert( array( 'title' => 'Item 3', 'status' => 'draft' ) );

		$total  = Item::count();
		$active = Item::count( array( 'status' => 'active' ) );

		$this->assertEquals( 3, $total );
		$this->assertEquals( 2, $active );
	}

	/**
	 * Test JSON column handling for meta field.
	 *
	 * @covers \Canvas\Models\Item::insert
	 * @covers \Canvas\Models\Item::find
	 */
	public function test_json_column_roundtrip(): void {
		$meta = array(
			'key'    => 'value',
			'nested' => array( 'a' => 1, 'b' => 2 ),
		);

		$id = Item::insert(
			array(
				'title'  => 'Meta Test',
				'status' => 'draft',
				'meta'   => $meta,
			)
		);

		$item = Item::find( $id );

		$this->assertIsArray( $item->meta );
		$this->assertEquals( 'value', $item->meta['key'] );
		$this->assertEquals( array( 'a' => 1, 'b' => 2 ), $item->meta['nested'] );
	}

	/**
	 * Test pagination with limit and offset.
	 *
	 * @covers \Canvas\Models\Item::find_all
	 */
	public function test_pagination(): void {
		// Create 10 items.
		for ( $i = 1; $i <= 10; $i++ ) {
			Item::insert(
				array(
					'title'  => "Item {$i}",
					'status' => 'active',
				)
			);
		}

		$page1 = Item::find_all( array(), 'id', 'ASC', 5, 0 );
		$page2 = Item::find_all( array(), 'id', 'ASC', 5, 5 );

		$this->assertCount( 5, $page1 );
		$this->assertCount( 5, $page2 );

		// Verify different items.
		$this->assertNotEquals( $page1[0]->id, $page2[0]->id );
	}

	/**
	 * Test blog ID isolation (multisite).
	 *
	 * @covers \Canvas\Models\Item::find
	 */
	public function test_blog_id_isolation(): void {
		// Create item on current blog.
		$id = Item::insert(
			array(
				'title'  => 'Blog 1 Item',
				'status' => 'active',
			)
		);

		// Item should be findable.
		$item = Item::find( $id );
		$this->assertNotNull( $item );

		// Manually change blog_id in DB to simulate another blog's item.
		global $wpdb;
		$table = $wpdb->prefix . 'canvas_items';
		$wpdb->update(
			$table,
			array( 'blog_id' => 999 ),
			array( 'id' => $id )
		);

		// Item should no longer be findable (different blog_id).
		$item = Item::find( $id );
		$this->assertNull( $item );
	}

	/**
	 * Test caching behavior.
	 *
	 * @covers \Canvas\Models\Item::find
	 */
	public function test_cache_is_cleared_on_update(): void {
		$id = Item::insert(
			array(
				'title'  => 'Cache Test',
				'status' => 'draft',
			)
		);

		// First find - populates cache.
		$item1 = Item::find( $id );
		$this->assertEquals( 'Cache Test', $item1->title );

		// Update item.
		Item::update( $id, array( 'title' => 'Updated Cache Test' ) );

		// Second find - should get updated data (cache cleared).
		$item2 = Item::find( $id );
		$this->assertEquals( 'Updated Cache Test', $item2->title );
	}
}
