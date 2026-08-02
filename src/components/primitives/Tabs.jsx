/**
 * Tabs Component
 *
 * Small accessible tabs primitive for admin surfaces. Uses WordPress Button for
 * the control layer, with ARIA roles and arrow-key roving focus owned here.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useId, useMemo, useState } from '@wordpress/element';

/**
 * Tabs primitive.
 *
 * @param {Object}        props
 * @param {Array<Object>} props.tabs              Tabs: { id, label, content }.
 * @param {string}        props.label             Accessible tablist label.
 * @param {string}        props.selectedId        Controlled selected tab id.
 * @param {string}        props.defaultSelectedId Initial selected tab id.
 * @param {Function}      props.onChange          Controlled change callback.
 * @param {string}        props.className         Additional CSS classes.
 * @return {JSX.Element|null} The tabs UI.
 */
export default function Tabs( {
	tabs = [],
	label = __( 'Sections', 'canvas' ),
	selectedId,
	defaultSelectedId,
	onChange,
	className = '',
} ) {
	const rootId = useId();
	const enabledTabs = useMemo(
		() => tabs.filter( ( tab ) => tab && tab.id ),
		[ tabs ]
	);
	const [ internalSelectedId, setInternalSelectedId ] = useState(
		defaultSelectedId || enabledTabs[ 0 ]?.id
	);

	if ( enabledTabs.length === 0 ) {
		return null;
	}

	const activeId = selectedId || internalSelectedId || enabledTabs[ 0 ].id;
	const activeTab =
		enabledTabs.find( ( tab ) => tab.id === activeId ) || enabledTabs[ 0 ];
	const classes = [ 'canvas-tabs', className ].filter( Boolean ).join( ' ' );

	const selectTab = ( nextId ) => {
		if ( ! selectedId ) {
			setInternalSelectedId( nextId );
		}
		if ( onChange ) {
			onChange( nextId );
		}
	};

	const focusTab = ( index ) => {
		const next = enabledTabs[ index ];
		const node = document.getElementById( `${ rootId }-${ next.id }-tab` );
		if ( node ) {
			node.focus();
		}
		selectTab( next.id );
	};

	const handleKeyDown = ( event, index ) => {
		if ( event.key === 'ArrowRight' ) {
			event.preventDefault();
			focusTab( ( index + 1 ) % enabledTabs.length );
		}
		if ( event.key === 'ArrowLeft' ) {
			event.preventDefault();
			focusTab(
				( index - 1 + enabledTabs.length ) % enabledTabs.length
			);
		}
		if ( event.key === 'Home' ) {
			event.preventDefault();
			focusTab( 0 );
		}
		if ( event.key === 'End' ) {
			event.preventDefault();
			focusTab( enabledTabs.length - 1 );
		}
	};

	return (
		<div className={ classes }>
			<div
				className="canvas-tabs__list"
				role="tablist"
				aria-label={ label }
			>
				{ enabledTabs.map( ( tab, index ) => {
					const active = tab.id === activeTab.id;

					return (
						<Button
							key={ tab.id }
							id={ `${ rootId }-${ tab.id }-tab` }
							className={ `canvas-tabs__tab ${
								active ? 'is-active' : ''
							}` }
							role="tab"
							tabIndex={ active ? 0 : -1 }
							aria-selected={ active }
							aria-controls={ `${ rootId }-${ tab.id }-panel` }
							onClick={ () => selectTab( tab.id ) }
							onKeyDown={ ( event ) =>
								handleKeyDown( event, index )
							}
						>
							{ tab.label }
						</Button>
					);
				} ) }
			</div>
			<div
				id={ `${ rootId }-${ activeTab.id }-panel` }
				className="canvas-tabs__panel"
				role="tabpanel"
				tabIndex={ 0 }
				aria-labelledby={ `${ rootId }-${ activeTab.id }-tab` }
			>
				{ activeTab.content }
			</div>
		</div>
	);
}
