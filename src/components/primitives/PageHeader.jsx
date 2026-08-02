/**
 * PageHeader
 *
 * One composition path for admin page headings. The visual classes stay in the
 * existing editorial kit; this component only centralizes the repeated markup.
 *
 * @package Canvas
 */

import accentedTitle from '../editorial/accentedTitle';

/**
 * Render a page title node.
 *
 * @param {string|JSX.Element} title  Title content.
 * @param {boolean}            accent Whether to accent the last word of a string title.
 * @return {string|JSX.Element} Renderable title.
 */
function renderTitle( title, accent ) {
	if ( accent && 'string' === typeof title ) {
		return accentedTitle( title );
	}

	return title;
}

/**
 * Shared page header.
 *
 * @param {Object}             props               Component props.
 * @param {JSX.Element}        [props.backLink]    Optional backlink node.
 * @param {string|JSX.Element} [props.eyebrow]     Optional eyebrow label.
 * @param {string|JSX.Element} props.title         Page title.
 * @param {string|JSX.Element} [props.description] Optional description copy.
 * @param {JSX.Element}        [props.content]     Optional replacement for title/description.
 * @param {JSX.Element}        [props.actions]     Optional trailing actions.
 * @param {boolean}            [props.accentTitle] Accent the last word of a string title.
 * @param {string}             [props.className]   Additional header class.
 * @return {JSX.Element} Page header.
 */
export default function PageHeader( {
	backLink,
	eyebrow,
	title,
	description,
	content,
	actions,
	accentTitle: shouldAccentTitle = false,
	className = '',
} ) {
	const headerClassName = [ 'canvas-page-header', className ]
		.filter( Boolean )
		.join( ' ' );

	return (
		<>
			{ backLink && (
				<div className="canvas-page-header-backlink">
					{ backLink }
				</div>
			) }
			{ eyebrow && (
				<span className="canvas-editorial-eyebrow">{ eyebrow }</span>
			) }
			<div className={ headerClassName }>
				<div className="canvas-page-header-content">
					{ content || (
						<>
							<h1 className="canvas-page-title">
								{ renderTitle(
									title,
									shouldAccentTitle
								) }
							</h1>
							{ description && (
								<p className="canvas-page-description">
									{ description }
								</p>
							) }
						</>
					) }
				</div>
				{ actions && (
					<div className="canvas-page-actions">{ actions }</div>
				) }
			</div>
		</>
	);
}
