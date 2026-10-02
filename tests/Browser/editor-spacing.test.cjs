const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const { test } = require( 'node:test' );
const { chromium } = require( 'playwright' );

const source = readFileSync( path.join( __dirname, '../../src/newsletter-editor/styling/managed-spacing.mjs' ), 'utf8' );
const existingCss = readFileSync( path.join( __dirname, '../../build/editor.style.css' ), 'utf8' );
const canvas = `<div class="editor-styles-wrapper"><div class="block-editor-block-list__layout is-root-container">
<div data-type="core/group" class="wp-block wp-block-group" style="padding:120px">
<p id="first" data-type="core/paragraph" class="wp-block" style="margin:70px;padding:80px">First paragraph</p>
<div id="nested" data-type="core/group" class="wp-block wp-block-group" style="padding:90px">
<p id="second" data-type="core/paragraph" class="wp-block">Second paragraph</p></div>
<div data-type="core/columns" class="wp-block wp-block-columns" style="display:flex;gap:40px">
<div id="column" data-type="core/column" class="wp-block wp-block-column"><p data-type="core/paragraph">Column</p></div></div>
<div id="spacer" data-type="core/spacer" class="wp-block" style="height:200px"></div>
</div></div></div>`;

test( 'editor spacing is live, reversible and scoped in both document and iframe canvases', { timeout: 30000 }, async () => {
	const browser = await chromium.launch( { headless: true, channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', timeout: 10000 } );
	try {
		const page = await browser.newPage();
		await page.route( '**/*', ( route ) => route.abort() );
		await page.setViewportSize( { width: 1100, height: 900 } );
		await page.setContent( `<style>${ existingCss }</style><aside style="padding:37px">Inspector</aside><div id="surface" style="width:680px">${ canvas }</div><iframe name="editor-canvas" style="width:320px"></iframe>` );
		await page.evaluate( async ( { source, canvas, existingCss } ) => {
			const frame = document.querySelector( 'iframe' );
			frame.contentDocument.head.innerHTML = `<style>${ existingCss }</style>`;
			frame.contentDocument.body.innerHTML = canvas;
			window.spacing = await import( `data:text/javascript;charset=utf-8,${ encodeURIComponent( source ) }` );
			window.disposeSpacing = window.spacing.mountManagedSpacing( document );
		}, { source, canvas, existingCss } );
		const measure = () => page.evaluate( () => {
			function read( doc ) {
				const root = doc.querySelector( '.is-root-container' );
				const css = ( selector ) => doc.defaultView.getComputedStyle( doc.querySelector( selector ) );
				return {
					gap: css( '#first' ).marginBottom, padding: css( '#first' ).paddingLeft,
					gutter: css( '.is-root-container > [data-type]' ).paddingLeft,
					nested: css( '#nested' ).paddingLeft, column: css( '#column' ).paddingLeft,
					spacer: css( '#spacer' ).height,
					scroll: root.scrollWidth, width: root.clientWidth,
				};
			}
			return { main: read( document ), frame: read( document.querySelector( 'iframe' ).contentDocument ), inspector: getComputedStyle( document.querySelector( 'aside' ) ).paddingLeft };
		} );
		await page.waitForFunction( () => document.querySelector( 'iframe' ).contentDocument.querySelector( '.rrze-managed-narrow' ) );
		let state = await measure();
		for ( const surface of [ state.main, state.frame ] ) {
			assert.equal( surface.gap, '16px' );
			assert.equal( surface.padding, '0px' );
			assert.equal( surface.nested, '0px' );
			assert.equal( surface.column, '8px' );
			assert.equal( surface.spacer, '16px' );
			assert.ok( surface.scroll <= surface.width + 1 );
		}
		assert.equal( state.main.gutter, '24px' );
		assert.equal( state.frame.gutter, '16px' );
		assert.equal( state.inspector, '37px' );
		await page.evaluate( () => { document.querySelector( '#surface' ).style.width = '320px'; } );
		await page.waitForFunction( () => document.querySelector( '#surface .rrze-managed-narrow' ) );
		state = await measure();
		assert.equal( state.main.gutter, '16px' );
		await page.evaluate( () => window.disposeSpacing() );
		state = await measure();
		assert.equal( state.main.gap, '70px' );
		assert.equal( state.main.padding, '80px' );
		assert.equal( state.frame.gap, '70px' );
		assert.equal( state.main.spacer, '200px' );
	} finally {
		await browser.close();
	}
} );
