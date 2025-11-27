<?php
/**
 * Base REST Controller
 *
 * Abstract base class for REST API controllers. Provides common functionality
 * for permission checking, input sanitization, and response formatting.
 *
 * @package Canvas
 */

namespace Canvas\API;

use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Controller class.
 *
 * Extend this class to create REST API endpoints with built-in:
 * - Permission checking
 * - Nonce verification
 * - Input sanitization
 * - Standard response formatting
 * - Pagination helpers
 */
abstract class Base_Controller extends WP_REST_Controller {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'canvas/v1';

	/**
	 * Route base for this controller.
	 *
	 * Override in child classes.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Default items per page.
	 *
	 * @var int
	 */
	protected int $default_per_page = 20;

	/**
	 * Maximum items per page.
	 *
	 * @var int
	 */
	protected int $max_per_page = 100;

	/**
	 * Check if user has required capability.
	 *
	 * @param string $capability The capability to check.
	 * @return bool|WP_Error True if has capability, WP_Error if not.
	 */
	protected function check_permission( string $capability ): bool|WP_Error {
		if ( ! current_user_can( $capability ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to perform this action.', 'canvas' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Verify the request nonce.
	 *
	 * Use this for non-cookie authenticated requests that include a nonce.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @param string          $action The nonce action (default: 'wp_rest').
	 * @return bool|WP_Error True if valid, WP_Error if not.
	 */
	protected function verify_nonce( WP_REST_Request $request, string $action = 'wp_rest' ): bool|WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( ! $nonce ) {
			$nonce = $request->get_param( '_wpnonce' );
		}

		if ( ! $nonce || ! wp_verify_nonce( $nonce, $action ) ) {
			return new WP_Error(
				'rest_cookie_invalid_nonce',
				__( 'Invalid or expired nonce.', 'canvas' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Sanitize a text input.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_text( mixed $value ): string {
		return sanitize_text_field( wp_unslash( $value ?? '' ) );
	}

	/**
	 * Sanitize a textarea input.
	 *
	 * Allows newlines but sanitizes other content.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized string.
	 */
	protected function sanitize_textarea( mixed $value ): string {
		return sanitize_textarea_field( wp_unslash( $value ?? '' ) );
	}

	/**
	 * Sanitize an integer input.
	 *
	 * @param mixed $value The value to sanitize.
	 * @param int   $default Default value if input is invalid.
	 * @return int Sanitized integer.
	 */
	protected function sanitize_int( mixed $value, int $default = 0 ): int {
		$sanitized = absint( $value );
		return $sanitized > 0 ? $sanitized : $default;
	}

	/**
	 * Sanitize a boolean input.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return bool Sanitized boolean.
	 */
	protected function sanitize_bool( mixed $value ): bool {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Sanitize an email input.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized email or empty string if invalid.
	 */
	protected function sanitize_email( mixed $value ): string {
		return sanitize_email( $value ?? '' );
	}

	/**
	 * Sanitize a URL input.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return string Sanitized URL.
	 */
	protected function sanitize_url( mixed $value ): string {
		return esc_url_raw( $value ?? '' );
	}

	/**
	 * Sanitize an array of allowed values.
	 *
	 * @param mixed         $value The value to check.
	 * @param array<string> $allowed Array of allowed values.
	 * @param string        $default Default value if not in allowed list.
	 * @return string The value if allowed, or default.
	 */
	protected function sanitize_enum( mixed $value, array $allowed, string $default ): string {
		$value = $this->sanitize_text( $value );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Get pagination parameters from request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return array{page: int, per_page: int, offset: int} Pagination parameters.
	 */
	protected function get_pagination_params( WP_REST_Request $request ): array {
		$page     = max( 1, $this->sanitize_int( $request->get_param( 'page' ), 1 ) );
		$per_page = min(
			$this->max_per_page,
			max( 1, $this->sanitize_int( $request->get_param( 'per_page' ), $this->default_per_page ) )
		);
		$offset   = ( $page - 1 ) * $per_page;

		return array(
			'page'     => $page,
			'per_page' => $per_page,
			'offset'   => $offset,
		);
	}

	/**
	 * Create a paginated response.
	 *
	 * @param array<mixed> $items The items for this page.
	 * @param int          $total Total items across all pages.
	 * @param int          $page Current page number.
	 * @param int          $per_page Items per page.
	 * @return WP_REST_Response Response with pagination headers.
	 */
	protected function paginated_response(
		array $items,
		int $total,
		int $page,
		int $per_page
	): WP_REST_Response {
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );

		$response = new WP_REST_Response(
			array(
				'items'      => $items,
				'pagination' => array(
					'total'       => $total,
					'total_pages' => $total_pages,
					'page'        => $page,
					'per_page'    => $per_page,
					'has_more'    => $page < $total_pages,
				),
			),
			200
		);

		// Add standard pagination headers.
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', $total_pages );

		return $response;
	}

	/**
	 * Create a success response.
	 *
	 * @param mixed $data Response data.
	 * @param int   $status HTTP status code.
	 * @return WP_REST_Response Success response.
	 */
	protected function success_response( mixed $data, int $status = 200 ): WP_REST_Response {
		return new WP_REST_Response( $data, $status );
	}

	/**
	 * Create an error response.
	 *
	 * @param string $code Error code.
	 * @param string $message Error message.
	 * @param int    $status HTTP status code.
	 * @return WP_Error Error response.
	 */
	protected function error_response( string $code, string $message, int $status = 400 ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * Standard permission check for viewing items.
	 *
	 * Override this in child classes for custom permissions.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function get_items_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'view_canvas' );
	}

	/**
	 * Standard permission check for viewing a single item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function get_item_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'view_canvas' );
	}

	/**
	 * Standard permission check for creating items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function create_item_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'edit_canvas_content' );
	}

	/**
	 * Standard permission check for updating items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function update_item_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'edit_canvas_content' );
	}

	/**
	 * Standard permission check for deleting items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function delete_item_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'manage_canvas' );
	}

	/**
	 * Get the common query parameters for collection endpoints.
	 *
	 * @return array<string, array<string, mixed>> Query parameter schema.
	 */
	public function get_collection_params(): array {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'canvas' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items per page.', 'canvas' ),
				'type'              => 'integer',
				'default'           => $this->default_per_page,
				'minimum'           => 1,
				'maximum'           => $this->max_per_page,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => __( 'Search term.', 'canvas' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'    => array(
				'description' => __( 'Sort order.', 'canvas' ),
				'type'        => 'string',
				'default'     => 'desc',
				'enum'        => array( 'asc', 'desc' ),
			),
			'orderby'  => array(
				'description' => __( 'Sort by field.', 'canvas' ),
				'type'        => 'string',
				'default'     => 'id',
			),
		);
	}
}
