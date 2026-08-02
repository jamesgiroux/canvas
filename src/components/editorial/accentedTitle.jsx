/**
 * accentedTitle
 *
 * Render a title string with its last word set in the serif-italic accent.
 * Exactly one accent word per title, and one accented title per surface.
 *
 * @package Canvas
 */

import { splitAccent } from './splitAccent';

/**
 * Wrap the last word of a label in an `<em>`, which _typography.scss renders as
 * the serif italic.
 *
 * @param {string} label The title text.
 * @return {JSX.Element} The title content.
 */
export default function accentedTitle( label ) {
	const { lead, accent } = splitAccent( label );

	return (
		<>
			{ lead }
			<em>{ accent }</em>
		</>
	);
}

export { splitAccent };
