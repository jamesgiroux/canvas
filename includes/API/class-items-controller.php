<?php
/**
 * Items REST Controller
 *
 * Example CRUD controller showing how to extend Base_Controller.
 * Copy and modify this for your own data models.
 *
 * @package Canvas
 */

namespace Canvas\API;

use Canvas\Models\Item;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Items Controller class.
 *
 * Provides REST API endpoints for the items model:
 * - GET    /canvas/v1/items       - List items
 * - POST   /canvas/v1/items       - Create item
 * - GET    /canvas/v1/items/{id}  - Get single item
 * - PUT    /canvas/v1/items/{id}  - Update item
 * - DELETE /canvas/v1/items/{id}  - Delete item
 */
class Items_Controller extends Base_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'items';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// Collection routes.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_create_args(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Single item routes.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'id' => array(
							'description' => __( 'Unique identifier for the item.', 'canvas' ),
							'type'        => 'integer',
							'required'    => true,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_update_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Get a collection of items.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_items( $request ): WP_REST_Response|WP_Error {
		// Get pagination params.
		$pagination = $this->get_pagination_params( $request );

		// Build query conditions.
		$where = array();

		// Filter by status if provided.
		$status = $request->get_param( 'status' );
		if ( $status ) {
			$where['status'] = $this->sanitize_text( $status );
		}

		// Get order params.
		$order_by = $this->sanitize_enum(
			$request->get_param( 'orderby' ),
			array( 'id', 'title', 'created_at', 'updated_at' ),
			'id'
		);
		$order    = $this->sanitize_enum(
			$request->get_param( 'order' ),
			array( 'asc', 'desc' ),
			'desc'
		);

		// Fetch items from model.
		$items = Item::find_all(
			$where,
			$order_by,
			strtoupper( $order ),
			$pagination['per_page'],
			$pagination['offset']
		);

		// Get total count.
		$total = Item::count( $where );

		// Transform items for response.
		$response_items = array_map(
			function ( $item ) {
				return $this->prepare_item_for_response( $item );
			},
			$items
		);

		return $this->paginated_response(
			$response_items,
			$total,
			$pagination['page'],
			$pagination['per_page']
		);
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$id   = $this->sanitize_int( $request->get_param( 'id' ) );
		$item = Item::find( $id );

		if ( ! $item ) {
			return $this->error_response(
				'item_not_found',
				__( 'Item not found.', 'canvas' ),
				404
			);
		}

		return $this->success_response( $this->prepare_item_for_response( $item ) );
	}

	/**
	 * Create a new item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		// Validate and sanitize input.
		$data = array(
			'title'     => $this->sanitize_text( $request->get_param( 'title' ) ),
			'content'   => $this->sanitize_textarea( $request->get_param( 'content' ) ),
			'status'    => $this->sanitize_enum(
				$request->get_param( 'status' ),
				array( 'draft', 'active', 'archived' ),
				'draft'
			),
			'meta'      => $request->get_param( 'meta' ) ?? array(),
			'author_id' => get_current_user_id(),
		);

		// Validate required fields.
		if ( empty( $data['title'] ) ) {
			return $this->error_response(
				'missing_title',
				__( 'Title is required.', 'canvas' ),
				400
			);
		}

		// Insert into database.
		$id = Item::insert( $data );

		if ( ! $id ) {
			return $this->error_response(
				'create_failed',
				__( 'Failed to create item.', 'canvas' ),
				500
			);
		}

		// Return created item.
		$item = Item::find( $id );

		return $this->success_response(
			$this->prepare_item_for_response( $item ),
			201
		);
	}

	/**
	 * Update an existing item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id   = $this->sanitize_int( $request->get_param( 'id' ) );
		$item = Item::find( $id );

		if ( ! $item ) {
			return $this->error_response(
				'item_not_found',
				__( 'Item not found.', 'canvas' ),
				404
			);
		}

		// Build update data from provided fields.
		$data = array();

		if ( $request->has_param( 'title' ) ) {
			$data['title'] = $this->sanitize_text( $request->get_param( 'title' ) );
		}

		if ( $request->has_param( 'content' ) ) {
			$data['content'] = $this->sanitize_textarea( $request->get_param( 'content' ) );
		}

		if ( $request->has_param( 'status' ) ) {
			$data['status'] = $this->sanitize_enum(
				$request->get_param( 'status' ),
				array( 'draft', 'active', 'archived' ),
				$item->status
			);
		}

		if ( $request->has_param( 'meta' ) ) {
			$data['meta'] = $request->get_param( 'meta' );
		}

		// Update in database.
		$success = Item::update( $id, $data );

		if ( ! $success ) {
			return $this->error_response(
				'update_failed',
				__( 'Failed to update item.', 'canvas' ),
				500
			);
		}

		// Return updated item.
		$item = Item::find( $id );

		return $this->success_response( $this->prepare_item_for_response( $item ) );
	}

	/**
	 * Delete an item.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id   = $this->sanitize_int( $request->get_param( 'id' ) );
		$item = Item::find( $id );

		if ( ! $item ) {
			return $this->error_response(
				'item_not_found',
				__( 'Item not found.', 'canvas' ),
				404
			);
		}

		$success = Item::delete( $id );

		if ( ! $success ) {
			return $this->error_response(
				'delete_failed',
				__( 'Failed to delete item.', 'canvas' ),
				500
			);
		}

		return $this->success_response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * Prepare an item for API response.
	 *
	 * @param object $item The item object from database.
	 * @return array<string, mixed> Formatted item data.
	 */
	protected function prepare_item_for_response( object $item ): array {
		return array(
			'id'         => (int) $item->id,
			'title'      => $item->title,
			'content'    => $item->content,
			'status'     => $item->status,
			'meta'       => $item->meta,
			'author_id'  => (int) $item->author_id,
			'created_at' => $item->created_at,
			'updated_at' => $item->updated_at,
		);
	}

	/**
	 * Get arguments for create endpoint.
	 *
	 * @return array<string, array<string, mixed>> Argument schema.
	 */
	protected function get_create_args(): array {
		return array(
			'title'   => array(
				'description'       => __( 'Item title.', 'canvas' ),
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'content' => array(
				'description'       => __( 'Item content.', 'canvas' ),
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'status'  => array(
				'description' => __( 'Item status.', 'canvas' ),
				'type'        => 'string',
				'default'     => 'draft',
				'enum'        => array( 'draft', 'active', 'archived' ),
			),
			'meta'    => array(
				'description' => __( 'Additional metadata.', 'canvas' ),
				'type'        => 'object',
				'default'     => array(),
			),
		);
	}

	/**
	 * Get arguments for update endpoint.
	 *
	 * @return array<string, array<string, mixed>> Argument schema.
	 */
	protected function get_update_args(): array {
		$args = $this->get_create_args();

		// Make all fields optional for updates.
		foreach ( $args as $key => $arg ) {
			$args[ $key ]['required'] = false;
		}

		return $args;
	}

	/**
	 * Get the item schema for documentation.
	 *
	 * @return array<string, mixed> Schema definition.
	 */
	public function get_public_item_schema(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'item',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'description' => __( 'Unique identifier.', 'canvas' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'title'      => array(
					'description' => __( 'Item title.', 'canvas' ),
					'type'        => 'string',
				),
				'content'    => array(
					'description' => __( 'Item content.', 'canvas' ),
					'type'        => 'string',
				),
				'status'     => array(
					'description' => __( 'Item status.', 'canvas' ),
					'type'        => 'string',
					'enum'        => array( 'draft', 'active', 'archived' ),
				),
				'meta'       => array(
					'description' => __( 'Additional metadata.', 'canvas' ),
					'type'        => 'object',
				),
				'author_id'  => array(
					'description' => __( 'Author user ID.', 'canvas' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'created_at' => array(
					'description' => __( 'Creation timestamp.', 'canvas' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
				'updated_at' => array(
					'description' => __( 'Last update timestamp.', 'canvas' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'readonly'    => true,
				),
			),
		);
	}
}
