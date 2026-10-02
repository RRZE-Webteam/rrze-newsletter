const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { transformSync } = require( '@babel/core' );

test( 'design controls appear only in Newsletter Styles while document panels and canvas styling remain available', () => {
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
	const allNodes = ( node ) => node && typeof node === 'object' ? [ node, ...node.children.flatMap( allNodes ) ] : [];
	const nodes = allNodes( tree ).filter( ( node ) => node && typeof node === 'object' );
	assert.equal( nodes.filter( ( node ) => node.type === 'Styling' ).length, 1, 'Design controls must have a single home' );
	assert.ok( ! tree.children.some( ( node ) => node.type === 'Styling' ) );
	const sidebar = tree.children.find( ( node ) => node.type === 'PluginSidebar' );
	assert.equal( sidebar.props.title, 'Newsletter Styles' );
	assert.equal( sidebar.children[ 0 ].type, 'Styling' );
	const shortcut = tree.children.find( ( node ) => node.type === 'PluginSidebarMoreMenuItem' );
	assert.equal( shortcut.props.target, sidebar.props.name );
	const panels = tree.children.filter( ( node ) => node.type === 'PluginDocumentSettingPanel' );
	assert.deepEqual( panels.map( ( panel ) => panel.props.title ), [ 'Email configuration', 'Sending rules', 'Testing', 'Layout' ] );
	assert.deepEqual( panels.map( ( panel ) => panel.children.map( ( child ) => child.type ) ), [ [ 'Sidebar' ], [ 'AdvancedSettings' ], [ 'Testing' ], [ 'Layout' ] ] );
	assert.equal( panels[ 0 ].props.name, 'newsletters-settings-panel', 'Keep the existing configuration panel preference' );
	assert.equal( panels[ 1 ].props.name, 'newsletters-sending-rules-panel', 'A new panel ID starts collapsed under WordPress panel preferences' );
	assert.equal( new Set( panels.map( ( panel ) => panel.props.name ) ).size, 4 );
	assert.equal( nodes.filter( ( node ) => node.type === 'PluginDocumentSettingPanel' ).length, 4, 'The panels must be siblings, not nested' );
	assert.equal( tree.children.filter( ( node ) => node.type === 'ApplyStyling' ).length, 1, 'Canvas styling must remain mounted even with the Styles sidebar closed' );
} );
