/**
 * External dependencies
 */
import { isEmpty } from "lodash";

/**
 * WordPress dependencies
 */
import { compose } from "@wordpress/compose";
import { withDispatch, withSelect } from "@wordpress/data";
import { createPortal, useEffect, useState } from "@wordpress/element";
import { registerPlugin } from "@wordpress/plugins";

/**
 * Plugin dependencies
 */
import withApiHandler from "../../components/with-api-handler";
import SendButton from "../../components/send-button";
import "./style.scss";

const Editor = compose([
    withApiHandler(),
    withSelect(select => {
        const {
            getCurrentPostId,
            getCurrentPostAttribute,
            getEditedPostAttribute,
            isPublishingPost,
            isSavingPost,
            isCleanNewPost
        } = select("core/editor");
        const { getActiveGeneralSidebarName } = select("core/edit-post");
        const meta = getEditedPostAttribute("meta");
        const status = getCurrentPostAttribute("status");
        const sentDate = getCurrentPostAttribute("date");

        return {
            isCleanNewPost: isCleanNewPost(),
            postId: getCurrentPostId(),
            isReady: meta.newsletterValidationErrors
                ? meta.newsletterValidationErrors.length === 0
                : false,
            activeSidebarName: getActiveGeneralSidebarName(),
            isPublishingOrSavingPost: isSavingPost() || isPublishingPost(),
            status,
            sentDate,
            isPublic: meta.rrze_newsletter_is_public
        };
    }),
    withDispatch(dispatch => {
        const {
            lockPostAutosaving,
            lockPostSaving,
            unlockPostSaving,
            editPost
        } = dispatch("core/editor");
        const { createNotice } = dispatch("core/notices");
        return {
            lockPostAutosaving,
            lockPostSaving,
            unlockPostSaving,
            editPost,
            createNotice
        };
    })
])(props => {
    const [publishEl] = useState(document.createElement("div"));
    // Create alternate publish button
    useEffect(() => {
        const publishButton = document.getElementsByClassName(
            "editor-post-publish-button__button"
        )[0];
        publishButton.parentNode.insertBefore(publishEl, publishButton);
    }, []);

    // Set color palette option.
    useEffect(() => {
        if (isEmpty(props.colorPalette)) {
            return;
        }
        props.apiFetchWithErrorHandling({
            path: `/rrze-newsletter/v1/color-palette`,
            data: props.colorPalette,
            method: "POST"
        });
    }, [JSON.stringify(props.colorPalette)]);

    // Lock or unlock post publishing.
    useEffect(() => {
        if (props.isReady) {
            props.unlockPostSaving("rrze-newsletter-post-lock");
        } else {
            props.lockPostSaving("rrze-newsletter-post-lock");
        }
    }, [props.isReady]);

    useEffect(() => {
        if ("publish" === props.status && !props.isPublishingOrSavingPost) {
            const dateTime = props.sentDate
                ? new Date(props.sentDate).toLocaleString()
                : "";

            // Lock autosaving after a newsletter is sent.
            props.lockPostAutosaving();

            // Show an editor notice if the newsletter has been sent.
            props.createNotice("success", props.successNote + dateTime, {
                isDismissible: false
            });
        }
    }, [props.status]);

    useEffect(() => {
        // Hide post title if the newsletter is a not a public post.
        const editorTitleEl = document.querySelector(".editor-post-title");
        if (editorTitleEl) {
            editorTitleEl.classList[props.isPublic ? "remove" : "add"](
                "rrze-newsletter-post-title-hidden"
            );
        }
    }, [props.isPublic]);

    return createPortal(<SendButton />, publishEl);
});

export default () => {
    registerPlugin("rrze-newsletter-edit", {
        render: Editor
    });
};
