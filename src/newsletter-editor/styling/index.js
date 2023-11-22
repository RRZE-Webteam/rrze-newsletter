/**
 * WordPress dependencies
 */
import { compose, useInstanceId } from '@wordpress/compose';
import {
	ColorPicker,
	BaseControl,
	Panel,
	PanelBody,
	PanelRow,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useSelect, withDispatch, withSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';
import SelectControlWithOptGroup from '../../components/select-control-with-optgroup/';

/**
 * Plugin dependencies
 */
import './style.scss';

const fontOptgroups = [
	{
		label: __( 'Sans Serif', 'rrze-newsletter' ),
		options: [
			{
				value: 'Arial, Helvetica, sans-serif',
				label: __( 'Arial', 'rrze-newsletter' ),
			},
			{
				value: 'Tahoma, sans-serif',
				label: __( 'Tahoma', 'rrze-newsletter' ),
			},
			{
				value: 'Trebuchet MS, sans-serif',
				label: __( 'Trebuchet', 'rrze-newsletter' ),
			},
			{
				value: 'Verdana, sans-serif',
				label: __( 'Verdana', 'rrze-newsletter' ),
			},
		],
	},

	{
		label: __( 'Serif', 'rrze-newsletter' ),
		options: [
			{
				value: 'Georgia, serif',
				label: __( 'Georgia', 'rrze-newsletter' ),
			},
			{
				value: 'Palatino, serif',
				label: __( 'Palatino', 'rrze-newsletter' ),
			},
			{
				value: 'Times New Roman, serif',
				label: __( 'Times New Roman', 'rrze-newsletter' ),
			},
		],
	},

	{
		label: __( 'Monospace', 'rrze-newsletter' ),
		options: [
			{
				value: 'Courier, monospace',
				label: __( 'Courier', 'rrze-newsletter' ),
			},
		],
	},
];

const customStylesSelector = ( select ) => {
	const { getEditedPostAttribute } = select( 'core/editor' );
	const meta = getEditedPostAttribute( 'meta' );
	return {
		fontBody:
			meta.rrze_newsletter_font_body ||
			fontOptgroups[ 1 ].options[ 0 ].value,
		fontHeader:
			meta.rrze_newsletter_font_header ||
			fontOptgroups[ 0 ].options[ 0 ].value,
		backgroundColor: meta.rrze_newsletter_background_color || '#ffffff',
	};
};

// Create a temporary DOM document (not displayed) for parsing CSS rules.
const doc = document.implementation.createHTMLDocument( 'Temp' );

/**
 * Takes a given CSS string, parses it, and scopes all its rules to the given `scope`.
 *
 * @param {string} scope The scope to apply to each rule in the CSS.
 * @param {string} css   The CSS to scope.
 * @return {string} Scoped CSS string.
 */
export const getScopedCss = ( scope, css ) => {
	const style =
		doc.querySelector( 'style' ) || document.createElement( 'style' );

	style.textContent = css;
	doc.head.appendChild( style );

	const rules = [ ...style.sheet.cssRules ];
	return rules
		.map( ( rule ) => {
			rule.selectorText = rule.selectorText
				.split( ',' )
				.map( ( selector ) => `${ scope } ${ selector }` )
				.join( ', ' );
			return rule.cssText;
		} )
		.join( '\n' );
};

/**
 * Hook to apply body and header fonts variables in store to an iframe as root
 * element style property.
 *
 * @return {import('react').RefObject} The component to be rendered.
 */
export const useCustomFontsInIframe = () => {
	const ref = useRef();
	const { fontBody, fontHeader } = useSelect( customStylesSelector );
	useEffect( () => {
		const node = ref.current;
		const updateIframe = () => {
			const iframe = node.querySelector(
				'iframe[title="Editor canvas"]'
			);
			if ( iframe ) {
				const updateStyleProperties = () => {
					const element = iframe.contentDocument?.documentElement;
					if ( element ) {
						element.style.setProperty(
							'--rrze-newsletter-body-font',
							fontBody
						);
						element.style.setProperty(
							'--rrze-newsletter-header-font',
							fontHeader
						);
						element
							.querySelector( 'body' )
							.style.setProperty( 'background', 'none' );
					}
				};
				updateStyleProperties();
				// Handle Firefox iframe.
				iframe.addEventListener( 'load', updateStyleProperties );
				return () => {
					iframe.removeEventListener( 'load', updateStyleProperties );
				};
			}
		};
		updateIframe();
		const observer = new MutationObserver( updateIframe );
		observer.observe( node, { childList: true } );
		return () => {
			observer.disconnect();
		};
	}, [ fontBody, fontHeader ] );
	return ref;
};

export const ApplyStyling = withSelect( customStylesSelector )( ( {
	fontBody,
	fontHeader,
	backgroundColor,
} ) => {
	useEffect( () => {
		document.documentElement.style.setProperty(
			'--rrze-newsletter-body-font',
			fontBody
		);
	}, [ fontBody ] );
	useEffect( () => {
		document.documentElement.style.setProperty(
			'--rrze-newsletter-header-font',
			fontHeader
		);
	}, [ fontHeader ] );
	useEffect( () => {
		const editorElement = document.querySelector(
			'.editor-styles-wrapper'
		);
		if ( editorElement ) {
			editorElement.style.backgroundColor = backgroundColor;
		}
	}, [ backgroundColor ] );

	return null;
} );

export const Styling = compose( [
	withDispatch( ( dispatch ) => {
		const { editPost } = dispatch( 'core/editor' );
		return { editPost };
	} ),
	withSelect( customStylesSelector ),
] )( ( { editPost, fontBody, fontHeader, backgroundColor } ) => {
	const updateStyleValue = ( key, value ) => {
		editPost( { meta: { [ key ]: value } } );
	};

	const instanceId = useInstanceId( SelectControlWithOptGroup );
	const id = `inspector-select-control-${ instanceId }`;

	return (
		<Panel>
			<PanelBody
				name="rrze-newsletter-typography-panel"
				title={ __( 'Typography', 'rrze-newsletter' ) }
			>
				<PanelRow>
					<SelectControlWithOptGroup
						label={ __( 'Headings font', 'rrze-newsletter' ) }
						value={ fontHeader }
						optgroups={ fontOptgroups }
						onChange={ ( value ) =>
							updateStyleValue(
								'rrze_newsletter_font_header',
								value
							)
						}
					/>
				</PanelRow>
				<PanelRow>
					<SelectControlWithOptGroup
						label={ __( 'Body font', 'rrze-newsletter' ) }
						value={ fontBody }
						optgroups={ fontOptgroups }
						onChange={ ( value ) =>
							updateStyleValue(
								'rrze_newsletter_font_body',
								value
							)
						}
					/>
				</PanelRow>
			</PanelBody>
			<PanelBody
				name="rrze-newsletter-color-panel"
				title={ __( 'Color', 'rrze-newsletter' ) }
			>
				<PanelRow className="rrze-newsletter__color-panel">
					<BaseControl
						label={ __( 'Background color', 'rrze-newsletter' ) }
						id={ id }
					>
						<ColorPicker
							id={ id }
							color={ backgroundColor }
							onChangeComplete={ ( value ) =>
								updateStyleValue(
									'rrze_newsletter_background_color',
									value.hex
								)
							}
							disableAlpha
						/>
					</BaseControl>
				</PanelRow>
			</PanelBody>
		</Panel>
	);
} );
