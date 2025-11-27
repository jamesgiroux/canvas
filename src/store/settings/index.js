/**
 * Settings Store
 *
 * WordPress data store for managing plugin settings.
 *
 * @package Canvas
 */

import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { API_PATHS } from '../../constants/api';

/**
 * Store namespace.
 */
export const STORE_NAME = 'canvas/settings';

/**
 * Track success message timeout to prevent memory leaks.
 */
let successTimeoutId = null;

/**
 * Default state.
 */
const DEFAULT_STATE = {
	settings: {},
	loading: false,
	saving: false,
	error: null,
	success: false,
};

/**
 * Action types.
 */
const ACTIONS = {
	SET_SETTINGS: 'SET_SETTINGS',
	UPDATE_SETTING: 'UPDATE_SETTING',
	SET_LOADING: 'SET_LOADING',
	SET_SAVING: 'SET_SAVING',
	SET_ERROR: 'SET_ERROR',
	SET_SUCCESS: 'SET_SUCCESS',
};

/**
 * Actions.
 */
const actions = {
	/**
	 * Set all settings.
	 *
	 * @param {Object} settings Settings object.
	 * @return {Object} Action object.
	 */
	setSettings( settings ) {
		return {
			type: ACTIONS.SET_SETTINGS,
			settings,
		};
	},

	/**
	 * Update single setting.
	 *
	 * @param {string} key   Setting key.
	 * @param {*}      value Setting value.
	 * @return {Object} Action object.
	 */
	updateSetting( key, value ) {
		return {
			type: ACTIONS.UPDATE_SETTING,
			key,
			value,
		};
	},

	/**
	 * Set loading state.
	 *
	 * @param {boolean} loading Loading state.
	 * @return {Object} Action object.
	 */
	setLoading( loading ) {
		return {
			type: ACTIONS.SET_LOADING,
			loading,
		};
	},

	/**
	 * Set saving state.
	 *
	 * @param {boolean} saving Saving state.
	 * @return {Object} Action object.
	 */
	setSaving( saving ) {
		return {
			type: ACTIONS.SET_SAVING,
			saving,
		};
	},

	/**
	 * Set error state.
	 *
	 * @param {string|null} error Error message or null.
	 * @return {Object} Action object.
	 */
	setError( error ) {
		return {
			type: ACTIONS.SET_ERROR,
			error,
		};
	},

	/**
	 * Set success state.
	 *
	 * @param {boolean} success Success state.
	 * @return {Object} Action object.
	 */
	setSuccess( success ) {
		return {
			type: ACTIONS.SET_SUCCESS,
			success,
		};
	},

	/**
	 * Fetch settings from API.
	 *
	 * @return {Function} Thunk function.
	 */
	fetchSettings() {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				const response = await apiFetch( {
					path: API_PATHS.SETTINGS,
				} );

				dispatch.setSettings( response );
			} catch ( error ) {
				dispatch.setError(
					error.message || 'Failed to fetch settings'
				);
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	/**
	 * Save settings to API.
	 *
	 * @param {Object} settings Settings to save.
	 * @return {Function} Thunk function.
	 */
	saveSettings( settings ) {
		return async ( { dispatch } ) => {
			dispatch.setSaving( true );
			dispatch.setError( null );
			dispatch.setSuccess( false );

			try {
				const response = await apiFetch( {
					path: API_PATHS.SETTINGS,
					method: 'POST',
					data: settings,
				} );

				dispatch.setSettings( response );
				dispatch.setSuccess( true );

				// Clear any existing timeout to prevent multiple timers.
				if ( successTimeoutId ) {
					clearTimeout( successTimeoutId );
				}

				// Clear success message after 3 seconds.
				successTimeoutId = setTimeout( () => {
					dispatch.setSuccess( false );
					successTimeoutId = null;
				}, 3000 );
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to save settings' );
			} finally {
				dispatch.setSaving( false );
			}
		};
	},
};

/**
 * Selectors.
 */
const selectors = {
	/**
	 * Get all settings.
	 *
	 * @param {Object} state Store state.
	 * @return {Object} Settings object.
	 */
	getSettings( state ) {
		return state.settings;
	},

	/**
	 * Get single setting.
	 *
	 * @param {Object} state Store state.
	 * @param {string} key   Setting key.
	 * @param {*}      defaultValue Default value if not set.
	 * @return {*} Setting value.
	 */
	getSetting( state, key, defaultValue = null ) {
		return state.settings[ key ] ?? defaultValue;
	},

	/**
	 * Get loading state.
	 *
	 * @param {Object} state Store state.
	 * @return {boolean} Loading state.
	 */
	isLoading( state ) {
		return state.loading;
	},

	/**
	 * Get saving state.
	 *
	 * @param {Object} state Store state.
	 * @return {boolean} Saving state.
	 */
	isSaving( state ) {
		return state.saving;
	},

	/**
	 * Get error state.
	 *
	 * @param {Object} state Store state.
	 * @return {string|null} Error message or null.
	 */
	getError( state ) {
		return state.error;
	},

	/**
	 * Get success state.
	 *
	 * @param {Object} state Store state.
	 * @return {boolean} Success state.
	 */
	isSuccess( state ) {
		return state.success;
	},
};

/**
 * Reducer.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Action object.
 * @return {Object} New state.
 */
function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case ACTIONS.SET_SETTINGS:
			return {
				...state,
				settings: action.settings,
			};

		case ACTIONS.UPDATE_SETTING:
			return {
				...state,
				settings: {
					...state.settings,
					[ action.key ]: action.value,
				},
			};

		case ACTIONS.SET_LOADING:
			return {
				...state,
				loading: action.loading,
			};

		case ACTIONS.SET_SAVING:
			return {
				...state,
				saving: action.saving,
			};

		case ACTIONS.SET_ERROR:
			return {
				...state,
				error: action.error,
			};

		case ACTIONS.SET_SUCCESS:
			return {
				...state,
				success: action.success,
			};

		default:
			return state;
	}
}

/**
 * Create and register the store.
 */
const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
} );

register( store );

export default store;
