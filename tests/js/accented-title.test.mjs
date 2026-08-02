import assert from 'node:assert/strict';
import test from 'node:test';

import { splitAccent } from '../../src/components/editorial/splitAccent.js';

test( 'splits a multi-word title into lead text and the accent word', () => {
	assert.deepEqual( splitAccent( 'Know who is ready' ), {
		lead: 'Know who is ',
		accent: 'ready',
	} );
} );

test( 'a single word is entirely the accent', () => {
	assert.deepEqual( splitAccent( 'Settings' ), {
		lead: '',
		accent: 'Settings',
	} );
} );

test( 'trims surrounding whitespace before splitting', () => {
	assert.deepEqual( splitAccent( '  Write next  ' ), {
		lead: 'Write ',
		accent: 'next',
	} );
} );

test( 'an empty or absent label yields empty parts', () => {
	assert.deepEqual( splitAccent( '' ), { lead: '', accent: '' } );
	assert.deepEqual( splitAccent( undefined ), { lead: '', accent: '' } );
} );
