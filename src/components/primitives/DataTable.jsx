/**
 * DataTable
 *
 * Shared admin table wrapper. Data-specific rows stay with their feature
 * components; this keeps table chrome, density, and row states on one class
 * contract.
 *
 * @package Canvas
 */

/**
 * Shared data table.
 *
 * @param {Object}      props
 * @param {string}      [props.className] Additional table classes.
 * @param {string}      [props.label]     Accessible table label.
 * @param {JSX.Element} props.children    Table sections.
 * @return {JSX.Element} Table.
 */
export default function DataTable( { className = '', label, children } ) {
	const tableClassName = [ 'canvas-table', className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<table className={ tableClassName } aria-label={ label }>
			{ children }
		</table>
	);
}
