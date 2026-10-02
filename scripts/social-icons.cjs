// Development-only asset pipeline. Never bootstrap WordPress or fetch remote icons.
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const assert = require( 'node:assert/strict' );
const { Resvg } = require( '@resvg/resvg-js' );
const root = path.join( __dirname, '..' );
const directory = path.join( root, 'assets/social-links' );
const registryPath = path.join( directory, 'services.json' );
const sourcePath = path.join( directory, 'sources.json' );
const args = process.argv.slice( 2 );
const mode = args[ 0 ];
assert.ok( [ 'import', 'build', 'check', 'preview' ].includes( mode ), 'Usage: social-icons.cjs import|build|check [WordPress root] or preview output.png' );
let services = fs.existsSync( registryPath ) ? JSON.parse( fs.readFileSync( registryPath ) ) : {};
let sources = fs.existsSync( sourcePath ) ? JSON.parse( fs.readFileSync( sourcePath ) ) : {};

function readWordPress( wpRoot ) {
	assert.ok( wpRoot, 'Supply the root of a local WordPress checkout.' );
	const php = fs.readFileSync( path.join( wpRoot, 'wp-includes/blocks/social-link.php' ), 'utf8' );
	const css = fs.readFileSync( path.join( wpRoot, 'wp-includes/blocks/social-links/style.css' ), 'utf8' );
	const icons = {};
	for ( const match of php.matchAll( /'([a-z0-9-]+)'\s*=> array\(\s*'name' => _x\( '([^']+)'[^\n]+\n\s*'icon' => '([^']+)'/g ) ) {
		const [ , service, name, svg ] = match;
		const rule = css.match( new RegExp( ':where\\(\\.wp-block-social-links:not\\(\\.is-style-logos-only\\)\\) \\.wp-social-link-' + service + ' \\{([^}]+)' ) )?.[ 1 ] || '';
		const color = rule.match( /background-color:\s*(#[a-f0-9]+);/i )?.[ 1 ] || '#f0f0f0';
		const foreground = rule.match( /\n\s*color:\s*(#[a-f0-9]+);/i )?.[ 1 ] || '#444';
		icons[ service ] = { name, color, defaultIcon: [ '#fff', '#ffffff' ].includes( foreground ) ? 'white' : 'black', svg };
	}
	const count = [ ...php.matchAll( /^\t\t'[a-z0-9-]+'\s*=> array\(/gm ) ].length;
	assert.ok( count > 0 );
	assert.equal( Object.keys( icons ).length, count, 'WordPress source format changed; update the importer.' );
	return icons;
}

if ( mode === 'import' ) {
	const icons = readWordPress( args[ 1 ] );
	// Preserve the original thirteen assets and brand colors during first import.
	const legacyPhp = fs.readFileSync( path.join( root, 'includes/MJML/SocialIcons.php' ), 'utf8' );
	const legacy = Object.fromEntries( [ ...legacyPhp.matchAll( /^        '([a-z0-9-]+)' => '(#[a-f0-9]+)'/gm ) ].map( ( m ) => [ m[ 1 ], m[ 2 ] ] ) );
	for ( const [ service, { svg, ...details } ] of Object.entries( icons ) ) {
		if ( ! services[ service ] ) {
			services[ service ] = { ...details, color: legacy[ service ] || details.color };
			if ( legacy[ service ] && service !== 'feed' ) { services[ service ].defaultIcon = 'white'; }
		}
		if ( ! legacy[ service ] && ( sources[ service ] || ! fs.existsSync( path.join( directory, `white-${ service }.png` ) ) ) ) {
			assert.ok( svg.startsWith( '<svg ' ) && ! /<(script|image|foreignObject)\b|\b(?:href|onload)=/i.test( svg ), 'Expected a self-contained upstream vector icon' );
			sources[ service ] = svg;
		}
	}
	fs.writeFileSync( registryPath, JSON.stringify( services, null, 2 ) + '\n' );
	fs.writeFileSync( sourcePath, JSON.stringify( sources, null, 2 ) + '\n' );
}

if ( mode === 'build' || mode === 'import' ) {
	for ( const [ service, svg ] of Object.entries( sources ) ) {
		assert.match( service, /^[a-z0-9-]+$/ );
		for ( const variant of [ 'black', 'white' ] ) {
			const image = new Resvg( svg.replace( '<svg ', `<svg fill="${ variant }" color="${ variant }" ` ), { fitTo: { mode: 'width', value: 128 } } ).render();
			fs.writeFileSync( path.join( directory, `${ variant }-${ service }.png` ), image.asPng() );
		}
	}
}

for ( const [ service, details ] of Object.entries( services ) ) {
	assert.match( service, /^[a-z0-9-]+$/ );
	assert.ok( details.name && /^#[a-f0-9]{3,6}$/i.test( details.color ) );
	assert.ok( [ 'black', 'white' ].includes( details.defaultIcon ) );
	for ( const variant of [ 'black', 'white' ] ) {
		const image = fs.readFileSync( path.join( directory, `${ variant }-${ service }.png` ) );
		assert.equal( image.subarray( 1, 4 ).toString(), 'PNG' );
		assert.equal( image.readUInt32BE( 16 ), 128 );
		assert.equal( image.readUInt32BE( 20 ), 128 );
	}
}
if ( mode === 'check' && args[ 1 ] ) {
	const missing = Object.keys( readWordPress( args[ 1 ] ) ).filter( ( service ) => ! services[ service ] );
	assert.deepEqual( missing, [], 'WordPress services missing from the email registry' );
}
console.log( `${ Object.keys( services ).length } social services verified (two PNG variants each).` );

if ( mode === 'preview' ) {
	assert.ok( args[ 1 ]?.endsWith( '.png' ), 'Supply an output PNG path.' );
	const cells = Object.keys( services ).map( ( service, index ) => {
		const x = ( index % 7 ) * 170;
		const y = Math.floor( index / 7 ) * 100;
		const images = [ 'black', 'white' ].map( ( variant, i ) => {
			const data = fs.readFileSync( path.join( directory, `${ variant }-${ service }.png` ) ).toString( 'base64' );
			return `<rect x="${ 10 + i * 75 }" y="5" width="64" height="64" fill="${ i ? '#333' : '#eee' }"/><image x="${ 18 + i * 75 }" y="13" width="48" height="48" href="data:image/png;base64,${ data }"/>`;
		} ).join( '' );
		return `<g transform="translate(${ x } ${ y })">${ images }<text x="10" y="88" font-size="13">${ service }</text></g>`;
	} ).join( '' );
	const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="1190" height="${ Math.ceil( Object.keys( services ).length / 7 ) * 100 }"><rect width="100%" height="100%" fill="white"/>${ cells }</svg>`;
	fs.writeFileSync( args[ 1 ], new Resvg( svg ).render().asPng() );
}
