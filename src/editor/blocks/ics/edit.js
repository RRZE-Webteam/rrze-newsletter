/**
 * WordPress dependencies
 */
import {
    BlockControls,
    InspectorControls,
    useBlockProps,
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
    ToolbarGroup,
} from "@wordpress/components";
import { useState } from "@wordpress/element";
import { edit } from "@wordpress/icons";
import ics from "./icon";
import { __ } from "@wordpress/i18n";
import ServerSideRender from "@wordpress/server-side-render";
import { ICS_BLOCK_NAME } from "./consts";

const DEFAULT_MIN_ITEMS = 1;
const DEFAULT_MAX_ITEMS = 15;

export default function ICSEdit({ attributes, setAttributes }) {
    const [isEditing, setIsEditing] = useState(!attributes.feedURL);

    const {
        feedURL,
        itemsToShow,
        displayLocation,
        displayOrganizer,
        displayDescription,
        descriptionLength,
        descriptionLimit,
        headingFontSize,
        textFontSize,
        headingColor,
        textColor,
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
                <Placeholder icon={ics} label="ICS">
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
                            onChange={(value) =>
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
            title: __("Edit ICS URL", "rrze-newsletter"),
            onClick: () => setIsEditing(true),
        },
    ];

    return (
        <>
            <BlockControls>
                <ToolbarGroup controls={toolbarControls} />
            </BlockControls>
            <InspectorControls>
                <PanelBody title={__("ICS settings", "rrze-newsletter")}>
                    <RangeControl
                        label={__("Number of items", "rrze-newsletter")}
                        value={itemsToShow}
                        onChange={(value) =>
                            setAttributes({ itemsToShow: value })
                        }
                        min={DEFAULT_MIN_ITEMS}
                        max={DEFAULT_MAX_ITEMS}
                        required
                    />
                    <ToggleControl
                        label={__("Display location", "rrze-newsletter")}
                        checked={displayLocation}
                        onChange={toggleAttribute("displayLocation")}
                    />
                    <ToggleControl
                        label={__("Display organizer", "rrze-newsletter")}
                        checked={displayOrganizer}
                        onChange={toggleAttribute("displayOrganizer")}
                    />
                    <ToggleControl
                        label={__("Display description", "rrze-newsletter")}
                        checked={displayDescription}
                        onChange={toggleAttribute("displayDescription")}
                    />
                    {displayDescription && (
                        <ToggleControl
                            label={__(
                                "Limit length of description",
                                "rrze-newsletter"
                            )}
                            checked={descriptionLimit}
                            onChange={toggleAttribute("descriptionLimit")}
                        />
                    )}
                    {descriptionLimit && (
                        <RangeControl
                            label={__(
                                "Max number of words in description",
                                "rrze-newsletter"
                            )}
                            value={descriptionLength}
                            onChange={(value) =>
                                setAttributes({ descriptionLength: value })
                            }
                            min={10}
                            max={100}
                            required
                        />
                    )}
                </PanelBody>
                <PanelBody title={__("Text style", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={attributes.textFontSize}
                        onChange={(value) => {
                            return setAttributes({ textFontSize: value });
                        }}
                    />
                    <ColorPicker
                        color={attributes.textColor || ""}
                        onChangeComplete={(value) =>
                            setAttributes({ textColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
                <PanelBody title={__("Heading style", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={attributes.headingFontSize}
                        onChange={(value) =>
                            setAttributes({ headingFontSize: value })
                        }
                    />
                    <ColorPicker
                        color={attributes.headingColor || ""}
                        onChangeComplete={(value) =>
                            setAttributes({ headingColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <Disabled>
                    <ServerSideRender
                        block={ICS_BLOCK_NAME}
                        attributes={attributes}
                    />
                </Disabled>
            </div>
        </>
    );
}
