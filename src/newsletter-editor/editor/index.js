/**
 * External dependencies
 */
import { get, isEmpty } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withDispatch, withSelect } from '@wordpress/data';
import { createPortal, useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Plugin dependencies
 */
import { getEditPostPayload } from '../utils';
import { getServiceProvider } from '../../service-providers';
import withApiHandler from '../../components/with-api-handler';
import SendButton from '../../components/send-button';
import './style.scss';

const Editor = compose( [
	withApiHandler(),
	withSelect( ( select ) => {
		const {
			getCurrentPostId,
			getCurrentPostAttribute,
			getEditedPostAttribute,
			isPublishingPost,
			isSavingPost,
			isCleanNewPost,
		} = select( 'core/editor' );
		const { getActiveGeneralSidebarName } = select( 'core/edit-post' );
		const { getSettings } = select( 'core/block-editor' );
		const meta = getEditedPostAttribute( 'meta' );
		const status = getCurrentPostAttribute( 'status' );
		const sentDate = getCurrentPostAttribute( 'date' );
		const settings = getSettings();
		const experimentalSettingsColors = get( settings, [
			'__experimentalFeatures',
			'global',
			'color',
			'palette',
		] );
		const colors = settings.colors || experimentalSettingsColors || [];

		return {
			isCleanNewPost: isCleanNewPost(),
			postId: getCurrentPostId(),
			isReady: meta.rrze_newsletter_validation_errors
				? meta.rrze_newsletter_validation_errors.length === 0
				: false,
			activeSidebarName: getActiveGeneralSidebarName(),
			isPublishingOrSavingPost: isSavingPost() || isPublishingPost(),
			colorPalette: colors.reduce(
				( _colors, { slug, color } ) => ( {
					..._colors,
					[ slug ]: color,
				} ),
				{}
			),
			status,
			sentDate,
		};
	} ),
	withDispatch( ( dispatch ) => {
		const {
			lockPostAutosaving,
			lockPostSaving,
			unlockPostSaving,
			editPost,
		} = dispatch( 'core/editor' );
		const { createNotice } = dispatch( 'core/notices' );
		return {
			lockPostAutosaving,
			lockPostSaving,
			unlockPostSaving,
			editPost,
			createNotice,
		};
	} ),
] )( ( props ) => {
	const [ publishEl ] = useState( document.createElement( 'div' ) );
	// Create alternate publish button
	useEffect( () => {
		const publishButton = document.getElementsByClassName(
			'editor-post-publish-button__button'
		)[ 0 ];
		publishButton.parentNode.insertBefore( publishEl, publishButton );
	}, [] );

	// Set color palette option.
	useEffect( () => {
		if ( isEmpty( props.colorPalette ) ) {
			return;
		}
		props.apiFetchWithErrorHandling( {
			path: `/rrze-newsletter/v1/color-palette`,
			data: props.colorPalette,
			method: 'POST',
		} );
	}, [ JSON.stringify( props.colorPalette ) ] );

	// Lock or unlock post publishing.
	useEffect( () => {
		if ( props.isReady ) {
			props.unlockPostSaving( 'rrze-newsletter-post-lock' );
		} else {
			props.lockPostSaving( 'rrze-newsletter-post-lock' );
		}
	}, [ props.isReady ] );

	useEffect( () => {
		if ( 'publish' === props.status && ! props.isPublishingOrSavingPost ) {
			const dateTime = props.sentDate
				? new Date( props.sentDate ).toLocaleString()
				: '';

			// Lock autosaving after a newsletter is sent.
			props.lockPostAutosaving();

			// Show an editor notice if the newsletter has been sent.
			props.createNotice( 'success', props.successNote + dateTime, {
				isDismissible: false,
			} );
		}
	}, [ props.status ] );

	return createPortal( <SendButton />, publishEl );
} );

export default () => {
	registerPlugin( 'rrze-newsletter-edit', {
		render: Editor,
	} );
};
