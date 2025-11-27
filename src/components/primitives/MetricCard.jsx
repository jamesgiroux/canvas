/**
 * MetricCard Component
 *
 * Displays a key metric with label, value, and optional trend.
 * Used in dashboards and summary views.
 *
 * @package Canvas
 */

import { Card, CardBody, Icon, Spinner } from '@wordpress/components';

/**
 * MetricCard component.
 *
 * @param {Object}      props           Component props.
 * @param {string}      props.label     Metric label/title.
 * @param {string|number} props.value   Metric value to display.
 * @param {string}      props.icon      Optional dashicon name.
 * @param {string}      props.trend     Trend direction (up, down, neutral).
 * @param {string}      props.trendValue Trend percentage or value.
 * @param {string}      props.variant   Card variant for styling.
 * @param {boolean}     props.loading   Show loading state.
 * @param {string}      props.className Additional CSS classes.
 * @return {JSX.Element} The metric card component.
 */
export default function MetricCard( {
	label,
	value,
	icon,
	trend,
	trendValue,
	variant = 'default',
	loading = false,
	className = '',
} ) {
	const classes = [
		'canvas-metric-card',
		`canvas-metric-card--${ variant }`,
		className,
	]
		.filter( Boolean )
		.join( ' ' );

	const getTrendIcon = () => {
		switch ( trend ) {
			case 'up':
				return 'arrow-up-alt';
			case 'down':
				return 'arrow-down-alt';
			default:
				return 'minus';
		}
	};

	const getTrendClass = () => {
		switch ( trend ) {
			case 'up':
				return 'canvas-metric-card__trend--up';
			case 'down':
				return 'canvas-metric-card__trend--down';
			default:
				return 'canvas-metric-card__trend--neutral';
		}
	};

	return (
		<Card className={ classes }>
			<CardBody className="canvas-metric-card__body">
				{ icon && (
					<div className="canvas-metric-card__icon">
						<Icon icon={ icon } size={ 24 } />
					</div>
				) }

				<div className="canvas-metric-card__content">
					<span className="canvas-metric-card__label">{ label }</span>

					{ loading ? (
						<div className="canvas-metric-card__loading">
							<Spinner />
						</div>
					) : (
						<span className="canvas-metric-card__value">
							{ value }
						</span>
					) }

					{ trend && trendValue && ! loading && (
						<div
							className={ `canvas-metric-card__trend ${ getTrendClass() }` }
						>
							<Icon icon={ getTrendIcon() } size={ 16 } />
							<span>{ trendValue }</span>
						</div>
					) }
				</div>
			</CardBody>
		</Card>
	);
}

/**
 * Grid container for multiple MetricCards.
 *
 * @param {Object}      props          Component props.
 * @param {JSX.Element} props.children Child MetricCard components.
 * @param {number}      props.columns  Number of columns (default 4).
 * @return {JSX.Element} The metric grid component.
 */
MetricCard.Grid = function MetricGrid( { children, columns = 4 } ) {
	return (
		<div
			className="canvas-metric-grid"
			style={ {
				display: 'grid',
				gridTemplateColumns: `repeat(${ columns }, 1fr)`,
				gap: '16px',
			} }
		>
			{ children }
		</div>
	);
};
