/**
 * Items Store
 *
 * WordPress data store for managing items state.
 * Uses @wordpress/data for Redux-like state management.
 *
 * @package Canvas
 */

import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { API_PATHS } from '../../constants/api';

/**
 * Store namespace.
 */
export const STORE_NAME = 'canvas/items';

/**
 * Default state.
 */
const DEFAULT_STATE = {
	items: [],
	currentItem: null,
	loading: false,
	error: null,
	pagination: {
		page: 1,
		perPage: 20,
		total: 0,
		totalPages: 0,
	},
};

/**
 * Action types.
 */
const ACTIONS = {
	SET_ITEMS: 'SET_ITEMS',
	SET_CURRENT_ITEM: 'SET_CURRENT_ITEM',
	ADD_ITEM: 'ADD_ITEM',
	UPDATE_ITEM: 'UPDATE_ITEM',
	REMOVE_ITEM: 'REMOVE_ITEM',
	SET_LOADING: 'SET_LOADING',
	SET_ERROR: 'SET_ERROR',
	SET_PAGINATION: 'SET_PAGINATION',
};

/**
 * Actions - Functions that return action objects or thunks.
 */
const actions = {
	/**
	 * Set items in store.
	 *
	 * @param {Array} items Items array.
	 * @return {Object} Action object.
	 */
	setItems( items ) {
		return {
			type: ACTIONS.SET_ITEMS,
			items,
		};
	},

	/**
	 * Set current item.
	 *
	 * @param {Object|null} item The item or null.
	 * @return {Object} Action object.
	 */
	setCurrentItem( item ) {
		return {
			type: ACTIONS.SET_CURRENT_ITEM,
			item,
		};
	},

	/**
	 * Add item to store.
	 *
	 * @param {Object} item The new item.
	 * @return {Object} Action object.
	 */
	addItem( item ) {
		return {
			type: ACTIONS.ADD_ITEM,
			item,
		};
	},

	/**
	 * Update item in store.
	 *
	 * @param {number} id   Item ID.
	 * @param {Object} data Updated data.
	 * @return {Object} Action object.
	 */
	updateItem( id, data ) {
		return {
			type: ACTIONS.UPDATE_ITEM,
			id,
			data,
		};
	},

	/**
	 * Remove item from store.
	 *
	 * @param {number} id Item ID.
	 * @return {Object} Action object.
	 */
	removeItem( id ) {
		return {
			type: ACTIONS.REMOVE_ITEM,
			id,
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
	 * Set pagination data.
	 *
	 * @param {Object} pagination Pagination object.
	 * @return {Object} Action object.
	 */
	setPagination( pagination ) {
		return {
			type: ACTIONS.SET_PAGINATION,
			pagination,
		};
	},

	/**
	 * Fetch items from API.
	 *
	 * @param {Object} params Query parameters.
	 * @return {Function} Thunk function.
	 */
	fetchItems( params = {} ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				const queryString = new URLSearchParams( params ).toString();
				const endpoint = `${ API_PATHS.ITEMS }${ queryString ? '?' + queryString : '' }`;

				const response = await apiFetch( { path: endpoint } );

				dispatch.setItems( response.items || [] );

				if ( response.pagination ) {
					dispatch.setPagination( {
						page: response.pagination.page,
						perPage: response.pagination.per_page,
						total: response.pagination.total,
						totalPages: response.pagination.total_pages,
					} );
				}
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to fetch items' );
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	/**
	 * Fetch single item by ID.
	 *
	 * @param {number} id Item ID.
	 * @return {Function} Thunk function.
	 */
	fetchItem( id ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				const response = await apiFetch( {
					path: API_PATHS.ITEM( id ),
				} );

				dispatch.setCurrentItem( response );
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to fetch item' );
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	/**
	 * Create new item.
	 *
	 * @param {Object} data Item data.
	 * @return {Function} Thunk function.
	 */
	createItem( data ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				const response = await apiFetch( {
					path: API_PATHS.ITEMS,
					method: 'POST',
					data,
				} );

				dispatch.addItem( response );
				return response;
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to create item' );
				throw error;
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	/**
	 * Update existing item.
	 *
	 * @param {number} id   Item ID.
	 * @param {Object} data Updated data.
	 * @return {Function} Thunk function.
	 */
	saveItem( id, data ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				const response = await apiFetch( {
					path: API_PATHS.ITEM( id ),
					method: 'PUT',
					data,
				} );

				dispatch.updateItem( id, response );
				return response;
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to update item' );
				throw error;
			} finally {
				dispatch.setLoading( false );
			}
		};
	},

	/**
	 * Delete item.
	 *
	 * @param {number} id Item ID.
	 * @return {Function} Thunk function.
	 */
	deleteItem( id ) {
		return async ( { dispatch } ) => {
			dispatch.setLoading( true );
			dispatch.setError( null );

			try {
				await apiFetch( {
					path: API_PATHS.ITEM( id ),
					method: 'DELETE',
				} );

				dispatch.removeItem( id );
			} catch ( error ) {
				dispatch.setError( error.message || 'Failed to delete item' );
				throw error;
			} finally {
				dispatch.setLoading( false );
			}
		};
	},
};

/**
 * Selectors - Functions to retrieve data from state.
 */
const selectors = {
	/**
	 * Get all items.
	 *
	 * @param {Object} state Store state.
	 * @return {Array} Items array.
	 */
	getItems( state ) {
		return state.items;
	},

	/**
	 * Get current item.
	 *
	 * @param {Object} state Store state.
	 * @return {Object|null} Current item or null.
	 */
	getCurrentItem( state ) {
		return state.currentItem;
	},

	/**
	 * Get item by ID.
	 *
	 * @param {Object} state Store state.
	 * @param {number} id    Item ID.
	 * @return {Object|undefined} Item or undefined.
	 */
	getItemById( state, id ) {
		return state.items.find( ( item ) => item.id === id );
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
	 * Get error state.
	 *
	 * @param {Object} state Store state.
	 * @return {string|null} Error message or null.
	 */
	getError( state ) {
		return state.error;
	},

	/**
	 * Get pagination data.
	 *
	 * @param {Object} state Store state.
	 * @return {Object} Pagination object.
	 */
	getPagination( state ) {
		return state.pagination;
	},
};

/**
 * Reducer - Handles state changes based on actions.
 *
 * @param {Object} state  Current state.
 * @param {Object} action Action object.
 * @return {Object} New state.
 */
function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case ACTIONS.SET_ITEMS:
			return {
				...state,
				items: action.items,
			};

		case ACTIONS.SET_CURRENT_ITEM:
			return {
				...state,
				currentItem: action.item,
			};

		case ACTIONS.ADD_ITEM:
			return {
				...state,
				items: [ action.item, ...state.items ],
			};

		case ACTIONS.UPDATE_ITEM:
			return {
				...state,
				items: state.items.map( ( item ) =>
					item.id === action.id ? { ...item, ...action.data } : item
				),
				currentItem:
					state.currentItem?.id === action.id
						? { ...state.currentItem, ...action.data }
						: state.currentItem,
			};

		case ACTIONS.REMOVE_ITEM:
			return {
				...state,
				items: state.items.filter( ( item ) => item.id !== action.id ),
				currentItem:
					state.currentItem?.id === action.id
						? null
						: state.currentItem,
			};

		case ACTIONS.SET_LOADING:
			return {
				...state,
				loading: action.loading,
			};

		case ACTIONS.SET_ERROR:
			return {
				...state,
				error: action.error,
			};

		case ACTIONS.SET_PAGINATION:
			return {
				...state,
				pagination: {
					...state.pagination,
					...action.pagination,
				},
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
