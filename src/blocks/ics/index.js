/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";

/**
 * Plugin dependencies
 */
import "./style.scss";
import { ICS_BLOCK_NAME } from "./consts";
import Icon from "./icon";
import ICSEdit from "./edit";

export default () => {
    registerBlockType(ICS_BLOCK_NAME, {
        title: __("ICS", "rrze-newsletter"),
        category: "widgets",
        icon: Icon,
        description: __(
            "Display entries from any ICS or ICal feed.",
            "rrze-newsletter"
        ),
        keywords: ["ics", "ical", "feed"],
        edit: ICSEdit,
        attributes: {
            feedURL: {
                type: "string",
                default: ""
            },
            itemsToShow: {
                type: "number",
                default: 5
            },
            displayDescription: {
                type: "boolean",
                default: false
            },
            displayLocation: {
                type: "boolean",
                default: false
            },
            displayOrganizer: {
                type: "boolean",
                default: false
            },
            descriptionLimit: {
                type: "boolean",
                default: false
            },
            descriptionLength: {
                type: "number",
                default: 25
            },
            textFontSize: {
                type: "number",
                default: 16
            },
            headingFontSize: {
                type: "number",
                default: 25
            },
            textColor: {
                type: "string",
                default: "#000"
            },
            headingColor: {
                type: "string",
                default: "#000"
            }
        }
    });
};
