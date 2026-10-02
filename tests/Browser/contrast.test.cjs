const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { chromium } = require( 'playwright' );

test( 'native contrast analysis uses isolated CSS, prevents network access and cleans up', { timeout: 30000 }, async () => {
	const browser = await chromium.launch( { headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', timeout: 10000 } );
	try {
		const page = await browser.newPage();
		const requests = [];
		await page.route( '**/*', ( route ) => { requests.push( route.request().url() ); return route.abort(); } );
		const source = readFileSync( path.join( __dirname, '../../src/editor/contrast/contrast.mjs' ), 'utf8' );
		const result = await page.evaluate( async ( moduleSource ) => {
			const { protectEmailHtml } = await import( 'data:text/javascript;base64,' + btoa( moduleSource ) );
			const html = '<!doctype html><html><head><style>a{color:black}body{color:black;background:white}</style></head><body>' +
				'<p style="background:#04316a">Dark <span style="background:white">Light</span><a href="https://example.test/">Link</a></p>' +
				'<p style="background-image:url(https://example.test/bg.png)">Uncertain</p>' +
				'<img src="https://example.test/image.png"><script>parent.__contrastScriptRan=true</script>' +
				'<!--[if mso]><table><tr><td>Outlook</td></tr></table><![endif]--></body></html>';
			const protectedEmail = await protectEmailHtml( html );
			const parsed = new DOMParser().parseFromString( protectedEmail.html, 'text/html' );
			const repeated = await protectEmailHtml( protectedEmail.html );
			return {
				corrected: protectedEmail.corrected, skipped: protectedEmail.skipped,
				parentColor: parsed.querySelector( 'p' ).style.color,
				childColor: parsed.querySelector( 'span' ).style.color,
				linkColor: parsed.querySelector( 'a' ).style.color,
				iframes: document.querySelectorAll( 'iframe' ).length,
				scriptRan: Boolean( window.__contrastScriptRan ),
				policyLeaked: protectedEmail.html.includes( 'rrze-contrast-policy' ),
				commentKept: protectedEmail.html.includes( '<!--[if mso]>' ),
				secondCorrections: repeated.corrected,
			};
		}, source );
		assert.deepEqual( result, {
			corrected: 2, skipped: 1, parentColor: 'rgb(255, 255, 255)',
			childColor: 'rgb(0, 0, 0)', linkColor: 'rgb(255, 255, 255)',
			iframes: 0, scriptRan: false, policyLeaked: false, commentKept: true, secondCorrections: 0,
		} );
		assert.deepEqual( requests, [] );
	} finally {
		await browser.close();
	}
} );
