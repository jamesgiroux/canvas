<?php
/**
 * Plugin Settings
 *
 * Single source of truth for the plugin's settings: the option name, the
 * default values, the REST argument schema, and per-key sanitization. The
 * installer, REST controller, cron, and uninstall routine all defer to this
 * class so the schema can never drift between them.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings registry.
 */
final class Settings {

	/**
	 * Option name where settings are stored.
	 *
	 * @var string
	 */
	public const OPTION = 'canvas_settings';

	/**
	 * Default settings values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'site_title'                 => '',
			'items_per_page'             => '20',
			'notifications_enabled'      => false,
			'debug_mode'                 => false,
			'preserve_data_on_uninstall' => false,
		);
	}

	/**
	 * Get all settings, merged over defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function all(): array {
		$stored = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $fallback Value to return when the key is unknown.
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$all = self::all();
		return $all[ $key ] ?? $fallback;
	}

	/**
	 * Persist a partial set of settings, sanitizing each provided key.
	 *
	 * Unknown keys are ignored. Returns the full, merged settings array.
	 *
	 * @param array<string, mixed> $updates Key => value pairs to update.
	 * @return array<string, mixed>
	 */
	public static function update( array $updates ): array {
		$current  = self::all();
		$defaults = self::defaults();

		foreach ( $updates as $key => $value ) {
			if ( array_key_exists( $key, $defaults ) ) {
				$current[ $key ] = self::sanitize( $key, $value );
			}
		}

		update_option( self::OPTION, $current );

		return $current;
	}

	/**
	 * Ensure the option exists with defaults (used on activation).
	 *
	 * @return void
	 */
	public static function install_defaults(): void {
		if ( false === get_option( self::OPTION ) ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	/**
	 * Sanitize a single setting value according to its key.
	 *
	 * @param string $key   Setting key.
	 * @param mixed  $value Raw value.
	 * @return mixed Sanitized value.
	 */
	public static function sanitize( string $key, mixed $value ): mixed {
		return match ( $key ) {
			'site_title'            => sanitize_text_field( (string) $value ),
			'items_per_page'        => self::sanitize_enum( $value, array( '10', '20', '50', '100' ), '20' ),
			'notifications_enabled',
			'debug_mode',
			'preserve_data_on_uninstall' => (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN ),
			default                 => sanitize_text_field( (string) $value ),
		};
	}

	/**
	 * REST argument schema for the settings endpoint.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function rest_args(): array {
		return array(
			'site_title'                 => array(
				'description'       => __( 'Custom site title.', 'canvas' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'items_per_page'             => array(
				'description' => __( 'Items per page.', 'canvas' ),
				'type'        => 'string',
				'enum'        => array( '10', '20', '50', '100' ),
			),
			'notifications_enabled'      => array(
				'description' => __( 'Enable notifications.', 'canvas' ),
				'type'        => 'boolean',
			),
			'debug_mode'                 => array(
				'description' => __( 'Enable debug mode.', 'canvas' ),
				'type'        => 'boolean',
			),
			'preserve_data_on_uninstall' => array(
				'description' => __( 'Keep plugin data when the plugin is deleted.', 'canvas' ),
				'type'        => 'boolean',
			),
		);
	}

	/**
	 * Constrain a value to an allowed set.
	 *
	 * @param mixed         $value    Raw value.
	 * @param array<string> $allowed  Allowed values.
	 * @param string        $fallback Value to use when not allowed.
	 * @return string
	 */
	private static function sanitize_enum( mixed $value, array $allowed, string $fallback ): string {
		$value = sanitize_text_field( (string) $value );
		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}
}
