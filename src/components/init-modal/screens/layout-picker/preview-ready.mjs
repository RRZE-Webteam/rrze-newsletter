// BlockPreview has no public ready callback. Observe only this preview's iframe.
export function watchPreview( host, onReady, onError, timeout = 15000 ) {
	const view = host.ownerDocument.defaultView;
	const frames = new Map();
	let stopped = false;
	const timer = view.setTimeout( () => { dispose(); onError(); }, timeout );
	const observer = new view.MutationObserver( scan );
	function dispose() {
		stopped = true;
		view.clearTimeout( timer );
		observer.disconnect();
		frames.forEach( ( cleanup ) => cleanup() );
		frames.clear();
	}
	function scan() {
		if ( stopped ) { return; }
		for ( const frame of host.querySelectorAll( 'iframe' ) ) {
			if ( frames.has( frame ) ) { continue; }
			let documentObserver;
			const inspect = () => {
				documentObserver?.disconnect();
				try {
					const doc = frame.contentDocument;
					if ( ! doc?.documentElement ) { return; }
					const check = () => {
						if ( ! stopped && doc.querySelector( '[data-block], .block-editor-block-list__block' ) ) {
							dispose();
							onReady();
						}
					};
					documentObserver = new view.MutationObserver( check );
					documentObserver.observe( doc.documentElement, { childList: true, subtree: true } );
					check();
				} catch { /* Cross-origin frames are not inspectable previews. */ }
			};
			frames.set( frame, () => { frame.removeEventListener( 'load', inspect ); documentObserver?.disconnect(); } );
			frame.addEventListener( 'load', inspect );
			inspect();
			if ( stopped ) { break; }
		}
	}
	observer.observe( host, { childList: true, subtree: true } );
	scan();
	return dispose;
}
