import apiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { showEmailPreview } from './preview';

const pending = new Set();

/** Load the saved newsletter with current feeds, without saving or sending it. */
export async function openEmailPreview( postId, isMounted = () => true ) {
	const isCurrent = () => {
		const editor = select( 'core/editor' );
		return isMounted() && editor.getCurrentPostType() === 'newsletter' && editor.getCurrentPostId() === postId;
	};
	if ( pending.has( postId ) || ! isCurrent() || select( 'core/editor' ).isSavingPost() ) return;
	if ( ! postId ) {
		showEmailPreview( '', { savedVersion: true } );
		return;
	}
	pending.add( postId );
	try {
		const response = await apiFetch( {
			path: `/rrze-newsletter/v1/email/${ postId }/preview`,
			method: 'GET',
		} );
		if ( ! isCurrent() ) return;
		const editor = select( 'core/editor' );
		showEmailPreview( typeof response?.html === 'string' ? response.html : '', {
			savedVersion: true,
			isStale: editor.isEditedPostDirty() || editor.isSavingPost(),
		} );
	} catch {
		if ( isCurrent() ) {
			dispatch( 'core/notices' ).createErrorNotice(
				__( 'The email preview could not be loaded. Please try again.', 'rrze-newsletter' ),
				{ id: 'rrze-newsletter-preview' }
			);
		}
	} finally {
		pending.delete( postId );
	}
}
