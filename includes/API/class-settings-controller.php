<?php
/**
 * Settings REST Controller
 *
 * Exposes the plugin settings over REST. All schema, defaults, and sanitization
 * live in Canvas\Settings\Settings — this controller is just the HTTP surface.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\API;

use Canvas\Settings\Settings;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings controller.
 *
 * Routes:
 * - GET  /canvas/v1/settings  Get all settings
 * - POST /canvas/v1/settings  Update settings
 */
final class Settings_Controller extends Base_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

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
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'get_settings_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'update_settings_permissions_check' ),
					'args'                => Settings::rest_args(),
				),
			)
		);
	}

	/**
	 * Get all settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ): WP_REST_Response {
		return $this->success_response( Settings::all() );
	}

	/**
	 * Update settings from the provided keys.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_settings( $request ): WP_REST_Response {
		$updates = array();
		foreach ( array_keys( Settings::defaults() ) as $key ) {
			if ( $request->has_param( $key ) ) {
				$updates[ $key ] = $request->get_param( $key );
			}
		}

		return $this->success_response( Settings::update( $updates ) );
	}

	/**
	 * Permission check for reading settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function get_settings_permissions_check( $request ): bool|\WP_Error {
		return $this->check_permission( 'view_canvas' );
	}

	/**
	 * Permission check for updating settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function update_settings_permissions_check( $request ): bool|\WP_Error {
		return $this->check_permission( 'manage_canvas' );
	}
}
