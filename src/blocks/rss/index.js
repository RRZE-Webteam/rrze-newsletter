/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Plugin dependencies
 */
import './style.scss';
import blockDefinition from './block.json';
import { RSS_BLOCK_NAME } from './consts';
import Icon from './icon';
import RSSEdit from './edit';

export default () => {
	registerBlockType( RSS_BLOCK_NAME, {
		...blockDefinition,
		title: __( 'RSS', 'rrze-newsletter' ),
		category: 'widgets',
		icon: Icon,
		description: __(
			'Display entries from any RSS or Atom feed.',
			'rrze-newsletter'
		),
		keywords: [ 'atom', 'feed' ],
		edit: RSSEdit,
	} );
};
