/**
 * WordPress dependencies
 */
import { createPortal, useLayoutEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Plugin dependencies
 */
import LayoutPicker from './screens/layout-picker';
import './style.scss';
import { mountAdminShell } from './admin-shell.mjs';

export default () => {
	const host = useRef();
	useLayoutEffect( () => mountAdminShell( host.current ), [] );
	// This is a workspace, not a modal: the admin bar remains keyboard accessible.
	return createPortal(
		<div ref={ host } className="rrze-newsletter-modal__screen-overlay">
			<section className="rrze-newsletter-modal__frame" aria-labelledby="rrze-newsletter-start-heading">
				<div className="rrze-newsletter-start__body">
					<header className="rrze-newsletter-start__header">
						<h1 id="rrze-newsletter-start-heading" tabIndex={ -1 }>{ __( 'Start a newsletter', 'rrze-newsletter' ) }</h1>
					</header>
					<div className="rrze-newsletter-start__children"><LayoutPicker /></div>
				</div>
			</section>
		</div>,
		document.body
	);
};
