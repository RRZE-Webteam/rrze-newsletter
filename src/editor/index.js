/**
 * WordPress dependencies
 */
import {
	registerBlockStyle,
	unregisterBlockStyle,
	unregisterBlockVariation,
} from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { addFilter, removeFilter } from '@wordpress/hooks';
import { registerPlugin } from '@wordpress/plugins';
import { __ } from '@wordpress/i18n';

/**
 * Plugin dependencies
 */
import './style.scss';
import { addBlocksValidationFilter } from './blocks-validation/blocks-filters';
import { NestedColumnsDetection } from './blocks-validation/nesting-detection';
import './api';
import '../newsletter-editor';

addBlocksValidationFilter();

/* Unregister core block styles that are unsupported in emails */
domReady( () => {
	unregisterBlockStyle( 'core/separator', 'dots' );
	unregisterBlockStyle( 'core/social-links', 'logos-only' );
	unregisterBlockStyle( 'core/social-links', 'pill-shape' );
	/* Unregister "row" group block variation */
	unregisterBlockVariation( 'core/group', 'group-row' );
} );

/* Remove Duotone filters */
removeFilter(
	'blocks.registerBlockType',
	'core/editor/duotone/add-attributes'
);
removeFilter( 'editor.BlockEdit', 'core/editor/duotone/with-editor-controls' );
removeFilter( 'editor.BlockListBlock', 'core/editor/duotone/with-styles' );

addFilter(
	'blocks.registerBlockType',
	'rrze-newsletter/core-blocks',
	( settings, name ) => {
		/* Remove left/right alignment options wherever possible */
		if (
			'core/paragraph' === name ||
			'core/buttons' === name ||
			'core/columns' === name ||
			'core/separator' === name
		) {
			settings.supports = { ...settings.supports, align: [] };
		}
		if ( 'core/group' === name ) {
			settings.supports = { ...settings.supports, align: [ 'full' ] };
		}
		return settings;
	}
);

registerBlockStyle( 'core/social-links', {
	name: 'circle-black',
	label: __( 'Circle Black', 'rrze-newsletter' ),
} );

registerBlockStyle( 'core/social-links', {
	name: 'circle-white',
	label: __( 'Circle White', 'rrze-newsletter' ),
} );

registerBlockStyle( 'core/social-links', {
	name: 'filled-black',
	label: __( 'Black', 'rrze-newsletter' ),
} );

registerBlockStyle( 'core/social-links', {
	name: 'filled-white',
	label: __( 'White', 'rrze-newsletter' ),
} );

registerPlugin( 'rrze-newsletter-plugin', {
	render: NestedColumnsDetection,
	icon: null,
} );
