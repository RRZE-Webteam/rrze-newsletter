const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse( execFileSync( 'php', [ path.join( __dirname, 'render-media-text.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );

for ( const [ name, mjml ] of Object.entries( fixtures ) ) {
	test( `${ name }: media and text compile to mail HTML with links and proportional images`, () => {
		const { html, errors } = mjml2html( mjml, { keepComments: false } );
		// Existing text processors emit these internal attributes; reject any new errors.
		assert.deepEqual( errors.filter( ( error ) => error.tagName !== 'mj-text' || error.message !== 'Attributes postId, link, textColor are illegal' ), [] );
		const dom = new JSDOM( html );
		const doc = dom.window.document;
		const image = doc.querySelector( 'img' );
		assert.equal( doc.querySelectorAll( 'img' ).length, 1 );
		assert.equal( image.alt, 'Media fixture' );
		assert.equal( image.closest( 'a' ).href, 'https://example.test/image-link' );
		assert.equal( image.style.height, 'auto' );
		assert.equal( image.style.width, '100%' );
		assert.ok( doc.querySelector( 'strong' ).textContent.includes( 'Newsletter content' ) );
		assert.ok( doc.querySelector( 'a[href="https://example.test/news"]' ) );
		if ( name !== 'nested' ) {
			assert.equal( doc.querySelectorAll( '.rrze-media-text-media' ).length, 1 );
			assert.equal( doc.querySelectorAll( '.rrze-media-text-content' ).length, 1 );
			assert.equal( image.width, name.endsWith( 'managed' ) ? 205 : 238 );
		}
		dom.window.close();
	} );
}
