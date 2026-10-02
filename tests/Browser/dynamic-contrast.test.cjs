const assert = require( 'node:assert/strict' );
const { spawnSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { chromium } = require( 'playwright' );
global.window = {};
const mjml = require( 'mjml-browser' );

function php( input = '' ) {
	const result = spawnSync( 'php', [ path.join( __dirname, '../MJML/render-dynamic-contrast.php' ) ], { input, encoding: 'utf8' } );
	assert.equal( result.status, 0, result.stderr );
	return result.stdout;
}

test( 'server protection makes late RSS/ICS text readable in compiled MJML', { timeout: 30000 }, async () => {
	const fixture = JSON.parse( php() );
	const { html, errors } = mjml( fixture.mjml );
	assert.deepEqual( errors, [] );
	const before = html.replace( /(?:RSS|ICS)_BLOCK_\w+/g, ( token ) => fixture.fragments[ token ] );
	const after = php( JSON.stringify( { html, fragments: fixture.fragments } ) );
	assert.ok( ! after.includes( 'rrze-feed' ) );
	assert.ok( after.includes( '<!--[if mso]>' ) );
	assert.ok( after.includes( 'Grüße aus dem RSS-Feed' ) );
	assert.ok( after.includes( '<p style="color:#123456">Already readable</p>' ) );
	const browser = await chromium.launch( { headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', timeout: 10000 } );
	try {
		const page = await browser.newPage();
		await page.route( '**/*', ( route ) => route.abort() );
		async function contrasts( markup, width ) {
			await page.setViewportSize( { width, height: 1000 } );
			await page.setContent( markup );
			return page.evaluate( () => {
				const rgb = ( value ) => value.match( /[\d.]+/g ).map( Number );
				const luminance = ( channels ) => channels.slice( 0, 3 ).reduce( ( sum, value, i ) => {
					const c = value / 255;
					return sum + [ 0.2126, 0.7152, 0.0722 ][ i ] * ( c <= 0.04045 ? c / 12.92 : ( ( c + 0.055 ) / 1.055 ) ** 2.4 );
				}, 0 );
				return [ ...document.querySelectorAll( '.rrze-newsletter-rss *, .rrze-newsletter-ics *' ) ]
					.filter( ( el ) => [ ...el.childNodes ].some( ( node ) => node.nodeType === 3 && node.textContent.trim() ) )
					.map( ( el ) => {
						let bg;
						for ( let node = el; node; node = node.parentElement ) {
							const candidate = rgb( getComputedStyle( node ).backgroundColor );
							if ( candidate.length === 3 || candidate[ 3 ] === 1 ) { bg = candidate; break; }
						}
						const a = luminance( rgb( getComputedStyle( el ).color ) );
						const b = luminance( bg );
						return { text: el.textContent, contrast: ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 ) };
					} );
			} );
		}
		const original = await contrasts( before, 680 );
		assert.ok( original.some( ( item ) => item.contrast < 1.1 ), 'Fixture must reproduce unreadable feed text' );
		for ( const width of [ 680, 375 ] ) {
			const result = await contrasts( after, width );
			assert.equal( result.length, 7 );
			for ( const item of result ) {
				assert.ok( item.contrast >= 4.5, `${ width }px: ${ item.text }: ${ item.contrast }` );
			}
		}
	} finally {
		await browser.close();
	}
} );
