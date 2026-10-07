const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test, after } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );
const { JSDOM } = require( 'jsdom' );

// Use the real core block registry, serializers and parser in an isolated DOM.
const dom = new JSDOM( '<html><body></body></html>', { url: 'https://example.test', pretendToBeVisual: true } );
for ( const key of [ 'window', 'document', 'navigator', 'HTMLElement', 'Element', 'Node', 'MutationObserver', 'File', 'FileList', 'DOMParser', 'localStorage' ] ) {
	Object.defineProperty( global, key, { value: dom.window[ key ], configurable: true } );
}
global.requestAnimationFrame = dom.window.requestAnimationFrame.bind( dom.window );
global.cancelAnimationFrame = dom.window.cancelAnimationFrame.bind( dom.window );
window.matchMedia = () => ( { matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} } );
const blocks = require( '@wordpress/blocks' );
require( '@wordpress/block-library' ).registerCoreBlocks();
after( () => dom.window.close() );

const folder = path.join( __dirname, '../../src/editor/blocks/post-inserter' );
const definition = require( path.join( folder, 'block.json' ) );
const defaults = Object.fromEntries( Object.entries( definition.attributes ).filter( ( [ , value ] ) => 'default' in value ).map( ( [ key, value ] ) => [ key, value.default ] ) );
const constants = { POST_INSERTER_BLOCK_NAME: definition.name, POST_INSERTER_STORE_NAME: 'rrze-newsletter/post-inserter' };
const post = {
	id: 42, title: { rendered: 'Article title' }, link: 'https://example.test/news?section=research&year=2026',
	excerpt: { rendered: '<p>First second third fourth fifth sixth.</p>' },
	featured_media: 12, featuredImageLargeURL: 'https://example.test/large.jpg', featuredImageMediumURL: 'https://example.test/medium.jpg',
	meta: { rrze_post_subtitle: 'Article subtitle' }, date: '2026-10-07T10:00:00', date_gmt: '2026-10-07T08:00:00',
	rrze_author_info: [ { display_name: 'Author', author_link: 'https://example.test/author' } ],
};

function load( file, dependencies ) {
	const { code } = transformSync( readFileSync( path.join( folder, file ), 'utf8' ), {
		babelrc: false, configFile: false,
		plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
	} );
	const exports = {};
	vm.runInNewContext( code, {
		exports, document, window,
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props: props || {}, children } ),
	} );
	return exports;
}

const utils = load( 'utils.js', {
	lodash: require( 'lodash' ), '@wordpress/blocks': blocks,
	'@wordpress/i18n': { __: ( value ) => value, _x: ( value ) => value },
	'@wordpress/date': { getSettings: () => ( { formats: { date: 'Y-m-d' } } ), dateI18n: () => '2026-10-07' },
	'./consts': constants,
} );
const generate = ( attributes = {}, posts = [ post ] ) => utils.getTemplateBlocks( posts, { ...defaults, ...attributes } );
const flatten = ( list ) => list.flatMap( ( block ) => [ block, ...flatten( block.innerBlocks ) ] );

test( 'article title and read-more button span the image/excerpt row, with selectable image side and heading level', () => {
	for ( const side of [ 'left', 'right' ] ) {
		for ( const level of [ 2, 3, 4, 5, 6 ] ) {
			const result = generate( { featuredImageAlignment: side, headingLevel: level, excerptLength: 3 } );
			assert.deepEqual( Array.from( result, ( block ) => block.name ), [ 'core/heading', 'core/columns', 'core/buttons' ] );
			assert.equal( result[ 0 ].attributes.level, level );
			assert.match( String( result[ 0 ].attributes.content ), /Article title/ );
			const children = result[ 1 ].innerBlocks.map( ( column ) => column.innerBlocks[ 0 ] );
			const firstColumn = result[ 1 ].innerBlocks[ 0 ];
			assert.equal( firstColumn.attributes.className, `rrze-newsletter-post-inserter-${ side === 'left' ? 'image' : 'text' }-left` );
			assert.equal( firstColumn.attributes.style.spacing.padding.left, '0' );
			assert.equal( firstColumn.attributes.style.spacing.margin.left, '0' );
			assert.equal( result[ 1 ].innerBlocks[ 1 ].attributes.className, undefined );
			assert.equal( children[ side === 'left' ? 0 : 1 ].name, 'core/image' );
			assert.equal( String( children[ side === 'left' ? 1 : 0 ].attributes.content ), 'First second third […]' );
			const button = result[ 2 ].innerBlocks[ 0 ];
			assert.equal( button.name, 'core/button' );
			assert.equal( button.attributes.url, post.link );
			assert.equal( String( button.attributes.text ), 'Continue reading…' );
		}
	}
} );

test( 'no image, top image, hidden excerpt and hidden read-more produce complete layouts without empty columns', () => {
	for ( const fixture of [
		[ { displayFeaturedImage: false }, post, [ 'core/heading', 'core/paragraph', 'core/buttons' ] ],
		[ {}, { ...post, featuredImageLargeURL: null, featuredImageMediumURL: null }, [ 'core/heading', 'core/paragraph', 'core/buttons' ] ],
		[ { featuredImageAlignment: 'top' }, post, [ 'core/heading', 'core/image', 'core/paragraph', 'core/buttons' ] ],
		[ { displayPostExcerpt: false }, post, [ 'core/heading', 'core/image', 'core/buttons' ] ],
		[ { displayContinueReading: false }, post, [ 'core/heading', 'core/columns' ] ],
	] ) {
		const [ attributes, article, names ] = fixture;
		assert.deepEqual( Array.from( generate( attributes, [ article ] ), ( block ) => block.name ), names );
	}
	assert.equal( generate( {}, [] ).length, 0 );
} );

test( 'classic read-more links retain text styling and image sizes keep their existing column proportions', () => {
	for ( const [ size, width, url ] of [ [ 'small', '25%', post.featuredImageMediumURL ], [ 'medium', '33.33%', post.featuredImageMediumURL ], [ 'large', '50%', post.featuredImageLargeURL ] ] ) {
		const result = generate( { continueReadingStyle: 'link', featuredImageSize: size, textFontSize: 18, textColor: '#123456' } );
		assert.equal( result[ 1 ].innerBlocks[ 0 ].attributes.width, width );
		assert.equal( result[ 1 ].innerBlocks[ 0 ].innerBlocks[ 0 ].attributes.url, url );
		const link = result.at( -1 );
		assert.equal( link.name, 'core/paragraph' );
		assert.equal( link.attributes.style.color.text, '#123456' );
		assert.equal( link.attributes.style.typography.fontSize, '18px' );
		assert.match( String( link.attributes.content ), /Continue reading…<\/a>/ );
	}
} );

test( 'subtitles remain below the title, metadata stays with the excerpt and article order/source data are preserved', () => {
	const posts = [ post, { ...post, id: 43, title: { rendered: 'Second article' } } ];
	const before = JSON.stringify( posts );
	const result = generate( { displayPostSubtitle: true, displayPostDate: true, displayAuthor: true }, posts );
	assert.deepEqual( Array.from( result, ( block ) => block.name ), [ 'core/heading', 'core/heading', 'core/columns', 'core/buttons', 'core/heading', 'core/heading', 'core/columns', 'core/buttons' ] );
	assert.equal( String( result[ 1 ].attributes.content ), 'Article subtitle' );
	assert.match( String( result[ 4 ].attributes.content ), /Second article/ );
	assert.equal( result[ 2 ].innerBlocks[ 1 ].innerBlocks.length, 3 );
	assert.equal( JSON.stringify( posts ), before );
} );

test( 'generated core blocks survive WordPress serialization and reload and provide backend button/link markup', () => {
	for ( const featuredImageAlignment of [ 'left', 'right', 'top' ] ) {
		for ( const continueReadingStyle of [ 'button', 'link' ] ) {
			const result = generate( { featuredImageAlignment, continueReadingStyle, headingLevel: 2 } );
			const markup = blocks.serialize( result );
			const parsed = blocks.parse( markup );
			assert.ok( flatten( parsed ).every( ( block ) => block.isValid ), markup );
			assert.equal( blocks.serialize( parsed ), markup );
			const backend = result.map( utils.convertBlockSerializationFormat );
			assert.match( backend[ 0 ].innerHTML, /<h2\b/ );
			const footer = backend.at( -1 );
			const content = continueReadingStyle === 'button' ? footer.innerBlocks[ 0 ].innerHTML : footer.innerHTML;
			const document = new JSDOM( content ).window.document;
			assert.equal( document.querySelector( 'a' ).getAttribute( 'href' ), post.link );
			assert.equal( document.querySelector( 'a' ).textContent, 'Continue reading…' );
		}
	}
} );

function editor( attributes = {} ) {
	let registered;
	const edits = [], effects = [], replacements = [];
	const props = {
		attributes: { ...defaults, ...attributes }, postList: [ post ], isLoadingPosts: false,
		setAttributes: ( change ) => edits.push( change ), replaceBlocks: ( ...args ) => replacements.push( args ),
		setHandledPostsIds() {}, removeBlock() {},
	};
	const module = load( 'index.js', {
		lodash: require( 'lodash' ), '@wordpress/blocks': { registerBlockType: ( name, settings ) => { registered = settings; } },
		'@wordpress/i18n': { __: ( value ) => value },
		'@wordpress/data': { withSelect: () => {}, withDispatch: () => {} },
		'@wordpress/compose': { compose: () => ( component ) => component },
		'@wordpress/element': { Fragment: 'Fragment', useMemo: ( callback ) => callback(), useState: ( value ) => [ value, () => {} ], useEffect: ( callback ) => effects.push( callback ) },
		'@wordpress/components': Object.fromEntries( [ 'RangeControl', 'Button', 'ToggleControl', 'FontSizePicker', 'ColorPicker', 'PanelBody', 'MenuItem', 'MenuGroup', 'ToolbarGroup', 'ToolbarButton', 'ToolbarDropdownMenu', 'Notice', 'Icon' ].map( ( name ) => [ name, name ] ) ),
		'@wordpress/block-editor': { InnerBlocks: 'InnerBlocks', InspectorControls: 'InspectorControls', BlockControls: 'BlockControls', HeadingLevelDropdown: 'HeadingLevelDropdown', useBlockProps: ( props ) => props },
		'@wordpress/icons': { pages: 'pages', alignCenter: 'center', alignLeft: 'left', alignRight: 'right' },
		'./style.scss': {}, './deduplication': {}, './block.json': definition, './utils': utils,
		'./query-controls': 'QueryControls', './consts': constants, './posts-preview': 'PostsPreview',
	} );
	module.default();
	const tree = registered.edit( props );
	const visit = ( node ) => node && typeof node === 'object' ? [ node, ...node.children.flat().flatMap( visit ) ] : [];
	return { edits, effects, replacements, nodes: visit( tree ) };
}

test( 'toolbar changes heading level, image side and read-more style; hidden content has no inactive controls', () => {
	const state = editor();
	const heading = state.nodes.find( ( node ) => node.type === 'HeadingLevelDropdown' );
	assert.deepEqual( Array.from( heading.props.options ), [ 2, 3, 4, 5, 6 ] );
	heading.props.onChange( 6 );
	assert.equal( state.edits.at( -1 ).headingLevel, 6 );
	for ( const side of [ 'left', 'right' ] ) {
		state.nodes.find( ( node ) => node.props.label === `Show image on ${ side }` ).props.onClick();
		assert.equal( state.edits.at( -1 ).featuredImageAlignment, side );
	}
	for ( const style of [ 'button', 'link' ] ) {
		const controls = editor( { continueReadingStyle: style } ).nodes.find( ( node ) => node.props.label === 'Continue reading style' ).props.controls;
		assert.equal( controls.find( ( control ) => control.isActive ).title.toLowerCase(), style );
	}
	const choices = state.nodes.find( ( node ) => node.props.label === 'Continue reading style' ).props.controls;
	choices[ 1 ].onClick();
	assert.equal( state.edits.at( -1 ).continueReadingStyle, 'link' );
	choices[ 0 ].onClick();
	assert.equal( state.edits.at( -1 ).continueReadingStyle, 'button' );
	const hidden = editor( { displayFeaturedImage: false, displayContinueReading: false } );
	assert.ok( ! hidden.nodes.some( ( node ) => node.props.label === 'Continue reading style' || node.props.label === 'Show image on left' ) );
} );

test( 'preview, backend serialization and insertion use the same generated layout', () => {
	const state = editor( { headingLevel: 5, featuredImageAlignment: 'right', continueReadingStyle: 'link' } );
	const preview = state.nodes.find( ( node ) => node.type === 'PostsPreview' ).props.blocks;
	state.effects.forEach( ( effect ) => effect() );
	assert.equal( JSON.stringify( state.edits.find( ( edit ) => edit.innerBlocksToInsert ).innerBlocksToInsert ), JSON.stringify( preview.map( utils.convertBlockSerializationFormat ) ) );
	state.nodes.find( ( node ) => node.type === 'Button' && node.children[ 0 ] === 'Insert posts' ).props.onClick();
	assert.equal( state.edits.at( -1 ).areBlocksInserted, true );
	const inserted = editor( { areBlocksInserted: true, headingLevel: 5, featuredImageAlignment: 'right', continueReadingStyle: 'link' } );
	inserted.effects.forEach( ( effect ) => effect() );
	assert.equal( inserted.replacements.length, 1 );
	assert.equal( blocks.serialize( inserted.replacements[ 0 ][ 0 ] ), blocks.serialize( preview ) );
	assert.deepEqual( Array.from( inserted.replacements[ 0 ][ 1 ] ), [ 42 ] );
} );
