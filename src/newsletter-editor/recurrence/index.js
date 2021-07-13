/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { ToggleControl, RangeControl } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { Fragment } from "@wordpress/element";

/**
 * Plugin dependencies
 */
import "./style.scss";

const RecurrenceSettingsComponent = props => {
    const {
        meta,
        updateIsRecurring,
        updateRecurrenceInDays,
        updateRecurrenceInHours
    } = props;
    const {
        rrze_newsletter_is_recurring,
        rrze_newsletter_recurrence_in_days,
        rrze_newsletter_recurrence_in_hours
    } = meta;

    return (
        <Fragment>
            <ToggleControl
                className="rrze-newsletter__recurrence-toggle-control rrze-newsletter__recurrence-toggle-control--separated"
                label={__("Make newsletter recurring?", "rrze-newsletter")}
                help={__(
                    "Make this newsletter recurring once it’s been sent.",
                    "rrze-newsletter"
                )}
                checked={rrze_newsletter_is_recurring}
                onChange={value => updateIsRecurring(value)}
            />
            {rrze_newsletter_is_recurring && (
                <Fragment>
                    <RangeControl
                        label={__(
                            "Recurrence interval in days",
                            "rrze-newsletter"
                        )}
                        value={rrze_newsletter_recurrence_in_days}
                        onChange={value => updateRecurrenceInDays(value)}
                        min={0}
                        max={30}
                        required
                    />
                    <RangeControl
                        label={__(
                            "Recurrence interval in hours",
                            "rrze-newsletter"
                        )}
                        value={rrze_newsletter_recurrence_in_hours}
                        onChange={value => updateRecurrenceInHours(value)}
                        min={1}
                        max={23}
                        required
                    />
                </Fragment>
            )}
        </Fragment>
    );
};

const mapStateToProps = select => {
    const { getEditedPostAttribute } = select("core/editor");

    return {
        meta: getEditedPostAttribute("meta")
    };
};

const mapDispatchToProps = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateIsRecurring: value =>
            editPost({ meta: { rrze_newsletter_is_recurring: value } })
    };
};

const mapDispatchToProps2 = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateRecurrenceInDays: value =>
            editPost({ meta: { rrze_newsletter_recurrence_in_days: value } })
    };
};

const mapDispatchToProps3 = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateRecurrenceInHours: value =>
            editPost({ meta: { rrze_newsletter_recurrence_in_hours: value } })
    };
};

export const RecurrenceSettings = compose([
    withSelect(mapStateToProps),
    withDispatch(mapDispatchToProps),
    withDispatch(mapDispatchToProps2),
    withDispatch(mapDispatchToProps3)
])(RecurrenceSettingsComponent);
