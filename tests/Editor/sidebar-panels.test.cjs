const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { transformSync } = require( '@babel/core' );

test( 'newsletter registers document styling panels directly, without an enclosing collapsible panel', () => {
	const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/newsletter-editor/index.js' ), 'utf8' ), {
		babelrc: false, configFile: false,
		plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
	} );
	let render;
	const dependencies = {
		'@wordpress/i18n': { __: ( text ) => text },
		'@wordpress/data': { withSelect: () => ( component ) => component, withDispatch: () => ( component ) => component },
		'@wordpress/compose': { compose: () => ( component ) => component },
		'@wordpress/element': { Fragment: 'Fragment', useState: () => [ false, () => {} ] },
		'@wordpress/editor': Object.fromEntries( [ 'PluginDocumentSettingPanel', 'PluginSidebar', 'PluginSidebarMoreMenuItem' ].map( ( type ) => [ type, type ] ) ),
		'@wordpress/plugins': { registerPlugin: ( name, settings ) => { render = settings.render; } },
		'@wordpress/icons': { styles: 'styles' },
		'../components/init-modal': 'InitModal', './layout/': 'Layout', './sidebar/': 'Sidebar', './testing/': 'Testing',
		'./styling/': { Styling: 'Styling', ApplyStyling: 'ApplyStyling' },
		'./advanced': { AdvancedSettings: 'AdvancedSettings' }, './editor/': () => {},
	};
	vm.runInNewContext( code, {
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props: props || {}, children } ), window: {},
	} );
	const tree = render( { layoutId: 1 } );
	const direct = tree.children.find( ( node ) => node.type === 'Styling' );
	assert.ok( direct, 'Document styling must not be nested in another panel' );
	assert.equal( direct.props.PanelComponent, 'PluginDocumentSettingPanel' );
	const sidebar = tree.children.find( ( node ) => node.type === 'PluginSidebar' );
	assert.equal( sidebar.children[ 0 ].type, 'Styling' );
	assert.equal( sidebar.children[ 0 ].props.PanelComponent, undefined );
} );
