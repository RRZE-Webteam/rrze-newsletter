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

const RecurrenceSettingsComponent = (props) => {
    const {
        meta,
        updateIsRecurring,
        updateRecurrenceRepeat,
        updateRecurrenceMonthly,
    } = props;

    const {
        rrze_newsletter_is_recurring,
        rrze_newsletter_recurrence_repeat,
        rrze_newsletter_recurrence_monthly,
    } = meta;

    return (
        <Fragment>
            <ToggleControl
                className="rrze-newsletter__recurrence-toggle-control rrze-newsletter__recurrence-toggle-control--separated"
                label={__("Recurrence", "rrze-newsletter")}
                help={__(
                    "Apply recurrence rules to the newsletter.",
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
                        onChange={(value) => updateRecurrenceRepeat(value)}
                    />
                    {rrze_newsletter_recurrence_repeat == "WEEKLY" && (
                        <RepeatWeekly />
                    )}
                    {rrze_newsletter_recurrence_repeat == "MONTHLY" && (
                        <RepeatMonthly
                            rrze_newsletter_recurrence_monthly={
                                rrze_newsletter_recurrence_monthly
                            }
                            updateRecurrenceMonthly={updateRecurrenceMonthly}
                        />
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

export const RecurrenceSettings = compose([
    withSelect(mapStateToProps),
    withDispatch(mapDispatchToIsRecurring),
    withDispatch(mapDispatchToRepeat),
    withDispatch(mapDispatchToMonthly),
])(RecurrenceSettingsComponent);
