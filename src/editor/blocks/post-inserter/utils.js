/**
 * External dependencies
 */
import { omit } from "lodash";

/**
 * WordPress dependencies
 */
import { __, _x } from "@wordpress/i18n";
import { createBlock, getBlockContent } from "@wordpress/blocks";
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
import { dateI18n, getSettings } from "@wordpress/date";

/**
 * Plugin dependencies
 */
import { POST_INSERTER_BLOCK_NAME } from "./consts";

const assignFontSize = (fontSize, attributes) => {
    if (typeof fontSize === "number") {
        fontSize = fontSize + "px";
    }
    const prev = attributes.style?.typography || {};
    attributes.style = {
        ...(attributes.style || {}),
        typography: { ...prev, fontSize },
    };
    return attributes;
};

const getHeadingBlockTemplate = (
    post,
    { headingFontSize, headingColor, headingLevel }
) => [
    "core/heading",
    assignFontSize(headingFontSize, {
        style: { color: { text: headingColor } },
        content: `<a href="${post.link}">${post.title.rendered}</a>`,
        level: [2, 3, 4, 5, 6].includes(headingLevel) ? headingLevel : 3,
    }),
];

const getDateBlockTemplate = (post, { textFontSize, textColor }) => {
    const dateFormat = getSettings().formats.date;
    return [
        "core/paragraph",
        assignFontSize(textFontSize, {
            content: dateI18n(dateFormat, post.date),
            fontSize: "normal",
            style: { color: { text: textColor } },
        }),
    ];
};

const getSubtitleBlockTemplate = (
    post,
    { subHeadingFontSize, subHeadingColor }
) => {
    const subtitle = post?.meta?.rrze_post_subtitle || "";
    const attributes = {
        level: 4,
        content: subtitle.trim(),
        style: { color: { text: subHeadingColor } },
    };
    return ["core/heading", assignFontSize(subHeadingFontSize, attributes)];
};

const getExcerptBlockTemplate = (
    post,
    { excerptLength, textFontSize, textColor }
) => {
    let excerpt = post.excerpt.rendered;
    const excerptElement = document.createElement("div");
    excerptElement.innerHTML = excerpt;
    excerpt = excerptElement.textContent || excerptElement.innerText || "";

    const words = excerpt.trim().split(/\s+/).filter(Boolean);
    const safeLen =
        Number.isFinite(excerptLength) && excerptLength > 0
            ? excerptLength
            : 15;
    const needsEllipsis = safeLen < words.length;

    const postExcerpt = needsEllipsis
        ? `${words.slice(0, safeLen).join(" ")} […]`
        : words.join(" ");

    const attributes = {
        content: postExcerpt.trim(),
        style: { color: { text: textColor } },
    };
    return ["core/paragraph", assignFontSize(textFontSize, attributes)];
};

const getContinueReadingBlockTemplate = (
    post,
    { textFontSize, textColor, continueReadingStyle }
) => {
    const text = __("Continue reading…", "rrze-newsletter");
    if (continueReadingStyle !== "link") {
        return [
            "core/buttons",
            { layout: { type: "flex", justifyContent: "left" } },
            [[
                "core/button",
                assignFontSize(textFontSize, { text, url: post.link }),
            ]],
        ];
    }

    const attributes = {
        content: `<a href="${post.link}">${text}</a>`,
        style: { color: { text: textColor } },
    };
    return ["core/paragraph", assignFontSize(textFontSize, attributes)];
};

const getAuthorBlockTemplate = (post, { textFontSize, textColor }) => {
    const { rrze_author_info } = post;

    if (Array.isArray(rrze_author_info) && rrze_author_info.length) {
        const authorLinks = rrze_author_info.reduce(
            (acc, { author_link, display_name }, i, arr) => {
                if (!author_link || !display_name) return acc;
                const link = `<a href="${author_link}">${display_name}</a>`;
                if (arr.length === 1) return [link];
                if (arr.length === 2)
                    return i === 0
                        ? [link]
                        : [acc[0], `${__("and ", "rrze-newsletter")}${link}`];
                if (i < arr.length - 1) {
                    const comma = _x(
                        ",",
                        "comma separator for multiple authors",
                        "rrze-newsletter"
                    );
                    return [...acc, `${link}${comma}`];
                }
                return [...acc, `${__("and ", "rrze-newsletter")}${link}`];
            },
            []
        );

        return [
            "core/heading",
            assignFontSize(textFontSize, {
                content: __("By ", "rrze-newsletter") + authorLinks.join(" "),
                fontSize: "normal",
                level: 6,
                style: { color: { text: textColor } },
            }),
        ];
    }

    return null;
};

const createBlockTemplatesForSinglePost = (post, attributes) => {
    const headingBlocks = [getHeadingBlockTemplate(post, attributes)];
    const postContentBlocks = [];
    const continueReadingBlocks = attributes.displayContinueReading
        ? [getContinueReadingBlockTemplate(post, attributes)]
        : [];

    if (attributes.displayPostSubtitle && post.meta?.rrze_post_subtitle) {
        headingBlocks.push(getSubtitleBlockTemplate(post, attributes));
    }

    if (attributes.displayAuthor) {
        const author = getAuthorBlockTemplate(post, attributes);

        if (author) {
            postContentBlocks.push(author);
        }
    }
    if (attributes.displayPostDate && post.date_gmt) {
        postContentBlocks.push(getDateBlockTemplate(post, attributes));
    }
    if (attributes.displayPostExcerpt) {
        postContentBlocks.push(getExcerptBlockTemplate(post, attributes));
    }
    const hasFeaturedImage =
        post.featuredImageLargeURL ||
        post.featuredImageMediumURL ||
        post.featuredImageFullURL ||
        post.featuredImageThumbURL;

    if (attributes.displayFeaturedImage && hasFeaturedImage) {
        // Picks the most suitable URL depending on alignment/size, with graceful fallbacks.
        const getImageBlock = (alignCenter = false) => {
            // Preferred URL when the image is on top (centered)
            const preferredTop =
                post.featuredImageLargeURL ||
                post.featuredImageFullURL ||
                post.featuredImageMediumURL ||
                post.featuredImageThumbURL;

            // Preferred URL when the image is on the side (left/right), honoring user size
            let preferredSide;
            switch (attributes.featuredImageSize) {
                case "small":
                    preferredSide =
                        post.featuredImageThumbURL ||
                        post.featuredImageMediumURL ||
                        post.featuredImageLargeURL ||
                        post.featuredImageFullURL;
                    break;
                case "medium":
                    preferredSide =
                        post.featuredImageMediumURL ||
                        post.featuredImageLargeURL ||
                        post.featuredImageFullURL ||
                        post.featuredImageThumbURL;
                    break;
                case "large":
                default:
                    preferredSide =
                        post.featuredImageLargeURL ||
                        post.featuredImageFullURL ||
                        post.featuredImageMediumURL ||
                        post.featuredImageThumbURL;
                    break;
            }

            const url = alignCenter ? preferredTop : preferredSide;

            return [
                "core/image",
                {
                    id: post.featured_media || undefined,
                    url,
                    href: post.link,
                    // Ensures the editor honors the anchor when clicking the image
                    linkDestination: "custom",
                    ...(alignCenter ? { align: "center" } : {}),
                },
            ];
        };

        if (
            attributes.featuredImageAlignment === "top" ||
            postContentBlocks.length === 0
        ) {
            return [
                ...headingBlocks,
                getImageBlock(true),
                ...postContentBlocks,
                ...continueReadingBlocks,
            ];
        }

        let imageColumnBlockSize = "50%";
        let postContentColumnBlockSize = "50%";

        if (attributes.featuredImageSize) {
            switch (attributes.featuredImageSize) {
                case "small":
                    imageColumnBlockSize = "25%";
                    postContentColumnBlockSize = "75%";
                    break;
                case "medium":
                    imageColumnBlockSize = "33.33%";
                    postContentColumnBlockSize = "66.66%";
                    break;
            }
        }

        const imageColumnBlock = [
            "core/column",
            { width: imageColumnBlockSize },
            [getImageBlock()],
        ];
        const postContentColumnBlock = [
            "core/column",
            { width: postContentColumnBlockSize },
            postContentBlocks,
        ];

        const columnsBlock = [
            "core/columns",
            {
                style: {
                    spacing: {
                        padding: {
                            bottom: "20px",
                        },
                    },
                },
            },
            [],
        ];

        columnsBlock[2] = attributes.featuredImageAlignment === "right"
            ? [postContentColumnBlock, imageColumnBlock]
            : [imageColumnBlock, postContentColumnBlock];

        // Keep the leading column flush with the heading and the gap on its right.
        columnsBlock[2].forEach((column, index) => {
            if (index < columnsBlock[2].length - 1) {
                column[1].className = column === imageColumnBlock
                    ? "rrze-newsletter-post-inserter-image-left"
                    : "rrze-newsletter-post-inserter-text-left";
                column[1].style = column[1].style || {};
                column[1].style.spacing = column[1].style.spacing || {};
                column[1].style.spacing.padding =
                    column[1].style.spacing.padding || {};
                column[1].style.spacing.padding.right = "20px";
                column[1].style.spacing.padding.left = "0";
                column[1].style.spacing.margin = { left: "0" };
            }
        });

        return [...headingBlocks, columnsBlock, ...continueReadingBlocks];
    }
    return [...headingBlocks, ...postContentBlocks, ...continueReadingBlocks];
};

const createBlockFromTemplate = ([name, blockAttributes, innerBlocks = []]) =>
    createBlock(
        name,
        blockAttributes,
        innerBlocks.map(createBlockFromTemplate)
    );

const createBlockTemplatesForPosts = (posts, attributes) =>
    posts.reduce((blocks, post) => {
        return [
            ...blocks,
            ...createBlockTemplatesForSinglePost(post, attributes),
        ];
    }, []);

export const getTemplateBlocks = (postList, attributes) =>
    createBlockTemplatesForPosts(postList, attributes).map(
        createBlockFromTemplate
    );

/**
 * Converts a block object to a shape processable by the backend,
 * which contains block's HTML.
 *
 * @param {Object} block block, as understood by the block editor
 * @return {Object} block with innerHTML, processable by the backend
 */
export const convertBlockSerializationFormat = (block) => ({
    attrs: omit(block.attributes, "content"),
    blockName: block.name,
    innerHTML: getBlockContent(block),
    innerBlocks: block.innerBlocks.map(convertBlockSerializationFormat),
});

// In some cases, the Post Inserter block should not handle deduplication.
// Previews might be displayed next to each other or next to a post, which results in multiple block lists.
// The deduplication store relies on the assumption that a post has a single blocks list, which
// is not true when there are block previews used.
export const setPreventDeduplicationForPostInserter = (blocks) =>
    blocks.map((block) => ({
        ...block,
        attributes:
            block.name === POST_INSERTER_BLOCK_NAME
                ? { ...block.attributes, preventDeduplication: true }
                : block.attributes,
        innerBlocks: block.innerBlocks
            ? setPreventDeduplicationForPostInserter(block.innerBlocks)
            : block.innerBlocks,
    }));
