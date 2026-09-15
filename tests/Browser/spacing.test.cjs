const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { chromium } = require( 'playwright' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse( execFileSync( 'php', [ path.join( __dirname, '../MJML/render-spacing.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );

test( 'managed spacing remains usable at narrow viewport widths', { timeout: 30000 }, async ( t ) => {
	const browser = await chromium.launch( { headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', timeout: 10000 } );
	try {
		const page = await browser.newPage();
		await page.route( '**/*', ( route ) => route.abort() );
		for ( const [ name, markup ] of Object.entries( fixtures ) ) {
			const { html, errors } = mjml2html( markup, { keepComments: false } );
			assert.deepEqual( errors, [] );
			for ( const stripHeadStyles of [ false, true ] ) {
				await t.test( `${ name }, head styles removed: ${ stripHeadStyles }`, async () => {
					for ( const width of [ 240, 320, 479, 480, 680 ] ) {
						await page.setViewportSize( { width, height: 800 } );
						await page.setContent( stripHeadStyles ? html.replace( /<style\b[^>]*>[\s\S]*?<\/style>/gi, '' ) : html );
						const size = await page.evaluate( () => ( {
							viewport: document.documentElement.clientWidth,
							scroll: document.documentElement.scrollWidth,
							gutter: getComputedStyle( document.querySelector( '.rrze-managed-spacing>table>tbody>tr>td' ) ).paddingLeft,
							images: Array.from( document.querySelectorAll( 'img' ), ( image ) => ( { width: image.getBoundingClientRect().width, right: image.getBoundingClientRect().right } ) ),
						} ) );
						assert.ok( size.scroll <= size.viewport + 1, JSON.stringify( size ) );
						assert.equal( size.gutter, ! stripHeadStyles && width <= 479 ? '16px' : '24px' );
						for ( const image of size.images ) {
							assert.ok( image.right <= size.viewport + 1, JSON.stringify( size ) );
							assert.ok( image.width > 100, JSON.stringify( size ) );
						}
					}
				} );
			}
		}
	} finally {
		await browser.close();
	}
} );
