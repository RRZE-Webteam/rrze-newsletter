import { Component, useEffect, useRef, useState } from '@wordpress/element';
import { Button, Notice, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import NewsletterPreview from '../../../newsletter-preview';
import { watchPreview } from './preview-ready.mjs';

class PreviewBoundary extends Component {
	state = { failed: false };
	static getDerivedStateFromError() { return { failed: true }; }
	componentDidCatch() { this.props.onError(); }
	render() { return this.state.failed ? null : this.props.children; }
}

export default function LayoutPreview( { preview, width } ) {
	const host = useRef();
	const [ attempt, setAttempt ] = useState( 0 );
	const [ status, setStatus ] = useState( 'loading' );
	useEffect( () => {
		if ( ! preview?.blocks.length ) { return; }
		return watchPreview( host.current, () => setStatus( 'ready' ), () => setStatus( 'error' ) );
	}, [ attempt, preview ] );
	if ( ! preview ) {
		return <Notice status="error" isDismissible={ false }>{ __( 'This template could not be previewed. Choose another template or start without one.', 'rrze-newsletter' ) }</Notice>;
	}
	if ( ! preview.blocks.length ) {
		return <p className="rrze-newsletter-picker-empty">{ __( 'This is a blank template. Add your content after starting.', 'rrze-newsletter' ) }</p>;
	}
	return (
		<div className="rrze-newsletter-picker-canvas" style={ { width, maxWidth: '100%' } }>
			{ status === 'loading' && <p role="status"><Spinner />{ __( 'Loading preview…', 'rrze-newsletter' ) }</p> }
			{ status === 'error' && <Notice status="warning" isDismissible={ false }>
				{ __( 'The preview is taking too long or could not be loaded.', 'rrze-newsletter' ) }
				<Button variant="secondary" onClick={ () => { setStatus( 'loading' ); setAttempt( attempt + 1 ); } }>{ __( 'Try again', 'rrze-newsletter' ) }</Button>
			</Notice> }
			<div ref={ host } aria-busy={ status === 'loading' }>
				<PreviewBoundary key={ attempt } onError={ () => setStatus( 'error' ) }>
					<NewsletterPreview { ...preview } viewportWidth={ width } />
				</PreviewBoundary>
			</div>
		</div>
	);
}
