const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { parse } = require( '@wordpress/block-serialization-default-parser' );
const { JSDOM } = require( 'jsdom' );

const root = path.join( __dirname, '../..' );
// Read the actual editor palette without bootstrapping WordPress or its database.
const palette = JSON.parse( execFileSync( 'php', [ '-r', `
	define('ABSPATH', getcwd() . '/');
	function __($value) { return $value; }
	require 'vendor/autoload.php';
	echo json_encode(RRZE\\Newsletter\\Editor::colorPallete());
` ], { cwd: root, encoding: 'utf8', timeout: 10000 } ) );
const colors = new Map( palette.map( ( { slug, color } ) => [ slug, color.toLowerCase() ] ) );
const layouts = Object.fromEntries(
	fs.readdirSync( path.join( root, 'includes/layouts' ) ).filter( ( name ) => name.endsWith( '.json' ) )
		.map( ( name ) => [ name, JSON.parse( fs.readFileSync( path.join( root, 'includes/layouts', name ), 'utf8' ) ).content ] )
);
const patterns = JSON.parse( fs.readFileSync( path.join( root, 'includes/Patterns/patterns.json' ), 'utf8' ) );

function* descendants( blocks ) {
	for ( const block of blocks ) {
		yield block;
		yield* descendants( block.innerBlocks );
	}
}

function checkReferences( value ) {
	if ( typeof value === 'string' ) {
		for ( const [ , slug ] of value.matchAll( /var:preset\|color\|([^\s";,)]+)/g ) ) {
			assert.ok( colors.has( slug ), `Unknown color preset reference: ${ slug }` );
		}
	} else if ( value && typeof value === 'object' ) {
		Object.values( value ).forEach( checkReferences );
	}
}

for ( const [ type, sources ] of Object.entries( { layout: layouts, pattern: patterns } ) ) {
	for ( const [ name, content ] of Object.entries( sources ) ) {
		test( `${ type } ${ name }: color presets resolve and match saved HTML`, () => {
			assert.equal( typeof content, 'string' );
			const blocks = [ ...descendants( parse( content ) ) ];
			assert.ok( blocks.some( ( block ) => block.blockName ), 'Expected Gutenberg blocks' );
			for ( const block of blocks ) {
				checkReferences( block.attrs );
				// innerHTML excludes nested blocks, so a child's class cannot satisfy its parent.
				const html = JSDOM.fragment( block.innerHTML );
				for ( const [ attribute, suffix ] of [ [ 'backgroundColor', 'background-color' ], [ 'textColor', 'color' ] ] ) {
					const slug = block.attrs[ attribute ];
					if ( slug === undefined ) continue;
					assert.ok( colors.has( slug ), `${ block.blockName }.${ attribute } must be a palette slug, got ${ slug }` );
					assert.ok( html.querySelector( `.has-${ slug }-${ suffix }` ), `${ block.blockName } is missing its saved ${ attribute } class` );
				}
				for ( const element of html.querySelectorAll( '[class]' ) ) {
					for ( const className of element.classList ) {
						const match = /^has-(.+?)-(?:background-color|color)$/.exec( className );
						if ( ! match || [ 'text', 'link' ].includes( match[ 1 ] ) ) continue;
						assert.ok( colors.has( match[ 1 ] ), `Unknown saved color class: ${ className }` );
					}
				}
				for ( const [ , slug ] of block.innerHTML.matchAll( /var\(--wp--preset--color--([^\s,)]+)/g ) ) {
					assert.ok( colors.has( slug ), `Unknown CSS color variable: ${ slug }` );
				}
			}
		} );
	}
}

for ( const [ name, accent ] of Object.entries( {
	'fau-default.json': '#04316a',
	'fau-medfak.json': '#18b4f1',
	'fau-natfak.json': '#7bb725',
	'fau-philfak.json': '#fdb735',
	'fau-rewi.json': '#c50f3c',
	'fau-techfak.json': '#8c9fb1',
} ) ) {
	test( `${ name }: group colors retain the intended design and spacing`, () => {
		const groups = [ ...descendants( parse( layouts[ name ] ) ) ].filter( ( block ) => block.blockName === 'core/group' );
		const backgrounds = groups.map( ( { attrs } ) => colors.get( attrs.backgroundColor ) ?? attrs.style?.color?.background );
		assert.deepEqual( backgrounds, [ '#ffffff', accent, '#ffffff', accent, '#ffffff', '#ffffff', accent, '#ffffff', '#ffffff', accent ] );
		// This custom color was already valid: do not replace literal colors indiscriminately.
		assert.equal( groups[ 2 ].attrs.style.color.background, '#ffffff' );
		assert.equal( groups[ 2 ].attrs.backgroundColor, undefined );
		assert.equal( groups[ 0 ].attrs.style.spacing.padding.top, 'var:preset|spacing|20' );
		assert.match( groups[ 0 ].innerHTML, /padding-top:var\(--wp--preset--spacing--20\)/ );
	} );
}
