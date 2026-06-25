<?php
/**
 * Main Plugin Class
 *
 * Thin orchestrator that wires the plugin's components together. It holds the
 * shared identifiers (REST namespace, admin slug) and delegates all real work to
 * focused, single-responsibility components implementing the Registrable contract.
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas;

use Canvas\Contracts\Registrable;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin loader.
 *
 * Singleton bootstrap. Construct the component graph once, then call register()
 * to let each component add its own hooks.
 */
final class Plugin {

	/**
	 * Single instance of this class.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * REST API namespace shared by all controllers.
	 *
	 * @var string
	 */
	public const API_NAMESPACE = 'canvas/v1';

	/**
	 * Top-level admin page slug.
	 *
	 * @var string
	 */
	public const ADMIN_SLUG = 'canvas';

	/**
	 * Registered components.
	 *
	 * @var array<int, Registrable>
	 */
	private array $components;

	/**
	 * Build the component graph.
	 */
	private function __construct() {
		$this->components = array(
			new Admin\Menu(),
			new Admin\Assets(),
			new API\Router(),
			new Database\Migration_Runner(),
		);
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin The plugin instance.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register every component's hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		foreach ( $this->components as $component ) {
			$component->register();
		}
	}
}
