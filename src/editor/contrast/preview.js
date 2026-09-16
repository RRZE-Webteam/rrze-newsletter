import { createRoot } from '@wordpress/element';
import { Modal, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function showEmailPreview( html, { savedVersion = false, isStale = false } = {} ) {
	const hasPreview = typeof html === 'string' && html.trim() !== '';
	const host = document.createElement( 'div' );
	document.body.appendChild( host );
	const root = createRoot( host );
	const close = () => {
		root.unmount();
		host.remove();
	};
	root.render(
		<Modal title={ __( 'Generated email preview', 'rrze-newsletter' ) } onRequestClose={ close } size="large">
			{ ! hasPreview && <Notice status="info" isDismissible={ false }>
				{ __( 'No generated email is available yet. Save the newsletter, then open the preview again.', 'rrze-newsletter' ) }
			</Notice> }
			{ hasPreview && savedVersion && <Notice status={ isStale ? 'warning' : 'info' } isDismissible={ false }>
				{ isStale
					? __( 'This is the last generated email. Your current changes may not be included. Finish saving the newsletter, then reopen the preview.', 'rrze-newsletter' )
					: __( 'This preview shows the last generated email saved for this newsletter.', 'rrze-newsletter' ) }
			</Notice> }
			<p>{ __( 'This is the generated email. Mail apps may render colors differently, especially in dark mode.', 'rrze-newsletter' ) }</p>
			{/* Keep own-site images in the authenticated site context. Never add
				allow-scripts: email HTML must remain inert, even on the same origin. */}
			{ hasPreview && <iframe title={ __( 'Email content', 'rrze-newsletter' ) } sandbox="allow-same-origin" srcDoc={ html } style={ { width: '100%', height: '65vh', border: 0 } } /> }
		</Modal>
	);
}
