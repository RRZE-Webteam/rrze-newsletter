/** Apply presentation only: never write the color to block attributes or post meta. */
export function mountCanvasBackground( document, color = '#fff' ) {
	const documents = new Map();
	const frames = new Map();
	const targets = new Map();
	const restore = ( element, previous ) => {
		if ( previous.value ) {
			element.style.setProperty( 'background-color', previous.value, previous.priority );
		} else {
			element.style.removeProperty( 'background-color' );
		}
	};
	const refresh = () => {
		for ( const doc of documents.keys() ) {
			for ( const element of doc.querySelectorAll( '.editor-styles-wrapper' ) ) {
				if ( targets.has( element ) ) { continue; }
				targets.set( element, {
					value: element.style.getPropertyValue( 'background-color' ),
					priority: element.style.getPropertyPriority( 'background-color' ),
				} );
				element.style.setProperty( 'background-color', color || '#fff' );
			}
		}
		for ( const [ element, previous ] of targets ) {
			if ( ! element.isConnected ) { restore( element, previous ); targets.delete( element ); }
		}
		for ( const frame of document.querySelectorAll( 'iframe[name="editor-canvas"]' ) ) {
			if ( frames.has( frame ) ) { continue; }
			let currentDocument;
			const load = () => {
				if ( currentDocument ) { detach( currentDocument ); }
				try {
					currentDocument = frame.contentDocument;
					attach( currentDocument );
				} catch { /* Ignore inaccessible frames. */ }
			};
			frames.set( frame, () => {
				frame.removeEventListener( 'load', load );
				if ( currentDocument ) { detach( currentDocument ); }
			} );
			frame.addEventListener( 'load', load );
			load();
		}
		for ( const [ frame, dispose ] of frames ) {
			if ( ! frame.isConnected ) { frames.delete( frame ); dispose(); }
		}
	};
	function attach( doc ) {
		if ( ! doc?.documentElement || documents.has( doc ) ) { return; }
		const observer = new doc.defaultView.MutationObserver( refresh );
		documents.set( doc, observer );
		// The iframe can load before Gutenberg portals its body into it.
		observer.observe( doc.documentElement, { childList: true, subtree: true } );
		refresh();
	}
	function detach( doc ) {
		documents.get( doc )?.disconnect();
		documents.delete( doc );
		for ( const [ element, previous ] of targets ) {
			if ( element.ownerDocument === doc ) { restore( element, previous ); targets.delete( element ); }
		}
	}
	attach( document );
	return () => {
		frames.forEach( ( dispose ) => dispose() );
		frames.clear();
		Array.from( documents.keys() ).forEach( detach );
	};
}
