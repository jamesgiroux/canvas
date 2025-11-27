/**
 * useSettings Hook
 *
 * Custom hook for accessing settings store with simplified API.
 *
 * @package Canvas
 */

import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { SETTINGS_STORE } from '../store';

/**
 * Hook for settings operations.
 *
 * @return {Object} Settings state and actions.
 */
export function useSettings() {
	// Select state from store.
	const { settings, loading, saving, error, success } = useSelect(
		( select ) => {
			const store = select( SETTINGS_STORE );
			return {
				settings: store.getSettings(),
				loading: store.isLoading(),
				saving: store.isSaving(),
				error: store.getError(),
				success: store.isSuccess(),
			};
		},
		[]
	);

	// Get dispatch functions.
	const {
		fetchSettings: dispatchFetchSettings,
		saveSettings: dispatchSaveSettings,
		updateSetting: dispatchUpdateSetting,
		setError,
		setSuccess,
	} = useDispatch( SETTINGS_STORE );

	// Memoize callbacks.
	const fetchSettings = useCallback(
		() => dispatchFetchSettings(),
		[ dispatchFetchSettings ]
	);

	const saveSettings = useCallback(
		( data ) => dispatchSaveSettings( data ),
		[ dispatchSaveSettings ]
	);

	const updateSetting = useCallback(
		( key, value ) => dispatchUpdateSetting( key, value ),
		[ dispatchUpdateSetting ]
	);

	const clearError = useCallback( () => setError( null ), [ setError ] );

	const clearSuccess = useCallback(
		() => setSuccess( false ),
		[ setSuccess ]
	);

	return {
		// State
		settings,
		loading,
		saving,
		error,
		success,

		// Actions
		fetchSettings,
		saveSettings,
		updateSetting,
		clearError,
		clearSuccess,
	};
}

/**
 * Hook for single setting value.
 *
 * @param {string} key          Setting key.
 * @param {*}      defaultValue Default value if not set.
 * @return {Array} [value, setValue] tuple.
 */
export function useSetting( key, defaultValue = null ) {
	const value = useSelect(
		( select ) => select( SETTINGS_STORE ).getSetting( key, defaultValue ),
		[ key, defaultValue ]
	);

	const { updateSetting } = useDispatch( SETTINGS_STORE );

	const setValue = useCallback(
		( newValue ) => updateSetting( key, newValue ),
		[ updateSetting, key ]
	);

	return [ value, setValue ];
}
