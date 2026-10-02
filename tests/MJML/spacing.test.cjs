const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse( execFileSync( 'php', [ path.join( __dirname, 'render-spacing.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );

for ( const [ name, mjml ] of Object.entries( fixtures ) ) {
	test( `${ name }: managed spacing compiles to fluid email HTML without nested padding growth`, () => {
		const { html, errors } = mjml2html( mjml, { keepComments: false } );
		assert.deepEqual( errors, [] );
		assert.ok( html.includes( 'rrze-managed-spacing' ) );
		assert.ok( ! html.includes( '200px' ) && ! html.includes( '300px 300px' ) );
		const images = html.match( /<img\b[^>]*>/g ) || [];
		const expectedWidth = [ 'grid', 'columns' ].includes( name ) ? 300 : 632;
		for ( const image of images ) {
			assert.ok( image.includes( `width="${ expectedWidth }"` ), image );
			assert.match( image, /height:auto/ );
			assert.match( image, /width:100%/ );
		}
		assert.equal( images.length, name === 'button' ? 0 : [ 'grid', 'columns' ].includes( name ) ? 2 : 1 );
		assert.match( html, /padding-left:16px !important/ );
		assert.match( html, /padding-right:16px !important/ );
	} );
}

test( 'managed typography rules override saved inline spacing and retain list indentation', () => {
	const markup = fixtures.root.replace( /<mj-image[^>]*\/>/, '<mj-text><p style="margin:80px;padding:150px">Paragraph</p><ul style="margin:200px;padding:120px"><li>List item</li></ul></mj-text>' );
	const { html, errors } = mjml2html( markup, { keepComments: false } );
	assert.deepEqual( errors, [] );
	assert.match( html, /<p style="margin: 0; padding: 0;">Paragraph/ );
	assert.match( html, /<ul style="margin: 0; padding: 0 0 0 20px;">/ );
	assert.match( html, /<li style="margin: 0; padding: 0;">List item/ );
} );
