/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import {
    ToggleControl,
    SelectControl,
    __experimentalText as Text,
} from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { Fragment } from "@wordpress/element";

/**
 * Plugin dependencies
 */
import RepeatWeekly from "./repeat-weekly";
import RepeatMonthly from "./repeat-monthly";
import "./style.scss";

const units = [
    {
        label: __("ASAP", "rrze-newsletter"),
        value: "ASAP",
    },
    {
        label: __("Hourly", "rrze-newsletter"),
        value: "HOURLY",
    },
    {
        label: __("Daily", "rrze-newsletter"),
        value: "DAILY",
    },
    {
        label: __("Weekly", "rrze-newsletter"),
        value: "WEEKLY",
    },
    {
        label: __("Monthly", "rrze-newsletter"),
        value: "MONTHLY",
    },
];

const AdvancedSettingsComponent = (props) => {
    const {
        meta,
        updateHasConditionals,
        updateRssNoItems,
        updateIcsNoItems,
        updateIsRecurring,
        updateRecurrenceRepeat,
        updateRecurrenceMonthly,
    } = props;
    const {
        rrze_newsletter_has_conditionals,
        rrze_newsletter_conditionals_rss_block,
        rrze_newsletter_conditionals_ics_block,
        rrze_newsletter_is_recurring,
        rrze_newsletter_recurrence_repeat,
        rrze_newsletter_recurrence_monthly,
    } = meta;

    return (
        <Fragment>
            <ToggleControl
                className="rrze-newsletter__conditionals-toggle-control rrze-newsletter__conditionals-toggle-control--separated"
                label={__("Advanced", "rrze-newsletter")}
                help={__(
                    "Apply advanced settings for sending the newsletter, especially if it contains blocks of dynamic content.",
                    "rrze-newsletter"
                )}
                checked={rrze_newsletter_has_conditionals}
                onChange={(value) => updateHasConditionals(value)}
            />
            {rrze_newsletter_has_conditionals && (
                <Fragment>
                    <ToggleControl
                        className="rrze-newsletter__conditionals-toggle-control"
                        label={__("RSS Block", "rrze-newsletter")}
                        help={__(
                            "Apply a condition to the sending of the newsletter: If no feed items are available, the newsletter will not be sent.",
                            "rrze-newsletter"
                        )}
                        checked={rrze_newsletter_conditionals_rss_block}
                        onChange={(value) => updateRssNoItems(value)}
                    />
                    <ToggleControl
                        className="rrze-newsletter__conditionals-toggle-control"
                        label={__("ICS Block", "rrze-newsletter")}
                        help={__(
                            "Apply a condition to the sending of the newsletter: If no events are available, the newsletter will not be sent.",
                            "rrze-newsletter"
                        )}
                        checked={rrze_newsletter_conditionals_ics_block}
                        onChange={(value) => updateIcsNoItems(value)}
                    />
                    <ToggleControl
                        className="rrze-newsletter__recurrence-toggle-control"
                        label={__("Recurrence", "rrze-newsletter")}
                        help={__(
                            "Apply recurrence rules: The newsletter will be sent recurringly depending on the conditions applied to the sending.",
                            "rrze-newsletter"
                        )}
                        checked={rrze_newsletter_is_recurring}
                        onChange={(value) => updateIsRecurring(value)}
                    />
                    {rrze_newsletter_is_recurring && (
                        <Fragment>
                            <Text>{__("Repeat", "rrze-newsletter")}</Text>
                            <SelectControl
                                value={rrze_newsletter_recurrence_repeat}
                                options={units}
                                onChange={(value) =>
                                    updateRecurrenceRepeat(value)
                                }
                            />
                            {rrze_newsletter_recurrence_repeat == "WEEKLY" && (
                                <RepeatWeekly />
                            )}
                            {rrze_newsletter_recurrence_repeat == "MONTHLY" && (
                                <RepeatMonthly
                                    rrze_newsletter_recurrence_monthly={
                                        rrze_newsletter_recurrence_monthly
                                    }
                                    updateRecurrenceMonthly={
                                        updateRecurrenceMonthly
                                    }
                                />
                            )}
                        </Fragment>
                    )}
                </Fragment>
            )}
        </Fragment>
    );
};

const mapStateToProps = (select) => {
    const { getEditedPostAttribute } = select("core/editor");
    return {
        meta: getEditedPostAttribute("meta"),
    };
};

const mapDispatchToProps = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateHasConditionals: (value) =>
            editPost({ meta: { rrze_newsletter_has_conditionals: value } }),
    };
};

const mapDispatchToProps2 = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateRssNoItems: (value) =>
            editPost({
                meta: { rrze_newsletter_conditionals_rss_block: value },
            }),
    };
};

const mapDispatchToProps3 = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateIcsNoItems: (value) =>
            editPost({
                meta: { rrze_newsletter_conditionals_ics_block: value },
            }),
    };
};

const mapDispatchToIsRecurring = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateIsRecurring: (value) =>
            editPost({ meta: { rrze_newsletter_is_recurring: value } }),
    };
};

const mapDispatchToRepeat = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateRecurrenceRepeat: (value) =>
            editPost({ meta: { rrze_newsletter_recurrence_repeat: value } }),
    };
};

const mapDispatchToMonthly = (dispatch) => {
    const { editPost } = dispatch("core/editor");
    return {
        updateRecurrenceMonthly: (value) =>
            editPost({ meta: { rrze_newsletter_recurrence_monthly: value } }),
    };
};

export const AdvancedSettings = compose([
    withSelect(mapStateToProps),
    withDispatch(mapDispatchToProps),
    withDispatch(mapDispatchToProps2),
    withDispatch(mapDispatchToProps3),
    withDispatch(mapDispatchToIsRecurring),
    withDispatch(mapDispatchToRepeat),
    withDispatch(mapDispatchToMonthly),
])(AdvancedSettingsComponent);
