const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );
const { test } = require( 'node:test' );
const { transformSync } = require( '@babel/core' );
const { JSDOM } = require( 'jsdom' );
const sass = require( 'sass' );
const root = path.join( __dirname, '../..' );

function loadComponent( file ) {
	const { code } = transformSync( readFileSync( path.join( root, file ), 'utf8' ), {
		babelrc: false, configFile: false,
		plugins: [ [ '@babel/plugin-transform-react-jsx', { pragma: 'createElement' } ], '@babel/plugin-transform-modules-commonjs' ],
	} );
	const dependencies = {
		'@wordpress/i18n': { __: ( value ) => value },
		'@wordpress/compose': { compose: () => ( component ) => component },
		'@wordpress/data': { withSelect: () => {}, withDispatch: () => {} },
		'@wordpress/element': { Fragment: 'Fragment' },
		'@wordpress/components': Object.fromEntries( [ 'Button', 'TextControl', 'TextareaControl', 'ToggleControl', 'SelectControl' ].map( ( type ) => [ type, type ] ) ),
		'classnames': require( 'classnames' ),
		'../utils': { hasValidEmail: () => true },
		'../../service-providers': { getServiceProvider: () => ( { ProviderSidebar: 'ProviderSidebar' } ) },
		'../../components/with-api-handler': () => {},
		'./repeat-weekly': 'RepeatWeekly', './repeat-monthly': 'RepeatMonthly', './style.scss': {},
	};
	const exported = {};
	vm.runInNewContext( code, {
		exports: exported,
		require: ( name ) => { assert.ok( name in dependencies, name ); return dependencies[ name ]; },
		createElement: ( type, props, ...children ) => ( { type, props: props || {}, children } ),
	} );
	return exported;
}
const descendants = ( node ) => node && typeof node === 'object' ? [ node, ...node.children.flatMap( descendants ) ] : [];

test( 'email configuration groups native controls and retains values, edits and update actions', () => {
	const Sidebar = loadComponent( 'src/newsletter-editor/sidebar/index.js' ).default;
	const edits = [], requests = [];
	const props = {
		inFlight: false, errors: {}, title: 'Subject', senderName: 'Team', senderEmail: 'sender@example.test',
		recipientEmail: 'list@example.test', replytoEmail: 'reply@example.test', previewText: 'Preview', postId: 42,
		editPost: ( value ) => edits.push( value ), apiFetchWithErrorHandling: ( value ) => requests.push( value ),
	};
	const tree = Sidebar( props );
	assert.equal( tree.props.className, 'rrze-newsletter__configuration' );
	const provider = tree.children[ 0 ];
	const subject = provider.props.renderSubject();
	assert.equal( subject.type, 'TextControl' );
	assert.equal( subject.props.label, 'Subject' );
	assert.ok( ! subject.props.hideLabelFromVision );
	subject.props.onChange( 'New subject' );
	assert.equal( edits.at( -1 ).title, 'New subject' );
	const recipient = provider.props.renderTo();
	assert.equal( recipient.props.value, props.recipientEmail );
	recipient.props.onChange( 'new-list@example.test' );
	assert.equal( edits.at( -1 ).meta.rrze_newsletter_to_email, 'new-list@example.test' );
	const sections = provider.props.renderFrom().children;
	assert.equal( sections.length, 2 );
	sections.forEach( ( section ) => assert.equal( section.props.className, 'rrze-newsletter__configuration-section' ) );
	const controls = sections.flatMap( descendants );
	for ( const [ label, value, key ] of [
		[ 'Name', 'Team', 'rrze_newsletter_from_name' ], [ 'Email', props.senderEmail, 'rrze_newsletter_from_email' ],
		[ 'ReplyTo', props.replytoEmail, 'rrze_newsletter_replyto' ], [ 'Preview text', 'Preview', 'rrze_newsletter_preview_text' ],
	] ) {
		const control = controls.find( ( node ) => node.props.label === label );
		assert.equal( control.props.value, value );
		control.props.onChange( 'Changed' );
		assert.equal( edits.at( -1 ).meta[ key ], 'Changed' );
	}
	assert.equal( requests.length, 0, 'Changing whitespace must not trigger requests' );
	controls.filter( ( node ) => node.type === 'Button' ).forEach( ( button ) => button.props.onClick() );
	assert.deepEqual( requests.map( ( request ) => request.data.key ), [ 'rrze_newsletter_from_name', 'rrze_newsletter_from_email', 'rrze_newsletter_replyto', 'rrze_newsletter_preview_text' ] );
	assert.ok( requests.every( ( request ) => request.method === 'POST' && request.path === '/rrze-newsletter/v1/post-meta/42' ) );
	const busy = Sidebar( { ...props, inFlight: true } ).children[ 0 ];
	assert.ok( descendants( busy.props.renderFrom() ).filter( ( node ) => [ 'TextControl', 'TextareaControl', 'Button' ].includes( node.type ) ).every( ( control ) => control.props.disabled ) );
} );

test( 'sending rules group conditional fields without altering toggles or recurrence controls', () => {
	const { AdvancedSettings } = loadComponent( 'src/newsletter-editor/advanced/index.js' );
	for ( const enabled of [ false, true ] ) {
		for ( const recurring of [ false, true ] ) {
			for ( const repeat of [ 'DAILY', 'WEEKLY', 'MONTHLY' ] ) {
				const changes = [];
				const tree = AdvancedSettings( {
					meta: { rrze_newsletter_has_conditionals: enabled, rrze_newsletter_is_recurring: recurring, rrze_newsletter_recurrence_repeat: repeat, rrze_newsletter_recurrence_monthly: 'first' },
					...Object.fromEntries( [ 'updateHasConditionals', 'updateRssNoItems', 'updateIcsNoItems', 'updateIsRecurring', 'updateRecurrenceRepeat', 'updateRecurrenceMonthly' ].map( ( name ) => [ name, ( value ) => changes.push( [ name, value ] ) ] ) ),
				} );
				assert.equal( tree.props.className, 'rrze-newsletter__sending-rules' );
				const nodes = descendants( tree );
				const toggles = nodes.filter( ( node ) => node.type === 'ToggleControl' );
				assert.equal( toggles.length, enabled ? 4 : 1 );
				toggles[ 0 ].props.onChange( true );
				assert.deepEqual( changes[ 0 ], [ 'updateHasConditionals', true ] );
				const select = nodes.find( ( node ) => node.type === 'SelectControl' );
				assert.equal( Boolean( select ), enabled && recurring );
				if ( select ) {
					assert.equal( select.props.label, 'Repeat' );
					assert.equal( select.props.value, repeat );
					select.props.onChange( 'WEEKLY' );
					assert.deepEqual( changes.at( -1 ), [ 'updateRecurrenceRepeat', 'WEEKLY' ] );
				}
				assert.equal( nodes.some( ( node ) => node.type === 'RepeatWeekly' ), enabled && recurring && repeat === 'WEEKLY' );
				assert.equal( nodes.some( ( node ) => node.type === 'RepeatMonthly' ), enabled && recurring && repeat === 'MONTHLY' );
			}
		}
	}
} );

test( 'panel CSS supplies consistent gaps and scoped separators without restyling other controls', () => {
	const css = [ 'sidebar', 'advanced' ].map( ( directory ) => sass.compileString(
		readFileSync( path.join( root, `src/newsletter-editor/${ directory }/style.scss` ), 'utf8' ).replaceAll( '"~', '"' ),
		{ loadPaths: [ path.join( root, 'node_modules' ) ], logger: sass.Logger.silent }
	).css ).join( '\n' );
	const dom = new JSDOM( `<style>.components-base-control {margin-bottom:24px}</style><style>${ css }</style>
		<div id="outside" class="components-base-control"></div>
		<div class="rrze-newsletter__configuration"><div class="components-base-control"></div><div class="rrze-newsletter__configuration-section"></div></div>
		<div class="rrze-newsletter__sending-rules"><div class="components-base-control"></div><div class="rrze-newsletter__sending-conditions"><div class="rrze-newsletter__sending-recurrence"><div class="rrze-newsletter__sending-schedule"></div></div></div></div>` );
	try {
		const cssFor = ( selector ) => dom.window.getComputedStyle( dom.window.document.querySelector( selector ) );
		for ( const name of [ 'configuration', 'configuration-section', 'sending-rules', 'sending-conditions', 'sending-recurrence', 'sending-schedule' ] ) {
			assert.equal( cssFor( `.rrze-newsletter__${ name }` ).gap, '16px' );
		}
		for ( const name of [ 'configuration', 'sending-rules' ] ) {
			assert.equal( cssFor( `.rrze-newsletter__${ name } .components-base-control` ).marginBottom, '0px' );
		}
		assert.equal( cssFor( '#outside' ).marginBottom, '24px' );
		assert.equal( cssFor( '.rrze-newsletter__configuration-section' ).paddingTop, '16px' );
		assert.equal( cssFor( '.rrze-newsletter__sending-recurrence' ).borderTopWidth, '1px' );
		assert.ok( [ '', '0px' ].includes( cssFor( '.rrze-newsletter__sending-rules' ).borderTopWidth ) );
	} finally { dom.window.close(); }
} );
