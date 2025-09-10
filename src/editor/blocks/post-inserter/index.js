/**
 * External dependencies
 */
import { isUndefined, pickBy } from "lodash";

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
    ToolbarGroup,
    ToolbarButton,
    ToolbarDropdownMenu,
    Notice,
    Icon,
} from "@wordpress/components";
import {
    InnerBlocks,
    InspectorControls,
    BlockControls,
    useBlockProps,
} from "@wordpress/block-editor";
import { Fragment, useEffect, useMemo, useState } from "@wordpress/element";
import { pages, alignCenter, alignLeft, alignRight } from "@wordpress/icons";

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
    isLoadingPosts,
    replaceBlocks,
    setHandledPostsIds,
    setInsertedPostsIds,
    removeBlock,
    blockEditorSettings,
}) => {
    const [isReady, setIsReady] = useState(!attributes.displayFeaturedImage);

    // Stringify to minimize flicker caused by referential changes.
    const stringifiedPostList = JSON.stringify(postList);

    // Build template blocks for preview.
    const templateBlocks = useMemo(
        () => getTemplateBlocks(postList, attributes),
        [stringifiedPostList, attributes]
    );
    const stringifiedTemplateBlocks = JSON.stringify(templateBlocks);

    // Preview readiness (esp. when featured images are shown).
    useEffect(() => {
        const { isDisplayingSpecificPosts, specificPosts } = attributes;

        if (!attributes.displayFeaturedImage) {
            setIsReady(true);
            return;
        }
        if (isDisplayingSpecificPosts && specificPosts.length === 0) {
            setIsReady(true);
            return;
        }

        if (!isLoadingPosts && postList.length === 0) {
            setIsReady(true);
            return;
        }

        setIsReady(false);

        if (postList.length > 0) {
            const images = [];
            postList.forEach(
                (post) =>
                    post.featured_media && images.push(post.featured_media)
            );

            if (images.length === 0) {
                setIsReady(true);
                return;
            }

            const imageBlocks =
                stringifiedTemplateBlocks.match(/\"name\":\"core\/image\"/g) ||
                [];
            if (imageBlocks.length === images.length) {
                setIsReady(true);
            }
        }
    }, [
        stringifiedPostList,
        stringifiedTemplateBlocks,
        attributes,
        isLoadingPosts,
        postList.length,
    ]);

    // Serialized inner blocks for backend processing.
    const innerBlocksToInsert = templateBlocks.map(
        convertBlockSerializationFormat
    );
    useEffect(() => {
        setAttributes({ innerBlocksToInsert });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [JSON.stringify(innerBlocksToInsert)]);

    const handledPostIds = postList.map((post) => post.id);

    // One-shot insertion: replace this block with the generated blocks.
    useEffect(() => {
        if (attributes.areBlocksInserted) {
            replaceBlocks(templateBlocks, handledPostIds);
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        attributes.areBlocksInserted,
        stringifiedTemplateBlocks,
        handledPostIds.join(),
    ]);

    // Dedup registration + cleanup on unmount.
    useEffect(() => {
        if (!attributes.preventDeduplication) {
            setHandledPostsIds(handledPostIds);
            return () => removeBlock();
        }
        return () => removeBlock();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [handledPostIds.join(), attributes.preventDeduplication]);

    const blockProps = useBlockProps({
        className: `rrze-newsletter-post-inserter ${
            !isReady ? "rrze-newsletter-post-inserter--loading" : ""
        }`,
    });

    if (attributes.areBlocksInserted) {
        return null;
    }

    const fallbackFontSizes = [
        { name: "S", slug: "s", size: 14 },
        { name: "M", slug: "m", size: 16 },
        { name: "L", slug: "l", size: 20 },
    ];

    const imageSizeOptions = [
        { value: "medium", name: __("Medium", "rrze-newsletter") },
        { value: "large", name: __("Large", "rrze-newsletter") },
    ];

    return (
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
                            __next40pxDefaultSize
                            __nextHasNoMarginBottom
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
                            "“Continue reading…” link",
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
                        __next40pxDefaultSize
                        fontSizes={
                            blockEditorSettings?.fontSizes?.length
                                ? blockEditorSettings.fontSizes
                                : fallbackFontSizes
                        }
                        value={attributes.textFontSize}
                        onChange={(value) =>
                            setAttributes({ textFontSize: value })
                        }
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
                        __next40pxDefaultSize
                        fontSizes={
                            blockEditorSettings?.fontSizes?.length
                                ? blockEditorSettings.fontSizes
                                : fallbackFontSizes
                        }
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
                        __next40pxDefaultSize
                        fontSizes={
                            blockEditorSettings?.fontSizes?.length
                                ? blockEditorSettings.fontSizes
                                : fallbackFontSizes
                        }
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
                    <ToolbarGroup>
                        <ToolbarButton
                            icon={alignCenter}
                            label={__("Show image on top", "rrze-newsletter")}
                            isPressed={
                                attributes.featuredImageAlignment === "top"
                            }
                            onClick={() =>
                                setAttributes({ featuredImageAlignment: "top" })
                            }
                        />
                        <ToolbarButton
                            icon={alignLeft}
                            label={__("Show image on left", "rrze-newsletter")}
                            isPressed={
                                attributes.featuredImageAlignment === "left"
                            }
                            onClick={() =>
                                setAttributes({
                                    featuredImageAlignment: "left",
                                })
                            }
                        />
                        <ToolbarButton
                            icon={alignRight}
                            label={__("Show image on right", "rrze-newsletter")}
                            isPressed={
                                attributes.featuredImageAlignment === "right"
                            }
                            onClick={() =>
                                setAttributes({
                                    featuredImageAlignment: "right",
                                })
                            }
                        />
                        {(attributes.featuredImageAlignment === "left" ||
                            attributes.featuredImageAlignment === "right") && (
                            <ToolbarDropdownMenu
                                text={__("Image Size", "rrze-newsletter")}
                                icon={null}
                            >
                                {({ onClose }) => (
                                    <MenuGroup>
                                        {imageSizeOptions.map((entry) => (
                                            <MenuItem
                                                icon={
                                                    attributes.featuredImageSize ===
                                                    entry.value
                                                        ? "yes"
                                                        : null
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
                                                    onClose();
                                                }}
                                                role="menuitemradio"
                                            >
                                                {entry.name}
                                            </MenuItem>
                                        ))}
                                    </MenuGroup>
                                )}
                            </ToolbarDropdownMenu>
                        )}
                    </ToolbarGroup>
                )}
            </BlockControls>

            <div {...blockProps}>
                <div className="rrze-newsletter-post-inserter__header">
                    <Icon icon={pages} />
                    <span>{__("Post Inserter", "rrze-newsletter")}</span>
                </div>

                <PostsPreview
                    isReady={isReady}
                    blocks={templateBlocks}
                    viewportWidth={
                        attributes.featuredImageAlignment === "top" ||
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

                {/* Notice when no posts were found */}
                {!isLoadingPosts && postList.length === 0 && (
                    <div style={{ padding: "1em" }}>
                        <Notice status="info" isDismissible={false}>
                            {__(
                                "No posts found with the current filters.",
                                "rrze-newsletter"
                            )}
                        </Notice>
                    </div>
                )}

                {/* Locked InnerBlocks – cannot add, remove or move */}
                <div className="rrze-newsletter-post-inserter__content">
                    <InnerBlocks
                        allowedBlocks={[]}
                        templateLock="all"
                        renderAppender={false}
                    />
                </div>

                <div className="rrze-newsletter-post-inserter__footer">
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

        const core = select("core");
        const be = select("core/block-editor");

        // Read handled IDs from custom store (guarded)
        let excludeHandled = [];
        try {
            if (!preventDeduplication) {
                const storeSel = select(POST_INSERTER_STORE_NAME);
                if (storeSel?.getHandledPostIds) {
                    excludeHandled =
                        storeSel.getHandledPostIds(props.clientId) || [];
                }
            }
        } catch {
            excludeHandled = [];
        }

        // Normalize categories to IDs
        const catIds =
            Array.isArray(categories) && categories.length
                ? categories.map((c) => c?.id).filter(Boolean)
                : [];

        // Build query; `_embed: true` to retrieve featured media inline
        const baseQuery = {
            categories: catIds.length ? catIds : undefined,
            tags: Array.isArray(tags) && tags.length ? tags : undefined,
            order: order || "desc",
            orderby: orderBy || "date",
            per_page: Number.isFinite(parseInt(postsToShow, 10))
                ? parseInt(postsToShow, 10)
                : 3,
            exclude: excludeHandled,
            categories_exclude:
                Array.isArray(categoryExclusions) && categoryExclusions.length
                    ? categoryExclusions
                    : undefined,
            tags_exclude:
                Array.isArray(tagExclusions) && tagExclusions.length
                    ? tagExclusions
                    : undefined,
            excerpt_length: Number.isFinite(parseInt(excerptLength, 10))
                ? parseInt(excerptLength, 10)
                : undefined,
            exclude_sponsors: displaySponsoredPosts ? 0 : 1,
            _embed: true,
        };

        // Specific posts mode
        const includeIds = isDisplayingSpecificPosts
            ? specificPosts?.map((p) => p?.id).filter(Boolean)
            : [];

        const postListQuery = isDisplayingSpecificPosts
            ? includeIds.length
                ? {
                      include: includeIds,
                      per_page: includeIds.length,
                      _embed: true,
                  }
                : { include: [0], per_page: 0, _embed: true }
            : pickBy(baseQuery, (value) => !isUndefined(value));

        // Stable resolver args to also ask for isResolving
        const resolverArgs = ["postType", postType || "post", postListQuery];

        const isLoadingPosts = core.isResolving(
            "core",
            "getEntityRecords",
            resolverArgs
        );
        let posts = core.getEntityRecords(...resolverArgs) || [];

        // Maintain order for specific posts
        if (isDisplayingSpecificPosts && includeIds.length) {
            const byId = new Map(posts.map((p) => [p.id, p]));
            posts = includeIds.reduce(
                (acc, id) => (byId.has(id) ? [...acc, byId.get(id)] : acc),
                []
            );
        }

        // Map embedded media to robust size URLs with fallbacks
        const mapFeaturedImage = (post) => {
            const media = post?._embedded?.["wp:featuredmedia"]?.[0];
            const full = media?.source_url || null;
            const sizes = media?.media_details?.sizes || {};

            const thumb = sizes?.thumbnail?.source_url || null;
            const medium = sizes?.medium?.source_url || null;
            const large = sizes?.large?.source_url || null;

            return {
                ...post,
                featuredImageThumbURL: thumb || medium || large || full,
                featuredImageMediumURL: medium || large || full || thumb,
                featuredImageLargeURL: large || full || medium || thumb,
                featuredImageFullURL: full || large || medium || thumb,
            };
        };

        return {
            existingBlocks: be.getBlocks(),
            blockEditorSettings: be.getSettings(),
            selectedBlock: be.getSelectedBlock(),
            postList: posts.map(mapFeaturedImage),
            isLoadingPosts,
        };
    }),

    withDispatch((dispatch, props) => {
        const { replaceBlocks } = dispatch("core/block-editor");
        const { setHandledPostsIds, setInsertedPostsIds, removeBlock } =
            dispatch(POST_INSERTER_STORE_NAME);

        return {
            replaceBlocks: (blocks, handledIds) => {
                const current = props?.selectedBlock;
                const targetClientId = current?.clientId || props.clientId;
                replaceBlocks(targetClientId, blocks);
                try {
                    if (Array.isArray(handledIds) && handledIds.length) {
                        setInsertedPostsIds(handledIds);
                    }
                } catch {}
            },
            setHandledPostsIds: (ids, p = props) => {
                try {
                    setHandledPostsIds(ids, p);
                } catch {}
            },
            setInsertedPostsIds: (ids) => {
                try {
                    setInsertedPostsIds(ids);
                } catch {}
            },
            removeBlock: () => {
                try {
                    removeBlock(props.clientId);
                } catch {}
            },
        };
    }),
])(PostInserterBlock);

export default () => {
    registerBlockType(POST_INSERTER_BLOCK_NAME, {
        ...blockDefinition,
        title: "Post Inserter",
        icon: <Icon icon={pages} />,
        edit: PostInserterBlockWithSelect,
        // Persistent wrapper so the block is selectable even when empty
        save: () => {
            const blockProps = useBlockProps.save({
                className: "rrze-newsletter-post-inserter",
            });
            return (
                <div {...blockProps}>
                    <InnerBlocks.Content />
                </div>
            );
        },
    });
};
