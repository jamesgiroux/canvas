/**
 * Dashboard Page
 *
 * Main overview page showing key metrics and recent activity.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { useEffect, useMemo } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import MetricCard from '../components/primitives/MetricCard';
import { useItems } from '../hooks';

/**
 * Dashboard page component.
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onNavigate Navigation callback for sub-routes.
 * @return {JSX.Element} The dashboard page.
 */
export default function Dashboard( { onNavigate } ) {
	const { items, loading, error, fetchItems } = useItems();

	// Fetch items on mount.
	useEffect( () => {
		fetchItems();
	}, [ fetchItems ] );

	// Calculate metrics from items - memoized for performance.
	const metrics = useMemo( () => {
		return {
			total: items.length,
			active: items.filter( ( i ) => i.status === 'active' ).length,
			draft: items.filter( ( i ) => i.status === 'draft' ).length,
			archived: items.filter( ( i ) => i.status === 'archived' ).length,
		};
	}, [ items ] );

	return (
		<div className="canvas-page-content">
			{/* Page Header */}
			<div className="canvas-page-header">
				<div className="canvas-page-header-content">
					<h1 className="canvas-page-title">
						{ __( 'Dashboard', 'canvas' ) }
					</h1>
					<p className="canvas-page-description">
						{ __( 'Overview of your items and recent activity.', 'canvas' ) }
					</p>
				</div>
			</div>

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{/* Metrics Section */}
			<div className="canvas-page-section">
				<h2>{ __( 'Metrics', 'canvas' ) }</h2>
				<MetricCard.Grid columns={ 4 }>
					<MetricCard
						label={ __( 'Total Items', 'canvas' ) }
						value={ metrics.total }
						icon="archive"
						loading={ loading }
					/>
					<MetricCard
						label={ __( 'Active', 'canvas' ) }
						value={ metrics.active }
						icon="yes-alt"
						variant="success"
						loading={ loading }
					/>
					<MetricCard
						label={ __( 'Draft', 'canvas' ) }
						value={ metrics.draft }
						icon="edit"
						loading={ loading }
					/>
					<MetricCard
						label={ __( 'Archived', 'canvas' ) }
						value={ metrics.archived }
						icon="archive"
						variant="info"
						loading={ loading }
					/>
				</MetricCard.Grid>
			</div>

			{/* Recent Items Section */}
			<div className="canvas-page-section">
				<h2>{ __( 'Recent Items', 'canvas' ) }</h2>
				{ loading ? (
					<div className="canvas-loading">
						<span>{ __( 'Loading...', 'canvas' ) }</span>
					</div>
				) : items.length === 0 ? (
					<div className="canvas-empty-state">
						<span className="dashicons dashicons-portfolio" />
						<h3>{ __( 'No items yet', 'canvas' ) }</h3>
						<p>{ __( 'Create your first item to get started.', 'canvas' ) }</p>
					</div>
				) : (
					<ul className="canvas-recent-list">
						{ items.slice( 0, 5 ).map( ( item ) => (
							<li key={ item.id } className="canvas-recent-list__item">
								<span className="canvas-recent-list__title">
									{ item.title }
								</span>
								<span className="canvas-recent-list__meta">
									{ item.status }
								</span>
							</li>
						) ) }
					</ul>
				) }
			</div>
		</div>
	);
}
