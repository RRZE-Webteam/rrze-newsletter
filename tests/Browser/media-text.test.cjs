const assert = require( 'node:assert/strict' );
const { execFileSync } = require( 'node:child_process' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { chromium } = require( 'playwright' );

global.window = {};
const mjml2html = require( 'mjml-browser' );
const fixtures = JSON.parse( execFileSync( 'php', [ path.join( __dirname, '../MJML/render-media-text.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );

test( 'media and text respect desktop order, mobile stacking and narrow mail widths', { timeout: 60000 }, async ( t ) => {
	const browser = await chromium.launch( { headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', timeout: 10000 } );
	try {
		const page = await browser.newPage();
		await page.route( '**/*', ( route ) => route.request().url().endsWith( '/media.jpg' )
			? route.fulfill( { contentType: 'image/svg+xml', body: '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="600"><rect width="1200" height="600" fill="#04316a"/></svg>' } )
			: route.abort() );
		for ( const [ name, markup ] of Object.entries( fixtures ) ) {
			const { html, errors } = mjml2html( markup, { keepComments: false } );
			assert.deepEqual( errors.filter( ( error ) => error.tagName !== 'mj-text' || error.message !== 'Attributes postId, link, textColor are illegal' ), [] );
			await t.test( name, async () => {
				for ( const width of [ 280, 375, 479, 480, 680 ] ) {
					await page.setViewportSize( { width, height: 900 } );
					await page.setContent( html );
					await page.locator( 'img' ).evaluate( ( img ) => img.decode() );
					const size = await page.evaluate( () => {
						const rect = ( selector ) => document.querySelector( selector )?.getBoundingClientRect().toJSON();
						return { viewport: document.documentElement.clientWidth, scroll: document.documentElement.scrollWidth,
							media: rect( '.rrze-media-text-media' ), text: rect( '.rrze-media-text-content' ), image: rect( 'img' ) };
					} );
					assert.ok( size.scroll <= size.viewport + 1, `${ name }/${ width }: ${ JSON.stringify( size ) }` );
					assert.ok( Math.abs( size.image.width / size.image.height - 2 ) < .03, 'Image keeps its proportions' );
					if ( name === 'nested' ) continue;
					if ( width < 480 && name.includes( '-stacked-' ) ) {
						assert.ok( size.text.top >= size.media.bottom - 1, 'Media precedes text on mobile, even for right-side media' );
					} else if ( name.startsWith( 'right-' ) ) {
						assert.ok( size.media.left >= size.text.right - 1, 'Right-side media stays on the right' );
					} else {
						assert.ok( size.media.right <= size.text.left + 1, 'Left-side media stays on the left' );
					}
				}
			} );
		}
	} finally {
		await browser.close();
	}
} );
