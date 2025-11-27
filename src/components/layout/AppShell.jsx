/**
 * Application Shell Component
 *
 * Main app routing and layout using simple flexbox structure.
 * Full-screen takeover that hides WordPress admin UI.
 *
 * @package Canvas
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { SnackbarList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

import Sidebar from './Sidebar';
import ErrorBoundary from './ErrorBoundary';

// Views
import Dashboard from '../../pages/Dashboard';
import Items from '../../pages/Items';
import Settings from '../../pages/Settings';

// Register stores
import '../../store';

/**
 * Parse hash URL for sub-routing within a page.
 *
 * @return {Object} Route info with view and params.
 */
const parseRoute = () => {
	const hash = window.location.hash.slice( 1 ); // Remove #
	const [ view, params ] = hash.split( '?' );

	const parsedParams = {};
	if ( params ) {
		params.split( '&' ).forEach( ( param ) => {
			const [ key, value ] = param.split( '=' );
			parsedParams[ key ] = decodeURIComponent( value );
		} );
	}

	return {
		view: view || 'list',
		params: parsedParams,
	};
};

/**
 * Main application shell.
 *
 * @return {JSX.Element} AppShell component.
 */
export default function AppShell() {
	const [ route, setRoute ] = useState( parseRoute() );
	const [ transitionState, setTransitionState ] = useState( 'idle' );
	const [ displayedRoute, setDisplayedRoute ] = useState( parseRoute() );
	const transitionTimeoutRef = useRef( null );

	// Handle route transitions with animation.
	const transitionToRoute = useCallback( ( newRoute ) => {
		if ( transitionTimeoutRef.current ) {
			clearTimeout( transitionTimeoutRef.current );
		}

		setTransitionState( 'entering' );
		setDisplayedRoute( newRoute );

		transitionTimeoutRef.current = setTimeout( () => {
			setTransitionState( 'idle' );
		}, 150 );
	}, [] );

	// Handle hash changes for sub-routing.
	useEffect( () => {
		const handleHashChange = () => {
			const newRoute = parseRoute();
			setRoute( newRoute );
			transitionToRoute( newRoute );
		};

		window.addEventListener( 'hashchange', handleHashChange );
		return () => {
			window.removeEventListener( 'hashchange', handleHashChange );
			if ( transitionTimeoutRef.current ) {
				clearTimeout( transitionTimeoutRef.current );
			}
		};
	}, [ transitionToRoute ] );

	/**
	 * Navigate to a hash route (for sub-views within a page).
	 *
	 * @param {string} view   View name.
	 * @param {Object} params Route params.
	 */
	const navigate = ( view, params = {} ) => {
		const paramString = Object.keys( params )
			.map( ( key ) => `${ key }=${ encodeURIComponent( params[ key ] ) }` )
			.join( '&' );

		const hash = paramString ? `#${ view }?${ paramString }` : `#${ view }`;
		window.location.hash = hash;
	};

	/**
	 * Handle sidebar navigation (page-based).
	 *
	 * @param {string} pageId Page ID.
	 */
	const handleSidebarNavigate = ( pageId ) => {
		window.location.href = `admin.php?page=${ pageId }`;
	};

	// Determine current page from URL.
	const urlParams = new URLSearchParams( window.location.search );
	const page = urlParams.get( 'page' );

	/**
	 * Render the appropriate view based on current page.
	 */
	const renderContent = () => {
		// Main dashboard page.
		if ( page === 'canvas' ) {
			return <Dashboard onNavigate={ navigate } />;
		}

		// Items page.
		if ( page === 'canvas-items' ) {
			return <Items onNavigate={ navigate } />;
		}

		// Settings page.
		if ( page === 'canvas-settings' ) {
			return <Settings />;
		}

		// Default fallback.
		return <Dashboard onNavigate={ navigate } />;
	};

	// Get notices for Snackbar display.
	const notices = useSelect(
		( select ) =>
			select( noticesStore )
				.getNotices()
				.filter( ( notice ) => notice.type === 'snackbar' ),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

	// Get transition class.
	const getTransitionClass = () => {
		if ( transitionState === 'entering' ) {
			return 'canvas-view canvas-view-entering';
		}
		return 'canvas-view';
	};

	return (
		<>
			<div className="canvas-container">
				<div className="canvas-sidebar">
					<div className="canvas-sidebar-inner">
						<Sidebar
							currentPage={ page }
							onNavigate={ handleSidebarNavigate }
						/>
					</div>
				</div>
				<div className="canvas-content">
					<div className="canvas-content-inner">
						<div className="canvas-view-container">
							<div className={ getTransitionClass() }>
								<ErrorBoundary>
									{ renderContent() }
								</ErrorBoundary>
							</div>
						</div>
					</div>
				</div>
			</div>
			<SnackbarList
				notices={ notices }
				onRemove={ removeNotice }
			/>
		</>
	);
}
