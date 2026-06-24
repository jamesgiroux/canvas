<?php
/**
 * Admin Assets
 *
 * Enqueues the React admin bundle and the optional block-editor bundle, and
 * localizes the data the front end needs (REST URL, nonce, capabilities,
 * settings). Assets load only on the plugin's own admin screens.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Admin;

use Canvas\Contracts\Registrable;
use Canvas\Plugin;
use Canvas\Settings\Settings;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset enqueuer.
 */
final class Assets implements Registrable {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Enqueue the admin SPA on plugin screens only.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin( string $hook_suffix ): void {
		if ( ! $this->is_plugin_screen( $hook_suffix ) ) {
			return;
		}

		$asset = $this->asset_meta( CANVAS_PATH . 'build/index.asset.php' );

		wp_enqueue_script(
			'canvas-admin',
			CANVAS_URL . 'build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'canvas-admin',
			CANVAS_URL . 'build/index.css',
			array( 'wp-components' ),
			$asset['version']
		);

		wp_localize_script( 'canvas-admin', 'canvasData', $this->script_data() );
		wp_set_script_translations( 'canvas-admin', 'canvas', CANVAS_PATH . 'languages' );
	}

	/**
	 * Data passed to the admin SPA.
	 *
	 * @return array<string, mixed>
	 */
	private function script_data(): array {
		$user = wp_get_current_user();

		return array(
			'apiUrl'      => rest_url( Plugin::API_NAMESPACE ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'adminUrl'    => admin_url(),
			'pluginUrl'   => CANVAS_URL,
			'version'     => CANVAS_VERSION,
			'currentUser' => array(
				'id'           => $user->ID,
				'displayName'  => $user->display_name,
				'capabilities' => array(
					'manage' => current_user_can( Menu::MANAGE_CAP ),
					'view'   => current_user_can( Menu::VIEW_CAP ),
					'edit'   => current_user_can( 'edit_canvas_content' ),
				),
			),
			'settings'    => Settings::all(),
		);
	}

	/**
	 * Read a wp-scripts asset manifest, falling back to sane defaults.
	 *
	 * @param string $path Path to the *.asset.php file.
	 * @return array{dependencies: array<int, string>, version: string}
	 */
	private function asset_meta( string $path ): array {
		if ( is_readable( $path ) ) {
			$meta = require $path;
			if ( is_array( $meta ) ) {
				return array(
					'dependencies' => $meta['dependencies'] ?? array(),
					'version'      => $meta['version'] ?? CANVAS_VERSION,
				);
			}
		}

		return array(
			'dependencies' => array(),
			'version'      => CANVAS_VERSION,
		);
	}

	/**
	 * Whether the given hook suffix is one of the plugin's screens.
	 *
	 * @param string $hook_suffix Current admin page hook suffix.
	 * @return bool
	 */
	private function is_plugin_screen( string $hook_suffix ): bool {
		$slug    = Plugin::ADMIN_SLUG;
		$screens = array(
			'toplevel_page_' . $slug,
			$slug . '_page_' . $slug . '-items',
			$slug . '_page_' . $slug . '-settings',
		);

		return in_array( $hook_suffix, $screens, true );
	}
}
