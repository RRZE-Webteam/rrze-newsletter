const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const { transformSync } = require( '@babel/core' );

const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/editor/contrast/preview.js' ), 'utf8' ), {
	babelrc: false, configFile: false,
	plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
} );

function previewHarness() {
	const dom = new JSDOM( '<body><main>Editor</main></body>' );
	const renders = [];
	const dependencies = {
		'@wordpress/element': { createRoot: ( host ) => ( {
			render: ( tree ) => renders.push( { host, tree, unmounted: false } ),
			unmount: () => { renders.find( ( item ) => item.host === host ).unmounted = true; },
		} ) },
		'@wordpress/components': { Modal: 'Modal' },
		'@wordpress/i18n': { __: ( text ) => text },
	};
	const exported = {};
	vm.runInNewContext( code, {
		exports: exported, document: dom.window.document,
		require: ( name ) => { assert.ok( name in dependencies ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props, children } ),
	} );
	return { dom, renders, show: exported.showEmailPreview };
}

test( 'email preview retains the site origin for protected images but never enables scripts or other sandbox capabilities', () => {
	const state = previewHarness();
	try {
		const html = '<!doctype html><html><head></head><body><img src="https://newsletter.example.test/uploads/cover.jpg"><!--[if mso]>Outlook<![endif]--></body></html>';
		state.show( html );
		const frame = state.renders[ 0 ].tree.children.find( ( node ) => node?.type === 'iframe' );
		// An empty sandbox makes even own-site images cross-site requests, losing
		// SameSite authentication cookies or failing same-origin resource policies.
		assert.equal( frame.props.sandbox, 'allow-same-origin' );
		assert.equal( frame.props.srcDoc, html, 'Preview must not rewrite the saved email or its image URLs' );
		assert.equal( frame.props.title, 'Email content' );
	} finally {
		state.dom.window.close();
	}
} );

test( 'closing and reopening the email preview unmounts only its own host', () => {
	const state = previewHarness();
	try {
		for ( let index = 0; index < 2; index++ ) {
			state.show( '<html><body>Preview</body></html>' );
			const item = state.renders[ index ];
			assert.ok( item.host.isConnected );
			item.tree.props.onRequestClose();
			assert.ok( item.unmounted );
			assert.equal( item.host.isConnected, false );
			assert.equal( state.dom.window.document.body.innerHTML, '<main>Editor</main>' );
		}
	} finally {
		state.dom.window.close();
	}
} );
