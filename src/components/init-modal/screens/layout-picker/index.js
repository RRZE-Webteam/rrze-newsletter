/**
 * External dependencies
 */
import classnames from "classnames";
import { find } from "lodash";

/**
 * WordPress dependencies
 */
import { parse } from "@wordpress/blocks";
import { Fragment, useMemo, useState, useEffect } from "@wordpress/element";
import { compose } from "@wordpress/compose";
import { withSelect, withDispatch, select } from "@wordpress/data";
import { Button, Spinner } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

/**
 * Plugin dependencies
 */
import { BLANK_LAYOUT_ID } from "../../../../utils/consts";
import {
    isUserDefinedLayout,
    getBaseUrl,
    convertRelativeUrlsToAbsolute,
} from "../../../../utils";
import { useLayoutsState } from "../../../../utils/hooks";
import SingleLayoutPreview from "./SingleLayoutPreview";
import NewsletterPreview from "../../../newsletter-preview";

const LAYOUTS_TABS = [
    {
        title: __("Prebuilt", "rrze-newsletter"),
        filter: (layout) => layout.post_author === undefined,
    },
    {
        title: __("Saved", "rrze-newsletter"),
        filter: isUserDefinedLayout,
        isEditable: true,
    },
];

const LayoutPicker = ({
    getBlocks,
    insertBlocks,
    replaceBlocks,
    savePost,
    setNewsletterMeta,
}) => {
    const { layouts, isFetchingLayouts, deleteLayoutPost } = useLayoutsState();

    const insertLayout = (layoutId) => {
        const { post_content: content, meta = {} } =
            find(layouts, { ID: layoutId }) || {};

        const blocksToInsert = content ? parse(content) : [];

        const existingBlocksIds = getBlocks().map(({ clientId }) => clientId);

        if (existingBlocksIds.length) {
            replaceBlocks(existingBlocksIds, blocksToInsert);
        } else {
            insertBlocks(blocksToInsert);
        }

        const metaPayload = {
            rrze_newsletter_template_id: layoutId,
            ...meta,
        };

        setNewsletterMeta(metaPayload);
        setTimeout(savePost, 1);
    };

    const [selectedLayoutId, setSelectedLayoutId] = useState(null);

    const layoutPreviewProps = useMemo(() => {
        const layout =
            selectedLayoutId && find(layouts, { ID: selectedLayoutId });
        if (!layout) {
            return null;
        }

        const absolutePostContent = convertRelativeUrlsToAbsolute(
            layout.post_content,
            getBaseUrl()
        );

        return {
            blocks: parse(absolutePostContent),
            meta: layout.meta,
        };
    }, [selectedLayoutId, layouts.length]);

    const canRenderPreview =
        layoutPreviewProps && layoutPreviewProps.blocks.length > 0;

    const renderPreview = () =>
        canRenderPreview ? (
            <NewsletterPreview {...layoutPreviewProps} />
        ) : (
            <p>{__("Select a layout to preview.", "rrze-newsletter")}</p>
        );

    const [activeTabIndex, setActiveTabIndex] = useState(0);
    const activeTab = LAYOUTS_TABS[activeTabIndex];
    const displayedLayouts = layouts.filter(activeTab.filter);

    // Switch tab to user layouts if there are any.
    useEffect(() => {
        if (layouts.filter(isUserDefinedLayout).length) {
            setActiveTabIndex(1);
        }
    }, [layouts.length]);

    return (
        <Fragment>
            <div className="rrze-newsletter-modal__content">
                <div className="rrze-newsletter-tabs rrze-newsletter-buttons-group">
                    {LAYOUTS_TABS.map(({ title }, i) => (
                        <Button
                            key={i}
                            disabled={isFetchingLayouts}
                            className={classnames(
                                "rrze-newsletter-tabs__button",
                                {
                                    "rrze-newsletter-tabs__button--is-active":
                                        !isFetchingLayouts &&
                                        i === activeTabIndex,
                                }
                            )}
                            onClick={() => setActiveTabIndex(i)}
                        >
                            {title}
                        </Button>
                    ))}
                </div>
                <div
                    className={classnames("rrze-newsletter-modal__layouts", {
                        "rrze-newsletter-modal__layouts--loading":
                            isFetchingLayouts,
                    })}
                >
                    {isFetchingLayouts ? (
                        <Spinner />
                    ) : (
                        <div
                            className={classnames({
                                "rrze-newsletter-layouts":
                                    displayedLayouts.length > 0,
                            })}
                        >
                            {displayedLayouts.length ? (
                                displayedLayouts.map((layout) => (
                                    <SingleLayoutPreview
                                        key={layout.ID}
                                        selectedLayoutId={selectedLayoutId}
                                        setSelectedLayoutId={
                                            setSelectedLayoutId
                                        }
                                        deleteHandler={deleteLayoutPost}
                                        isEditable={activeTab.isEditable}
                                        {...layout}
                                    />
                                ))
                            ) : (
                                <span>
                                    {__(
                                        'Turn any newsletter to a layout via the "Layout" sidebar menu in the editor.',
                                        "rrze-newsletter"
                                    )}
                                </span>
                            )}
                        </div>
                    )}
                </div>

                <div className="rrze-newsletter-modal__preview">
                    {!isFetchingLayouts && renderPreview()}
                </div>
            </div>
            <div className="rrze-newsletter-modal__action-buttons">
                <Button
                    isSecondary
                    onClick={() => insertLayout(BLANK_LAYOUT_ID)}
                >
                    {__("Start With A Blank Layout", "rrze-newsletter")}
                </Button>
                <span className="separator"> </span>
                <Button
                    isPrimary
                    disabled={isFetchingLayouts || !canRenderPreview}
                    onClick={() => insertLayout(selectedLayoutId)}
                >
                    {__("Use Selected Layout", "rrze-newsletter")}
                </Button>
            </div>
        </Fragment>
    );
};

export default compose([
    withSelect((select) => {
        const { getBlocks } = select("core/block-editor");
        return {
            getBlocks,
        };
    }),
    withDispatch((dispatch) => {
        const { savePost, editPost } = dispatch("core/editor");
        const { insertBlocks, replaceBlocks } = dispatch("core/block-editor");
        return {
            savePost,
            insertBlocks,
            replaceBlocks,
            setNewsletterMeta: (meta) => editPost({ meta }),
        };
    }),
])(LayoutPicker);
