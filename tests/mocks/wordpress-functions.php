<?php
/**
 * WordPress Function Mocks
 *
 * Minimal mocks for running unit tests without WordPress.
 * Add more mocks as needed for your tests.
 *
 * @package Canvas
 */

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action.
	 *
	 * @param string $tag Hook name.
	 * @param mixed  $callback Callback function.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Number of args.
	 */
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// No-op for unit tests.
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Mock add_filter.
	 *
	 * @param string $tag Hook name.
	 * @param mixed  $callback Callback function.
	 * @param int    $priority Priority.
	 * @param int    $accepted_args Number of args.
	 */
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// No-op for unit tests.
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function.
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Text domain.
	 * @return string Unchanged text.
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * Mock escaped translation function.
	 *
	 * @param string $text Text to translate.
	 * @param string $domain Text domain.
	 * @return string Escaped text.
	 */
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Mock wp_json_encode.
	 *
	 * @param mixed $data Data to encode.
	 * @param int   $options JSON options.
	 * @param int   $depth Max depth.
	 * @return string|false JSON string.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field.
	 *
	 * @param string $str String to sanitize.
	 * @return string Sanitized string.
	 */
	function sanitize_text_field( $str ) {
		return htmlspecialchars( strip_tags( $str ), ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Mock absint.
	 *
	 * @param mixed $maybeint Number to convert.
	 * @return int Absolute integer.
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

if ( ! function_exists( 'get_current_blog_id' ) ) {
	/**
	 * Mock get_current_blog_id.
	 *
	 * @return int Blog ID.
	 */
	function get_current_blog_id() {
		return 1;
	}
}

// Simple in-memory transient storage for testing.
global $mock_transients;
$mock_transients = array();

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Mock get_transient.
	 *
	 * @param string $transient Transient name.
	 * @return mixed Transient value or false.
	 */
	function get_transient( $transient ) {
		global $mock_transients;

		if ( isset( $mock_transients[ $transient ] ) ) {
			$data = $mock_transients[ $transient ];
			// Check expiration.
			if ( $data['expiration'] === 0 || $data['expiration'] > time() ) {
				return $data['value'];
			}
			unset( $mock_transients[ $transient ] );
		}

		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Mock set_transient.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Value to store.
	 * @param int    $expiration Time until expiration in seconds.
	 * @return bool True on success.
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $mock_transients;

		$mock_transients[ $transient ] = array(
			'value'      => $value,
			'expiration' => $expiration > 0 ? time() + $expiration : 0,
		);

		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Mock delete_transient.
	 *
	 * @param string $transient Transient name.
	 * @return bool True on success.
	 */
	function delete_transient( $transient ) {
		global $mock_transients;

		if ( isset( $mock_transients[ $transient ] ) ) {
			unset( $mock_transients[ $transient ] );
			return true;
		}

		return false;
	}
}

// WordPress time constants.
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}
