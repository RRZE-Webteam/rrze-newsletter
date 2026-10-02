const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
// Playwright is already installed through @wordpress/scripts.
const { chromium } = require( 'playwright' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse(
	execFileSync( 'php', [ path.join( __dirname, '../MJML/render-buttons.php' ) ], {
		encoding: 'utf8',
		timeout: 10000,
	} )
);

test( 'buttons fit narrow viewports inside the padded newsletter', { timeout: 30000 }, async ( t ) => {
	const browser = await chromium.launch( {
		headless: true,
		channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome',
		timeout: 10000,
	} );
	try {
		const page = await browser.newPage();
		await page.route( '**/*', ( route ) => route.abort() );
		const measure = () => page.evaluate( () => ( {
			viewport: document.documentElement.clientWidth,
			scroll: document.documentElement.scrollWidth,
			buttonRight: document.querySelector( 'a' ).getBoundingClientRect().right,
		} ) );

		await t.test( 'control: the previous fixed-width markup really overflows', async () => {
			const legacy = fixtures.full.replace( /(<mj-button\b[^>]*\bwidth=")100%"/, '$1600px"' );
			assert.notEqual( legacy, fixtures.full );
			await page.setViewportSize( { width: 320, height: 800 } );
			await page.setContent( mjml2html( legacy, { keepComments: false } ).html );
			const size = await measure();
			assert.ok( size.scroll > size.viewport, JSON.stringify( size ) );
		} );

		for ( const [ name, markup ] of Object.entries( fixtures ) ) {
			const { html, errors } = mjml2html( markup, { keepComments: false } );
			assert.deepEqual( errors, [] );
			for ( const stripHeadStyles of [ false, true ] ) {
				await t.test( `${ name }, head styles removed: ${ stripHeadStyles }`, async () => {
					const content = stripHeadStyles
						? html.replace( /<style\b[^>]*>[\s\S]*?<\/style>/gi, '' )
						: html;
					for ( const width of [ 240, 280, 320, 375, 479, 480, 600, 680 ] ) {
						await page.setViewportSize( { width, height: 800 } );
						await page.setContent( content );
						const size = await measure();
						assert.ok( size.scroll <= size.viewport + 1, JSON.stringify( size ) );
						assert.ok( size.buttonRight <= size.viewport + 1, JSON.stringify( size ) );
					}
				} );
			}
		}
	} finally {
		await browser.close();
	}
} );
