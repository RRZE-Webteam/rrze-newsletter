const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { transformSync } = require( '@babel/core' );
const modulePath = '../../src/components/init-modal/admin-shell.mjs';

function fixture( bar = true ) {
	const dom = new JSDOM( '<body class="is-fullscreen-mode"><div id="wpwrap">' +
		( bar ? '<div id="wpadminbar"><a href="#toolbar">Toolbar</a></div>' : '' ) +
		'<div id="adminmenumain"></div><div id="wpbody"></div><div id="wpfooter" inert="inert"></div></div>' +
		'<div id="start"><h1 tabindex="-1">Start</h1></div></body>' );
	const doc = dom.window.document;
	return { dom, doc, host: doc.getElementById( 'start' ) };
}
test( 'start view reserves the actual admin-bar height and leaves toolbar ancestors accessible', async () => {
	const { mountAdminShell } = await import( modulePath );
	const { dom, doc, host } = fixture();
	let height = 32;
	doc.getElementById( 'wpadminbar' ).getBoundingClientRect = () => ( { height } );
	let resized, disconnected = false;
	dom.window.ResizeObserver = class {
		constructor( fn ) { resized = fn; }
		observe() {}
		disconnect() { disconnected = true; }
	};
	const dispose = mountAdminShell( host );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '32px' );
	height = 46; resized();
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '46px' );
	assert.ok( doc.getElementById( 'wpbody' ).hasAttribute( 'inert' ) );
	assert.equal( doc.querySelector( '#wpadminbar' ).closest( '[inert], [aria-hidden=true]' ), null );
	assert.ok( doc.body.classList.contains( 'is-fullscreen-mode' ), 'Do not change the fullscreen preference' );
	doc.querySelector( '#wpadminbar a' ).focus();
	assert.equal( doc.activeElement.textContent, 'Toolbar', 'Focus is not trapped in the start screen' );
	dispose();
	assert.ok( disconnected );
	assert.equal( doc.getElementById( 'wpbody' ).hasAttribute( 'inert' ), false );
	assert.equal( doc.getElementById( 'wpfooter' ).getAttribute( 'inert' ), 'inert' );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '' );
	assert.equal( doc.body.className, 'is-fullscreen-mode' );
	assert.equal( doc.activeElement.textContent, 'Toolbar', 'Do not steal focus back from the toolbar' );
	dom.window.close();
} );
test( 'missing toolbar leaves no gap and pre-existing state is restored', async () => {
	const { mountAdminShell } = await import( modulePath );
	const { dom, doc, host } = fixture( false );
	doc.body.classList.add( 'rrze-newsletter-start-open' );
	host.style.setProperty( '--rrze-newsletter-adminbar-height', '8px', 'important' );
	const dispose = mountAdminShell( host );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '0px' );
	dispose();
	assert.ok( doc.body.classList.contains( 'rrze-newsletter-start-open' ) );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '8px' );
	assert.equal( host.style.getPropertyPriority( '--rrze-newsletter-adminbar-height' ), 'important' );
	dom.window.close();
} );
test( 'resize fallback updates the offset and cleanup restores initial focus', async () => {
	const { mountAdminShell } = await import( modulePath );
	const { dom, doc, host } = fixture();
	let height = 32;
	doc.querySelector( '#wpadminbar' ).getBoundingClientRect = () => ( { height } );
	const link = doc.querySelector( '#wpadminbar a' );
	link.focus();
	const dispose = mountAdminShell( host );
	assert.equal( doc.activeElement.tagName, 'H1' );
	height = 46;
	dom.window.dispatchEvent( new dom.window.Event( 'resize' ) );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '46px' );
	dispose();
	assert.equal( doc.activeElement, link );
	height = 60;
	dom.window.dispatchEvent( new dom.window.Event( 'resize' ) );
	assert.equal( host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' ), '' );
	dom.window.close();
} );
test( 'start component uses a labelled non-modal body portal and returns shell cleanup', () => {
	const { dom, doc, host } = fixture();
	const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/components/init-modal/index.js' ), 'utf8' ), {
		babelrc: false, configFile: false,
		plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
	} );
	const effects = [];
	const cleanup = () => {};
	const dependencies = {
		'@wordpress/element': { createPortal: ( child, target ) => ( { child, target } ), useRef: () => ( { current: host } ), useLayoutEffect: ( fn ) => effects.push( fn ) },
		'@wordpress/i18n': { __: ( text ) => text },
		'./screens/layout-picker': 'LayoutPicker', './style.scss': {},
		'./admin-shell.mjs': { mountAdminShell: ( node ) => { assert.equal( node, host ); return cleanup; } },
	};
	const exported = {};
	vm.runInNewContext( code, { exports: exported, document: doc,
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props, children } ),
	} );
	const portal = exported.default();
	assert.equal( portal.target, doc.body );
	assert.equal( portal.child.children[ 0 ].type, 'section' );
	assert.equal( portal.child.children[ 0 ].props[ 'aria-labelledby' ], 'rrze-newsletter-start-heading' );
	assert.equal( effects[ 0 ](), cleanup );
	dom.window.close();
} );
