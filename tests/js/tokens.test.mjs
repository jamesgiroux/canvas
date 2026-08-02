import assert from 'node:assert/strict';
import { readFile, readdir } from 'node:fs/promises';
import test from 'node:test';

const tokensJsonUrl = new URL( '../../tokens.json', import.meta.url );
const tokensScssUrl = new URL(
	'../../src/styles/_tokens.scss',
	import.meta.url
);
const partialsDirUrl = new URL(
	'../../src/styles/components/',
	import.meta.url
);

// WordPress's own custom properties. The kit re-points these at the plugin
// accent; they are not ours to rename and must survive canvas-init.py intact.
const WP_VARIABLES = [
	'--wp-admin-theme-color',
	'--wp-admin-theme-color-darker-10',
	'--wp-admin-theme-color-darker-20',
	'--wp-admin-border-width-focus',
	'--wp-components-color-accent',
	'--wp-components-color-accent-darker-10',
	'--wp-components-color-accent-darker-20',
];

const readTokens = async () =>
	JSON.parse( await readFile( tokensJsonUrl, 'utf8' ) );
const readScss = async () => readFile( tokensScssUrl, 'utf8' );

/**
 * Read every SCSS partial in the components directory.
 *
 * @return {Promise<Array<{name: string, source: string}>>} Partial sources.
 */
async function readPartials() {
	const names = ( await readdir( partialsDirUrl ) ).filter( ( name ) =>
		name.endsWith( '.scss' )
	);
	return Promise.all(
		names.map( async ( name ) => ( {
			name,
			source: await readFile( new URL( name, partialsDirUrl ), 'utf8' ),
		} ) )
	);
}

/**
 * Every `--<prefix>-name: value;` declaration in the stylesheet.
 *
 * @param {string} scss   Stylesheet source.
 * @param {string} prefix Kit prefix, e.g. "canvas".
 * @return {Map<string, string>} Token name (without prefix) to declared value.
 */
function declaredTokens( scss, prefix ) {
	const pattern = new RegExp(
		`--${ prefix }-([a-z0-9-]+)\\s*:\\s*([^;]+);`,
		'g'
	);
	const found = new Map();
	for ( const match of scss.matchAll( pattern ) ) {
		found.set( match[ 1 ], match[ 2 ].trim() );
	}
	return found;
}

/**
 * Normalize insignificant CSS whitespace and casing for parity comparisons.
 *
 * @param {string} value Token value.
 * @return {string} Comparable token value.
 */
function normalizeTokenValue( value ) {
	return String( value ).trim().replace( /\s+/g, ' ' ).toLowerCase();
}

/**
 * Every Canvas token registered in tokens.json with its canonical CSS value.
 *
 * @param {Object} tokens Parsed token registry.
 * @return {Map<string, string>} Token name to canonical CSS value.
 */
function registeredTokens( tokens ) {
	const groups = [
		[ 'palette', Object.entries( tokens.palette || {} ) ],
		[ 'alias', Object.entries( tokens.alias || {} ) ],
		[
			'fonts',
			Object.entries( tokens.fonts || {} ).map( ( [ name, value ] ) => [
				`font-${ name }`,
				value,
			] ),
		],
	];
	const registered = new Map();

	for ( const [ group, entries ] of groups ) {
		for ( const [ name, value ] of entries ) {
			assert.equal(
				typeof value,
				'string',
				`${ group }.${ name } must contain a canonical CSS value`
			);
			assert.ok(
				! registered.has( name ),
				`--${ tokens.prefix }-${ name } is registered more than once`
			);
			registered.set( name, value );
		}
	}

	return registered;
}

test( 'every token in tokens.json is declared in _tokens.scss with the same value', async () => {
	const tokens = await readTokens();
	const declared = declaredTokens( await readScss(), tokens.prefix );
	const registered = registeredTokens( tokens );

	for ( const [ name, value ] of registered ) {
		assert.ok(
			declared.has( name ),
			`--${ tokens.prefix }-${ name } missing from _tokens.scss`
		);
		assert.equal(
			normalizeTokenValue( declared.get( name ) ),
			normalizeTokenValue( value ),
			`--${ tokens.prefix }-${ name } value differs between tokens.json and _tokens.scss`
		);
	}
} );

test( 'every Canvas token declared in _tokens.scss appears in tokens.json with the same value', async () => {
	const tokens = await readTokens();
	const declared = declaredTokens( await readScss(), tokens.prefix );
	const registered = registeredTokens( tokens );

	for ( const [ name, value ] of declared ) {
		assert.ok(
			registered.has( name ),
			`--${ tokens.prefix }-${ name } is declared in _tokens.scss but absent from tokens.json`
		);
		assert.equal(
			normalizeTokenValue( value ),
			normalizeTokenValue( registered.get( name ) ),
			`--${ tokens.prefix }-${ name } value differs between _tokens.scss and tokens.json`
		);
	}
} );

test( 'every color token carries a documented role', async () => {
	const tokens = await readTokens();
	for ( const [ name, entry ] of Object.entries( tokens.color ) ) {
		assert.equal( typeof entry.value, 'string', `color.${ name }.value` );
		assert.ok(
			Object.hasOwn( tokens.palette, name ),
			`color.${ name } has no canonical palette token`
		);
		assert.equal(
			normalizeTokenValue( entry.value ),
			normalizeTokenValue( tokens.palette[ name ] ),
			`color.${ name }.value differs from palette.${ name }`
		);
		assert.ok(
			entry.role && entry.role.length > 0,
			`color.${ name }.role is empty`
		);
	}
} );

test( "WordPress's own variables are present and never prefixed", async () => {
	const scss = await readScss();
	const tokens = await readTokens();

	for ( const variable of WP_VARIABLES ) {
		assert.ok(
			scss.includes( `${ variable }:` ),
			`${ variable } is not re-anchored in _tokens.scss`
		);
		assert.ok(
			! scss.includes( `--${ tokens.prefix }${ variable }` ),
			`${ variable } has been prefixed, which breaks the @wordpress/components re-anchor`
		);
	}
} );

test( 'the kit declares a version and a prefix', async () => {
	const tokens = await readTokens();
	assert.match( tokens.version, /^\d+\.\d+\.\d+$/ );
	assert.match( tokens.prefix, /^[a-z][a-z0-9-]*$/ );
} );

test( 'no partial hardcodes a duration; every one comes from a token', async () => {
	for ( const { name, source } of await readPartials() ) {
		const literals =
			source.match( /(?:transition|animation)[^;{]*?\b\d+m?s\b/g ) || [];
		assert.deepEqual(
			literals,
			[],
			`${ name } hardcodes a duration: ${ literals.join(
				', '
			) }. Use a --duration token.`
		);
	}
} );

test( 'the reveal stagger caps after its fifth step', async () => {
	const motion = await readFile(
		new URL( '../../src/styles/components/_motion.scss', import.meta.url ),
		'utf8'
	);

	assert.match(
		motion,
		/\.canvas-reveal:nth-child\(n \+ 6\)\s*{[^}]*animation-delay:\s*calc\(var\(--canvas-duration-reveal\) \* 0\.7\);/s
	);
} );

test( 'every partial that animates also collapses under reduced motion', async () => {
	const partials = await readPartials();
	assert.ok(
		partials.some( ( { name } ) => name === '_motion.scss' ),
		'_motion.scss is missing, so the motion accessibility guard is incomplete'
	);

	for ( const { name, source } of partials ) {
		const animates = /(?:^|\s)(?:transition|animation):/m.test( source );
		if ( ! animates ) {
			continue;
		}
		assert.ok(
			source.includes( 'prefers-reduced-motion' ),
			`${ name } animates but declares no prefers-reduced-motion collapse`
		);
	}
} );
