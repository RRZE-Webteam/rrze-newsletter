import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function layoutDescription( layout ) {
	if ( layout.post_author !== undefined ) {
		return __( 'Your saved design, ready to reuse.', 'rrze-newsletter' );
	}
	const descriptions = {
		'Newsletter 1': __( 'Classic layout with text and images.', 'rrze-newsletter' ),
		'Newsletter 2': __( 'Alternative layout with highlighted sections.', 'rrze-newsletter' ),
		'FAU Newsletter': __( 'General FAU design for university news.', 'rrze-newsletter' ),
		'FAU MedFak Newsletter': __( 'FAU design for the Faculty of Medicine.', 'rrze-newsletter' ),
		'FAU NatFak Newsletter': __( 'FAU design for the Faculty of Sciences.', 'rrze-newsletter' ),
		'FAU PhilFak Newsletter': __( 'FAU design for Humanities, Social Sciences, and Theology.', 'rrze-newsletter' ),
		'FAU ReWi Newsletter': __( 'FAU design for Business, Economics, and Law.', 'rrze-newsletter' ),
		'FAU TechFak Newsletter': __( 'FAU design for the Faculty of Engineering.', 'rrze-newsletter' ),
	};
	return descriptions[ layout.post_title ] || __( 'A starting point for your newsletter.', 'rrze-newsletter' );
}

export default function SingleLayoutPreview( { layout, selected, onSelect, recommended } ) {
	return (
		<Button className={ 'rrze-newsletter-layout-card' + ( selected ? ' is-selected' : '' ) }
			aria-pressed={ selected } onClick={ onSelect }>
			<span className="rrze-newsletter-layout-card__icon" aria-hidden="true">▤</span>
			<span className="rrze-newsletter-layout-card__text">
				<strong>{ layout.post_title }</strong>
				<span>{ layoutDescription( layout ) }</span>
				{ recommended && <small>{ __( 'General starting point', 'rrze-newsletter' ) }</small> }
			</span>
			<span aria-hidden="true">{ selected ? '✓' : '›' }</span>
		</Button>
	);
}
