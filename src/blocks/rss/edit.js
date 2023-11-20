/**
 * WordPress dependencies
 */
import {
    BlockControls,
    InspectorControls,
    useBlockProps
} from "@wordpress/block-editor";
import {
    Button,
    Disabled,
    FontSizePicker,
    ColorPicker,
    PanelBody,
    Placeholder,
    RangeControl,
    TextControl,
    ToggleControl,
    ToolbarGroup
} from "@wordpress/components";
import { useState } from "@wordpress/element";
import { edit } from "@wordpress/icons";
import rss from "./icon";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import { RSS_BLOCK_NAME } from "./consts";

const DEFAULT_MIN_ITEMS = 1;
const DEFAULT_MAX_ITEMS = 15;

export default function RSSEdit({ attributes, setAttributes }) {
    const [isEditing, setIsEditing] = useState(!attributes.feedURL);

    const {
        feedURL,
        sinceLastSend,
        itemsToShow,
        displayDate,
        displayContent,
        excerptLimit,
        excerptLength,
        displayReadMore,
        headingFontSize,
        textFontSize,
        headingColor,
        textColor
    } = attributes;

    function toggleAttribute(propName) {
        return () => {
            const value = attributes[propName];

            setAttributes({ [propName]: !value });
        };
    }

    function onSubmitURL(event) {
        event.preventDefault();

        if (feedURL) {
            setIsEditing(false);
        }
    }

    const blockEditorSettings = wp.data
        .select("core/block-editor")
        .getSettings();

    const blockProps = useBlockProps();

    if (isEditing) {
        return (
            <div {...blockProps}>
                <Placeholder icon={rss} label="RSS">
                    <form
                        onSubmit={onSubmitURL}
                        className="wp-block-rss__placeholder-form"
                    >
                        <TextControl
                            placeholder={__(
                                "Enter URL here…",
                                "rrze-newsletter"
                            )}
                            value={feedURL}
                            onChange={value =>
                                setAttributes({ feedURL: value })
                            }
                            className="wp-block-rss__placeholder-input"
                        />
                        <Button variant="primary" type="submit">
                            {__("Use URL", "rrze-newsletter")}
                        </Button>
                    </form>
                </Placeholder>
            </div>
        );
    }

    const toolbarControls = [
        {
            icon: edit,
            title: __("Edit RSS URL", "rrze-newsletter"),
            onClick: () => setIsEditing(true)
        }
    ];

    return (
        <>
            <BlockControls>
                <ToolbarGroup controls={toolbarControls} />
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("RSS settings", "rrze-newsletter")}>
                    <RangeControl
                        label={__("Number of items", "rrze-newsletter")}
                        value={itemsToShow}
                        onChange={value =>
                            setAttributes({ itemsToShow: value })
                        }
                        min={DEFAULT_MIN_ITEMS}
                        max={DEFAULT_MAX_ITEMS}
                        required
                    />
                    <ToggleControl
                        label={__(
                            "Display only new items since the last time the newsletter was sent.",
                            "rrze-newsletter"
                        )}
                        checked={sinceLastSend}
                        onChange={toggleAttribute("sinceLastSend")}
                    />
                    <ToggleControl
                        label={__("Display date", "rrze-newsletter")}
                        checked={displayDate}
                        onChange={toggleAttribute("displayDate")}
                    />
                    <ToggleControl
                        label={__("Display content", "rrze-newsletter")}
                        checked={displayContent}
                        onChange={toggleAttribute("displayContent")}
                    />
                    {displayContent && (
                        <ToggleControl
                            label={__(
                                "Limit length of content",
                                "rrze-newsletter"
                            )}
                            checked={excerptLimit}
                            onChange={toggleAttribute("excerptLimit")}
                        />
                    )}
                    {excerptLimit && (
                        <RangeControl
                            label={__(
                                "Max number of words in content",
                                "rrze-newsletter"
                            )}
                            value={excerptLength}
                            onChange={value =>
                                setAttributes({ excerptLength: value })
                            }
                            min={10}
                            max={100}
                            required
                        />
                    )}
                    {displayContent && (
                        <ToggleControl
                            label={__(
                                'Display "Continue reading"',
                                "rrze-newsletter"
                            )}
                            checked={displayReadMore}
                            onChange={toggleAttribute("displayReadMore")}
                        />
                    )}
                </PanelBody>
                <PanelBody title={__("Heading", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={headingFontSize}
                        fallbackFontSize={25}
                        onChange={value =>
                            setAttributes({
                                headingFontSize: isNaN(value) ? 16 : value
                            })
                        }
                    />
                    <ColorPicker
                        color={headingColor}
                        onChangeComplete={value =>
                            setAttributes({ headingColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
                <PanelBody title={__("Text", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={textFontSize}
                        fallbackFontSize={16}
                        onChange={value =>
                            setAttributes({
                                textFontSize: isNaN(value) ? 16 : value
                            })
                        }
                    />
                    <ColorPicker
                        color={textColor}
                        onChangeComplete={value =>
                            setAttributes({ textColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <Disabled>
                    <ServerSideRender
                        block={RSS_BLOCK_NAME}
                        attributes={attributes}
                    />
                </Disabled>
            </div>
        </>
    );
}
