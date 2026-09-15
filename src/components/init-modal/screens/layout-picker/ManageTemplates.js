import { useState } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import { Button, Modal, Notice, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { LAYOUT_CPT_SLUG } from '../../../../utils/consts';

function TemplateRow( { layout, onRefresh, onDelete } ) {
	const [ title, setTitle ] = useState( layout.post_title );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( false );
	const run = async ( remove ) => {
		if ( remove && ! window.confirm( __( 'Are you sure you want to delete this layout?', 'rrze-newsletter' ) ) ) { return; }
		setBusy( true );
		setError( false );
		try {
			if ( remove ) { await onDelete( layout.ID ); }
			else {
				await dispatch( 'core' ).saveEntityRecord( 'postType', LAYOUT_CPT_SLUG, { id: layout.ID, title: title.trim() } );
				onRefresh();
			}
		} catch { setError( true ); }
		finally { setBusy( false ); }
	};
	return <div className="rrze-newsletter-template-management-row">
		<TextControl label={ __( 'Template name', 'rrze-newsletter' ) } value={ title } onChange={ setTitle } disabled={ busy } />
		<Button variant="secondary" disabled={ busy || ! title.trim() || title === layout.post_title } onClick={ () => run( false ) }>{ __( 'Save', 'rrze-newsletter' ) }</Button>
		<Button variant="tertiary" isDestructive disabled={ busy } onClick={ () => run( true ) }>{ __( 'Delete', 'rrze-newsletter' ) }</Button>
		{ error && <Notice status="error" isDismissible={ false }>{ __( 'The template could not be updated. Please try again.', 'rrze-newsletter' ) }</Notice> }
	</div>;
}

export default function ManageTemplates( { layouts, onClose, onRefresh, onDelete } ) {
	return <Modal title={ __( 'Manage saved templates', 'rrze-newsletter' ) } onRequestClose={ onClose }>
		{ layouts.map( ( layout ) => <TemplateRow key={ layout.ID } layout={ layout } onRefresh={ onRefresh } onDelete={ onDelete } /> ) }
		{ ! layouts.length && <p>{ __( 'No templates here yet', 'rrze-newsletter' ) }</p> }
		<Button variant="secondary" onClick={ onClose }>{ __( 'Close', 'rrze-newsletter' ) }</Button>
	</Modal>;
}
