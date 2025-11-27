/**
 * Store Index
 *
 * Registers all data stores and exports store names.
 *
 * @package Canvas
 */

// Import stores to register them.
import './items';
import './settings';

// Export store names for use with useSelect/useDispatch.
export { STORE_NAME as ITEMS_STORE } from './items';
export { STORE_NAME as SETTINGS_STORE } from './settings';
