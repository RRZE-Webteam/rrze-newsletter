const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );
const { code } = transformSync( readFileSync( path.join( __dirname, '../../src/newsletter-editor/styling/spacing-inspector.js' ), 'utf8' ), {
	babelrc: false, configFile: false,
	plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement', pragmaFrag: 'Fragment' } ], '@babel/plugin-transform-modules-commonjs' ],
} );

test( 'selected block dimensions show a non-dismissable hint only for managed newsletters', async () => {
	const policy = await import( '../../src/newsletter-editor/styling/managed-spacing.mjs' );
	for ( const [ postType, mode, globalEnabled, expected ] of [
		[ 'newsletter', 'inherit', true, true ], [ 'newsletter', 'inherit', false, false ],
		[ 'newsletter', 'managed', false, true ], [ 'newsletter', 'expert', true, false ],
		[ 'post', 'managed', true, false ],
		[ 'newsletter', 'inherit', '1', true ], [ 'newsletter', 'inherit', '', false ],
		[ 'newsletter', 'expert', '1', false ], [ 'newsletter', 'inherit', '0', false ],
	] ) {
		const exported = {};
		const deps = {
			'@wordpress/block-editor': { InspectorControls: 'InspectorControls' },
			'@wordpress/components': { Notice: 'Notice' },
			'@wordpress/data': { useSelect: ( selector ) => selector( () => ( {
				getCurrentPostType: () => postType,
				getEditedPostAttribute: () => ( { rrze_newsletter_spacing_mode: mode } ),
			} ) ) },
			'@wordpress/i18n': { __: ( text ) => text },
			'./managed-spacing.mjs': policy,
		};
		vm.runInNewContext( code, {
			exports: exported, require: ( name ) => { assert.ok( name in deps ); return deps[ name ]; },
			createElement: ( type, props, ...children ) => ( { type, props, children } ), Fragment: 'Fragment',
			window: { rrze_newsletter_data: { global_managed_spacing: globalEnabled } },
		} );
		const hint = exported.ManagedSpacingHint();
		assert.equal( Boolean( hint ), expected );
		if ( hint ) {
			assert.equal( hint.props.group, 'dimensions' );
			assert.equal( hint.children[ 0 ].props.isDismissible, false );
		}
		const wrapper = exported.withManagedSpacingHint( 'OriginalBlockEdit' );
		assert.equal( wrapper( { isSelected: false } ).children[ 1 ], false );
		assert.equal( wrapper( { isSelected: true } ).children[ 0 ].type, 'OriginalBlockEdit' );
	}
} );
