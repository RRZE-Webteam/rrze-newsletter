import { useSelect } from '@wordpress/data';
import { PluginPreviewMenuItem } from '@wordpress/editor';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { openEmailPreview } from './preview-request';

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
		pending.current = true;
		setLoading( true );
		try {
			await openEmailPreview( postId, () => mounted.current );
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
