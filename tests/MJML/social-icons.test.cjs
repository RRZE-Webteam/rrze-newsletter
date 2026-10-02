const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { JSDOM } = require( 'jsdom' );
global.window = {};
const mjml2html = require( 'mjml-browser' );
const services = require( '../../assets/social-links/services.json' );
const fixtures = JSON.parse( execFileSync( 'php', [ path.join( __dirname, 'render-social-icons.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );

for ( const mode of [ 'all', 'labels' ] ) {
	test( `compiled social icons: ${ mode } retains every service, direct destination and accessible label`, () => {
		const { html, errors } = mjml2html( fixtures[ mode ] );
		assert.deepEqual( errors, [] );
		const dom = new JSDOM( html );
		const doc = dom.window.document;
		assert.equal( doc.querySelectorAll( 'img' ).length, 49 );
		assert.equal( doc.querySelectorAll( 'svg' ).length, 0 );
		for ( const [ service, details ] of Object.entries( services ) ) {
			const icon = doc.querySelector( `img[src$="/${ details.defaultIcon }-${ service }.png"]` );
			assert.ok( icon, service );
			assert.equal( icon.getAttribute( 'alt' ), details.name );
			assert.equal( icon.closest( 'a' ).getAttribute( 'href' ), service === 'mail' ? 'mailto:team@example.test' : `https://example.test/${ service }` );
			if ( mode === 'labels' ) { assert.ok( doc.body.textContent.includes( details.name ) ); }
		}
		dom.window.close();
	} );
}

test( 'compiled fallback stays clickable, visibly labelled and escaped without losing neighbouring links', () => {
	const { html, errors } = mjml2html( fixtures.fallback );
	assert.deepEqual( errors, [] );
	const dom = new JSDOM( html );
	const doc = dom.window.document;
	assert.equal( doc.querySelectorAll( 'img' ).length, 3 );
	const link = doc.querySelector( 'a[href="https://example.test/future?a=1&b=2"]' );
	assert.ok( link );
	assert.ok( doc.body.textContent.includes( '<b>Future & news</b>' ) );
	assert.equal( doc.querySelectorAll( 'b, script, [href^="javascript:"]' ).length, 0 );
	assert.ok( doc.querySelector( 'img[src$="black-chain.png"]' ) );
	dom.window.close();
} );
