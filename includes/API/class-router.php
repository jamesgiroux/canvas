<?php
/**
 * REST Router
 *
 * Instantiates and registers the plugin's REST controllers on rest_api_init.
 * Add new controllers to the list in controllers().
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\API;

use Canvas\Contracts\Registrable;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller registrar.
 */
final class Router implements Registrable {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes for every controller.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		foreach ( $this->controllers() as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * The plugin's REST controllers.
	 *
	 * @return array<int, Base_Controller>
	 */
	private function controllers(): array {
		return array(
			new Items_Controller(),
			new Settings_Controller(),
		);
	}
}
