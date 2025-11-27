/**
 * useItems Hook
 *
 * Custom hook for accessing items store with simplified API.
 * Wraps @wordpress/data store for cleaner component usage.
 *
 * @package Canvas
 */

import { useCallback } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { ITEMS_STORE } from '../store';

/**
 * Hook for items CRUD operations.
 *
 * @return {Object} Items state and actions.
 */
export function useItems() {
	// Select state from store.
	const { items, currentItem, loading, error, pagination } = useSelect(
		( select ) => {
			const store = select( ITEMS_STORE );
			return {
				items: store.getItems(),
				currentItem: store.getCurrentItem(),
				loading: store.isLoading(),
				error: store.getError(),
				pagination: store.getPagination(),
			};
		},
		[]
	);

	// Get dispatch functions.
	const {
		fetchItems: dispatchFetchItems,
		fetchItem: dispatchFetchItem,
		createItem: dispatchCreateItem,
		saveItem: dispatchSaveItem,
		deleteItem: dispatchDeleteItem,
		setCurrentItem,
		setError,
	} = useDispatch( ITEMS_STORE );

	// Memoize callbacks for stable references.
	const fetchItems = useCallback(
		( params = {} ) => dispatchFetchItems( params ),
		[ dispatchFetchItems ]
	);

	const fetchItem = useCallback(
		( id ) => dispatchFetchItem( id ),
		[ dispatchFetchItem ]
	);

	const createItem = useCallback(
		( data ) => dispatchCreateItem( data ),
		[ dispatchCreateItem ]
	);

	const updateItem = useCallback(
		( id, data ) => dispatchSaveItem( id, data ),
		[ dispatchSaveItem ]
	);

	const deleteItem = useCallback(
		( id ) => dispatchDeleteItem( id ),
		[ dispatchDeleteItem ]
	);

	const clearCurrentItem = useCallback(
		() => setCurrentItem( null ),
		[ setCurrentItem ]
	);

	const clearError = useCallback( () => setError( null ), [ setError ] );

	return {
		// State
		items,
		currentItem,
		loading,
		error,
		pagination,

		// Actions
		fetchItems,
		fetchItem,
		createItem,
		updateItem,
		deleteItem,
		clearCurrentItem,
		clearError,
	};
}

/**
 * Hook for single item operations.
 *
 * @param {number} id Item ID to load.
 * @return {Object} Item state and actions.
 */
export function useItem( id ) {
	const { items, currentItem, loading, error } = useSelect(
		( select ) => {
			const store = select( ITEMS_STORE );
			return {
				items: store.getItems(),
				currentItem: store.getCurrentItem(),
				loading: store.isLoading(),
				error: store.getError(),
			};
		},
		[]
	);

	const { fetchItem: dispatchFetchItem, saveItem, deleteItem } =
		useDispatch( ITEMS_STORE );

	// Get item from current or find in list.
	const item = currentItem?.id === id ? currentItem : items.find( ( i ) => i.id === id );

	const fetch = useCallback(
		() => dispatchFetchItem( id ),
		[ dispatchFetchItem, id ]
	);

	const update = useCallback(
		( data ) => saveItem( id, data ),
		[ saveItem, id ]
	);

	const remove = useCallback(
		() => deleteItem( id ),
		[ deleteItem, id ]
	);

	return {
		item,
		loading,
		error,
		fetch,
		update,
		remove,
	};
}
