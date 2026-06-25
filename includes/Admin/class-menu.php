<?php
/**
 * Admin Menu
 *
 * Registers the plugin's admin menu pages. Every page renders the same React
 * root element; the SPA decides what to show based on the `page` query arg.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Admin;

use Canvas\Contracts\Registrable;
use Canvas\Plugin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin menu registrar.
 */
final class Menu implements Registrable {

	/**
	 * Capability required to view the plugin's admin pages.
	 *
	 * @var string
	 */
	public const VIEW_CAP = 'view_canvas';

	/**
	 * Capability required to manage settings.
	 *
	 * @var string
	 */
	public const MANAGE_CAP = 'manage_canvas';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'register_pages' ) );
	}

	/**
	 * Register the menu and submenu pages.
	 *
	 * @return void
	 */
	public function register_pages(): void {
		$slug = Plugin::ADMIN_SLUG;

		add_menu_page(
			__( 'Canvas', 'canvas' ),
			__( 'Canvas', 'canvas' ),
			self::VIEW_CAP,
			$slug,
			array( $this, 'render' ),
			'dashicons-art',
			30
		);

		$subpages = array(
			array( $slug, __( 'Dashboard', 'canvas' ), self::VIEW_CAP ),
			array( $slug . '-items', __( 'Items', 'canvas' ), self::VIEW_CAP ),
			array( $slug . '-settings', __( 'Settings', 'canvas' ), self::MANAGE_CAP ),
		);

		foreach ( $subpages as [ $page_slug, $title, $capability ] ) {
			add_submenu_page( $slug, $title, $title, $capability, $page_slug, array( $this, 'render' ) );
		}
	}

	/**
	 * Render the React root element.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::VIEW_CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'canvas' ) );
		}

		echo '<div id="canvas-root" class="canvas-admin-wrap"></div>';
	}
}
