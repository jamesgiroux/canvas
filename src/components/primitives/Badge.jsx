/**
 * Badge Component
 *
 * Displays status or category badges with semantic colors.
 * Supports multiple variants for different contexts.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { BADGE_VARIANTS } from '../../constants/design';

/**
 * Badge component.
 *
 * @param {Object}  props           Component props.
 * @param {string}  props.children  Badge text content.
 * @param {string}  props.variant   Badge variant (default, success, warning, error, info).
 * @param {string}  props.size      Badge size (small, medium, large).
 * @param {string}  props.className Additional CSS classes.
 * @return {JSX.Element} The badge component.
 */
export default function Badge( {
	children,
	variant = 'default',
	size = 'medium',
	className = '',
} ) {
	// Get variant styles or fall back to default.
	const variantStyles = BADGE_VARIANTS[ variant ] || BADGE_VARIANTS.default;

	const style = {
		backgroundColor: variantStyles.background,
		color: variantStyles.color,
		borderColor: variantStyles.border,
	};

	const classes = [
		'canvas-badge',
		`canvas-badge--${ variant }`,
		`canvas-badge--${ size }`,
		className,
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<span className={ classes } style={ style }>
			{ children }
		</span>
	);
}

/**
 * Predefined badge types for common use cases.
 */
Badge.Status = function StatusBadge( { status, ...props } ) {
	const statusMap = {
		active: { variant: 'success', label: __( 'Active', 'canvas' ) },
		draft: { variant: 'default', label: __( 'Draft', 'canvas' ) },
		pending: { variant: 'warning', label: __( 'Pending', 'canvas' ) },
		archived: { variant: 'info', label: __( 'Archived', 'canvas' ) },
		error: { variant: 'error', label: __( 'Error', 'canvas' ) },
	};

	const config = statusMap[ status ] || statusMap.draft;

	return (
		<Badge variant={ config.variant } { ...props }>
			{ config.label }
		</Badge>
	);
};

Badge.Count = function CountBadge( { count, ...props } ) {
	return (
		<Badge variant="info" size="small" { ...props }>
			{ count }
		</Badge>
	);
};
