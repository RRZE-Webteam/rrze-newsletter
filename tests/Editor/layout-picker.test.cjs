const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const React = require( 'react' );
const { createRoot } = require( 'react-dom/client' );
const { JSDOM } = require( 'jsdom' );
const { transformSync } = require( '@babel/core' );
const folder = '../../src/components/init-modal/screens/layout-picker/';
function load( file, dependencies ) {
	const { code } = transformSync( readFileSync( path.join( __dirname, folder, file ), 'utf8' ), {
		babelrc: false, configFile: false,
		plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'React.createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
	} );
	const exports = {};
	vm.runInNewContext( code, { exports, React, setTimeout, window: global.window, require: ( name ) => {
		assert.ok( name in dependencies, name ); return dependencies[ name ];
	} } );
	return exports;
}
const defaults = [
	{ ID: 1, post_title: 'Newsletter 1', post_content: 'Classic' },
	{ ID: 3, post_title: 'FAU Newsletter', post_content: '{{EMAIL_ONLY}}News{{/EMAIL_ONLY}}', meta: { color: 'blue' } },
];
async function setup( extra = {} ) {
	const dom = new JSDOM( '<body><div id="test"></div></body>' );
	const previous = { window: global.window, document: global.document, act: global.IS_REACT_ACT_ENVIRONMENT };
	global.window = dom.window; global.document = dom.window.document; global.IS_REACT_ACT_ENVIRONMENT = true;
	const calls = [];
	const state = { layouts: defaults, isFetchingLayouts: false, retryLayouts: () => calls.push( [ 'retry' ] ), deleteLayoutPost: async ( id ) => calls.push( [ 'delete', id ] ), ...extra };
	const components = {
		Button: ( { children, variant, isDestructive, ...props } ) => React.createElement( props.href ? 'a' : 'button', props, children ),
		Modal: ( { children, title } ) => React.createElement( 'section', { 'aria-label': title }, children ),
		TextControl: ( { label, value, onChange, disabled } ) => React.createElement( 'input', { 'aria-label': label, value, disabled, onInput: ( event ) => onChange( event.target.value ) } ),
		Spinner: () => null,
		Notice: ( { children } ) => React.createElement( 'div', { role: 'alert' }, children ),
	};
	const deps = {
		'@wordpress/blocks': { parse: ( content ) => {
			if ( content === 'broken' ) { throw new Error( 'Invalid template' ); }
			return content ? [ { content } ] : [];
		} },
		'@wordpress/element': React,
		'@wordpress/compose': { compose: () => ( component ) => component },
		'@wordpress/data': { withSelect: () => {}, withDispatch: () => {}, dispatch: () => ( {
			createErrorNotice: () => calls.push( [ 'error' ] ),
			saveEntityRecord: async ( ...args ) => calls.push( [ 'rename', ...args ] ),
		} ) },
		'@wordpress/components': components,
		'@wordpress/i18n': { __: ( value ) => value },
		'../../../../utils/consts': { BLANK_LAYOUT_ID: 0, LAYOUT_CPT_SLUG: 'newsletter_layout' },
		'../../../../utils': { isUserDefinedLayout: ( layout ) => layout.post_author !== undefined, getBaseUrl: () => '/', convertRelativeUrlsToAbsolute: ( content ) => content },
		'../../../../utils/hooks': { useLayoutsState: () => state },
		'./LayoutPreview': ( { preview, width } ) => React.createElement( 'output', { 'data-width': width }, JSON.stringify( preview ) ),
		'./ManageTemplates': () => React.createElement( 'div', null, 'Management' ),
	};
	deps[ './SingleLayoutPreview' ] = load( 'SingleLayoutPreview.js', deps );
	deps[ './ManageTemplates' ] = load( 'ManageTemplates.js', deps );
	const { LayoutPicker } = load( 'index.js', deps );
	const props = {
		getBlocks: () => [], insertBlocks: ( blocks ) => calls.push( [ 'insert', blocks ] ),
		replaceBlocks: ( ...args ) => calls.push( [ 'replace', ...args ] ),
		setNewsletterMeta: ( meta ) => calls.push( [ 'meta', meta ] ),
		savePost: async () => calls.push( [ 'save' ] ),
	};
	const root = createRoot( dom.window.document.getElementById( 'test' ) );
	const render = () => React.act( () => root.render( React.createElement( LayoutPicker, props ) ) );
	await render();
	const button = ( label ) => [ ...dom.window.document.querySelectorAll( 'button' ) ].find( ( node ) => node.textContent === label );
	return { state, calls, props, render, doc: dom.window.document, button,
		click: async ( node ) => { await React.act( async () => { node.click(); await new Promise( ( resolve ) => setTimeout( resolve, 10 ) ); } ); },
		close: async () => { await React.act( () => root.unmount() ); dom.window.close(); global.window = previous.window; global.document = previous.document; global.IS_REACT_ACT_ENVIRONMENT = previous.act; },
	};
}

test( 'entry recommends a general template without writing editor state and renders only one preview', async () => {
	const h = await setup();
	try {
		assert.equal( h.doc.querySelectorAll( 'output' ).length, 1 );
		assert.ok( h.doc.querySelector( '.is-selected' ).textContent.includes( 'FAU Newsletter' ) );
		assert.equal( h.doc.querySelector( 'output' ).dataset.width, '680' );
		assert.ok( ! h.doc.querySelector( 'output' ).textContent.includes( 'EMAIL_ONLY' ) );
		assert.equal( h.doc.querySelector( 'a' ).getAttribute( 'href' ), 'edit.php?post_type=newsletter' );
		assert.equal( h.calls.length, 0 );
	} finally { await h.close(); }
} );
test( 'an empty saved category clears the old preview and disables starting with it', async () => {
	const h = await setup();
	try {
		await h.click( h.button( 'Saved' ) );
		assert.equal( h.doc.querySelector( 'output' ), null );
		assert.ok( h.button( 'Start with this template' ).disabled );
		assert.ok( h.doc.body.textContent.includes( 'No templates here yet' ) );
		await h.click( h.button( 'Browse prebuilt templates' ) );
		assert.ok( ! h.button( 'Start with this template' ).disabled );
		assert.equal( h.calls.length, 0 );
	} finally { await h.close(); }
} );
test( 'saved templates, viewport switches and same-length content updates stay in sync', async () => {
	const saved = { ID: 9, post_author: 2, post_title: 'Saved design', post_content: 'Old' };
	const h = await setup( { layouts: [ ...defaults, saved ] } );
	try {
		await h.click( h.button( 'Saved' ) );
		assert.ok( h.doc.querySelector( 'output' ).textContent.includes( 'Old' ) );
		await h.click( h.button( 'Mobile' ) );
		assert.equal( h.doc.querySelector( 'output' ).dataset.width, '375' );
		h.state.layouts = [ ...defaults, { ...saved, post_content: 'Updated' } ];
		await h.render();
		assert.ok( h.doc.querySelector( 'output' ).textContent.includes( 'Updated' ) );
		assert.equal( h.calls.length, 0 );
	} finally { await h.close(); }
} );
test( 'starting uses original tokens and template metadata, never sends mail', async () => {
	const h = await setup();
	try {
		await h.click( h.button( 'Start with this template' ) );
		assert.deepEqual( h.calls.map( ( call ) => call[ 0 ] ), [ 'insert', 'meta', 'save' ] );
		assert.equal( h.calls[ 0 ][ 1 ][ 0 ].content, defaults[ 1 ].post_content );
		assert.equal( h.calls[ 1 ][ 1 ].rrze_newsletter_template_id, 3 );
		assert.equal( h.calls[ 1 ][ 1 ].color, 'blue' );
	} finally { await h.close(); }
} );
test( 'blank start replaces existing blocks and does not inherit selected template styling', async () => {
	const h = await setup();
	try {
		h.props.getBlocks = () => [ { clientId: 'existing' } ];
		await h.render();
		await h.click( h.button( 'Start without a template' ) );
		assert.deepEqual( h.calls.map( ( call ) => call[ 0 ] ), [ 'replace', 'meta', 'save' ] );
		assert.equal( h.calls[ 0 ][ 2 ].length, 0 );
		assert.deepEqual( Object.keys( h.calls[ 1 ][ 1 ] ), [ 'rrze_newsletter_template_id' ] );
		assert.equal( h.calls[ 1 ][ 1 ].rrze_newsletter_template_id, 0 );
	} finally { await h.close(); }
} );
test( 'loading, failed requests and malformed templates do not permit an invalid start', async () => {
	const h = await setup( { isFetchingLayouts: true } );
	try {
		assert.ok( h.button( 'Start with this template' ).disabled );
		h.state.isFetchingLayouts = false; h.state.layoutsError = true; h.state.layouts = [];
		await h.render();
		await h.click( h.button( 'Try again' ) );
		assert.equal( h.calls[ 0 ][ 0 ], 'retry' );
		h.state.layoutsError = false; h.state.layouts = [ { ...defaults[ 0 ], post_content: 'broken' } ];
		await h.render();
		assert.ok( h.button( 'Start with this template' ).disabled );
		assert.ok( ! h.button( 'Start without a template' ).disabled );
	} finally { await h.close(); }
} );
test( 'save failure raises a notice that remains available after the picker closes', async () => {
	const h = await setup();
	try {
		h.props.savePost = async () => { throw new Error( 'Offline' ); };
		await h.render();
		await h.click( h.button( 'Start with this template' ) );
		assert.equal( h.calls.at( -1 )[ 0 ], 'error' );
		assert.ok( h.doc.querySelector( '[role=alert]' ) );
	} finally { await h.close(); }
} );
test( 'template management is separate: rename is explicit and does not publish or rewrite content', async () => {
	const h = await setup( { layouts: [ ...defaults, { ID: 9, post_author: 2, post_title: 'Saved design', post_content: 'Body' } ] } );
	try {
		await h.click( h.button( 'Saved' ) );
		assert.equal( h.doc.querySelector( 'input' ), null );
		await h.click( h.button( 'Manage saved templates' ) );
		await React.act( async () => {
			const input = h.doc.querySelector( 'input' );
			input.value = 'Renamed';
			input.dispatchEvent( new window.Event( 'input', { bubbles: true } ) );
		} );
		assert.equal( h.calls.length, 0 );
		await h.click( h.button( 'Save' ) );
		assert.deepEqual( h.calls[ 0 ].slice( 0, 3 ), [ 'rename', 'postType', 'newsletter_layout' ] );
		assert.deepEqual( Object.keys( h.calls[ 0 ][ 3 ] ).sort(), [ 'id', 'title' ] );
		assert.equal( h.calls[ 0 ][ 3 ].title, 'Renamed' );
		assert.equal( h.calls[ 1 ][ 0 ], 'retry' );
	} finally { await h.close(); }
} );
test( 'template deletion requires confirmation and a failed request is visible', async () => {
	const h = await setup( { layouts: [ { ID: 9, post_author: 2, post_title: 'Saved design', post_content: 'Body' } ],
		deleteLayoutPost: async () => { throw new Error( 'Forbidden' ); },
	} );
	try {
		await h.click( h.button( 'Saved' ) );
		await h.click( h.button( 'Manage saved templates' ) );
		window.confirm = () => false;
		await h.click( h.button( 'Delete' ) );
		assert.equal( h.doc.querySelector( '[role=alert]' ), null );
		window.confirm = () => true;
		await h.click( h.button( 'Delete' ) );
		assert.ok( h.doc.querySelector( '[role=alert]' ) );
		assert.ok( ! h.button( 'Delete' ).disabled );
		assert.equal( h.calls.length, 0 );
	} finally { await h.close(); }
} );
