const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test, before } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );
let spacingPolicy;
before( async () => { spacingPolicy = await import( '../../src/newsletter-editor/styling/managed-spacing.mjs' ); } );

// Execute the real inspector component; mock only WordPress stores and UI primitives.
const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/newsletter-editor/styling/index.js' ), 'utf8' ), {
	babelrc: false, configFile: false,
	plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
} );

function render( spacingMode, globalManaged = false ) {
	const edits = [];
	const effects = [];
	const mounts = [];
	const exported = {};
	const dependencies = {
		'@wordpress/compose': { compose: ( hocs ) => ( component ) => hocs.reduceRight( ( result, hoc ) => hoc( result ), component ), useInstanceId: () => 1 },
		'@wordpress/components': Object.fromEntries( [ 'ColorPicker', 'BaseControl', 'Panel', 'PanelBody', 'PanelRow', 'SelectControl', 'ToggleControl', 'Notice' ].map( ( name ) => [ name, name ] ) ),
		'@wordpress/i18n': { __: ( value ) => value },
		'@wordpress/data': {
			withSelect: ( selector ) => ( component ) => ( props ) => component( { ...props, ...selector( () => ( { getEditedPostAttribute: () => ( { rrze_newsletter_spacing_mode: spacingMode } ) } ) ) } ),
			withDispatch: ( selector ) => ( component ) => ( props ) => component( { ...props, ...selector( () => ( { editPost: ( edit ) => edits.push( edit ) } ) ) } ),
		},
		'@wordpress/element': { useEffect: ( callback, dependencies ) => effects.push( { callback, dependencies } ) },
		'../../components/select-control-with-optgroup/': 'SelectControlWithOptGroup',
		'./style.scss': {},
		'./managed-spacing.mjs': {
			isManagedSpacing: ( ...args ) => spacingPolicy.isManagedSpacing( ...args ),
			mountManagedSpacing: () => { mounts.push( 'mount' ); return () => mounts.push( 'cleanup' ); },
		},
	};
	vm.runInNewContext( code, {
		exports: exported,
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props: props || {}, children } ),
		document: { implementation: { createHTMLDocument: () => ( {} ) } },
		window: { rrze_newsletter_data: { global_managed_spacing: globalManaged } },
	} );
	const nodes = [];
	function visit( node ) {
		if ( ! node || typeof node !== 'object' ) { return; }
		nodes.push( node );
		node.children.forEach( visit );
	}
	visit( exported.Styling( {} ) );
	return { nodes, edits, effects, mounts, apply: () => exported.ApplyStyling( {} ) };
}

test( 'new newsletters inherit global spacing and offer explicit on/off overrides', () => {
	const { nodes, edits } = render();
	const control = nodes.find( ( node ) => node.type === 'SelectControl' );
	assert.equal( control.props.value, 'inherit' );
	assert.deepEqual( Array.from( control.props.options, ( option ) => option.value ), [ 'inherit', 'managed', 'expert' ] );
	for ( const mode of [ 'managed', 'expert', 'inherit' ] ) {
		control.props.onChange( mode );
		assert.equal( edits.at( -1 ).meta.rrze_newsletter_spacing_mode, mode );
		assert.deepEqual( Object.keys( edits.at( -1 ).meta ), [ 'rrze_newsletter_spacing_mode' ] );
	}
} );

for ( const [ globalManaged, globalEnabled ] of [ [ false, false ], [ true, true ], [ '', false ], [ '1', true ], [ '0', false ], [ 'false', false ] ] ) {
	for ( const mode of [ 'inherit', 'managed', 'expert' ] ) {
		test( `${ mode }, global managed=${ globalManaged }: inspector describes mode and warns only for expert override`, () => {
			const { nodes } = render( mode, globalManaged );
			const control = nodes.find( ( node ) => node.type === 'SelectControl' );
			assert.equal( control.props.value, mode );
			assert.match( control.props.help, globalEnabled ? /Global setting: managed spacing enabled/ : /Global setting: manual spacing/ );
			const warnings = nodes.filter( ( node ) => node.type === 'Notice' );
			assert.equal( warnings.length, mode === 'expert' ? 1 : 0 );
			if ( warnings.length ) {
				assert.equal( warnings[ 0 ].props.status, 'warning' );
				assert.equal( warnings[ 0 ].props.isDismissible, false );
			}
			assert.equal( nodes.some( ( node ) => node.type === 'p' && node.children.some( ( text ) => typeof text === 'string' && text.includes( 'consistent content gaps' ) ) ), mode === 'managed' || ( mode === 'inherit' && globalEnabled ) );
		} );
		test( `${ mode }, global managed=${ globalManaged }: ApplyStyling mounts preview only when enabled and returns cleanup`, () => {
			const state = render( mode, globalManaged );
			state.apply();
			const enabled = mode === 'managed' || ( mode === 'inherit' && globalEnabled );
			assert.equal( state.effects[ 0 ].dependencies[ 0 ], enabled );
			const cleanup = state.effects[ 0 ].callback();
			assert.deepEqual( state.mounts, enabled ? [ 'mount' ] : [] );
			cleanup?.();
			assert.deepEqual( state.mounts, enabled ? [ 'mount', 'cleanup' ] : [] );
		} );
	}
}
