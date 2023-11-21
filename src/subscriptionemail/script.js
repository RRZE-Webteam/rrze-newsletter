/**
 * External dependencies
 */
import mjml2html from 'mjml-browser';

jQuery( document ).ready( ( $ ) => {
	'use strict';
	$.ajax( {
		type: 'post',
		url: subscriptionEmail.ajaxUrl,
		data: {
			action: 'mjml2html',
			from: subscriptionEmail.from,
			fromName: subscriptionEmail.fromName,
			replyTo: subscriptionEmail.replyTo,
			to: subscriptionEmail.to,
			subject: subscriptionEmail.subject,
			body: mjml2html( subscriptionEmail.mjml, {
				keepComments: false,
			} ),
		},
	} );
} );
