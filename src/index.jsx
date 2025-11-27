/**
 * Canvas Plugin Entry Point
 *
 * Main entry point for the React admin application.
 * Renders the AppShell component into the WordPress admin page.
 *
 * @package Canvas
 */

import { createRoot } from '@wordpress/element';
import AppShell from './components/layout/AppShell';

// Import styles.
import './styles/main.scss';

/**
 * Initialize the application when DOM is ready.
 */
document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.getElementById( 'canvas-root' );

	if ( container ) {
		const root = createRoot( container );
		root.render( <AppShell /> );
	}
} );
