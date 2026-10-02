const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

// The browser bundle needs a window global, but compilation needs no browser.
global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse(
	execFileSync( 'php', [ path.join( __dirname, 'render-images.php' ) ], {
		encoding: 'utf8',
		timeout: 10000,
	} )
);

function compiledImage( name ) {
	// Use the same compiler and options as src/editor/api/index.js.
	const { html, errors } = mjml2html( fixtures[ name ], { keepComments: false } );
	assert.deepEqual( errors, [], 'Fixture must compile without MJML errors' );
	const images = html.match( /<img\b[^>]*>/g ) || [];
	assert.equal( images.length, 1 );
	const attributes = Object.fromEntries(
		[ ...images[ 0 ].matchAll( /([\w-]+)="([^"]*)"/g ) ].map(
			( [ , key, value ] ) => [ key, value ]
		)
	);
	const style = Object.fromEntries(
		attributes.style.split( ';' ).filter( Boolean ).map(
			( declaration ) => declaration.split( ':' ).map( ( part ) => part.trim() )
		)
	);
	return { attributes, style };
}

for ( const [ name, width ] of Object.entries( {
	landscape: '680',
	portrait: '600',
	small: '200',
	'pixel-width': '400',
	'percentage-width': '340',
	'oversized-width': '680',
	medium: '300',
	thumbnail: '150',
	'narrow-container': '260',
} ) ) {
	test( `${ name }: fluid width has an automatic inline height`, () => {
		const { attributes, style } = compiledImage( name );
		assert.equal( attributes.width, width );
		assert.equal( style.width, '100%' );
		// Inspect the inline rule, not the <=479px media-query workaround.
		// This contract also works above that breakpoint or without head CSS.
		assert.equal( style.height, 'auto' );
		assert.ok( ! attributes.height || attributes.height === 'auto' );
	} );
}

for ( const [ name, width, height ] of [
	[ 'explicit-dimensions', '400', '400' ],
	[ 'oversized-explicit-dimensions', '600', '400' ],
	[ 'height-only', '200', '100' ],
] ) {
	test( `${ name }: intentional dimensions remain intact`, () => {
		const { attributes, style } = compiledImage( name );
		assert.equal( attributes.width, width );
		assert.equal( attributes.height, height );
		assert.equal( style.height, `${ height }px` );
	} );
}
