<?php
/**
 * Items REST Controller
 *
 * Example CRUD controller showing how to extend Base_Controller. Copy and
 * modify this for your own data models.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\API;

use Canvas\Models\Item;
use Canvas\Models\Item_Status;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Items controller.
 *
 * Routes:
 * - GET    /canvas/v1/items       List items
 * - POST   /canvas/v1/items       Create item
 * - GET    /canvas/v1/items/{id}  Get single item
 * - PUT    /canvas/v1/items/{id}  Update item
 * - DELETE /canvas/v1/items/{id}  Delete item
 */
final class Items_Controller extends Base_Controller {

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
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_list_args(),
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
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( $request ): WP_REST_Response {
		$pagination = $this->get_pagination_params( $request );
		$order      = strtoupper( $this->sanitize_enum( $request->get_param( 'order' ), array( 'asc', 'desc' ), 'desc' ) );
		$order_by   = $this->sanitize_enum(
			$request->get_param( 'orderby' ),
			array( 'id', 'title', 'created_at', 'updated_at' ),
			'id'
		);
		$search     = $this->sanitize_text( $request->get_param( 'search' ) );

		if ( '' !== $search ) {
			$items = Item::search( $search, $pagination['per_page'] );
			$total = count( $items );
		} else {
			$where  = array();
			$status = $request->get_param( 'status' );
			if ( $status ) {
				$where['status'] = Item_Status::from_value( $status )->value;
			}

			$items = Item::find_all( $where, $order_by, $order, $pagination['per_page'], $pagination['offset'] );
			$total = Item::count( $where );
		}

		$response_items = array_map( array( $this, 'prepare_item_data' ), $items );

		return $this->paginated_response( $response_items, $total, $pagination['page'], $pagination['per_page'] );
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ): WP_REST_Response|WP_Error {
		$item = Item::find( $this->sanitize_int( $request->get_param( 'id' ) ) );

		if ( ! $item ) {
			return $this->error_response( 'item_not_found', __( 'Item not found.', 'canvas' ), 404 );
		}

		return $this->success_response( $this->prepare_item_data( $item ) );
	}

	/**
	 * Create an item.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_item( $request ): WP_REST_Response|WP_Error {
		$title = $this->sanitize_text( $request->get_param( 'title' ) );

		if ( '' === $title ) {
			return $this->error_response( 'missing_title', __( 'Title is required.', 'canvas' ), 400 );
		}

		$id = Item::insert(
			array(
				'title'     => $title,
				'content'   => $this->sanitize_textarea( $request->get_param( 'content' ) ),
				'status'    => $this->sanitize_status( $request->get_param( 'status' ) ),
				'meta'      => $request->get_param( 'meta' ) ?? array(),
				'author_id' => get_current_user_id(),
			)
		);

		if ( ! $id ) {
			return $this->error_response( 'create_failed', __( 'Failed to create item.', 'canvas' ), 500 );
		}

		return $this->success_response( $this->prepare_item_data( Item::find( $id ) ), 201 );
	}

	/**
	 * Update an item.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_item( $request ): WP_REST_Response|WP_Error {
		$id   = $this->sanitize_int( $request->get_param( 'id' ) );
		$item = Item::find( $id );

		if ( ! $item ) {
			return $this->error_response( 'item_not_found', __( 'Item not found.', 'canvas' ), 404 );
		}

		$data = array();

		if ( $request->has_param( 'title' ) ) {
			$data['title'] = $this->sanitize_text( $request->get_param( 'title' ) );
		}
		if ( $request->has_param( 'content' ) ) {
			$data['content'] = $this->sanitize_textarea( $request->get_param( 'content' ) );
		}
		if ( $request->has_param( 'status' ) ) {
			$data['status'] = $this->sanitize_status( $request->get_param( 'status' ) );
		}
		if ( $request->has_param( 'meta' ) ) {
			$data['meta'] = $request->get_param( 'meta' );
		}

		if ( $data && ! Item::update( $id, $data ) ) {
			return $this->error_response( 'update_failed', __( 'Failed to update item.', 'canvas' ), 500 );
		}

		return $this->success_response( $this->prepare_item_data( Item::find( $id ) ) );
	}

	/**
	 * Delete an item.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ): WP_REST_Response|WP_Error {
		$id = $this->sanitize_int( $request->get_param( 'id' ) );

		if ( ! Item::find( $id ) ) {
			return $this->error_response( 'item_not_found', __( 'Item not found.', 'canvas' ), 404 );
		}

		if ( ! Item::delete( $id ) ) {
			return $this->error_response( 'delete_failed', __( 'Failed to delete item.', 'canvas' ), 500 );
		}

		return $this->success_response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * Shape an item row into the response payload.
	 *
	 * @param object $item Item row.
	 * @return array<string, mixed>
	 */
	private function prepare_item_data( object $item ): array {
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
	 * Coerce a raw status value to a valid status string.
	 *
	 * @param mixed $value Raw status.
	 * @return string
	 */
	private function sanitize_status( mixed $value ): string {
		return Item_Status::from_value( $value )->value;
	}

	/**
	 * Arguments for the list endpoint (collection params + status filter).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function get_list_args(): array {
		return array_merge(
			$this->get_collection_params(),
			array(
				'status' => array(
					'description' => __( 'Filter by status.', 'canvas' ),
					'type'        => 'string',
					'enum'        => Item_Status::values(),
				),
			)
		);
	}

	/**
	 * Arguments for the create endpoint.
	 *
	 * @return array<string, array<string, mixed>>
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
				'default'     => Item_Status::Draft->value,
				'enum'        => Item_Status::values(),
			),
			'meta'    => array(
				'description' => __( 'Additional metadata.', 'canvas' ),
				'type'        => 'object',
				'default'     => array(),
			),
		);
	}

	/**
	 * Arguments for the update endpoint (all fields optional).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	protected function get_update_args(): array {
		$args = $this->get_create_args();
		foreach ( $args as $key => $arg ) {
			$args[ $key ]['required'] = false;
		}
		return $args;
	}

	/**
	 * Public item schema.
	 *
	 * @return array<string, mixed>
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
					'enum'        => Item_Status::values(),
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
