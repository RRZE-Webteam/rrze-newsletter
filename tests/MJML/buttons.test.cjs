const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse(
	execFileSync( 'php', [ path.join( __dirname, 'render-buttons.php' ) ], {
		encoding: 'utf8',
		timeout: 10000,
	} )
);

function inlineStyle( tag ) {
	const style = tag.match( /\bstyle="([^"]*)"/ )[ 1 ];
	return Object.fromEntries( style.split( ';' ).filter( Boolean ).map(
		( declaration ) => declaration.split( ':' ).map( ( part ) => part.trim() )
	) );
}

for ( const [ name, width ] of Object.entries( {
	full: '100%',
	half: '50%',
	quarter: '25%',
	fractional: '33.3%',
	'above-maximum': '100%',
	'below-minimum': '1%',
	outline: '100%',
	automatic: undefined,
	unsupported: undefined,
} ) ) {
	test( `${ name }: button does not acquire a fixed pixel width`, () => {
		const { html, errors } = mjml2html( fixtures[ name ], { keepComments: false } );
		assert.deepEqual( errors, [] );
		const links = html.match( /<a\b[^>]*>/g ) || [];
		const tables = ( html.match( /<table\b[^>]*>/g ) || [] ).filter(
			( tag ) => tag.includes( 'border-collapse:separate' )
		);
		assert.equal( links.length, 1 );
		assert.equal( tables.length, 1 );
		assert.equal( inlineStyle( tables[ 0 ] ).width, width );
		// MJML must not turn 100% into a 552px link plus 48px padding.
		const linkStyle = inlineStyle( links[ 0 ] );
		assert.equal( linkStyle.width, undefined );
		assert.equal( linkStyle.padding, '12px 24px' );
		assert.ok( links[ 0 ].includes( 'href="https://example.test/news"' ) );
		assert.ok( ! /\bwidth="\d+"/.test( links[ 0 ] + tables[ 0 ] ) );
	} );
}
