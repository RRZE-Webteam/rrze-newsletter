const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );

const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/editor/contrast/preview-menu.js' ), 'utf8' ), {
	babelrc: false, configFile: false,
	plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
} );

function harness( overrides = {} ) {
	const state = { postType: 'newsletter', postId: 42, saving: false, dirty: false, ...overrides };
	const calls = [], previews = [], errors = [], loading = [], registrations = [], cleanups = [];
	const editor = {
		getCurrentPostType: () => state.postType,
		getCurrentPostId: () => state.postId,
		isSavingPost: () => state.saving,
		isEditedPostDirty: () => state.dirty,
	};
	const select = ( store ) => { assert.equal( store, 'core/editor' ); return editor; };
	const dependencies = {
		'@wordpress/api-fetch': async ( options ) => {
			calls.push( options );
			return state.fetch ? state.fetch() : { meta: { saved_email: '<html>Latest saved mail</html>' } };
		},
		'@wordpress/data': {
			select, useSelect: ( callback ) => callback( select ),
			dispatch: ( store ) => { assert.equal( store, 'core/notices' ); return { createErrorNotice: ( ...args ) => errors.push( args ) }; },
		},
		'@wordpress/editor': { PluginPreviewMenuItem: 'PluginPreviewMenuItem' },
		'@wordpress/element': {
			useState: () => [ false, ( value ) => loading.push( value ) ],
			useRef: ( value ) => ( { current: value } ),
			useEffect: ( callback ) => cleanups.push( callback() ),
		},
		'@wordpress/i18n': { __: ( text ) => text },
		'@wordpress/plugins': { registerPlugin: ( ...args ) => registrations.push( args ) },
		'./preview': { showEmailPreview: ( ...args ) => previews.push( args ) },
	};
	const exported = {};
	vm.runInNewContext( code, {
		exports: exported,
		window: { rrze_newsletter_data: { email_html_meta: 'saved_email' } },
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props, children } ),
	} );
	return { state, calls, previews, errors, loading, registrations, cleanups, menu: exported.EmailPreviewMenuItem() };
}

test( 'registers the native preview menu entry for newsletters only', () => {
	const state = harness();
	assert.equal( state.registrations[ 0 ][ 0 ], 'rrze-newsletter-email-preview' );
	assert.equal( typeof state.registrations[ 0 ][ 1 ].render, 'function' );
	assert.equal( state.menu.type, 'PluginPreviewMenuItem' );
	assert.equal( state.menu.children[ 0 ], 'Email preview' );
	for ( const postType of [ 'post', 'page', 'newsletter_layout', undefined ] ) assert.equal( harness( { postType } ).menu, null );
} );

test( 'each click loads the latest saved HTML read-only and reuses the existing modal', async () => {
	const state = harness();
	await state.menu.props.onClick();
	state.state.fetch = () => ( { meta: { saved_email: '<html>Newly saved mail</html>' } } );
	await state.menu.props.onClick();
	assert.equal( state.calls.length, 2 );
	for ( const call of state.calls ) {
		assert.equal( call.method, 'GET' );
		assert.equal( call.path, '/wp/v2/newsletter/42?context=edit&_fields=meta' );
		assert.equal( call.data, undefined, 'Preview must not save or send a newsletter' );
	}
	assert.equal( state.previews[ 0 ][ 0 ], '<html>Latest saved mail</html>' );
	assert.equal( state.previews[ 1 ][ 0 ], '<html>Newly saved mail</html>' );
	assert.equal( state.previews[ 1 ][ 1 ].savedVersion, true );
	assert.equal( state.previews[ 1 ][ 1 ].isStale, false );
	assert.deepEqual( state.loading, [ true, false, true, false ] );
} );

test( 'pending changes are checked after loading; duplicate clicks are ignored', async () => {
	let resolve;
	const state = harness( { fetch: () => new Promise( ( done ) => { resolve = done; } ) } );
	const request = state.menu.props.onClick();
	await state.menu.props.onClick();
	state.state.dirty = true;
	resolve( { meta: { saved_email: 'Saved HTML' } } );
	await request;
	assert.equal( state.calls.length, 1 );
	assert.equal( state.previews.length, 1 );
	assert.equal( state.previews[ 0 ][ 1 ].isStale, true );
} );

test( 'saving disables the menu and starting a save during loading marks the preview as potentially stale', async () => {
	const saving = harness( { saving: true } );
	assert.equal( saving.menu.props.disabled, true );
	await saving.menu.props.onClick();
	assert.equal( saving.calls.length, 0 );
	const state = harness();
	state.state.fetch = () => { state.state.saving = true; return { meta: { saved_email: 'Saved HTML' } }; };
	await state.menu.props.onClick();
	assert.equal( state.previews[ 0 ][ 1 ].isStale, true );
} );

test( 'new newsletters and missing email metadata use the empty preview state', async () => {
	const fresh = harness( { postId: undefined } );
	await fresh.menu.props.onClick();
	assert.equal( fresh.calls.length, 0 );
	assert.equal( fresh.previews[ 0 ][ 0 ], '' );
	for ( const response of [ {}, { meta: {} }, { meta: { saved_email: null } } ] ) {
		const state = harness( { fetch: () => response } );
		await state.menu.props.onClick();
		assert.equal( state.previews[ 0 ][ 0 ], '' );
	}
} );

test( 'failed requests show a notice and allow retrying without opening stale HTML', async () => {
	const state = harness( { fetch: () => { throw new Error( 'Forbidden' ); } } );
	await state.menu.props.onClick();
	assert.equal( state.previews.length, 0 );
	assert.equal( state.errors[ 0 ][ 1 ].id, 'rrze-newsletter-preview' );
	assert.deepEqual( state.loading, [ true, false ] );
	state.state.fetch = undefined;
	await state.menu.props.onClick();
	assert.equal( state.previews.length, 1 );
} );

test( 'late responses never open a preview after unmounting or changing newsletters', async () => {
	for ( const change of [ ( state ) => state.cleanups.forEach( ( cleanup ) => cleanup() ), ( state ) => { state.state.postId = 99; }, ( state ) => { state.state.postType = 'post'; } ] ) {
		const state = harness();
		state.state.fetch = () => { change( state ); return { meta: { saved_email: 'Wrong mail' } }; };
		await state.menu.props.onClick();
		assert.equal( state.previews.length, 0 );
	}
} );
