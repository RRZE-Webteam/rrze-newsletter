/**
 * External dependencies
 */
import { pick, omit, includes } from 'lodash';
import mjml2html from 'mjml-browser';

/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { select as globalSelect, dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { protectEmailHtml } from '../contrast/contrast.mjs';
import { showEmailPreview } from '../contrast/preview';

const POST_META_WHITELIST = [
	'rrze_newsletter_preview_text',
	'rrze_newsletter_font_body',
	'rrze_newsletter_font_header',
	'rrze_newsletter_background_color',
	'rrze_newsletter_contrast_protection',
	'rrze_newsletter_spacing_mode',
	'rrze_newsletter_sent',
];

/**
 * Use a middleware to hijack the post update request.
 * When a post is about to be updated, first the email-compliant HTML has
 * to be produced. To do that, MJML (more at mjml.io) is used.
 */
apiFetch.use( async ( options, next ) => {
	const { method, path, data = {} } = options;

	// Only run in update request.
	if ( method !== 'POST' && method !== 'PUT' ) {
		return next( options );
	}

	// Only run if the update contains the newsletter content.
	if ( ! data.content || ! data.id ) {
		return next( options );
	}

	const mjml_handling_post_types =
		window.rrze_newsletter_data.mjml_handling_post_types;

	// Only run if the request is for a post type that is handled by MJML.
	if (
		! mjml_handling_post_types.some(
			( postType ) => path.indexOf( postType ) !== -1
		)
	) {
		return next( options );
	}

	const editorSelector = globalSelect( 'core/editor' );
	const postType = editorSelector.getCurrentPostType();

	// Only run if the current post type is allowed to be handled by MJML.
	if ( ! includes( mjml_handling_post_types, postType ) ) {
		return next( options );
	}

	const emailHTMLMetaName = window.rrze_newsletter_data.email_html_meta;
	// Strip the meta which will be updated explicitly from post update payload.
	if ( options.data.meta ) {
		options.data.meta = omit( options.data.meta, [
			...POST_META_WHITELIST,
			emailHTMLMetaName,
		] );
	}

	// First, save post meta. It is not saved when saving a draft, so
	// it's saved here in order for the backend to have access to these.
	const postMeta = editorSelector.getEditedPostAttribute( 'meta' );
	await apiFetch( {
		data: { meta: pick( postMeta, POST_META_WHITELIST ) },
		method: 'POST',
		path: `/wp/v2/${ postType }/${ data.id }`,
	} );

	// Then, send the content over to the server to convert the post content
	// into MJML markup.
	const { mjml, managed_spacing } = await apiFetch( {
		path: `/rrze-newsletter/v1/post-mjml`,
		method: 'POST',
		data: {
			post_id: data.id,
			title: data.title,
			content: data.content,
		},
	} );

	// Once received MJML markup, convert it to email-compliant HTML
	// and save as post meta for later retrieval.
	const { html } = mjml2html( mjml, { keepComments: false } );
	let protectedEmail;
	try {
		protectedEmail = await protectEmailHtml( html, postMeta.rrze_newsletter_contrast_protection !== false );
	} catch ( error ) {
		dispatch( 'core/notices' ).createErrorNotice(
			__( 'Email contrast protection could not run. Please retry saving or disable it in the newsletter styling settings.', 'rrze-newsletter' ),
			{ id: 'rrze-newsletter-contrast' }
		);
		throw error;
	}
	await apiFetch( {
		data: { meta: { [ emailHTMLMetaName ]: protectedEmail.html } },
		method: 'POST',
		path: `/wp/v2/${ postType }/${ data.id }`,
	} );
	dispatch( 'core/notices' ).removeNotice( 'rrze-newsletter-contrast' );
	dispatch( 'core/notices' ).removeNotice( 'rrze-newsletter-spacing' );
	if ( managed_spacing ) {
		dispatch( 'core/notices' ).createInfoNotice(
			__( 'Managed spacing was applied to the generated email. Manual spacing and nested group padding were normalized; editor blocks were not changed. Preview the email before sending.', 'rrze-newsletter' ),
			{ id: 'rrze-newsletter-spacing', actions: [ {
				label: __( 'Preview generated email', 'rrze-newsletter' ),
				onClick: () => showEmailPreview( protectedEmail.html ),
			} ] }
		);
	}
	if ( protectedEmail.corrected || protectedEmail.skipped ) {
		dispatch( 'core/notices' ).createWarningNotice(
			sprintf(
				/* translators: 1: corrected text elements, 2: elements needing manual review. */
				__( 'Email contrast protection: %1$d text elements adjusted; %2$d could not be checked safely (for example, image backgrounds or transparency). Review the generated email before sending. Editor block colors were not changed.', 'rrze-newsletter' ),
				protectedEmail.corrected, protectedEmail.skipped
			),
			{ id: 'rrze-newsletter-contrast', actions: [ {
				label: __( 'Preview generated email', 'rrze-newsletter' ),
				onClick: () => showEmailPreview( protectedEmail.html ),
			} ] }
		);
	}

	return next( options ); // Proceed with the post update request.
} );
