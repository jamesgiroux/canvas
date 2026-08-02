/**
 * useCountUp
 *
 * Animate a display number up from zero on mount. Pairs with the tabular-nums
 * rule in _typography.scss so the digits do not reflow while they move, and
 * collapses to the final value under reduced motion.
 *
 * @package
 */

import { useEffect, useRef, useState } from '@wordpress/element';

// Mirrors --canvas-duration-chart in _tokens.scss. CSS custom properties are
// not readable from the requestAnimationFrame path, so the value is duplicated
// here; keep the two in step.
export const COUNT_UP_DURATION_MS = 650;

/**
 * Whether a value is an actual finite number, without coercion.
 *
 * @param {*} value Candidate value.
 * @return {boolean} Whether the value can be animated.
 */
export function isFiniteNumber( value ) {
	return 'number' === typeof value && Number.isFinite( value );
}

/**
 * Cubic ease-out.
 *
 * @param {number} progress Normalized time, 0 to 1.
 * @return {number} Eased progress, 0 to 1.
 */
export function easeOutCubic( progress ) {
	return 1 - Math.pow( 1 - progress, 3 );
}

/**
 * The animated value at a point in time.
 *
 * @param {number} target   The final value.
 * @param {number} elapsed  Milliseconds since the animation started.
 * @param {number} duration Total duration in milliseconds.
 * @param {number} start    Value to animate from.
 * @return {number} The value to display now.
 */
export function valueAt( target, elapsed, duration, start = 0 ) {
	if ( ! isFiniteNumber( target ) || ! isFiniteNumber( start ) ) {
		return 0;
	}

	if ( ! Number.isFinite( duration ) || duration <= 0 ) {
		return target;
	}

	const progress = Math.min( 1, Math.max( 0, elapsed / duration ) );
	return start + ( target - start ) * easeOutCubic( progress );
}

/**
 * Decimal places carried by a numeric source value.
 *
 * Intl.NumberFormat supports at most 20 fractional digits.
 *
 * @param {number} value Source value.
 * @return {number} Fraction digits to retain while animating.
 */
function fractionDigits( value ) {
	if ( Number.isInteger( value ) ) {
		return 0;
	}

	const [ coefficient, exponentText = '0' ] = String( value )
		.toLowerCase()
		.split( 'e' );
	const coefficientFraction = coefficient.split( '.' )[ 1 ]?.length || 0;
	const exponent = Number( exponentText );

	return Math.min( 20, Math.max( 0, coefficientFraction - exponent ) );
}

/**
 * Format a metric's animated value without coercing its source value.
 *
 * @param {*}      source   Original MetricCard value.
 * @param {number} animated Current animated value.
 * @return {*} Localized number or the original non-numeric value.
 */
export function formatCountUpValue( source, animated ) {
	if ( ! isFiniteNumber( source ) || ! isFiniteNumber( animated ) ) {
		return source;
	}

	const precision = fractionDigits( source );
	if ( 0 === precision ) {
		return Math.round( animated ).toLocaleString();
	}

	return animated.toLocaleString( undefined, {
		minimumFractionDigits: precision,
		maximumFractionDigits: precision,
	} );
}

/**
 * Count from zero on mount, then from the current value when the target changes.
 *
 * @param {number} target The final numeric value.
 * @return {number} The current animated value.
 */
export default function useCountUp( target ) {
	const [ value, setValue ] = useState( 0 );
	const valueRef = useRef( 0 );
	const hasMountedRef = useRef( false );

	useEffect( () => {
		const isFirstMount = ! hasMountedRef.current;
		hasMountedRef.current = true;

		const updateValue = ( nextValue ) => {
			valueRef.current = nextValue;
			setValue( nextValue );
		};

		if ( ! isFiniteNumber( target ) ) {
			updateValue( 0 );
			return undefined;
		}

		const start = isFirstMount ? 0 : valueRef.current;

		const reduce =
			'undefined' !== typeof window &&
			window.matchMedia?.( '(prefers-reduced-motion: reduce)' ).matches;

		if ( reduce || start === target ) {
			updateValue( target );
			return undefined;
		}

		const startedAt = performance.now();
		let frameId;

		const tick = ( now ) => {
			const elapsed = now - startedAt;
			if ( elapsed >= COUNT_UP_DURATION_MS ) {
				updateValue( target );
				return;
			}

			updateValue(
				valueAt( target, elapsed, COUNT_UP_DURATION_MS, start )
			);
			frameId = window.requestAnimationFrame( tick );
		};

		frameId = window.requestAnimationFrame( tick );

		return () => window.cancelAnimationFrame( frameId );
	}, [ target ] );

	return value;
}
