/* globals activationNoticeDismissParams */

jQuery( document ).ready( ( $ ) => {
	$( document ).on(
		'click',
		'.rrze-newsletter-activation-notice .notice-dismiss',
		() => {
			const data = {
				action: 'rrze_newsletter_activation_notice_dismiss',
			};
			const { ajaxurl } =
				window &&
				window.activationNoticeDismissParams;
			$.post( ajaxurl, data, () => null );
		}
	);
} );
