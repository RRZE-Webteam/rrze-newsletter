/**
 * External dependencies
 */
import { isUndefined, find, pickBy, get } from "lodash";

/**
 * WordPress dependencies
 */
import { registerBlockType } from "@wordpress/blocks";
import { __ } from "@wordpress/i18n";
import { withSelect, withDispatch } from "@wordpress/data";
import { compose } from "@wordpress/compose";
import {
    RangeControl,
    Button,
    ToggleControl,
    FontSizePicker,
    ColorPicker,
    PanelBody,
    MenuItem,
    MenuGroup,
    Toolbar,
    ToolbarDropdownMenu,
} from "@wordpress/components";
import {
    InnerBlocks,
    InspectorControls,
    BlockControls,
} from "@wordpress/block-editor";
import { Fragment, useEffect, useMemo, useState } from "@wordpress/element";
import { Icon, check, pages } from "@wordpress/icons";

/**
 * Plugin dependencies
 */
import "./style.scss";
import "./deduplication";
import blockDefinition from "./block.json";
import { getTemplateBlocks, convertBlockSerializationFormat } from "./utils";
import QueryControlsSettings from "./query-controls";
import { POST_INSERTER_BLOCK_NAME, POST_INSERTER_STORE_NAME } from "./consts";
import PostsPreview from "./posts-preview";

const PostInserterBlock = ({
    setAttributes,
    attributes,
    postList,
    replaceBlocks,
    setHandledPostsIds,
    setInsertedPostsIds,
    removeBlock,
    blockEditorSettings,
}) => {
    const [isReady, setIsReady] = useState(!attributes.displayFeaturedImage);
    const stringifiedPostList = JSON.stringify(postList);

    // Stringify added to minimize flicker.
    const templateBlocks = useMemo(
        () => getTemplateBlocks(postList, attributes),
        [stringifiedPostList, attributes]
    );

    const stringifiedTemplateBlocks = JSON.stringify(templateBlocks);

    useEffect(() => {
        const { isDisplayingSpecificPosts, specificPosts } = attributes;

        // No spinner if we're not dealing with images.
        if (!attributes.displayFeaturedImage) {
            return setIsReady(true);
        }

        // No spinner if we're in the middle of selecting a specific post.
        if (isDisplayingSpecificPosts && 0 === specificPosts.length) {
            return setIsReady(true);
        }

        // Reset ready state.
        setIsReady(false);

        // If we have a post to show, check for featured image blocks.
        if (0 < postList.length) {
            // Find all the featured images.
            const images = [];
            postList.map(
                (post) =>
                    post.featured_media && images.push(post.featured_media)
            );

            // If no posts have featured media, skip loading state.
            if (0 === images.length) {
                return setIsReady(true);
            }

            // Wait for image blocks to be added to the BlockPreview.
            const imageBlocks =
                stringifiedTemplateBlocks.match(/\"name\":\"core\/image\"/g) ||
                [];

            // Preview is ready once all image blocks are accounted for.
            if (imageBlocks.length === images.length) {
                setIsReady(true);
            }
        }
    }, [stringifiedPostList, stringifiedTemplateBlocks]);

    const innerBlocksToInsert = templateBlocks.map(
        convertBlockSerializationFormat
    );
    useEffect(() => {
        setAttributes({ innerBlocksToInsert });
    }, [JSON.stringify(innerBlocksToInsert)]);

    const handledPostIds = postList.map((post) => post.id);

    useEffect(() => {
        if (attributes.areBlocksInserted) {
            replaceBlocks(templateBlocks);
            setInsertedPostsIds(handledPostIds);
        }
    }, [attributes.areBlocksInserted]);

    useEffect(() => {
        if (!attributes.preventDeduplication) {
            setHandledPostsIds(handledPostIds);
            return removeBlock;
        }
    }, [handledPostIds.join()]);

    const blockControlsImages = [
        {
            icon: "align-none",
            title: __("Show image on top", "rrze-newsletter"),
            isActive: attributes.featuredImageAlignment === "top",
            onClick: () => setAttributes({ featuredImageAlignment: "top" }),
        },
        {
            icon: "align-pull-left",
            title: __("Show image on left", "rrze-newsletter"),
            isActive: attributes.featuredImageAlignment === "left",
            onClick: () => setAttributes({ featuredImageAlignment: "left" }),
        },
        {
            icon: "align-pull-right",
            title: __("Show image on right", "rrze-newsletter"),
            isActive: attributes.featuredImageAlignment === "right",
            onClick: () => setAttributes({ featuredImageAlignment: "right" }),
        },
    ];

    const imageSizeOptions = [
        {
            value: "small",
            name: __("Small", "rrze-newsletter"),
        },
        {
            value: "medium",
            name: __("Medium", "rrze-newsletter"),
        },
        {
            value: "large",
            name: __("Large", "rrze-newsletter"),
        },
    ];

    return attributes.areBlocksInserted ? null : (
        <Fragment>
            <InspectorControls>
                <PanelBody
                    title={__("Post content settings", "rrze-newsletter")}
                >
                    <ToggleControl
                        label={__("Post subtitle", "rrze-newsletter")}
                        checked={attributes.displayPostSubtitle}
                        onChange={(value) =>
                            setAttributes({ displayPostSubtitle: value })
                        }
                    />
                    <ToggleControl
                        label={__("Post excerpt", "rrze-newsletter")}
                        checked={attributes.displayPostExcerpt}
                        onChange={(value) =>
                            setAttributes({ displayPostExcerpt: value })
                        }
                    />
                    {attributes.displayPostExcerpt && (
                        <RangeControl
                            label={__(
                                "Max number of words in excerpt",
                                "rrze-newsletter"
                            )}
                            value={attributes.excerptLength}
                            onChange={(value) =>
                                setAttributes({ excerptLength: value })
                            }
                            min={10}
                            max={100}
                        />
                    )}
                    <ToggleControl
                        label={__("Date", "rrze-newsletter")}
                        checked={attributes.displayPostDate}
                        onChange={(value) =>
                            setAttributes({ displayPostDate: value })
                        }
                    />
                    <ToggleControl
                        label={__("Featured image", "rrze-newsletter")}
                        checked={attributes.displayFeaturedImage}
                        onChange={(value) =>
                            setAttributes({ displayFeaturedImage: value })
                        }
                    />
                    <ToggleControl
                        label={__("Author's name", "rrze-newsletter")}
                        checked={attributes.displayAuthor}
                        onChange={(value) =>
                            setAttributes({ displayAuthor: value })
                        }
                    />
                    <ToggleControl
                        label={__(
                            '"Continue reading…" link',
                            "rrze-newsletter"
                        )}
                        checked={attributes.displayContinueReading}
                        onChange={(value) =>
                            setAttributes({ displayContinueReading: value })
                        }
                    />
                </PanelBody>
                <PanelBody
                    title={__("Sorting and filtering", "rrze-newsletter")}
                >
                    <QueryControlsSettings
                        attributes={attributes}
                        setAttributes={setAttributes}
                    />
                </PanelBody>
                <PanelBody title={__("Text style", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={attributes.textFontSize}
                        onChange={(value) => {
                            return setAttributes({ textFontSize: value });
                        }}
                    />
                    <ColorPicker
                        color={attributes.textColor || ""}
                        onChangeComplete={(value) =>
                            setAttributes({ textColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
                <PanelBody title={__("Heading style", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={attributes.headingFontSize}
                        onChange={(value) =>
                            setAttributes({ headingFontSize: value })
                        }
                    />
                    <ColorPicker
                        color={attributes.headingColor || ""}
                        onChangeComplete={(value) =>
                            setAttributes({ headingColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
                <PanelBody title={__("Subtitle style", "rrze-newsletter")}>
                    <FontSizePicker
                        fontSizes={blockEditorSettings.fontSizes}
                        value={attributes.subHeadingFontSize}
                        onChange={(value) =>
                            setAttributes({ subHeadingFontSize: value })
                        }
                    />
                    <ColorPicker
                        color={attributes.subHeadingColor || ""}
                        onChangeComplete={(value) =>
                            setAttributes({ subHeadingColor: value.hex })
                        }
                        disableAlpha
                    />
                </PanelBody>
            </InspectorControls>

            <BlockControls>
                {attributes.displayFeaturedImage && (
                    <>
                        <Toolbar controls={blockControlsImages} />
                        {(attributes.featuredImageAlignment === "left" ||
                            attributes.featuredImageAlignment === "right") && (
                            <Toolbar>
                                <ToolbarDropdownMenu
                                    text={__("Image Size", "rrze-newsletter")}
                                    icon={null}
                                >
                                    {({ onClose }) => (
                                        <MenuGroup>
                                            {imageSizeOptions.map((entry) => {
                                                return (
                                                    <MenuItem
                                                        icon={
                                                            (attributes.featuredImageSize ===
                                                                entry.value ||
                                                                (!attributes.featuredImageSize &&
                                                                    entry.value ===
                                                                        "large")) &&
                                                            check
                                                        }
                                                        isSelected={
                                                            attributes.featuredImageSize ===
                                                            entry.value
                                                        }
                                                        key={entry.value}
                                                        onClick={() => {
                                                            setAttributes({
                                                                featuredImageSize:
                                                                    entry.value,
                                                            });
                                                        }}
                                                        onClose={onClose}
                                                        role="menuitemradio"
                                                    >
                                                        {entry.name}
                                                    </MenuItem>
                                                );
                                            })}
                                        </MenuGroup>
                                    )}
                                </ToolbarDropdownMenu>
                            </Toolbar>
                        )}
                    </>
                )}
            </BlockControls>

            <div
                className={`rrze-posts-inserter ${
                    !isReady ? "rrze-posts-inserter--loading" : ""
                }`}
            >
                <div className="rrze-posts-inserter__header">
                    <Icon icon={pages} />
                    <span>{__("Posts Inserter", "rrze-newsletter")}</span>
                </div>
                <PostsPreview
                    isReady={isReady}
                    blocks={templateBlocks}
                    viewportWidth={
                        "top" === attributes.featuredImageAlignment ||
                        !attributes.displayFeaturedImage
                            ? 574
                            : 1148
                    }
                    className={
                        attributes.displayFeaturedImage
                            ? "image-" + attributes.featuredImageAlignment
                            : null
                    }
                />
                <div className="rrze-posts-inserter__footer">
                    <Button
                        isPrimary
                        onClick={() =>
                            setAttributes({ areBlocksInserted: true })
                        }
                    >
                        {__("Insert posts", "rrze-newsletter")}
                    </Button>
                </div>
            </div>
        </Fragment>
    );
};

const PostInserterBlockWithSelect = compose([
    withSelect((select, props) => {
        const {
            postsToShow,
            order,
            orderBy,
            postType,
            categories,
            isDisplayingSpecificPosts,
            specificPosts,
            preventDeduplication,
            tags,
            tagExclusions,
            categoryExclusions,
            excerptLength,
            displaySponsoredPosts,
        } = props.attributes;
        const { getEntityRecords, getMedia } = select("core");
        const { getSelectedBlock, getBlocks, getSettings } =
            select("core/block-editor");
        const catIds =
            categories && categories.length > 0
                ? categories.map((cat) => cat.id)
                : [];

        const { getHandledPostIds } = select(POST_INSERTER_STORE_NAME);
        const exclude = getHandledPostIds(props.clientId);

        let posts = [];
        const isHandlingSpecificPosts =
            isDisplayingSpecificPosts && specificPosts.length > 0;
        const query = {
            categories: catIds,
            tags,
            order,
            orderby: orderBy,
            per_page: postsToShow,
            exclude: preventDeduplication ? [] : exclude,
            categories_exclude: categoryExclusions,
            tags_exclude: tagExclusions,
            excerpt_length: excerptLength,
            exclude_sponsors: displaySponsoredPosts ? 0 : 1,
        };

        if (!isDisplayingSpecificPosts || isHandlingSpecificPosts) {
            const postListQuery = isDisplayingSpecificPosts
                ? { include: specificPosts.map((post) => post.id) }
                : pickBy(query, (value) => !isUndefined(value));

            posts = getEntityRecords("postType", postType, postListQuery) || [];
        }

        // Order posts in the order as they appear in the input
        if (isHandlingSpecificPosts) {
            posts = specificPosts.reduce((all, { id }) => {
                const found = find(posts, ["id", id]);
                return found ? [...all, found] : all;
            }, []);
        }

        return {
            // Not used by the component, but needed in deduplication.
            existingBlocks: getBlocks(),
            blockEditorSettings: getSettings(),
            selectedBlock: getSelectedBlock(),
            postList: posts.map((post) => {
                if (post.featured_media) {
                    const image = getMedia(post.featured_media);
                    const fallbackImageURL = get(image, "source_url", null);
                    const featuredImageMediumURL =
                        get(
                            image,
                            ["media_details", "sizes", "medium", "source_url"],
                            null
                        ) || fallbackImageURL;
                    const featuredImageLargeURL =
                        get(
                            image,
                            ["media_details", "sizes", "large", "source_url"],
                            null
                        ) || fallbackImageURL;
                    return {
                        ...post,
                        featuredImageMediumURL,
                        featuredImageLargeURL,
                    };
                }
                return post;
            }),
        };
    }),
    withDispatch((dispatch, props) => {
        const { replaceBlocks } = dispatch("core/block-editor");
        const { setHandledPostsIds, setInsertedPostsIds, removeBlock } =
            dispatch(POST_INSERTER_STORE_NAME);
        return {
            replaceBlocks: (blocks) => {
                replaceBlocks(props.selectedBlock.clientId, blocks);
            },
            setHandledPostsIds: (ids) => setHandledPostsIds(ids, props),
            setInsertedPostsIds,
            removeBlock: () => removeBlock(props.clientId),
        };
    }),
])(PostInserterBlock);

export default () => {
    registerBlockType(POST_INSERTER_BLOCK_NAME, {
        ...blockDefinition,
        title: "Post Inserter",
        icon: <Icon icon={pages} />,
        edit: PostInserterBlockWithSelect,
        save: () => <InnerBlocks.Content />,
    });
};
