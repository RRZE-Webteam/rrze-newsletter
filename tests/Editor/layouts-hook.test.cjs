const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const React = require( 'react' );
const { createRoot } = require( 'react-dom/client' );
const { JSDOM } = require( 'jsdom' );
const { transformSync } = require( '@babel/core' );
const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/utils/hooks.js' ), 'utf8' ), {
	babelrc: false, configFile: false, plugins: [ '@babel/plugin-transform-modules-commonjs' ],
} );
async function harness( apiFetch ) {
	const dom = new JSDOM( '<body><main></main></body>' );
	const previous = { window: global.window, document: global.document, act: global.IS_REACT_ACT_ENVIRONMENT };
	global.window = dom.window; global.document = dom.window.document; global.IS_REACT_ACT_ENVIRONMENT = true;
	const exported = {};
	const dependencies = { '@wordpress/api-fetch': apiFetch, '@wordpress/element': React, './consts': { LAYOUT_CPT_SLUG: 'newsletter_layout' } };
	vm.runInNewContext( code, { exports: exported, require: ( name ) => { assert.ok( name in dependencies ); return dependencies[ name ]; } } );
	let value;
	function App() { value = exported.useLayoutsState(); return null; }
	const root = createRoot( dom.window.document.querySelector( 'main' ) );
	await React.act( async () => root.render( React.createElement( App ) ) );
	return { read: () => value, close: async () => {
		await React.act( () => root.unmount() ); dom.window.close();
		global.window = previous.window; global.document = previous.document; global.IS_REACT_ACT_ENVIRONMENT = previous.act;
	} };
}
test( 'layout request failure ends loading, retry recovers, malformed response fails visibly', async () => {
	let response = 'failure';
	const h = await harness( async () => { if ( response === 'failure' ) { throw new Error( 'offline' ); } return response; } );
	try {
		assert.equal( h.read().isFetchingLayouts, false );
		assert.equal( h.read().layoutsError, true );
		response = [ { ID: 9 } ];
		await React.act( async () => h.read().retryLayouts() );
		assert.equal( h.read().layoutsError, false );
		assert.equal( h.read().layouts[ 0 ].ID, 9 );
		response = {};
		await React.act( async () => h.read().retryLayouts() );
		assert.equal( h.read().layoutsError, true );
		assert.equal( h.read().isFetchingLayouts, false );
	} finally { await h.close(); }
} );
test( 'failed deletion keeps the saved template; only confirmed success removes it', async () => {
	let failDelete = true;
	const h = await harness( async ( options ) => {
		if ( options.method !== 'DELETE' ) { return [ { ID: 9 }, { ID: 10 } ]; }
		assert.equal( options.path, '/wp/v2/newsletter_layout/9' );
		if ( failDelete ) { throw new Error( 'Forbidden' ); }
		return {};
	} );
	try {
		await React.act( async () => { await assert.rejects( h.read().deleteLayoutPost( 9 ), /Forbidden/ ); } );
		assert.equal( h.read().layouts.length, 2 );
		failDelete = false;
		await React.act( () => h.read().deleteLayoutPost( 9 ) );
		assert.equal( h.read().layouts.length, 1 );
		assert.equal( h.read().layouts[ 0 ].ID, 10 );
	} finally { await h.close(); }
} );
