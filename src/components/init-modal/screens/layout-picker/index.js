import { parse } from '@wordpress/blocks';
import { Fragment, useMemo, useState } from '@wordpress/element';
import { compose } from '@wordpress/compose';
import { withSelect, withDispatch, dispatch } from '@wordpress/data';
import { Button, Spinner, Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { BLANK_LAYOUT_ID } from '../../../../utils/consts';
import { isUserDefinedLayout, getBaseUrl, convertRelativeUrlsToAbsolute } from '../../../../utils';
import { useLayoutsState } from '../../../../utils/hooks';
import SingleLayoutPreview, { layoutDescription } from './SingleLayoutPreview';
import LayoutPreview from './LayoutPreview';
import ManageTemplates from './ManageTemplates';

export const LayoutPicker = ( { getBlocks, insertBlocks, replaceBlocks, savePost, setNewsletterMeta } ) => {
	const { layouts, isFetchingLayouts, layoutsError, retryLayouts, deleteLayoutPost } = useLayoutsState();
	const [ managing, setManaging ] = useState( false );
	const [ category, setCategory ] = useState( 'prebuilt' );
	const [ selectedId, setSelectedId ] = useState( null );
	const [ device, setDevice ] = useState( 'desktop' );
	const [ isStarting, setIsStarting ] = useState( false );
	const [ startError, setStartError ] = useState( false );
	const displayed = layouts.filter( ( layout ) => category === 'saved' ? isUserDefinedLayout( layout ) : layout.post_author === undefined );
	const recommended = displayed.find( ( layout ) => layout.post_author === undefined && layout.post_title === 'FAU Newsletter' );
	// Never resolve a selection outside the visible category.
	const selected = selectedId === null ? ( recommended || displayed[ 0 ] ) : displayed.find( ( layout ) => layout.ID === selectedId );
	const preview = useMemo( () => {
		if ( ! selected ) { return null; }
		try {
			// Preview only: preserve original personalization tokens on insertion.
			const content = convertRelativeUrlsToAbsolute( selected.post_content || '', getBaseUrl() )
				.replace( /\{\{\/?EMAIL_ONLY\}\}/g, '' );
			return { blocks: parse( content ), meta: selected.meta };
		} catch { return null; }
	}, [ selected ] );

	const start = async ( blank = false ) => {
		if ( isStarting || ( ! blank && ( ! selected || isFetchingLayouts || layoutsError || ! preview ) ) ) { return; }
		setIsStarting( true );
		setStartError( false );
		try {
			const blocks = blank ? [] : parse( selected.post_content || '' );
			const existing = getBlocks().map( ( block ) => block.clientId );
			if ( existing.length ) { replaceBlocks( existing, blocks ); } else { insertBlocks( blocks ); }
			setNewsletterMeta( { ...( blank ? {} : selected.meta ), rrze_newsletter_template_id: blank ? BLANK_LAYOUT_ID : selected.ID } );
			// Let the editor mount after the template metadata closes this dialog.
			await new Promise( ( resolve ) => setTimeout( resolve, 0 ) );
			await savePost();
		} catch {
			setStartError( true );
			// Template metadata may already have closed the modal.
			dispatch( 'core/notices' ).createErrorNotice(
				__( 'The newsletter could not be saved. Please try again in the editor.', 'rrze-newsletter' ),
				{ id: 'rrze-newsletter-start' }
			);
		}
		finally { setIsStarting( false ); }
	};
	const chooseCategory = ( value ) => { setCategory( value ); setSelectedId( null ); };

	return (
		<Fragment>
			<div className="rrze-newsletter-modal__intro">
				<p>{ __( 'Choose a starting point. You can edit all content and adjust the design afterwards.', 'rrze-newsletter' ) }</p>
				<Button variant="link" href="edit.php?post_type=newsletter">{ __( 'Back to newsletters', 'rrze-newsletter' ) }</Button>
			</div>
			<div className="rrze-newsletter-modal__content">
				<section className="rrze-newsletter-modal__library" aria-label={ __( 'Templates', 'rrze-newsletter' ) }>
					<div className="rrze-newsletter-picker-switch" role="group" aria-label={ __( 'Template category', 'rrze-newsletter' ) }>
						<Button aria-pressed={ category === 'prebuilt' } onClick={ () => chooseCategory( 'prebuilt' ) }>{ __( 'Prebuilt', 'rrze-newsletter' ) }</Button>
						<Button aria-pressed={ category === 'saved' } onClick={ () => chooseCategory( 'saved' ) }>{ __( 'Saved', 'rrze-newsletter' ) }</Button>
					</div>
					<div className="rrze-newsletter-modal__layouts" aria-busy={ isFetchingLayouts }>
						{ isFetchingLayouts ? <p role="status"><Spinner />{ __( 'Loading templates…', 'rrze-newsletter' ) }</p> : layoutsError ? (
							<Notice status="error" isDismissible={ false }>
								{ __( 'Templates could not be loaded.', 'rrze-newsletter' ) }
								<Button variant="secondary" onClick={ retryLayouts }>{ __( 'Try again', 'rrze-newsletter' ) }</Button>
							</Notice>
						) : displayed.length ? displayed.map( ( layout ) => (
							<SingleLayoutPreview key={ layout.ID } layout={ layout } selected={ selected?.ID === layout.ID }
								recommended={ layout === recommended } onSelect={ () => setSelectedId( layout.ID ) } />
						) ) : (
							<div className="rrze-newsletter-picker-empty">
								<h3>{ __( 'No templates here yet', 'rrze-newsletter' ) }</h3>
								<p>{ __( 'Start with a prebuilt design or a blank newsletter. You can save your own template later in the editor.', 'rrze-newsletter' ) }</p>
								{ category === 'saved' && <Button variant="secondary" onClick={ () => chooseCategory( 'prebuilt' ) }>{ __( 'Browse prebuilt templates', 'rrze-newsletter' ) }</Button> }
							</div>
						) }
					</div>
					<p className="rrze-newsletter-modal__library-note">{ __( 'Manage your own templates later under Layout in the editor.', 'rrze-newsletter' ) }</p>
					{ category === 'saved' && displayed.length > 0 && <Button variant="link" onClick={ () => setManaging( true ) }>{ __( 'Manage saved templates', 'rrze-newsletter' ) }</Button> }
				</section>
				<section className="rrze-newsletter-modal__preview" aria-label={ __( 'Template preview', 'rrze-newsletter' ) }>
					<div className="rrze-newsletter-modal__preview-heading">
						<div><h2>{ selected?.post_title || __( 'Template preview', 'rrze-newsletter' ) }</h2>
							{ selected && <p>{ layoutDescription( selected ) }</p> }</div>
						<div className="rrze-newsletter-picker-switch" role="group" aria-label={ __( 'Preview width', 'rrze-newsletter' ) }>
							<Button aria-pressed={ device === 'desktop' } onClick={ () => setDevice( 'desktop' ) }>{ __( 'Desktop', 'rrze-newsletter' ) }</Button>
							<Button aria-pressed={ device === 'mobile' } onClick={ () => setDevice( 'mobile' ) }>{ __( 'Mobile', 'rrze-newsletter' ) }</Button>
						</div>
					</div>
					<div className="rrze-newsletter-modal__preview-scroll">
						{ ! isFetchingLayouts && selected ? (
							<LayoutPreview key={ selected.ID + '-' + device + '-' + selected.post_content } preview={ preview } width={ device === 'mobile' ? 375 : 680 } />
						) : <p className="rrze-newsletter-picker-empty">{ __( 'Choose a template from the list to see its preview.', 'rrze-newsletter' ) }</p> }
					</div>
					<p className="rrze-newsletter-modal__preview-note">{ __( 'Layout preview with sample content. Check the generated email before sending; mail apps may display it differently.', 'rrze-newsletter' ) }</p>
				</section>
			</div>
			{ startError && <Notice status="error" isDismissible={ false }>{ __( 'The newsletter could not be saved. Please try again in the editor.', 'rrze-newsletter' ) }</Notice> }
			<div className="rrze-newsletter-modal__action-buttons">
				<span>{ __( 'Nothing is sent when you start.', 'rrze-newsletter' ) }</span>
				<Button variant="secondary" disabled={ isStarting } onClick={ () => start( true ) }>{ __( 'Start without a template', 'rrze-newsletter' ) }</Button>
				<Button variant="primary" disabled={ isStarting || isFetchingLayouts || layoutsError || ! selected || ! preview } onClick={ () => start() }>
					{ isStarting ? __( 'Starting…', 'rrze-newsletter' ) : __( 'Start with this template', 'rrze-newsletter' ) }
				</Button>
			</div>
			{ managing && <ManageTemplates layouts={ layouts.filter( isUserDefinedLayout ) } onClose={ () => setManaging( false ) } onRefresh={ retryLayouts } onDelete={ deleteLayoutPost } /> }
		</Fragment>
	);
};

export default compose( [
	withSelect( ( select ) => ( { getBlocks: select( 'core/block-editor' ).getBlocks } ) ),
	withDispatch( ( dispatch ) => ( {
		insertBlocks: dispatch( 'core/block-editor' ).insertBlocks,
		replaceBlocks: dispatch( 'core/block-editor' ).replaceBlocks,
		savePost: dispatch( 'core/editor' ).savePost,
		setNewsletterMeta: ( meta ) => dispatch( 'core/editor' ).editPost( { meta } ),
	} ) ),
] )( LayoutPicker );
