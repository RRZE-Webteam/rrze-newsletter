/** Keep the start screen below the toolbar without changing editor preferences. */
export function mountAdminShell( host ) {
	const doc = host.ownerDocument;
	const view = doc.defaultView;
	const className = 'rrze-newsletter-start-open';
	const hadClass = doc.body.classList.contains( className );
	const previousFocus = doc.activeElement;
	const bar = doc.getElementById( 'wpadminbar' );
	const previousOffset = host.style.getPropertyValue( '--rrze-newsletter-adminbar-height' );
	const previousPriority = host.style.getPropertyPriority( '--rrze-newsletter-adminbar-height' );
	doc.body.classList.add( className );
	// Hide only the covered workspace, not wpwrap/wpcontent, which may contain
	// the admin bar. Real nested modals still manage their own focus isolation.
	const covered = [ 'wpbody', 'adminmenumain', 'wpfooter' ]
		.map( ( id ) => doc.getElementById( id ) )
		.filter( ( node ) => node && ! node.contains( host ) && ! node.contains( bar ) );
	const inertValues = covered.map( ( node ) => [ node, node.getAttribute( 'inert' ) ] );
	covered.forEach( ( node ) => node.setAttribute( 'inert', '' ) );
	const resize = () => {
		const height = bar?.isConnected ? bar.getBoundingClientRect().height : 0;
		host.style.setProperty( '--rrze-newsletter-adminbar-height', height + 'px' );
	};
	const observer = bar && view.ResizeObserver ? new view.ResizeObserver( resize ) : null;
	observer?.observe( bar );
	view.addEventListener( 'resize', resize );
	resize();
	host.querySelector( 'h1' )?.focus( { preventScroll: true } );
	return () => {
		observer?.disconnect();
		view.removeEventListener( 'resize', resize );
		doc.body.classList.toggle( className, hadClass );
		inertValues.forEach( ( [ node, value ] ) => {
			if ( value === null ) { node.removeAttribute( 'inert' ); }
			else { node.setAttribute( 'inert', value ); }
		} );
		if ( previousOffset ) { host.style.setProperty( '--rrze-newsletter-adminbar-height', previousOffset, previousPriority ); }
		else { host.style.removeProperty( '--rrze-newsletter-adminbar-height' ); }
		if ( host.contains( doc.activeElement ) && previousFocus?.isConnected ) {
			previousFocus.focus( { preventScroll: true } );
		}
	};
}
