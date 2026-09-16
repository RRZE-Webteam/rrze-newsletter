import { InspectorControls } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { isManagedSpacing } from './managed-spacing.mjs';

export function ManagedSpacingHint() {
	const enabled = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		return editor.getCurrentPostType() === 'newsletter' && isManagedSpacing(
			editor.getEditedPostAttribute( 'meta' )?.rrze_newsletter_spacing_mode,
			window.rrze_newsletter_data?.global_managed_spacing
		);
	}, [] );
	if ( ! enabled ) { return null; }
	return (
		<InspectorControls group="dimensions">
			<Notice status="info" isDismissible={ false }>
				{ __( 'Managed email spacing is active. Manual spacing values remain saved, but are overridden in the editor and generated email. Open Newsletter Styles > Email spacing and switch to Expert mode to use them.', 'rrze-newsletter' ) }
			</Notice>
		</InspectorControls>
	);
}

export const withManagedSpacingHint = ( BlockEdit ) => ( props ) => (
	<>
		<BlockEdit { ...props } />
		{ props.isSelected && <ManagedSpacingHint /> }
	</>
);
