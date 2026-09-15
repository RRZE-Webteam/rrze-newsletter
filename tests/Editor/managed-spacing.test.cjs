const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const policy = import( '../../src/newsletter-editor/styling/managed-spacing.mjs' );
const canvas = '<div class="editor-styles-wrapper"><div class="is-root-container"><p data-type="core/paragraph" style="margin:90px;padding:80px">Unchanged content</p></div></div>';
const tick = () => new Promise( ( resolve ) => setImmediate( resolve ) );

for ( const legacyWrapper of [ false, true ] ) {
	for ( const type of [ 'heading', 'paragraph' ] ) {
		test( `${ type } bottom spacing stays contained by modern/legacy groups (legacy=${ legacyWrapper })`, async () => {
			const { mountManagedSpacing } = await policy;
			const tag = type === 'heading' ? 'h3' : 'p';
			const group = ( children ) => `<div data-type="core/group" style="background-color:#04316a">${ legacyWrapper ? `<div class="wp-block-group__inner-container">${ children }</div>` : children }</div>`;
			for ( const depth of [ 0, 1, 12 ] ) {
				let markup = `<${ tag } data-type="core/${ type }">News</${ tag }>`;
				for ( let level = 0; level <= depth; level++ ) markup = group( markup );
				const dom = new JSDOM( `<div class="editor-styles-wrapper"><div class="is-root-container">${ markup }</div></div>` );
				const doc = dom.window.document;
				const original = doc.body.innerHTML;
				const dispose = mountManagedSpacing( doc );
				const css = ( node ) => dom.window.getComputedStyle( node );
				// jsdom does not perform layout: assert the formatting context that
				// prevents the last child's margin from collapsing outside its group.
				for ( const container of doc.querySelectorAll( '[data-type="core/group"], .wp-block-group__inner-container' ) ) {
					assert.equal( css( container ).display, 'flow-root' );
					assert.ok( [ '', '0px' ].includes( css( container ).paddingBottom ), 'Nested groups must not accumulate padding' );
				}
				assert.equal( css( doc.querySelector( tag ) ).marginBottom, '16px' );
				dispose();
				assert.equal( doc.body.innerHTML, original, 'Expert mode restores the original content and styling' );
				assert.equal( css( doc.querySelector( '[data-type="core/group"]' ) ).display, 'block' );
				dom.window.close();
			}
		} );
	}
}

test( 'group margin containment leaves grid, columns, buttons and outside groups in their own layouts', async () => {
	const { mountManagedSpacing } = await policy;
	const dom = new JSDOM( `<div id="outside" data-type="core/group"></div><div class="editor-styles-wrapper"><div class="is-root-container">
		<div id="grid" data-type="core/group" class="is-layout-grid" style="display:grid"><div class="wp-block-group__inner-container"></div></div>
		<div id="columns" data-type="core/columns" style="display:flex"></div>
		<div id="buttons" data-type="core/buttons"></div>
	</div></div>` );
	const doc = dom.window.document;
	const dispose = mountManagedSpacing( doc );
	for ( const [ selector, display ] of [ [ '#outside', 'block' ], [ '#grid', 'grid' ], [ '#grid > div', 'block' ], [ '#columns', 'flex' ], [ '#buttons', 'block' ] ] ) {
		assert.equal( dom.window.getComputedStyle( doc.querySelector( selector ) ).display, display );
	}
	dispose();
	dom.window.close();
} );

test( 'editor mode resolution matches PHP precedence, including missing and invalid values', async () => {
	const { isManagedSpacing } = await policy;
	for ( const globalEnabled of [ false, true ] ) {
		for ( const mode of [ undefined, '', 'inherit', 'managed', 'expert', 'unknown' ] ) {
			assert.equal( isManagedSpacing( mode, globalEnabled ), mode === 'managed' || ( mode !== 'expert' && globalEnabled ) );
		}
	}
} );

test( 'WordPress localized checkbox values activate inherited spacing without treating false-like strings as enabled', async () => {
	const { isManagedSpacing } = await policy;
	// wp_localize_script serializes scalar true/false as "1"/"".
	assert.equal( isManagedSpacing( 'inherit', '1' ), true );
	assert.equal( isManagedSpacing( undefined, '1' ), true );
	assert.equal( isManagedSpacing( 'expert', '1' ), false );
	for ( const value of [ '', '0', 'false', 'off', null, undefined, {}, [] ] ) {
		assert.equal( isManagedSpacing( 'inherit', value ), false );
		assert.equal( isManagedSpacing( 'managed', value ), true );
	}
} );

test( 'activation styles only the canvas and cleanup restores original markup and manual values', async () => {
	const { mountManagedSpacing, ROOT_CLASS, STYLE_ID } = await policy;
	const dom = new JSDOM( `<html><head></head><body><aside>Inspector</aside>${ canvas }</body></html>` );
	const doc = dom.window.document;
	const original = doc.body.innerHTML;
	const block = doc.querySelector( '[data-type]' );
	const originalBlock = block.outerHTML;
	const dispose = mountManagedSpacing( doc );
	assert.ok( doc.querySelector( `.is-root-container.${ ROOT_CLASS }` ) );
	assert.ok( doc.getElementById( STYLE_ID ) );
	assert.equal( block.outerHTML, originalBlock );
	assert.equal( doc.querySelector( 'aside' ).outerHTML, '<aside>Inspector</aside>' );
	dispose();
	assert.equal( doc.body.innerHTML, original );
	assert.equal( doc.getElementById( STYLE_ID ), null );
	// Simulate a second on/off toggle.
	mountManagedSpacing( doc )();
	assert.equal( doc.body.innerHTML, original );
	dom.window.close();
} );

test( 'late canvases and editor frames are styled; unrelated email and pattern previews are untouched', async () => {
	const { mountManagedSpacing, ROOT_CLASS, STYLE_ID } = await policy;
	const dom = new JSDOM( '<html><head></head><body></body></html>' );
	const doc = dom.window.document;
	const dispose = mountManagedSpacing( doc );
	doc.body.innerHTML = `${ canvas }<iframe name="editor-canvas" title="Editor-Inhalt"></iframe><iframe title="Email content"></iframe>`;
	const editor = doc.querySelector( '[name="editor-canvas"]' );
	const other = doc.querySelector( '[title="Email content"]' );
	editor.contentDocument.body.innerHTML = canvas;
	other.contentDocument.body.innerHTML = canvas;
	await tick();
	assert.ok( editor.contentDocument.querySelector( `.${ ROOT_CLASS }` ) );
	assert.equal( other.contentDocument.getElementById( STYLE_ID ), null );
	assert.ok( doc.querySelector( `.${ ROOT_CLASS }` ) );
	// Repeated frame load does not accumulate styles/listeners.
	editor.dispatchEvent( new dom.window.Event( 'load' ) );
	assert.equal( editor.contentDocument.querySelectorAll( `#${ STYLE_ID }` ).length, 1 );
	const frameDoc = editor.contentDocument;
	editor.remove();
	await tick();
	assert.equal( frameDoc.getElementById( STYLE_ID ), null );
	dispose();
	doc.body.innerHTML = canvas;
	await tick();
	assert.equal( doc.querySelector( `.${ ROOT_CLASS }` ), null );
	dom.window.close();
} );

test( 'post-inserter preview frames and late replacement roots inherit the policy', async () => {
	const { mountManagedSpacing, ROOT_CLASS, STYLE_ID } = await policy;
	const dom = new JSDOM( '<html><head></head><body><div class="rrze-newsletter-post-inserter__preview"><iframe></iframe></div></body></html>' );
	const doc = dom.window.document;
	const frameDoc = doc.querySelector( 'iframe' ).contentDocument;
	const dispose = mountManagedSpacing( doc );
	frameDoc.body.innerHTML = canvas;
	await tick();
	const oldRoot = frameDoc.querySelector( '.is-root-container' );
	assert.ok( oldRoot.classList.contains( ROOT_CLASS ) );
	frameDoc.body.innerHTML = canvas;
	await tick();
	assert.ok( ! oldRoot.classList.contains( ROOT_CLASS ) );
	assert.ok( frameDoc.querySelector( `.${ ROOT_CLASS }` ) );
	dispose();
	assert.equal( frameDoc.getElementById( STYLE_ID ), null );
	dom.window.close();
} );

test( 'responsive gutters use canvas width and observers disconnect on cleanup', async () => {
	const { mountManagedSpacing } = await policy;
	const dom = new JSDOM( `<html><head></head><body>${ canvas }</body></html>` );
	let callback;
	let disconnected = false;
	dom.window.ResizeObserver = class {
		constructor( handler ) { callback = handler; }
		observe() {}
		unobserve() {}
		disconnect() { disconnected = true; }
	};
	const dispose = mountManagedSpacing( dom.window.document );
	const root = dom.window.document.querySelector( '.is-root-container' );
	for ( const width of [ 680, 479, 320, 480, 0 ] ) {
		callback( [ { target: root, contentRect: { width } } ] );
		assert.equal( root.classList.contains( 'rrze-managed-narrow' ), width > 0 && width < 480 );
	}
	dispose();
	assert.ok( disconnected );
	dom.window.close();
} );

test( 'iframe load without a body and later React body replacements retain managed spacing', async () => {
	const { mountManagedSpacing, ROOT_CLASS, STYLE_ID } = await policy;
	const dom = new JSDOM( '<html><head></head><body><iframe name="editor-canvas"></iframe></body></html>' );
	const doc = dom.window.document;
	const frame = doc.querySelector( 'iframe' );
	const frameDoc = frame.contentDocument;
	// Gutenberg removes the initial body, then portals a new one after load.
	frameDoc.body.remove();
	// Its compatibility loader may copy the parent's stylesheet first.
	const copiedStyle = frameDoc.createElement( 'style' );
	copiedStyle.id = STYLE_ID;
	frameDoc.head.appendChild( copiedStyle );
	const dispose = mountManagedSpacing( doc );
	frame.dispatchEvent( new dom.window.Event( 'load' ) );
	const body = frameDoc.createElement( 'body' );
	body.innerHTML = canvas;
	frameDoc.documentElement.appendChild( body );
	await tick();
	const oldRoot = frameDoc.querySelector( '.is-root-container' );
	assert.ok( oldRoot.classList.contains( ROOT_CLASS ) );
	const replacement = frameDoc.createElement( 'body' );
	replacement.innerHTML = canvas;
	body.replaceWith( replacement );
	await tick();
	assert.ok( frameDoc.querySelector( `.${ ROOT_CLASS }` ) );
	assert.ok( ! oldRoot.classList.contains( ROOT_CLASS ) );
	assert.equal( frameDoc.querySelectorAll( `#${ STYLE_ID }` ).length, 1 );
	dispose();
	assert.equal( frameDoc.getElementById( STYLE_ID ), null );
	assert.equal( frameDoc.querySelector( `.${ ROOT_CLASS }` ), null );
	dom.window.close();
} );

test( 'React root class updates do not silently turn off the spacing preview', async () => {
	const { mountManagedSpacing, ROOT_CLASS } = await policy;
	const dom = new JSDOM( `<html><head></head><body>${ canvas }</body></html>` );
	const doc = dom.window.document;
	const dispose = mountManagedSpacing( doc );
	const root = doc.querySelector( '.is-root-container' );
	root.className = 'is-root-container is-outline-mode';
	await tick();
	assert.ok( root.classList.contains( ROOT_CLASS ) );
	dispose();
	assert.equal( root.className, 'is-root-container is-outline-mode' );
	dom.window.close();
} );
