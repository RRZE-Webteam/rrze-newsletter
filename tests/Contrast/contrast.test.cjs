const assert = require( 'node:assert/strict' );
const { test } = require( 'node:test' );
const { JSDOM } = require( 'jsdom' );
const guard = import( '../../src/editor/contrast/contrast.mjs' );

// This installed jsdom lacks parts of computed CSS. Supply controlled CSS
// boundaries for inheritance, named colors and gradients; native CSS is separate.
function styleView( window ) {
	const rgb = ( value ) => ( { black: 'rgb(0, 0, 0)', white: 'rgb(255, 255, 255)' } )[ value ] || value;
	return { getComputedStyle( element ) {
		const style = window.getComputedStyle( element );
		let color = style.color;
		for ( let parent = element.parentElement; ! color && parent; parent = parent.parentElement ) {
			color = window.getComputedStyle( parent ).color;
		}
		return {
			color: rgb( color ) || 'rgb(0, 0, 0)',
			backgroundColor: rgb( style.backgroundColor ) || 'rgba(0, 0, 0, 0)',
			backgroundImage: style.backgroundImage || ( element.getAttribute( 'style' )?.includes( 'linear-gradient' ) ? 'linear-gradient(black,white)' : '' ),
			opacity: style.opacity,
			filter: style.filter,
			mixBlendMode: style.mixBlendMode,
			visibility: style.visibility,
			display: style.display,
		};
	} };
}

async function protect( content, head = '' ) {
	const dom = new JSDOM( `<!doctype html><html><head>${ head }</head><body style="background:#ffffff;color:#000000">${ content }</body></html>` );
	const { protectDocument } = await guard;
	const result = protectDocument( dom.window.document, styleView( dom.window ) );
	return { ...result, dom, doc: dom.window.document };
}

test( 'WCAG luminance has known reference values and symmetric contrast', async () => {
	const { contrastRatio } = await guard;
	assert.equal( contrastRatio( [ 0, 0, 0 ], [ 255, 255, 255 ] ), 21 );
	assert.equal( contrastRatio( [ 12, 34, 56 ], [ 12, 34, 56 ] ), 1 );
	assert.ok( Math.abs( contrastRatio( [ 119, 119, 119 ], [ 255, 255, 255 ] ) - 4.478089453577214 ) < 1e-12 );
	assert.equal( contrastRatio( [ 100, 50, 0 ], [ 200, 230, 255 ] ), contrastRatio( [ 200, 230, 255 ], [ 100, 50, 0 ] ) );
} );

test( 'thresholds are not rounded up and readable colors are left alone', async () => {
	const { readableColor } = await guard;
	assert.equal( readableColor( [ 119, 119, 119 ], [ 255, 255, 255 ] ), '#000000' );
	assert.equal( readableColor( [ 118, 118, 118 ], [ 255, 255, 255 ] ), null );
	assert.equal( readableColor( [ 0, 0, 0 ], [ 0, 0, 0 ] ), '#ffffff' );
	assert.equal( readableColor( [ 255, 255, 255 ], [ 255, 255, 255 ] ), '#000000' );
} );

test( 'only supported computed sRGB values are accepted', async () => {
	const { parseColor } = await guard;
	assert.deepEqual( parseColor( 'rgb(0, 128, 255)' ), [ 0, 128, 255, 1 ] );
	assert.deepEqual( parseColor( 'rgba(0, 0, 0, 0.5)' ), [ 0, 0, 0, 0.5 ] );
	assert.deepEqual( parseColor( 'transparent' ), [ 0, 0, 0, 0 ] );
	for ( const value of [ 'rgb(999, 0, 0)', 'rgba(0, 0, 0, 2)', 'var(--unknown)', 'color(display-p3 1 0 0)', '', null ] ) {
		assert.equal( parseColor( value ), null );
	}
} );

test( 'dark inherited backgrounds correct paragraphs, headings, links and captions', async () => {
	const { corrected, skipped, doc, dom } = await protect( '<section style="background:#04316a"><h2>Heading</h2><p>Text <a href="https://example.test/">Link</a></p><figure><figcaption>Caption</figcaption></figure><ul><li>Item</li></ul></section>', 'a{color:#000000}' );
	assert.equal( corrected, 5 );
	assert.equal( skipped, 0 );
	for ( const element of doc.querySelectorAll( 'h2,p,a,figcaption,li' ) ) {
		assert.equal( element.style.color, 'rgb(255, 255, 255)' );
	}
	assert.equal( doc.querySelector( 'a' ).href, 'https://example.test/' );
	dom.window.close();
} );

test( 'a corrected parent cannot break a readable child on a different background', async () => {
	const { corrected, doc, dom } = await protect( '<p style="background:black">Dark <span style="background:white">Light</span></p>' );
	assert.equal( corrected, 1 );
	assert.equal( doc.querySelector( 'p' ).style.color, 'rgb(255, 255, 255)' );
	assert.equal( doc.querySelector( 'span' ).style.color, 'rgb(0, 0, 0)' );
	dom.window.close();
} );

test( 'accessible colors and all markup remain untouched when no correction is needed', async () => {
	const { corrected, skipped, doc, dom } = await protect( '<p style="color:#04316a">Brand color</p>' );
	assert.equal( corrected, 0 );
	assert.equal( skipped, 0 );
	assert.equal( doc.querySelector( 'p' ).getAttribute( 'style' ), 'color:#04316a' );
	dom.window.close();
} );

for ( const background of [ 'background-image:linear-gradient(black,white)', 'background-image:url(https://example.test/never-fetch.png)', 'background:rgba(0,0,0,0.5)', 'opacity:0.5', 'filter:invert(1)', 'mix-blend-mode:multiply' ] ) {
	test( `uncertain background is reported without guessing: ${ background }`, async () => {
		const { corrected, skipped, doc, dom } = await protect( `<section style="${ background }"><p style="color:#eeeeee">Manual review</p></section>` );
		assert.equal( corrected, 0 );
		assert.equal( skipped, 1 );
		assert.equal( doc.querySelector( 'p' ).getAttribute( 'style' ), 'color:#eeeeee' );
		dom.window.close();
	} );
}

test( 'an opaque child background can be checked even over an image', async () => {
	const { corrected, skipped, dom } = await protect( '<section style="background-image:url(https://example.test/no.png)"><p style="background:black;color:black">Text</p></section>' );
	assert.equal( corrected, 1 );
	assert.equal( skipped, 0 );
	dom.window.close();
} );

test( 'hidden text and non-HTML content are not modified', async () => {
	const { corrected, skipped, dom } = await protect( '<div style="display:none"><p style="color:white">Preview text</p></div><svg><text>Logo</text></svg>' );
	assert.equal( corrected, 0 );
	assert.equal( skipped, 0 );
	dom.window.close();
} );

test( 'protection is idempotent and preserves Outlook comments and placeholders', async () => {
	const { protectDocument } = await guard;
	const { corrected, doc, dom } = await protect( '<!--[if mso]><table><tr><td><![endif]--><p style="color:white">{{=FIRST_NAME}}</p><!--[if mso]></td></tr></table><![endif]-->' );
	assert.equal( corrected, 1 );
	assert.equal( protectDocument( doc, styleView( dom.window ) ).corrected, 0 );
	assert.ok( doc.body.innerHTML.includes( '<!--[if mso]><table><tr><td><![endif]-->' ) );
	assert.ok( doc.body.textContent.includes( '{{=FIRST_NAME}}' ) );
	dom.window.close();
} );

test( 'disabled safeguard returns byte-for-byte original HTML without a browser', async () => {
	const { protectEmailHtml } = await guard;
	const html = '<html>Untouched</html>';
	assert.deepEqual( await protectEmailHtml( html, false ), { html, corrected: 0, skipped: 0 } );
} );

test( 'real MJML output: dark button and caption are corrected, readable sibling survives', async () => {
	global.window = {};
	const mjml2html = require( 'mjml-browser' );
	const { html, errors } = mjml2html( '<mjml><mj-body background-color="#ffffff"><mj-section><mj-column><mj-button color="#000000" background-color="#04316a" href="https://example.test/">Button</mj-button><mj-text color="#000000" container-background-color="#04316a">Caption</mj-text><mj-text color="#04316a">Readable</mj-text></mj-column></mj-section></mj-body></mjml>', { keepComments: false } );
	assert.deepEqual( errors, [] );
	const dom = new JSDOM( html );
	const { protectDocument, contrastRatio, parseColor } = await guard;
	const link = dom.window.document.querySelector( 'a' );
	assert.ok( contrastRatio( parseColor( dom.window.getComputedStyle( link ).color ), [ 4, 49, 106 ] ) < 4.5 );
	const result = protectDocument( dom.window.document, styleView( dom.window ) );
	assert.equal( result.corrected, 2 );
	assert.equal( link.style.color, 'rgb(255, 255, 255)' );
	assert.equal( link.getAttribute( 'href' ), 'https://example.test/' );
	dom.window.close();
} );

test( 'correcting dark text leaves unrelated readable elements completely unchanged', async () => {
	const { doc, dom, corrected } = await protect( '<p style="background:#04316a;color:black">Correct me</p><p id="readable" style="color:#04316a;background:white;border:1px solid red">Keep me</p>' );
	assert.equal( corrected, 1 );
	assert.equal( doc.getElementById( 'readable' ).getAttribute( 'style' ), 'color:#04316a;background:white;border:1px solid red' );
	dom.window.close();
} );

// Full rendering pipeline with synthetic blocks; no WordPress database/network.
const groupFixtures = JSON.parse( require( 'node:child_process' ).execFileSync( 'php', [ require( 'node:path' ).join( __dirname, '../MJML/render-group-backgrounds.php' ) ], { encoding: 'utf8', timeout: 10000 } ) );
for ( const [ name, mjml ] of Object.entries( groupFixtures ) ) {
	test( `${ name }: group backgrounds survive compilation and contrast protection changes only text colors`, async () => {
		global.window = {};
		const { html, errors } = require( 'mjml-browser' )( mjml );
		// Existing list rendering emits internal metadata; MJML ignores it. Keep
		// that separate from this regression and reject any new validation errors.
		assert.deepEqual( errors.filter( ( error ) => error.tagName !== 'mj-text' || error.message !== 'Attributes postId, link, textColor are illegal' ), [] );
		const dom = new JSDOM( html );
		const doc = dom.window.document;
		const view = styleView( dom.window );
		const background = ( node ) => {
			for ( let element = node; element; element = element.parentElement ) {
				const color = view.getComputedStyle( element ).backgroundColor;
				if ( color !== 'rgba(0, 0, 0, 0)' ) { return color; }
			}
		};
		const expected = { dark: 'rgb(4, 49, 106)', light: 'rgb(255, 255, 255)', readable: 'rgb(4, 49, 106)', deep: 'rgb(4, 49, 106)', link: 'rgb(4, 49, 106)', sibling: 'rgb(255, 255, 255)' };
		for ( const [ id, color ] of Object.entries( expected ) ) { assert.equal( background( doc.getElementById( id ) ), color, id ); }
		const original = doc.documentElement.cloneNode( true );
		const { protectDocument } = await guard;
		const result = protectDocument( doc, view );
		assert.equal( result.corrected, 3 );
		for ( const id of [ 'dark', 'deep', 'link' ] ) { assert.equal( doc.getElementById( id ).style.color, 'rgb(255, 255, 255)', id ); }
		for ( const [ id, color ] of Object.entries( expected ) ) { assert.equal( background( doc.getElementById( id ) ), color, id ); }
		const button = doc.querySelector( 'a[href="https://example.test/button"]' );
		assert.equal( background( button ), 'rgb(255, 204, 0)' );
		// Compare the whole document, ignoring only color declarations on elements
		// that received a correction (or required inherited-color preservation).
		const after = [ doc.documentElement, ...doc.documentElement.querySelectorAll( '*' ) ];
		const before = [ original, ...original.querySelectorAll( '*' ) ];
		assert.equal( after.length, before.length );
		for ( let i = 0; i < after.length; i++ ) {
			if ( after[ i ].getAttribute( 'style' ) === before[ i ].getAttribute( 'style' ) ) { continue; }
			const clone = after[ i ].cloneNode( false );
			clone.style.removeProperty( 'color' );
			const initial = before[ i ].cloneNode( false );
			initial.style.removeProperty( 'color' );
			assert.equal( clone.style.cssText, initial.style.cssText, 'No other CSS property may change' );
			if ( before[ i ].hasAttribute( 'style' ) ) { after[ i ].setAttribute( 'style', before[ i ].getAttribute( 'style' ) ); }
			else { after[ i ].removeAttribute( 'style' ); }
		}
		assert.equal( doc.documentElement.outerHTML, original.outerHTML, 'All markup, background declarations and Outlook comments remain intact' );
		dom.window.close();
	} );
}
