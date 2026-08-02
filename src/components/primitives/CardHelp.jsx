/**
 * CardHelp Component
 *
 * The quiet "what is this card?" affordance: a small info glyph beside a card
 * title whose explainer copy lives in a tooltip instead of a permanent
 * paragraph. Reporting cards each used to open with a sentence of prose; the
 * explainer is worth keeping, but as help-on-demand, the data is the card.
 *
 * Honesty captions (thin-sample, sampled-window, the receipts disclaimer) are
 * NOT help copy and never move in here, they qualify the figures and stay
 * visible next to them.
 *
 * @package Canvas
 */

import { __ } from '@wordpress/i18n';
import { Tooltip, Icon } from '@wordpress/components';

/**
 * CardHelp glyph.
 *
 * @param {Object} props       Component props.
 * @param {string} props.text  The explainer copy shown in the tooltip.
 * @param {string} props.label Accessible name for the button, card-specific
 *                             (e.g. "About Visitor satisfaction"), so several
 *                             help glyphs on one page stay distinguishable in
 *                             a screen reader's control list.
 * @return {JSX.Element} The help affordance.
 */
export default function CardHelp( { text, label } ) {
	return (
		<Tooltip
			text={ text }
			className="canvas-card-help__tip"
			delay={ 200 }
		>
			<button
				type="button"
				className="canvas-card-help"
				aria-label={ label || __( 'About this card', 'canvas' ) }
			>
				<Icon icon="info-outline" size={ 16 } />
			</button>
		</Tooltip>
	);
}
