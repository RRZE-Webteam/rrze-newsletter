/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";

/**
 * Plugin dependencies
 */
import "./style.scss";
import "./editor.scss";
import { RSS_BLOCK_NAME } from "./consts";
import Icon from "./icon";
import RSSEdit from "./edit";

export default () => {
    registerBlockType(RSS_BLOCK_NAME, {
        title: __("RSS", "rrze-newsletter"),
        category: "widgets",
        icon: Icon,
        description: __(
            "Display entries from any RSS or Atom feed.",
            "rrze-newsletter"
        ),
        keywords: ["atom", "feed"],
        edit: RSSEdit,
        attributes: {
            feedURL: {
                type: "string",
                default: ""
            },
            sinceLastSend: {
                type: "boolean",
                default: false
            },             
            itemsToShow: {
                type: "number",
                default: 5
            },
            displayExcerpt: {
                type: "boolean",
                default: false
            },
            displayDate: {
                type: "boolean",
                default: false
            },
            excerptLength: {
                type: "number",
                default: 25
            },
            displayReadMore: {
                type: "boolean",
                default: false
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
