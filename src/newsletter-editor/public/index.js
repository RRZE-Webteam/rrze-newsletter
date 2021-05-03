/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { ToggleControl } from "@wordpress/components";
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { Fragment } from "@wordpress/element";

/**
 * Plugin dependencies
 */
import "./style.scss";

const PublicSettingsComponent = props => {
    const { meta, updateIsPublic } = props;
    const { rrze_newsletter_is_public } = meta;

    return (
        <Fragment>
            <ToggleControl
                className="rrze-newsletter__public-toggle-control"
                label={__("Make newsletter page public?", "rrze-newsletter")}
                help={__(
                    "Make this newsletter viewable as a public page once it’s been sent.",
                    "rrze-newsletter"
                )}
                checked={rrze_newsletter_is_public}
                onChange={value => updateIsPublic(value)}
            />
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
        updateIsPublic: value =>
            editPost({ meta: { rrze_newsletter_is_public: value } })
    };
};

export const PublicSettings = compose([
    withSelect(mapStateToProps),
    withDispatch(mapDispatchToProps)
])(PublicSettingsComponent);
