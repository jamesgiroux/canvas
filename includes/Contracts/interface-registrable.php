<?php
/**
 * Registrable Contract
 *
 * @package Canvas
 */

declare(strict_types=1);

namespace Canvas\Contracts;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A component that wires its own WordPress hooks.
 *
 * Each registrable owns one responsibility (admin menu, assets, REST, cron, …)
 * and is responsible for adding the actions/filters it needs. The Plugin loader
 * simply collects registrables and calls register() on each.
 */
interface Registrable {

	/**
	 * Add the component's hooks.
	 *
	 * @return void
	 */
	public function register(): void;
}
