/**
 * External dependencies
 */
import { isEqual, find } from "lodash";

/**
 * WordPress dependencies
 */
import { compose } from "@wordpress/compose";
import { parse, serialize } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import { withDispatch, withSelect } from "@wordpress/data";
import { Fragment, useState, useEffect, useMemo } from "@wordpress/element";
import { Button, Modal, TextControl, Spinner } from "@wordpress/components";

/**
 * Plugin dependencies
 */
import { useLayoutsState } from "../../utils/hooks";
import { LAYOUT_CPT_SLUG, NEWSLETTER_CPT_SLUG } from "../../utils/consts";
import {
    isUserDefinedLayout,
    getBaseUrl,
    convertRelativeUrlsToAbsolute,
} from "../../utils";
import "./style.scss";
import NewsletterPreview from "../../components/newsletter-preview";

export default compose([
    withSelect((select) => {
        const { getEditedPostAttribute, isEditedPostEmpty, getCurrentPostId } =
            select("core/editor");
        const { getBlocks } = select("core/block-editor");
        const meta = getEditedPostAttribute("meta");
        const {
            rrze_newsletter_template_id: layoutId,
            rrze_newsletter_background_color,
            rrze_newsletter_font_body,
            rrze_newsletter_font_header,
        } = meta;
        return {
            layoutId,
            postTitle: getEditedPostAttribute("title"),
            postBlocks: getBlocks(),
            isEditedPostEmpty: isEditedPostEmpty(),
            currentPostId: getCurrentPostId(),
            stylingMeta: {
                rrze_newsletter_background_color,
                rrze_newsletter_font_body,
                rrze_newsletter_font_header,
            },
        };
    }),
    withDispatch((dispatch, { currentPostId, stylingMeta }) => {
        const { replaceBlocks } = dispatch("core/block-editor");
        const { editPost } = dispatch("core/editor");
        const { saveEntityRecord } = dispatch("core");
        return {
            replaceBlocks,
            saveLayoutIdMeta: (id) => {
                editPost({ meta: { rrze_newsletter_template_id: id } });
                saveEntityRecord("postType", NEWSLETTER_CPT_SLUG, {
                    id: currentPostId,
                    meta: { rrze_newsletter_template_id: id, ...stylingMeta },
                });
            },
            saveLayout: (payload) =>
                saveEntityRecord("postType", LAYOUT_CPT_SLUG, {
                    status: "publish",
                    ...payload,
                }),
        };
    }),
])(({
    saveLayoutIdMeta,
    layoutId,
    replaceBlocks,
    saveLayout,
    postBlocks,
    postTitle,
    isEditedPostEmpty,
    stylingMeta,
}) => {
    const [warningModalVisible, setWarningModalVisible] = useState(false);
    const { layouts, isFetchingLayouts } = useLayoutsState();

    const [usedLayout, setUsedLayout] = useState({});

    useEffect(() => {
        setUsedLayout(find(layouts, { ID: layoutId }) || {});
    }, [layouts.length]);

    const absolutePostContent = convertRelativeUrlsToAbsolute(
        usedLayout.post_content,
        getBaseUrl()
    );

    const blockPreview = useMemo(() => {
        return usedLayout.post_content ? parse(absolutePostContent) : null;
    }, [usedLayout]);

    const clearPost = () => {
        const clientIds = postBlocks.map(({ clientId }) => clientId);
        if (clientIds && clientIds.length) {
            replaceBlocks(clientIds, []);
        }
    };

    const [isSavingLayout, setIsSavingLayout] = useState(false);
    const [isManageModalVisible, setIsManageModalVisible] = useState(null);
    const [newLayoutName, setNewLayoutName] = useState(postTitle);

    const handleLayoutUpdate = (updatedLayout) => {
        setIsSavingLayout(false);
        // Set this new layout as the newsletter's layout
        saveLayoutIdMeta(updatedLayout.id);

        // Update the layout preview
        // The shape of this data is different than the API response for CPT
        setUsedLayout({
            ...updatedLayout,
            post_content: updatedLayout.content.raw,
            post_title: updatedLayout.title.raw,
            post_type: LAYOUT_CPT_SLUG,
        });
    };

    const postContent = useMemo(() => serialize(postBlocks), [postBlocks]);
    const isPostContentSameAsLayout =
        postContent === usedLayout.post_content &&
        isEqual(usedLayout.meta, stylingMeta);

    const handleSaveAsLayout = () => {
        setIsSavingLayout(true);
        const updatePayload = {
            title: newLayoutName,
            content: postContent,
            meta: stylingMeta,
        };
        saveLayout(updatePayload).then((newLayout) => {
            setIsManageModalVisible(false);

            handleLayoutUpdate(newLayout);
        });
    };

    const handeLayoutUpdate = () => {
        if (
            confirm(
                __(
                    "Are you sure you want to overwrite this layout?",
                    "rrze-newsletter"
                )
            )
        ) {
            setIsSavingLayout(true);
            const updatePayload = {
                id: usedLayout.ID,
                content: postContent,
                meta: stylingMeta,
            };
            saveLayout(updatePayload).then(handleLayoutUpdate);
        }
    };

    const isUsingCustomLayout = isUserDefinedLayout(usedLayout);

    return (
        <Fragment>
            {Boolean(layoutId && isFetchingLayouts) && (
                <div className="rrze-newsletter-layouts__spinner">
                    <Spinner />
                </div>
            )}
            {blockPreview !== null && (
                <div className="rrze-newsletter-layouts">
                    <div className="rrze-newsletter-layouts__item">
                        <div className="rrze-newsletter-layouts__item-preview">
                            <NewsletterPreview
                                meta={usedLayout.meta}
                                blocks={blockPreview}
                                viewportWidth={600}
                            />
                        </div>
                        <div className="rrze-newsletter-layouts__item-label">
                            {usedLayout.post_title}
                        </div>
                    </div>
                </div>
            )}
            <div className="rrze-newsletter-buttons-group rrze-newsletter-buttons-group--spaced">
                <Button
                    isPrimary
                    disabled={isEditedPostEmpty || isSavingLayout}
                    onClick={() => setIsManageModalVisible(true)}
                >
                    {__("Save new layout", "rrze-newsletter")}
                </Button>

                {isUsingCustomLayout && (
                    <Button
                        isSecondary
                        disabled={isPostContentSameAsLayout || isSavingLayout}
                        onClick={handeLayoutUpdate}
                    >
                        {__("Update layout", "rrze-newsletter")}
                    </Button>
                )}
            </div>

            <br />

            <Button
                isSecondary
                isLink
                isDestructive
                onClick={() => setWarningModalVisible(true)}
            >
                {__("Reset newsletter layout", "rrze-newsletter")}
            </Button>

            {isManageModalVisible && (
                <Modal
                    className="rrze-newsletter__modal"
                    title={__("Save newsletter as a layout", "rrze-newsletter")}
                    onRequestClose={() => setIsManageModalVisible(null)}
                >
                    <TextControl
                        label={__("Title", "rrze-newsletter")}
                        disabled={isSavingLayout}
                        value={newLayoutName}
                        onChange={setNewLayoutName}
                    />
                    <Button
                        isPrimary
                        disabled={isSavingLayout || newLayoutName.length === 0}
                        onClick={handleSaveAsLayout}
                    >
                        {__("Save", "rrze-newsletter")}
                    </Button>
                    <Button
                        isSecondary
                        onClick={() => setIsManageModalVisible(null)}
                    >
                        {__("Cancel", "rrze-newsletter")}
                    </Button>
                </Modal>
            )}

            {warningModalVisible && (
                <Modal
                    className="rrze-newsletter__modal"
                    title={__(
                        "Overwrite newsletter content?",
                        "rrze-newsletter"
                    )}
                    onRequestClose={() => setWarningModalVisible(false)}
                >
                    <p>
                        {__(
                            "Changing the newsletter's layout will remove any customizations or edits you have already made.",
                            "rrze-newsletter"
                        )}
                    </p>
                    <Button
                        isPrimary
                        onClick={() => {
                            clearPost();
                            saveLayoutIdMeta(-1);
                            setWarningModalVisible(false);
                        }}
                    >
                        {__("Reset layout", "rrze-newsletter")}
                    </Button>
                    <Button
                        isSecondary
                        onClick={() => setWarningModalVisible(false)}
                    >
                        {__("Cancel", "rrze-newsletter")}
                    </Button>
                </Modal>
            )}
        </Fragment>
    );
});
