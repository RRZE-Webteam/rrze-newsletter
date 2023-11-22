/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch } from '@wordpress/data';
import { Fragment } from '@wordpress/element';
import { Button, TextControl, TextareaControl } from '@wordpress/components';

/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Plugin dependencies
 */
import { hasValidEmail } from '../utils';
import { getServiceProvider } from '../../service-providers';
import withApiHandler from '../../components/with-api-handler';
import './style.scss';

const Sidebar = ( {
	inFlight,
	errors,
	editPost,
	title,
	senderName,
	senderEmail,
	recipientEmail,
	replytoEmail,
	previewText,
	apiFetchWithErrorHandling,
	postId,
} ) => {
	const renderSubject = () => (
		<TextControl
			label={ __( 'Subject', 'rrze-newsletter' ) }
			className="rrze-newsletter__subject-textcontrol"
			value={ title }
			disabled={ inFlight }
			onChange={ ( value ) => editPost( { title: value } ) }
			hideLabelFromVision
		/>
	);

	const recipientEmailClasses = classnames(
		'rrze-newsletter__recipient-textcontrol',
		errors.rrze_newsletter_unverified_recipient_domain &&
			'rrze-newsletter__error'
	);

	const senderEmailClasses = classnames(
		'rrze-newsletter__email-textcontrol',
		errors.rrze_newsletter_unverified_sender_domain &&
			'rrze-newsletter__error'
	);

	const updateMetaValueInAPI = ( data ) =>
		apiFetchWithErrorHandling( {
			data,
			method: 'POST',
			path: `/rrze-newsletter/v1/post-meta/${ postId }`,
		} );

	const renderTo = () => (
		<TextControl
			label={ __(
				'Recipient (Email Distribution List)',
				'rrze-newsletter'
			) }
			className={ recipientEmailClasses }
			value={ recipientEmail }
			type="email"
			disabled={ inFlight }
			onChange={ ( value ) =>
				editPost( { meta: { rrze_newsletter_to_email: value } } )
			}
		/>
	);

	const renderFrom = () => (
		<Fragment>
			<strong>{ __( 'From', 'rrze-newsletter' ) }</strong>
			<TextControl
				label={ __( 'Name', 'rrze-newsletter' ) }
				className="rrze-newsletter__name-textcontrol"
				value={ senderName }
				disabled={ inFlight }
				onChange={ ( value ) =>
					editPost( { meta: { rrze_newsletter_from_name: value } } )
				}
			/>
			<TextControl
				label={ __( 'Email', 'rrze-newsletter' ) }
				className={ senderEmailClasses }
				value={ senderEmail }
				type="email"
				disabled={ inFlight }
				onChange={ ( value ) =>
					editPost( { meta: { rrze_newsletter_from_email: value } } )
				}
			/>
			<TextControl
				label={ __( 'ReplyTo', 'rrze-newsletter' ) }
				className={ senderEmailClasses }
				value={ replytoEmail }
				type="email"
				disabled={ inFlight }
				onChange={ ( value ) =>
					editPost( { meta: { rrze_newsletter_replyto: value } } )
				}
			/>
			<Button
				isLink
				onClick={ () => {
					updateMetaValueInAPI( {
						key: 'rrze_newsletter_from_name',
						value: senderName,
					} );
					updateMetaValueInAPI( {
						key: 'rrze_newsletter_from_email',
						value: senderEmail,
					} );
					updateMetaValueInAPI( {
						key: 'rrze_newsletter_replyto',
						value: replytoEmail,
					} );
				} }
				disabled={
					inFlight ||
					( senderEmail.length
						? ! hasValidEmail( senderEmail )
						: false )
				}
			>
				{ __( 'Update Sender', 'rrze-newsletter' ) }
			</Button>

			<TextareaControl
				label={ __( 'Preview text', 'rrze-newsletter' ) }
				className="rrze-newsletter__name-textcontrol rrze-newsletter__name-textcontrol--separated"
				value={ previewText }
				disabled={ inFlight }
				onChange={ ( value ) =>
					editPost( {
						meta: { rrze_newsletter_preview_text: value },
					} )
				}
			/>
			<Button
				isLink
				onClick={ () =>
					updateMetaValueInAPI( {
						key: 'rrze_newsletter_preview_text',
						value: previewText,
					} )
				}
				disabled={ inFlight }
			>
				{ __( 'Update preview text', 'rrze-newsletter' ) }
			</Button>
		</Fragment>
	);

	const { ProviderSidebar } = getServiceProvider();
	return (
		<Fragment>
			<ProviderSidebar
				postId={ postId }
				inFlight={ inFlight }
				renderSubject={ renderSubject }
				renderTo={ renderTo }
				renderFrom={ renderFrom }
				updateMeta={ ( meta ) => editPost( { meta } ) }
			/>
		</Fragment>
	);
};

export default compose( [
	withApiHandler(),
	withSelect( ( select ) => {
		const { getEditedPostAttribute, getCurrentPostId } =
			select( 'core/editor' );
		const meta = getEditedPostAttribute( 'meta' );
		return {
			title: getEditedPostAttribute( 'title' ),
			postId: getCurrentPostId(),
			senderName: meta.rrze_newsletter_from_name || '',
			senderEmail: meta.rrze_newsletter_from_email || '',
			recipientEmail: meta.rrze_newsletter_to_email || '',
			replytoEmail: meta.rrze_newsletter_replyto || '',
			previewText: meta.rrze_newsletter_preview_text || '',
		};
	} ),
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );
		return { editPost };
	} ),
] )( Sidebar );
