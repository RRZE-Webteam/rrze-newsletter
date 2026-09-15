/** Editor-only approximation of the ManagedSpacing PHP output policy. */
export const ROOT_CLASS = 'rrze-newsletter-managed-editor';
export const STYLE_ID = 'rrze-newsletter-managed-editor-css';

export function isManagedSpacing( mode, globalEnabled = false ) {
	// wp_localize_script converts scalar PHP booleans to "1" / "".
	// Accept the enabled transport value explicitly, not arbitrary truthy strings.
	const enabled = globalEnabled === true || globalEnabled === '1';
	return mode === 'managed' || ( mode !== 'expert' && enabled );
}

// Scope everything to block-list roots, never toolbars, titles or other admin UI.
// Keep these values in sync with includes/MJML/ManagedSpacing.php and processors.
export const editorSpacingCss = `
.${ ROOT_CLASS }.is-root-container {
	--rrze-managed-gutter:24px;
	box-sizing:border-box;
	width:100%; max-width:680px;
	margin-left:auto !important; margin-right:auto !important;
	padding:0 !important;
}
.${ ROOT_CLASS }.rrze-managed-narrow { --rrze-managed-gutter:16px; }
.${ ROOT_CLASS } [data-type] {
	box-sizing:border-box;
	max-width:100%;
	margin:0 !important; padding:0 !important;
}
.${ ROOT_CLASS } [data-type="core/group"],
.${ ROOT_CLASS } .wp-block-group__inner-container,
.${ ROOT_CLASS } [data-type="core/columns"],
.${ ROOT_CLASS } [data-type="core/buttons"] {
	gap:0 !important;
}
.${ ROOT_CLASS } [data-type="core/group"]:not(.is-layout-grid),
.${ ROOT_CLASS } [data-type="core/buttons"] { display:block !important; }
.${ ROOT_CLASS } [data-type="core/paragraph"],
.${ ROOT_CLASS } [data-type="core/heading"],
.${ ROOT_CLASS } [data-type="core/image"],
.${ ROOT_CLASS } [data-type="core/list"],
.${ ROOT_CLASS } [data-type="core/button"],
.${ ROOT_CLASS } [data-type="core/separator"],
.${ ROOT_CLASS } [data-type="core/social-links"],
.${ ROOT_CLASS } [data-type="rrze-newsletter/rss"],
.${ ROOT_CLASS } [data-type="rrze-newsletter/ics"] {
	margin-bottom:16px !important;
}
.${ ROOT_CLASS } [data-type="core/list"] { padding-left:20px !important; }
.${ ROOT_CLASS } [data-type="core/list-item"] [data-type="core/list"],
.${ ROOT_CLASS } [data-type="core/list-item"] [data-type="core/paragraph"],
.${ ROOT_CLASS } [data-type="core/list-item"] [data-type="core/heading"] { margin-bottom:0 !important; }
.${ ROOT_CLASS } [data-type="core/column"],
.${ ROOT_CLASS } [data-type="core/group"].is-layout-grid > [data-type] {
	padding:0 8px !important;
}
.${ ROOT_CLASS } [data-type="core/button"] .wp-block-button__link {
	padding:12px 24px !important; margin:0 !important;
}
.${ ROOT_CLASS } [data-type="core/image"] figure {
	margin:0 !important; padding:0 !important;
}
.${ ROOT_CLASS } [data-type="core/image"] img { max-width:100%; }
.${ ROOT_CLASS } [data-type="core/image"] figcaption {
	margin:16px 0 0 !important; padding:0 !important;
}
.${ ROOT_CLASS } [data-type="core/spacer"],
.${ ROOT_CLASS } [data-type="core/spacer"] .components-resizable-box__container {
	height:16px !important; min-height:16px !important; max-height:16px !important;
}
/* Older editors wrap aligned blocks; modern editors use the block directly. */
.${ ROOT_CLASS } > [data-type],
.${ ROOT_CLASS } > [data-align] > [data-type] {
	padding-left:var(--rrze-managed-gutter) !important;
	padding-right:var(--rrze-managed-gutter) !important;
}
.${ ROOT_CLASS } > [data-type="core/group"],
.${ ROOT_CLASS } > [data-align] > [data-type="core/group"] {
	padding-top:16px !important;
}
.${ ROOT_CLASS } > [data-type="core/list"],
.${ ROOT_CLASS } > [data-align] > [data-type="core/list"] {
	padding-left:calc(var(--rrze-managed-gutter) + 20px) !important;
}
.${ ROOT_CLASS } > [data-type].alignfull:not([data-type="core/group"]),
.${ ROOT_CLASS } > [data-align="full"] > [data-type]:not([data-type="core/group"]) {
	padding-left:0 !important; padding-right:0 !important;
}
`;

const ROOT_SELECTOR = '.editor-styles-wrapper .is-root-container, .editor-styles-wrapper.is-root-container';
// Stable iframe name avoids depending on the translated "Editor canvas" title.
// Restrict previews to this plugin: don't restyle pattern pickers or email previews.
const FRAME_SELECTOR = 'iframe[name="editor-canvas"], .rrze-newsletter-post-inserter__preview iframe';

/**
 * Attach presentation to current and future canvases. The disposer restores the
 * original DOM; no block attributes, inline spacing or editor-store data change.
 */
export function mountManagedSpacing( document ) {
	const documents = new Map();
	const frames = new Map();
	let stopped = false;

	function attachDocument( doc ) {
		if ( stopped || ! doc?.head || ! doc.documentElement || documents.has( doc ) ) {
			return;
		}
		// Gutenberg may already have copied this style from the parent document.
		const style = doc.getElementById( STYLE_ID ) || doc.createElement( 'style' );
		style.id = STYLE_ID;
		style.textContent = editorSpacingCss;
		doc.head.appendChild( style );
		const roots = new Map();
		const setWidth = ( root, width ) => root.classList.toggle( 'rrze-managed-narrow', width > 0 && width < 480 );
		const resize = doc.defaultView.ResizeObserver ? new doc.defaultView.ResizeObserver( ( entries ) => {
			entries.forEach( ( entry ) => setWidth( entry.target, entry.contentRect.width ) );
		} ) : null;
		const refresh = () => {
			for ( const root of doc.querySelectorAll( ROOT_SELECTOR ) ) {
				if ( roots.has( root ) ) {
					// React can replace the root class attribute (e.g. outline mode).
					root.classList.add( ROOT_CLASS );
					setWidth( root, root.clientWidth );
					continue;
				}
				roots.set( root, {
					managed: root.classList.contains( ROOT_CLASS ),
					narrow: root.classList.contains( 'rrze-managed-narrow' ),
				} );
				root.classList.add( ROOT_CLASS );
				setWidth( root, root.clientWidth );
				resize?.observe( root );
			}
			for ( const [ root, previous ] of roots ) {
				if ( ! root.isConnected ) {
					restoreRoot( root, previous );
					resize?.unobserve( root );
					roots.delete( root );
				}
			}
			for ( const frame of doc.querySelectorAll( FRAME_SELECTOR ) ) {
				if ( frames.has( frame ) ) { continue; }
				let currentDocument;
				const load = () => {
					if ( currentDocument ) { detachDocument( currentDocument ); }
					try {
						currentDocument = frame.contentDocument;
						attachDocument( currentDocument );
					} catch {
						// A cross-origin frame is not an editor canvas we can style.
					}
				};
				frames.set( frame, () => {
					frame.removeEventListener( 'load', load );
					if ( currentDocument ) { detachDocument( currentDocument ); }
				} );
				frame.addEventListener( 'load', load );
				load();
			}
			for ( const [ frame, dispose ] of frames ) {
				if ( ! frame.isConnected ) { frames.delete( frame ); dispose(); }
			}
		};
		const observer = new doc.defaultView.MutationObserver( ( records ) => {
			if ( records.some( ( record ) => record.type === 'childList'
				|| ( roots.has( record.target ) && ! record.target.classList.contains( ROOT_CLASS ) ) ) ) {
				refresh();
			}
		} );
		documents.set( doc, () => {
			observer.disconnect();
			resize?.disconnect();
			roots.forEach( ( previous, root ) => restoreRoot( root, previous ) );
			style.remove();
		} );
		// Gutenberg portals its body after iframe load and can replace it later.
		// Observe the stable html element, even when no body exists yet.
		// Ignore character data, inline styles and unrelated class changes.
		observer.observe( doc.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
		refresh();
	}

	function restoreRoot( root, previous ) {
		root.classList.toggle( ROOT_CLASS, previous.managed );
		root.classList.toggle( 'rrze-managed-narrow', previous.narrow );
	}

	function detachDocument( doc ) {
		const dispose = documents.get( doc );
		documents.delete( doc );
		dispose?.();
		for ( const [ frame, remove ] of frames ) {
			if ( frame.ownerDocument === doc ) { frames.delete( frame ); remove(); }
		}
	}

	attachDocument( document );
	return () => {
		stopped = true;
		frames.forEach( ( dispose ) => dispose() );
		frames.clear();
		Array.from( documents.keys() ).forEach( detachDocument );
	};
}
