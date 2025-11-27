<?php
/**
 * Settings REST Controller
 *
 * Handles plugin settings via REST API.
 *
 * @package Canvas
 */

namespace Canvas\API;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Controller class.
 *
 * Provides REST API endpoints for plugin settings:
 * - GET  /canvas/v1/settings - Get all settings
 * - POST /canvas/v1/settings - Update settings
 */
class Settings_Controller extends Base_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'settings';

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'canvas_settings';

	/**
	 * Default settings.
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = array(
		'site_title'            => '',
		'items_per_page'        => '20',
		'notifications_enabled' => false,
		'debug_mode'            => false,
		'log_retention'         => '30',
	);

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
					'args'                => $this->get_settings_args(),
				),
			)
		);
	}

	/**
	 * Get all settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response Response with settings.
	 */
	public function get_settings( $request ): WP_REST_Response {
		$settings = get_option( self::OPTION_NAME, array() );
		$settings = wp_parse_args( $settings, $this->defaults );

		return $this->success_response( $settings );
	}

	/**
	 * Update settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_settings( $request ): WP_REST_Response|WP_Error {
		$current = get_option( self::OPTION_NAME, array() );
		$updates = array();

		// Process each setting from request.
		foreach ( array_keys( $this->defaults ) as $key ) {
			if ( $request->has_param( $key ) ) {
				$updates[ $key ] = $this->sanitize_setting( $key, $request->get_param( $key ) );
			}
		}

		// Merge with current settings.
		$settings = array_merge( $current, $updates );

		// Save to database.
		update_option( self::OPTION_NAME, $settings );

		// Return merged settings.
		$settings = wp_parse_args( $settings, $this->defaults );

		return $this->success_response( $settings );
	}

	/**
	 * Sanitize a setting value based on its key.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Value to sanitize.
	 * @return mixed Sanitized value.
	 */
	private function sanitize_setting( string $key, mixed $value ): mixed {
		switch ( $key ) {
			case 'site_title':
				return $this->sanitize_text( $value );

			case 'items_per_page':
				return $this->sanitize_enum( $value, array( '10', '20', '50', '100' ), '20' );

			case 'log_retention':
				return $this->sanitize_enum( $value, array( '7', '30', '90', '365' ), '30' );

			case 'notifications_enabled':
			case 'debug_mode':
				return $this->sanitize_bool( $value );

			default:
				return $this->sanitize_text( $value );
		}
	}

	/**
	 * Permission check for getting settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function get_settings_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'view_canvas' );
	}

	/**
	 * Permission check for updating settings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if permitted, WP_Error if not.
	 */
	public function update_settings_permissions_check( $request ): bool|WP_Error {
		return $this->check_permission( 'manage_canvas' );
	}

	/**
	 * Get arguments for settings endpoint.
	 *
	 * @return array<string, array<string, mixed>> Argument schema.
	 */
	private function get_settings_args(): array {
		return array(
			'site_title'            => array(
				'description'       => __( 'Custom site title.', 'canvas' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'items_per_page'        => array(
				'description' => __( 'Items per page.', 'canvas' ),
				'type'        => 'string',
				'enum'        => array( '10', '20', '50', '100' ),
			),
			'notifications_enabled' => array(
				'description' => __( 'Enable notifications.', 'canvas' ),
				'type'        => 'boolean',
			),
			'debug_mode'            => array(
				'description' => __( 'Enable debug mode.', 'canvas' ),
				'type'        => 'boolean',
			),
			'log_retention'         => array(
				'description' => __( 'Log retention period in days.', 'canvas' ),
				'type'        => 'string',
				'enum'        => array( '7', '30', '90', '365' ),
			),
		);
	}
}
