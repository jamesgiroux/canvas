/**
 * Sidebar Navigation Component
 *
 * Left sidebar navigation for Canvas app with:
 * - Back to WP Admin link
 * - Plugin branding (logo, title, description)
 * - Sectioned navigation
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Navigation Section Header Component.
 *
 * @param {Object} props       Component props.
 * @param {string} props.title Section title.
 * @return {JSX.Element} Section header.
 */
const SectionHeader = ( { title } ) => (
	<div className="canvas-nav-section-header">
		<span className="canvas-nav-section-title">{ title }</span>
	</div>
);

/**
 * Sidebar Navigation Component.
 *
 * @param {Object}   props             Component props.
 * @param {string}   props.currentPage Current page ID.
 * @param {Function} props.onNavigate  Navigation callback.
 * @return {JSX.Element} Sidebar component.
 */
export default function Sidebar( { currentPage, onNavigate } ) {
	// Main section navigation items.
	const mainItems = [
		{
			id: 'canvas',
			label: __( 'Dashboard', 'canvas' ),
			icon: 'dashboard',
		},
		{
			id: 'canvas-items',
			label: __( 'Items', 'canvas' ),
			icon: 'list-view',
		},
	];

	// System section navigation items.
	const systemItems = [
		{
			id: 'canvas-settings',
			label: __( 'Settings', 'canvas' ),
			icon: 'admin-settings',
		},
	];

	/**
	 * Handle navigation click.
	 *
	 * @param {string} pageId Page ID to navigate to.
	 */
	const handleNavigate = ( pageId ) => {
		if ( onNavigate ) {
			onNavigate( pageId );
		} else {
			window.location.href = `admin.php?page=${ pageId }`;
		}
	};

	/**
	 * Render navigation items.
	 *
	 * @param {Array} items Navigation items.
	 * @return {JSX.Element[]} Rendered nav items.
	 */
	const renderNavItems = ( items ) =>
		items.map( ( item ) => {
			const isActive = currentPage === item.id;
			return (
				<Button
					key={ item.id }
					className={ `canvas-nav-item ${ isActive ? 'is-active' : '' }` }
					onClick={ () => handleNavigate( item.id ) }
					aria-current={ isActive ? 'page' : undefined }
				>
					<span
						className={ `dashicons dashicons-${ item.icon }` }
						aria-hidden="true"
					/>
					{ item.label }
				</Button>
			);
		} );

	return (
		<>
			{/* Back to WP Admin button */}
			<div className="canvas-back-to-admin">
				<a href={ window.canvasData?.adminUrl || '/wp-admin/' }>
					<span
						className="dashicons dashicons-wordpress"
						aria-hidden="true"
					/>
					{ __( 'Back to WP Admin', 'canvas' ) }
				</a>
			</div>

			{/* Branding header */}
			<div className="canvas-sidebar-header">
				<h1>
					<span
						className="dashicons dashicons-art canvas-logo"
						aria-hidden="true"
					/>
					{ __( 'Canvas', 'canvas' ) }
				</h1>
				<p className="canvas-sidebar-subtitle">
					{ __( 'WordPress plugin starter framework.', 'canvas' ) }
				</p>
			</div>

			{/* Navigation menu with sections */}
			<nav
				className="canvas-nav"
				aria-label={ __( 'Canvas navigation', 'canvas' ) }
			>
				{/* Main Section */}
				<SectionHeader title={ __( 'Main', 'canvas' ) } />
				{ renderNavItems( mainItems ) }

				{/* System Section */}
				<SectionHeader title={ __( 'System', 'canvas' ) } />
				{ renderNavItems( systemItems ) }
			</nav>
		</>
	);
}
