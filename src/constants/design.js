/**
 * Design System Constants
 *
 * Centralized design tokens for consistent styling.
 * Based on WordPress admin color scheme.
 *
 * @package Canvas
 */

/**
 * Spacing scale based on 4px grid.
 */
export const SPACING = {
	xs: '4px',
	sm: '8px',
	md: '16px',
	lg: '24px',
	xl: '32px',
	xxl: '48px',
};

/**
 * Color palette following WordPress admin conventions.
 * Uses CSS variables where possible for theme compatibility.
 */
export const COLORS = {
	// Primary colors
	primary: 'var(--wp-admin-theme-color, #007cba)',
	primaryDark: 'var(--wp-admin-theme-color-darker-10, #006ba1)',
	primaryLight: 'var(--wp-admin-theme-color-lighter-20, #4da1cc)',

	// Semantic colors
	success: '#00a32a',
	successBackground: '#edfaef',
	warning: '#dba617',
	warningBackground: '#fcf9e8',
	error: '#d63638',
	errorBackground: '#fcf0f1',
	info: '#72aee6',
	infoBackground: '#f0f6fc',

	// Neutrals
	text: '#1e1e1e',
	textSecondary: '#757575',
	textMuted: '#949494',
	border: '#c3c4c7',
	borderLight: '#dcdcde',
	background: '#f0f0f1',
	backgroundAlt: '#ffffff',

	// WordPress admin specific
	adminBar: '#23282d',
	adminBarText: '#ffffff',
};

/**
 * Badge variant definitions.
 */
export const BADGE_VARIANTS = {
	default: {
		background: COLORS.background,
		color: COLORS.text,
		border: COLORS.border,
	},
	success: {
		background: COLORS.successBackground,
		color: COLORS.success,
		border: COLORS.success,
	},
	warning: {
		background: COLORS.warningBackground,
		color: '#6e4d00',
		border: COLORS.warning,
	},
	error: {
		background: COLORS.errorBackground,
		color: COLORS.error,
		border: COLORS.error,
	},
	info: {
		background: COLORS.infoBackground,
		color: '#0a4b78',
		border: COLORS.info,
	},
};

/**
 * Typography scale.
 */
export const TYPOGRAPHY = {
	fontFamily: "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif",
	fontSize: {
		xs: '11px',
		sm: '12px',
		md: '13px',
		lg: '14px',
		xl: '16px',
		xxl: '20px',
		h1: '23px',
		h2: '20px',
		h3: '17px',
	},
	fontWeight: {
		normal: 400,
		medium: 500,
		semibold: 600,
		bold: 700,
	},
	lineHeight: {
		tight: 1.2,
		normal: 1.4,
		relaxed: 1.6,
	},
};

/**
 * Breakpoints for responsive design.
 */
export const BREAKPOINTS = {
	mobile: '600px',
	tablet: '782px',
	desktop: '960px',
	wide: '1280px',
};

/**
 * Shadow definitions.
 */
export const SHADOWS = {
	sm: '0 1px 2px rgba(0, 0, 0, 0.05)',
	md: '0 1px 3px rgba(0, 0, 0, 0.1)',
	lg: '0 2px 8px rgba(0, 0, 0, 0.15)',
	xl: '0 4px 16px rgba(0, 0, 0, 0.15)',
};

/**
 * Border radius values.
 */
export const RADIUS = {
	sm: '2px',
	md: '4px',
	lg: '8px',
	full: '9999px',
};

/**
 * Z-index layers.
 */
export const Z_INDEX = {
	dropdown: 100,
	sticky: 200,
	modal: 1000,
	popover: 1100,
	tooltip: 1200,
};

/**
 * Animation durations.
 */
export const ANIMATION = {
	fast: '150ms',
	normal: '250ms',
	slow: '400ms',
};
