/**
 * Split a title into its lead text and its accent word.
 *
 * The kit's type system sets exactly one word per hero headline in serif
 * italic: the emphatic noun. This is the split, kept free of JSX so it can be
 * tested without a DOM.
 *
 * @package
 */

/**
 * Split a label at its final space.
 *
 * @param {string} label The title text.
 * @return {{lead: string, accent: string}} Lead text (with its trailing space) and the accent word.
 */
export function splitAccent( label ) {
	const text = String( label || '' ).trim();
	if ( '' === text ) {
		return { lead: '', accent: '' };
	}

	const lastSpace = text.lastIndexOf( ' ' );
	if ( -1 === lastSpace ) {
		return { lead: '', accent: text };
	}

	return {
		lead: text.slice( 0, lastSpace + 1 ),
		accent: text.slice( lastSpace + 1 ),
	};
}
