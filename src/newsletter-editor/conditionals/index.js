/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { ToggleControl, SelectControl } from "@wordpress/components";
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
        updateHasConditionals,
        updateOperator,
        updateRssNoItems,
        updateIcsNoItems
    } = props;
    const {
        rrze_newsletter_has_conditionals,
        rrze_newsletter_conditionals_operator,
        rrze_newsletter_conditionals_rss_block,
        rrze_newsletter_conditionals_ics_block
    } = meta;

    return (
        <Fragment>
            <ToggleControl
                className="rrze-newsletter__conditionals-toggle-control rrze-newsletter__conditionals-toggle-control--separated"
                label={__("Conditionals", "rrze-newsletter")}
                help={__(
                    "Apply conditionals for sending the newsletter.",
                    "rrze-newsletter"
                )}
                checked={rrze_newsletter_has_conditionals}
                onChange={value => updateHasConditionals(value)}
            />
            {rrze_newsletter_has_conditionals && (
                <Fragment>
                    <SelectControl
                        label={__("Logical Operator", "rrze-newsletter")}
                        value={rrze_newsletter_conditionals_operator}
                        options={[
                            {
                                label: __("OR", "rrze-newsletter"),
                                value: "or"
                            },
                            {
                                label: __("AND", "rrze-newsletter"),
                                value: "and"
                            }
                        ]}
                        onChange={value => updateOperator(value)}
                    />
                    <ToggleControl
                        className="rrze-newsletter__conditionals-toggle-control"
                        label={__("RSS Block Condition", "rrze-newsletter")}
                        help={__(
                            "If no feed items are available, the newsletter will not be sent.",
                            "rrze-newsletter"
                        )}
                        checked={rrze_newsletter_conditionals_rss_block}
                        onChange={value => updateRssNoItems(value)}
                    />
                    <ToggleControl
                        className="rrze-newsletter__conditionals-toggle-control"
                        label={__("ICS Block Condition", "rrze-newsletter")}
                        help={__(
                            "If no events are available, the newsletter will not be sent.",
                            "rrze-newsletter"
                        )}
                        checked={rrze_newsletter_conditionals_ics_block}
                        onChange={value => updateIcsNoItems(value)}
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
        updateHasConditionals: value =>
            editPost({ meta: { rrze_newsletter_has_conditionals: value } })
    };
};

const mapDispatchToProps2 = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateOperator: value =>
            editPost({ meta: { rrze_newsletter_conditionals_operator: value } })
    };
};

const mapDispatchToProps3 = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateRssNoItems: value =>
            editPost({
                meta: { rrze_newsletter_conditionals_rss_block: value }
            })
    };
};

const mapDispatchToProps4 = dispatch => {
    const { editPost } = dispatch("core/editor");

    return {
        updateIcsNoItems: value =>
            editPost({
                meta: { rrze_newsletter_conditionals_ics_block: value }
            })
    };
};

export const ConditionalsSettings = compose([
    withSelect(mapStateToProps),
    withDispatch(mapDispatchToProps),
    withDispatch(mapDispatchToProps2),
    withDispatch(mapDispatchToProps3),
    withDispatch(mapDispatchToProps4)
])(RecurrenceSettingsComponent);
