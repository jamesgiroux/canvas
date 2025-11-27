/**
 * API Constants
 *
 * Centralized API endpoint paths and configuration.
 *
 * @package Canvas
 */

/**
 * API namespace and version.
 */
export const API_NAMESPACE = 'canvas/v1';

/**
 * API endpoint paths (relative to namespace).
 */
export const API_ENDPOINTS = {
	// Items endpoints.
	ITEMS: '/items',
	ITEM: ( id ) => `/items/${ id }`,

	// Settings endpoints.
	SETTINGS: '/settings',
};

/**
 * Get full API path with namespace.
 *
 * @param {string} endpoint The endpoint path.
 * @return {string} Full API path.
 */
export const getApiPath = ( endpoint ) => `/${ API_NAMESPACE }${ endpoint }`;

/**
 * API paths (full paths including namespace).
 */
export const API_PATHS = {
	// Items.
	ITEMS: getApiPath( API_ENDPOINTS.ITEMS ),
	ITEM: ( id ) => getApiPath( API_ENDPOINTS.ITEM( id ) ),

	// Settings.
	SETTINGS: getApiPath( API_ENDPOINTS.SETTINGS ),
};

/**
 * Default API fetch options.
 */
export const API_DEFAULTS = {
	ITEMS_PER_PAGE: 20,
	MAX_ITEMS_PER_PAGE: 100,
};
