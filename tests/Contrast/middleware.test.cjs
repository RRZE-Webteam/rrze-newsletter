const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );

// Execute the real save middleware with recorded WordPress/API boundaries.
const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/editor/api/index.js' ), 'utf8' ), {
	babelrc: false, configFile: false, plugins: [ '@babel/plugin-transform-modules-commonjs' ],
} );

function harness( { enabled, failure, skipped = 0 } = {} ) {
	let middleware;
	const calls = [];
	const notices = [];
	const previews = [];
	const guardCalls = [];
	const apiFetch = async ( options ) => {
		calls.push( options );
		return options.path === '/rrze-newsletter/v1/post-mjml' ? { mjml: '<mjml />' } : {};
	};
	apiFetch.use = ( handler ) => { middleware = handler; };
	const meta = { rrze_newsletter_contrast_protection: enabled, rrze_newsletter_background_color: '#000000' };
	const dependencies = {
		lodash: require( 'lodash' ),
		'mjml-browser': () => ( { html: '<html>Original</html>' } ),
		'@wordpress/api-fetch': apiFetch,
		'@wordpress/data': {
			select: () => ( { getCurrentPostType: () => 'newsletter', getEditedPostAttribute: () => meta } ),
			dispatch: () => ( {
				createWarningNotice: ( ...args ) => notices.push( [ 'warning', ...args ] ),
				createErrorNotice: ( ...args ) => notices.push( [ 'error', ...args ] ),
				removeNotice: ( ...args ) => notices.push( [ 'remove', ...args ] ),
			} ),
		},
		'@wordpress/i18n': { __: ( text ) => text, sprintf: ( text ) => text },
		'../contrast/contrast.mjs': { protectEmailHtml: async ( html, active ) => {
			guardCalls.push( [ html, active ] );
			if ( failure ) { throw failure; }
			return { html: active ? '<html>Corrected</html>' : html, corrected: active ? 1 : 0, skipped };
		} },
		'../contrast/preview': { showEmailPreview: ( html ) => previews.push( html ) },
	};
	vm.runInNewContext( code, {
		exports: {},
		require: ( name ) => {
			assert.ok( name in dependencies, `Unexpected dependency: ${ name }` );
			return dependencies[ name ];
		},
		window: { rrze_newsletter_data: { mjml_handling_post_types: [ 'newsletter' ], email_html_meta: 'rrze_newsletter_email_html' } },
	} );
	const options = { method: 'POST', path: '/wp/v2/newsletter/42', data: { id: 42, title: 'Test', content: '<p>Original blocks</p>', meta: {} } };
	let forwarded = false;
	return { calls, notices, previews, guardCalls, options,
		run: () => middleware( options, async () => { forwarded = true; return 'saved'; } ),
		forwarded: () => forwarded,
	};
}

test( 'default protection saves corrected HTML and previews that exact HTML without rewriting blocks', async () => {
	const state = harness( { skipped: 2 } );
	assert.equal( await state.run(), 'saved' );
	assert.equal( state.guardCalls[ 0 ][ 1 ], true );
	assert.equal( state.calls[ 2 ].data.meta.rrze_newsletter_email_html, '<html>Corrected</html>' );
	assert.equal( state.options.data.content, '<p>Original blocks</p>' );
	const notice = state.notices.find( ( item ) => item[ 0 ] === 'warning' );
	assert.equal( notice[ 2 ].id, 'rrze-newsletter-contrast' );
	notice[ 2 ].actions[ 0 ].onClick();
	assert.deepEqual( state.previews, [ '<html>Corrected</html>' ] );
} );

test( 'opting out persists false and saves the unmodified compiler output', async () => {
	const state = harness( { enabled: false } );
	await state.run();
	assert.equal( state.calls[ 0 ].data.meta.rrze_newsletter_contrast_protection, false );
	assert.equal( state.guardCalls[ 0 ][ 1 ], false );
	assert.equal( state.calls[ 2 ].data.meta.rrze_newsletter_email_html, '<html>Original</html>' );
	assert.ok( ! state.notices.some( ( item ) => item[ 0 ] === 'warning' ) );
} );

test( 'analysis failure is visible and does not save unchecked HTML or continue the update', async () => {
	const failure = new Error( 'Analysis failed' );
	const state = harness( { failure } );
	await assert.rejects( state.run(), failure );
	assert.equal( state.calls.length, 2 );
	assert.equal( state.forwarded(), false );
	assert.equal( state.notices[ 0 ][ 0 ], 'error' );
} );
