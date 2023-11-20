/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";

/**
 * Plugin dependencies
 */
import "./style.scss";
import blockDefinition from "./block.json";
import { ICS_BLOCK_NAME } from "./consts";
import Icon from "./icon";
import ICSEdit from "./edit";

export default () => {
    registerBlockType(ICS_BLOCK_NAME, {
        ...blockDefinition,
        title: __("ICS", "rrze-newsletter"),
        category: "widgets",
        icon: Icon,
        description: __(
            "Display entries from any ICS or ICal feed.",
            "rrze-newsletter"
        ),
        keywords: ["ics", "ical", "feed"],
        edit: ICSEdit,
    });
};
