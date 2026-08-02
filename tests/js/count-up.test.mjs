import assert from 'node:assert/strict';
import test from 'node:test';

import {
	easeOutCubic,
	formatCountUpValue,
	isFiniteNumber,
	valueAt,
} from '../../src/components/primitives/useCountUp.js';

test( 'easing starts at zero and ends at one', () => {
	assert.equal( easeOutCubic( 0 ), 0 );
	assert.equal( easeOutCubic( 1 ), 1 );
} );

test( 'easing decelerates: it is past halfway before half the time', () => {
	assert.ok( easeOutCubic( 0.5 ) > 0.5 );
} );

test( 'valueAt returns the exact target once elapsed reaches the duration', () => {
	assert.equal( valueAt( 250, 650, 650 ), 250 );
} );

test( 'valueAt clamps past the duration rather than overshooting', () => {
	assert.equal( valueAt( 250, 1200, 650 ), 250 );
} );

test( 'valueAt interpolates subsequent changes from the current value', () => {
	assert.equal( valueAt( 250, 325, 650, 100 ), 231.25 );
	assert.equal( valueAt( 100, 325, 650, 250 ), 118.75 );
} );

test( 'valueAt returns zero for a non-numeric target', () => {
	assert.equal( valueAt( 'not a number', 300, 650 ), 0 );
} );

test( 'valueAt completes immediately when duration is not positive', () => {
	assert.equal( valueAt( 250, 0, 0 ), 250 );
	assert.equal( valueAt( 250, 0, -1 ), 250 );
} );

test( 'only actual finite numbers are count-up values', () => {
	assert.equal( isFiniteNumber( 1248 ), true );
	assert.equal( isFiniteNumber( 12.34 ), true );
	assert.equal( isFiniteNumber( '1248' ), false );
	assert.equal( isFiniteNumber( '' ), false );
	assert.equal( isFiniteNumber( null ), false );
	assert.equal( isFiniteNumber( true ), false );
	assert.equal( isFiniteNumber( Number.POSITIVE_INFINITY ), false );
} );

test( 'metric formatting localizes integers and preserves decimal precision', () => {
	assert.equal( formatCountUpValue( 1248, 1248 ), ( 1248 ).toLocaleString() );
	assert.equal(
		formatCountUpValue( 12.34, 6.17 ),
		( 6.17 ).toLocaleString( undefined, {
			minimumFractionDigits: 2,
			maximumFractionDigits: 2,
		} )
	);
} );

test( 'metric formatting preserves non-number values verbatim', () => {
	assert.strictEqual( formatCountUpValue( '1248', 1248 ), '1248' );
	assert.strictEqual( formatCountUpValue( '', 0 ), '' );
	assert.strictEqual( formatCountUpValue( null, 0 ), null );
	assert.strictEqual( formatCountUpValue( true, 1 ), true );
} );
