// WCAG relative luminance and contrast, without rounding at the 4.5 threshold.
export function contrastRatio( first, second ) {
	const luminance = ( rgb ) => rgb.slice( 0, 3 ).reduce( ( sum, value, index ) => {
		const channel = value / 255;
		return sum + [ 0.2126, 0.7152, 0.0722 ][ index ] *
			( channel <= 0.04045 ? channel / 12.92 : ( ( channel + 0.055 ) / 1.055 ) ** 2.4 );
	}, 0 );
	const a = luminance( first );
	const b = luminance( second );
	return ( Math.max( a, b ) + 0.05 ) / ( Math.min( a, b ) + 0.05 );
}

// Computed sRGB colors only. Unknown/wide-gamut colors are not guessed.
export function parseColor( value ) {
	if ( value === 'transparent' ) {
		return [ 0, 0, 0, 0 ];
	}
	const match = value?.match( /^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/i );
	if ( ! match ) {
		return null;
	}
	const color = [ ...match.slice( 1, 4 ).map( Number ), Number( match[ 4 ] ?? 1 ) ];
	return color.slice( 0, 3 ).every( ( channel ) => channel >= 0 && channel <= 255 ) &&
		color[ 3 ] >= 0 && color[ 3 ] <= 1 ? color : null;
}

export function readableColor( foreground, background ) {
	if ( contrastRatio( foreground, background ) >= 4.5 ) {
		return null;
	}
	return contrastRatio( [ 0, 0, 0 ], background ) >= contrastRatio( [ 255, 255, 255 ], background )
		? '#000000' : '#ffffff';
}

/** Resolve only backgrounds we can assess confidently. */
function backgroundBehind( element, styleOf ) {
	let background = null;
	for ( let node = element; node; node = node.parentElement ) {
		const style = styleOf( node );
		// Opacity/filter/blending affects descendants even behind an opaque box.
		if ( ( style.opacity && Number( style.opacity ) !== 1 ) ||
			( style.filter && style.filter !== 'none' ) ||
			( style.mixBlendMode && style.mixBlendMode !== 'normal' ) ) {
			return null;
		}
		if ( background ) {
			continue;
		}
		if ( style.backgroundImage && style.backgroundImage !== 'none' ) {
			return null;
		}
		const color = parseColor( style.backgroundColor );
		if ( ! color || ( color[ 3 ] > 0 && color[ 3 ] < 1 ) ) {
			return null;
		}
		if ( color[ 3 ] === 1 ) {
			background = color;
		}
	}
	return background;
}

/**
 * Inspect a rendered, isolated document. Snapshot all text colors before edits:
 * changing a parent's color must not make a previously readable child unreadable.
 */
export function protectDocument( doc, view ) {
	const cache = new Map();
	const styleOf = ( element ) => {
		if ( ! cache.has( element ) ) {
			cache.set( element, view.getComputedStyle( element ) );
		}
		return cache.get( element );
	};
	const decisions = [];
	let corrected = 0;
	let skipped = 0;
	for ( const element of [ doc.body, ...doc.body.querySelectorAll( '*' ) ] ) {
		if ( element.closest( 'script, style, noscript, svg, math, template' ) ||
			! [ ...element.childNodes ].some( ( node ) => node.nodeType === 3 && node.textContent.trim() ) ) {
			continue;
		}
		const style = styleOf( element );
		if ( style.visibility === 'hidden' || style.display === 'none' ||
			[ ...ancestors( element ) ].some( ( node ) => styleOf( node ).display === 'none' ) ) {
			continue;
		}
		const foreground = parseColor( style.color );
		const background = backgroundBehind( element, styleOf );
		let replacement = null;
		if ( ! foreground || foreground[ 3 ] !== 1 || ! background ) {
			skipped++;
		} else {
			replacement = readableColor( foreground, background );
			if ( replacement ) {
				corrected++;
			}
		}
		decisions.push( { element, color: replacement || style.color, replacement } );
	}
	if ( corrected ) {
		// Preserve readable descendants of corrected ancestors, but do not rewrite
		// unrelated readable elements or reserialize their background declarations.
		const changed = new Set( decisions.filter( ( decision ) => decision.replacement ).map( ( decision ) => decision.element ) );
		for ( const { element, color, replacement } of decisions ) {
			if ( color && ( replacement || [ ...ancestors( element ) ].some( ( parent ) => changed.has( parent ) ) ) ) {
				element.style.setProperty( 'color', color, 'important' );
			}
		}
	}
	return { corrected, skipped };
}

function* ancestors( element ) {
	for ( let parent = element.parentElement; parent; parent = parent.parentElement ) {
		yield parent;
	}
}

/** Runs only in the editor, never inside a recipient's email client. */
export async function protectEmailHtml( html, enabled = true ) {
	if ( ! enabled ) {
		return { html, corrected: 0, skipped: 0 };
	}
	const frame = document.createElement( 'iframe' );
	frame.title = 'Email contrast analysis';
	frame.setAttribute( 'aria-hidden', 'true' );
	frame.setAttribute( 'sandbox', 'allow-same-origin' );
	frame.style.cssText = 'position:fixed;left:-10000px;width:680px;height:800px;border:0;pointer-events:none;';
	const policy = '<meta id="rrze-contrast-policy" http-equiv="Content-Security-Policy" content="default-src \'none\'; style-src \'unsafe-inline\'; base-uri \'none\'; form-action \'none\'">';
	if ( ! /<head\b[^>]*>/i.test( html ) ) {
		throw new Error( 'Email HTML is missing its head element.' );
	}
	let timer;
	try {
		await new Promise( ( resolve, reject ) => {
			timer = setTimeout( () => reject( new Error( 'Email contrast analysis timed out.' ) ), 5000 );
			frame.onload = resolve;
			frame.srcdoc = html.replace( /<head\b[^>]*>/i, ( head ) => head + policy );
			document.body.appendChild( frame );
		} );
		const doc = frame.contentDocument;
		const result = protectDocument( doc, frame.contentWindow );
		doc.getElementById( 'rrze-contrast-policy' ).remove();
		return { ...result, html: result.corrected ? '<!doctype html>\n' + doc.documentElement.outerHTML : html };
	} finally {
		clearTimeout( timer );
		frame.remove();
	}
}
