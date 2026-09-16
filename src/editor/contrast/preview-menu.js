import apiFetch from '@wordpress/api-fetch';
import { dispatch, select, useSelect } from '@wordpress/data';
import { PluginPreviewMenuItem } from '@wordpress/editor';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { showEmailPreview } from './preview';

export function EmailPreviewMenuItem() {
	const { postType, postId, saving } = useSelect( ( store ) => {
		const editor = store( 'core/editor' );
		return {
			postType: editor.getCurrentPostType(),
			postId: editor.getCurrentPostId(),
			saving: editor.isSavingPost(),
		};
	}, [] );
	const [ loading, setLoading ] = useState( false );
	const pending = useRef( false );
	const mounted = useRef( true );
	useEffect( () => {
		mounted.current = true;
		return () => { mounted.current = false; };
	}, [] );
	if ( postType !== 'newsletter' ) return null;

	const openPreview = async () => {
		if ( pending.current || saving ) return;
		if ( ! postId ) {
			showEmailPreview( '', { savedVersion: true } );
			return;
		}
		pending.current = true;
		setLoading( true );
		try {
			// Fetch on every click: the save middleware writes the generated HTML
			// separately, so the editor store can still contain an older version.
			const post = await apiFetch( {
				path: `/wp/v2/newsletter/${ postId }?context=edit&_fields=meta`,
				method: 'GET',
			} );
			const editor = select( 'core/editor' );
			if ( ! mounted.current || editor.getCurrentPostId() !== postId || editor.getCurrentPostType() !== 'newsletter' ) return;
			const html = post?.meta?.[ window.rrze_newsletter_data.email_html_meta ];
			showEmailPreview( typeof html === 'string' ? html : '', {
				savedVersion: true,
				isStale: editor.isEditedPostDirty() || editor.isSavingPost(),
			} );
		} catch {
			if ( mounted.current ) {
				dispatch( 'core/notices' ).createErrorNotice(
					__( 'The email preview could not be loaded. Please try again.', 'rrze-newsletter' ),
					{ id: 'rrze-newsletter-preview' }
				);
			}
		} finally {
			pending.current = false;
			if ( mounted.current ) setLoading( false );
		}
	};

	return <PluginPreviewMenuItem onClick={ openPreview } disabled={ loading || saving }>
		{ loading ? __( 'Loading email preview…', 'rrze-newsletter' ) : __( 'Email preview', 'rrze-newsletter' ) }
	</PluginPreviewMenuItem>;
}

registerPlugin( 'rrze-newsletter-email-preview', { render: EmailPreviewMenuItem, icon: 'email' } );
