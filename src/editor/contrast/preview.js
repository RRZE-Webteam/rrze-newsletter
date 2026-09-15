import { createRoot } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function showEmailPreview( html ) {
	const host = document.createElement( 'div' );
	document.body.appendChild( host );
	const root = createRoot( host );
	const close = () => {
		root.unmount();
		host.remove();
	};
	root.render(
		<Modal title={ __( 'Generated email preview', 'rrze-newsletter' ) } onRequestClose={ close } size="large">
			<p>{ __( 'This is the generated email. Mail apps may render colors differently, especially in dark mode.', 'rrze-newsletter' ) }</p>
			<iframe title={ __( 'Email content', 'rrze-newsletter' ) } sandbox="" srcDoc={ html } style={ { width: '100%', height: '65vh', border: 0 } } />
		</Modal>
	);
}
