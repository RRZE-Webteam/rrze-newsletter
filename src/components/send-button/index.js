/**
 * WordPress dependencies
 */
import { withDispatch, withSelect } from "@wordpress/data";
import { compose } from "@wordpress/compose";
import { Button, Modal, Notice } from "@wordpress/components";
import { Fragment, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";

/**
 * External dependencies
 */
import { get } from "lodash";

import "./style.scss";

export default compose([
    withDispatch((dispatch) => {
        const { editPost, savePost } = dispatch("core/editor");
        return { editPost, savePost };
    }),
    withSelect((select, { forceIsDirty }) => {
        const {
            getCurrentPost,
            getEditedPostAttribute,
            getEditedPostVisibility,
            isEditedPostPublishable,
            isEditedPostSaveable,
            isSavingPost,
            isEditedPostBeingScheduled,
            isCurrentPostPublished,
        } = select("core/editor");
        return {
            isPublishable: forceIsDirty || isEditedPostPublishable(),
            isSaveable: isEditedPostSaveable(),
            status: getEditedPostAttribute("status"),
            isSaving: isSavingPost(),
            isEditedPostBeingScheduled: isEditedPostBeingScheduled(),
            hasPublishAction: get(
                getCurrentPost(),
                ["_links", "wp:action-publish"],
                false
            ),
            visibility: getEditedPostVisibility(),
            meta: getEditedPostAttribute("meta"),
            isPublished: isCurrentPostPublished(),
        };
    }),
])(
    ({
        editPost,
        savePost,
        isPublishable,
        isSaveable,
        isSaving,
        status,
        isEditedPostBeingScheduled,
        hasPublishAction,
        visibility,
        meta,
        isPublished,
    }) => {
        const { newsletterValidationErrors = [], rrze_newsletter_is_public } = meta;

        const isButtonEnabled =
            (isPublishable || isEditedPostBeingScheduled) &&
            isSaveable &&
            !isPublished &&
            !isSaving &&
            0 === newsletterValidationErrors.length;
        let label;
        if (isPublished) {
            if (isSaving) label = __("Sending", "rrze-newsletter");
            else {
                label = rrze_newsletter_is_public
                    ? __("Sent and Published", "rrze-newsletter")
                    : __("Sent", "rrze-newsletter");
            }
        } else if ("future" === status) {
            // Scheduled to be sent
            label = __("Scheduled", "rrze-newsletter");
        } else if (isEditedPostBeingScheduled) {
            label = __("Schedule sending", "rrze-newsletter");
        } else {
            label = rrze_newsletter_is_public
                ? __("Send and Publish", "rrze-newsletter")
                : __("Send", "rrze-newsletter");
        }

        let publishStatus;
        if (!hasPublishAction) {
            publishStatus = "pending";
        } else if (visibility === "private") {
            publishStatus = "private";
        } else if (isEditedPostBeingScheduled) {
            publishStatus = "future";
        } else {
            publishStatus = "publish";
        }

        const triggerNewsletterSend = () => {
            editPost({ status: publishStatus });
            savePost();
        };

        const [modalVisible, setModalVisible] = useState(false);

        // For sent newsletters, display the generic button text.
        if (isPublished) {
            return (
                <Button
                    className="editor-post-publish-button"
                    isBusy={isSaving}
                    isPrimary
                    disabled={isSaving}
                    onClick={savePost}
                >
                    {isSaving
                        ? __("Updating...", "rrze-newsletter")
                        : __("Update", "rrze-newsletter")}
                </Button>
            );
        }

        return (
            <Fragment>
                <Button
                    className="editor-post-publish-button"
                    isBusy={isSaving && "publish" === status}
                    isPrimary
                    onClick={async () => {
                        await savePost();
                        setModalVisible(true);
                    }}
                    disabled={!isButtonEnabled}
                >
                    {label}
                </Button>
                {modalVisible && (
                    <Modal
                        className="rrze-newsletter__modal"
                        title={__("Send your newsletter?", "rrze-newsletter")}
                        onRequestClose={() => setModalVisible(false)}
                    >
                        {newsletterValidationErrors.length ? (
                            <Notice status="error" isDismissible={false}>
                                {__(
                                    "The following errors prevent the newsletter from being sent:",
                                    "rrze-newsletter"
                                )}
                                <ul>
                                    {newsletterValidationErrors.map(
                                        (error, i) => (
                                            <li key={i}>{error}</li>
                                        )
                                    )}
                                </ul>
                            </Notice>
                        ) : null}
                        <Button
                            isPrimary
                            disabled={newsletterValidationErrors.length > 0}
                            onClick={() => {
                                triggerNewsletterSend();
                                setModalVisible(false);
                            }}
                        >
                            {__("Send", "rrze-newsletter")}
                        </Button>
                        <Button
                            isSecondary
                            onClick={() => setModalVisible(false)}
                        >
                            {__("Cancel", "rrze-newsletter")}
                        </Button>
                    </Modal>
                )}
            </Fragment>
        );
    }
);
