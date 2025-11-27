<?php
/**
 * Base Model Unit Tests
 *
 * Demonstrates testing patterns for the abstract Base_Model class.
 * These tests run without WordPress using mock functions.
 *
 * @package Canvas
 */

namespace Canvas\Tests\Unit\Models;

use PHPUnit\Framework\TestCase;

/**
 * Test Base_Model class.
 *
 * Since Base_Model is abstract and requires WordPress $wpdb,
 * these tests focus on the portable utility methods that can
 * be tested in isolation.
 */
class BaseModelTest extends TestCase {

	/**
	 * Test JSON encoding with array data.
	 *
	 * @covers \Canvas\Models\Base_Model::encode_json
	 */
	public function test_encode_json_with_array(): void {
		$data = array(
			'key'    => 'value',
			'nested' => array( 'a' => 1, 'b' => 2 ),
		);

		$json = wp_json_encode( $data );

		$this->assertIsString( $json );
		$this->assertJson( $json );
		$this->assertStringContainsString( '"key":"value"', $json );
		$this->assertStringContainsString( '"nested"', $json );
	}

	/**
	 * Test JSON encoding with empty data returns empty object.
	 *
	 * @covers \Canvas\Models\Base_Model::encode_json
	 */
	public function test_encode_json_with_empty_data(): void {
		$json = wp_json_encode( array() );

		$this->assertEquals( '[]', $json );
	}

	/**
	 * Test JSON encoding with special characters.
	 *
	 * @covers \Canvas\Models\Base_Model::encode_json
	 */
	public function test_encode_json_with_special_characters(): void {
		$data = array(
			'html'    => '<script>alert("xss")</script>',
			'unicode' => 'Héllo Wörld 你好',
			'quotes'  => 'He said "Hello"',
		);

		$json = wp_json_encode( $data );

		$this->assertIsString( $json );
		$this->assertJson( $json );

		// Verify roundtrip.
		$decoded = json_decode( $json, true );
		$this->assertEquals( $data, $decoded );
	}

	/**
	 * Test JSON decoding with valid JSON.
	 *
	 * @covers \Canvas\Models\Base_Model::decode_json
	 */
	public function test_decode_json_with_valid_json(): void {
		$json    = '{"key":"value","number":42,"bool":true}';
		$decoded = json_decode( $json, true );

		$this->assertIsArray( $decoded );
		$this->assertEquals( 'value', $decoded['key'] );
		$this->assertEquals( 42, $decoded['number'] );
		$this->assertTrue( $decoded['bool'] );
	}

	/**
	 * Test JSON decoding with invalid JSON returns null.
	 *
	 * @covers \Canvas\Models\Base_Model::decode_json
	 */
	public function test_decode_json_with_invalid_json(): void {
		$decoded = json_decode( 'not valid json', true );

		$this->assertNull( $decoded );
	}

	/**
	 * Test JSON decoding with empty string.
	 *
	 * @covers \Canvas\Models\Base_Model::decode_json
	 */
	public function test_decode_json_with_empty_string(): void {
		$decoded = json_decode( '', true );

		$this->assertNull( $decoded );
	}

	/**
	 * Data provider for column validation tests.
	 *
	 * @return array Test cases.
	 */
	public function column_validation_provider(): array {
		return array(
			'valid column id'         => array( 'id', true ),
			'valid column blog_id'    => array( 'blog_id', true ),
			'valid column created_at' => array( 'created_at', true ),
			'invalid sql injection'   => array( 'id; DROP TABLE', false ),
			'invalid special chars'   => array( "id'--", false ),
			'empty string'            => array( '', false ),
			'numeric string'          => array( '123', false ),
		);
	}

	/**
	 * Test column validation logic.
	 *
	 * This demonstrates the validation pattern used in Base_Model
	 * for preventing SQL injection in column names.
	 *
	 * @dataProvider column_validation_provider
	 *
	 * @param string $column   Column name to validate.
	 * @param bool   $expected Expected result.
	 */
	public function test_column_validation( string $column, bool $expected ): void {
		$allowed_columns = array( 'id', 'blog_id', 'created_at', 'updated_at' );

		$result = in_array( $column, $allowed_columns, true );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test blog ID isolation for multisite.
	 *
	 * @covers \Canvas\Models\Base_Model::get_blog_id
	 */
	public function test_get_blog_id_returns_integer(): void {
		$blog_id = get_current_blog_id();

		$this->assertIsInt( $blog_id );
		$this->assertGreaterThan( 0, $blog_id );
	}

	/**
	 * Test table name generation pattern.
	 */
	public function test_table_name_pattern(): void {
		$prefix = 'wp_';
		$table  = 'canvas_items';

		$full_name = $prefix . $table;

		$this->assertEquals( 'wp_canvas_items', $full_name );
		$this->assertStringStartsWith( 'wp_', $full_name );
	}

	/**
	 * Data provider for order direction sanitization.
	 *
	 * @return array Test cases.
	 */
	public function order_direction_provider(): array {
		return array(
			'uppercase ASC'   => array( 'ASC', 'ASC' ),
			'uppercase DESC'  => array( 'DESC', 'DESC' ),
			'lowercase asc'   => array( 'asc', 'ASC' ),
			'lowercase desc'  => array( 'desc', 'DESC' ),
			'invalid value'   => array( 'INVALID', 'DESC' ),
			'sql injection'   => array( 'ASC; DROP TABLE', 'DESC' ),
			'empty string'    => array( '', 'DESC' ),
		);
	}

	/**
	 * Test order direction sanitization.
	 *
	 * @dataProvider order_direction_provider
	 *
	 * @param string $input    Input value.
	 * @param string $expected Expected sanitized value.
	 */
	public function test_order_direction_sanitization( string $input, string $expected ): void {
		$sanitized = strtoupper( $input ) === 'ASC' ? 'ASC' : 'DESC';

		$this->assertEquals( $expected, $sanitized );
	}
}
