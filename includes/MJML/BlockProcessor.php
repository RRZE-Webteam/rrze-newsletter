<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use BorlabsCookie\Cookie\Frontend\Style;
use RRZE\Newsletter\Helper;
use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;
use RRZE\Newsletter\MJML\SocialIcons;

use function RRZE\Newsletter\plugin;

/**
 * Class BlockProcessor
 * 
 * Processes MJML blocks for rendering in newsletters.
 * 
 * @package RRZE\Newsletter\MJML
 */
class BlockProcessor
{
    private const MAX_REMOTE_IMAGE_LOOKUPS = 2;

    private const REMOTE_IMAGE_TIMEOUT = 1;

    private static int $remoteImageLookups = 0;

    private static array $imageSizeCache = [];

    public static function beginRender(): void
    {
        self::$remoteImageLookups = 0;
        self::$imageSizeCache = [];
    }

    /**
     * Renders a MJML component based on the block data.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $defaultAttrs Default attributes to apply to the block.
     * @param bool $isInColumn Whether the block is inside a column.
     * @param bool $isInGroup Whether the block is inside a group.
     * @param bool $isInList Whether the block is inside a list.
     * @return string Rendered MJML markup for the block.
     */
    public static function renderMjmlComponent(
        $postId,
        $block,
        $defaultAttrs = [],
        $isInColumn = false,
        $isInGroup = false,
        $isInList = false,
        $availableWidth = Renderer::EMAIL_WIDTH
    ) {
        $blockName    = $block['blockName'];
        $attrs        = $block['attrs'];
        $innerBlocks  = $block['innerBlocks'];
        $innerHtml    = $block['innerHTML'];
        $innerContent = $block['innerContent'] ?? [$innerHtml];

        // If the block is empty and not a container, return nothing.
        if (!isset($attrs['innerBlocksToInsert']) && self::isEmptyBlock($block)) {
            return '';
        }

        $defaultAttrs['postId'] = $postId;
        $attrs = array_merge($defaultAttrs, $attrs);
        $attrs = AttributeHandler::processAttributes($attrs);

        $padding = StyleProcessor::getPaddingFromAttributes($attrs);

        $sectionAttrs = array_merge($attrs, ['padding' => '0']);
        $columnAttrs  = ['padding' => $padding ?: '0'];

        $fontFamily = $blockName === 'core/heading' ? Renderer::getFontHeader() : Renderer::getFontBody();

        // Switch dispatches to helper methods
        switch ($blockName) {
            case 'core/paragraph':
            case 'core/heading':
                $markup = self::renderTextBlock($block, $attrs, $innerHtml, $isInList, $fontFamily);
                break;

            case 'core/list':
            case 'core/list-item':
                $markup = self::renderListBlock($postId, $block, $attrs, $innerBlocks, $innerContent, $isInList, $fontFamily, $availableWidth);
                break;

            case 'core/image':
                $imageWidth = self::subtractHorizontalPadding($availableWidth, $columnAttrs['padding']);
                $markup = self::renderImageBlock($attrs, $innerHtml, $fontFamily, $imageWidth);
                $sectionAttrs = self::filterSectionAttributes($sectionAttrs);
                break;

            case 'core/separator':
                $markup = self::renderSeparatorBlock($attrs, $sectionAttrs);
                break;

            case 'core/spacer':
                $markup = self::renderSpacerBlock($attrs);
                break;

            case 'core/social-links':
                $markup = self::renderSocialLinksBlock($attrs, $innerBlocks);
                break;

            case 'core/column':
                $markup = self::renderColumnBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $columnAttrs, $availableWidth);
                break;

            case 'core/columns':
                $columnsWidth = self::subtractHorizontalPadding($availableWidth, $padding);
                $markup = self::renderColumnsBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $columnsWidth);
                break;

            case 'core/group':
                $markup = self::renderGroupBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $availableWidth);
                break;

            case 'rrze-newsletter/rss':
                $markup = self::renderNewsletterRssBlock($postId, $block, $attrs, $fontFamily, $columnAttrs);
                break;

            case 'rrze-newsletter/ics':
                $markup = self::renderNewsletterIcsBlock($postId, $block, $attrs, $fontFamily, $columnAttrs);
                break;

            default:
                $markup = self::renderDefaultBlock($block, $attrs, $columnAttrs);
                break;
        }

        if ($markup === '') {
            return '';
        }

        // Wrapping logic for columns/sections
        $isPostInserterBlock = $blockName === 'rrze-newsletter/post-inserter';
        $isGroupBlock = $blockName === 'core/group';

        if (
            !$isInColumn &&
            !$isInList &&
            !$isGroupBlock &&
            !in_array($blockName, ['core/columns', 'core/column', 'core/separator'], true) &&
            !$isPostInserterBlock
        ) {
            $columnAttrs['width'] = '100%';
            $markup = '<mj-column ' . AttributeHandler::arrayToAttributes($columnAttrs) . '>' . $markup . '</mj-column>';
        }

        if (
            !$isInColumn &&
            !$isInList &&
            !$isGroupBlock &&
            !$isPostInserterBlock
        ) {
            if ($padding && $blockName === 'core/columns') {
                $sectionAttrs['padding'] = $padding;
            }
            return '<mj-section ' . AttributeHandler::arrayToAttributes($sectionAttrs) . '>' . $markup . '</mj-section>';
        }

        return $markup;
    }

    /**
     * Determines whether the block is empty.
     *
     * @param WP_Block $block The block.
     * @return bool Whether the block is empty.
     */
    public static function isEmptyBlock($block)
    {
        $blocksWithoutInnerHtml = [
            'rrze-newsletter/rss',
            'rrze-newsletter/ics',
        ];

        $emptyBlockName = empty($block['blockName']);
        $emptyHtml = !in_array($block['blockName'], $blocksWithoutInnerHtml, true) && empty($block['innerHTML']);

        return $emptyBlockName || $emptyHtml;
    }

    /**
     * Render paragraph or heading as mj-text.
     * 
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param bool $isInList Whether the block is inside a list.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the text block.
     */
    private static function renderTextBlock($block, $attrs, $innerHtml, $isInList, $fontFamily)
    {
        // Inherit link color if available
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor($attrs['style']['elements']['link']['color']['text']);
        }

        $textAlign = $attrs['style']['typography']['textAlign'] ?? 'left';
        if (!in_array($textAlign, ['left', 'center', 'right', 'justify'], true)) {
            $textAlign = 'left';
        }
        $innerHtml = self::applyTextAlignment($innerHtml, $textAlign);

        $textAttrs = array_merge([
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
            'align'       => $textAlign,
        ], $attrs);

        if (isset($textAttrs['background-color'])) {
            $textAttrs['container-background-color'] = $textAttrs['background-color'];
            unset($textAttrs['background-color']);
        }

        $innerHtml = StyleProcessor::applyLinkColor($block, $attrs, $innerHtml);

        if ($isInList) {
            return $innerHtml;
        }
        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Adds alignment directly to the rendered HTML element for email clients
     * that do not reliably inherit text alignment from the MJML container.
     */
    private static function applyTextAlignment(string $innerHtml, string $textAlign): string
    {
        $processor = new \WP_HTML_Tag_Processor($innerHtml);
        if (!$processor->next_tag()) {
            return $innerHtml;
        }

        $style = (string) $processor->get_attribute('style');
        $style = preg_replace('/(?:^|;)\s*text-align\s*:[^;]*/i', '', $style) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        $style = ($style !== '' ? $style . '; ' : '') . 'text-align: ' . $textAlign . ';';

        $processor->set_attribute('style', $style);
        $processor->set_attribute('align', $textAlign);

        return $processor->get_updated_html();
    }

    /**
     * Render a list or list-item as mj-text and recursively render children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $innerContent The inner content of the block.
     * @param bool $isInList Whether the block is inside a list.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the list block.
     */
    private static function renderListBlock($postId, $block, $attrs, $innerBlocks, $innerContent, $isInList, $fontFamily, $availableWidth)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor($attrs['style']['elements']['link']['color']['text']);
        }
        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $markup = '';
        if (!$isInList) {
            $markup .= '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>';
        }

        $markup .= $innerContent[0];
        if (!empty($innerBlocks) && count($innerContent) > 1) {
            foreach ($innerBlocks as $innerBlock) {
                $markup .= self::renderMjmlComponent($postId, $innerBlock, [], false, false, true, $availableWidth);
            }
            $markup .= $innerContent[count($innerContent) - 1];
        }

        if (!$isInList) {
            $markup .= '</mj-text>';
        }
        return $markup;
    }

    /**
     * Render image block as mj-image and figcaption as mj-text.
     * 
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the image block.
     */
    private static function renderImageBlock($attrs, $innerHtml, $fontFamily, $availableWidth)
    {
        $imageContent = self::parseImageContent($innerHtml);
        if ($imageContent === null) {
            return '';
        }

        $image = $imageContent['image'];
        $imageUrl = self::normalizeImageUrl($image->getAttribute('src'));
        $imgAttrs = self::buildImageAttributes($image, $attrs, $imageUrl);
        $imgAttrs = self::resolveImageDimensions(
            $imgAttrs,
            $attrs,
            $imageContent['size'],
            $imageUrl,
            $availableWidth
        );
        $imgAttrs = self::applyImagePresentationAttributes($imgAttrs, $image, $attrs);
        $markup = '<mj-image ' . AttributeHandler::arrayToAttributes($imgAttrs) . ' />';

        return $markup . self::renderImageCaption(
            $imageContent['caption'],
            $attrs,
            $fontFamily
        );
    }

    /**
     * @return array{image: \DOMElement, caption: \DOMText|null, size: array{0: int, 1: int}|null}|null
     */
    private static function parseImageContent(string $innerHtml): ?array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $innerHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXpath($dom);
        $image = $xpath->query('//img')[0];
        if (!$image instanceof \DOMElement) {
            return null;
        }

        $width = self::resolvePixelValue($image->getAttribute('width'));
        $height = self::resolvePixelValue($image->getAttribute('height'));

        return [
            'image' => $image,
            'caption' => $xpath->query('//figcaption/text()')[0] ?: null,
            'size' => $width && $height ? [$width, $height] : null,
        ];
    }

    private static function normalizeImageUrl(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if (str_starts_with($imageUrl, '//')) {
            $scheme = wp_parse_url(home_url('/'), PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $imageUrl;
        }
        if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
            return home_url($imageUrl);
        }

        return $imageUrl;
    }

    private static function buildImageAttributes(
        \DOMElement $image,
        array $attrs,
        string $imageUrl
    ): array {
        $imgAttrs = [
            'padding'         => '0',
            'align'           => $attrs['align'] ?? 'left',
            'fluid-on-mobile' => 'true',
            'src'             => $imageUrl,
        ];

        $alt = trim($image->getAttribute('alt'));
        if ($alt !== '') {
            $imgAttrs['alt'] = $alt;
        }

        $presetWidth = self::getImagePresetWidth($attrs);
        if ($presetWidth !== null) {
            $imgAttrs['width'] = $presetWidth;
        }
        if (isset($attrs['width'])) {
            $imgAttrs['width'] = $attrs['width'];
        }

        $requestedHeight = self::resolvePixelValue($attrs['height'] ?? null);
        if ($requestedHeight !== null) {
            $imgAttrs['height'] = $requestedHeight . 'px';
        }

        return $imgAttrs;
    }

    private static function getImagePresetWidth(array $attrs): ?string
    {
        $sizeSlug = $attrs['sizeSlug'] ?? null;
        $className = $attrs['className'] ?? '';

        if ($sizeSlug === 'medium' || str_contains($className, 'size-medium')) {
            return '300px';
        }
        if ($sizeSlug === 'thumbnail' || str_contains($className, 'size-thumbnail')) {
            return '150px';
        }

        return null;
    }

    /**
     * @param array{0: int, 1: int}|null $htmlImageSize
     */
    private static function resolveImageDimensions(
        array $imgAttrs,
        array $attrs,
        ?array $htmlImageSize,
        string $imageUrl,
        int $availableWidth
    ): array {
        $requestedHeight = self::resolvePixelValue($attrs['height'] ?? null);
        $requestedWidth = self::resolveWidth($imgAttrs['width'] ?? null, $availableWidth);

        $imgAttrs = self::normalizeRequestedImageWidth(
            $imgAttrs,
            $requestedWidth,
            $requestedHeight,
            $availableWidth
        );
        $requestedWidth = self::resolveWidth($imgAttrs['width'] ?? null, $availableWidth);

        $needsImageSize = !isset($imgAttrs['height']) || $requestedWidth === null;
        $imageSize = $needsImageSize
            ? ($htmlImageSize ?? self::getImageSize($imageUrl, $attrs))
            : null;

        if ($requestedHeight !== null && $requestedWidth === null) {
            return self::resolveHeightOnlyImage(
                $imgAttrs,
                $imageSize,
                $requestedHeight,
                $availableWidth
            );
        }
        if ($imageSize && !isset($imgAttrs['height'])) {
            return self::applyImageAspectRatio(
                $imgAttrs,
                $imageSize,
                $requestedWidth,
                $availableWidth
            );
        }

        return $imgAttrs;
    }

    private static function normalizeRequestedImageWidth(
        array $imgAttrs,
        ?int $requestedWidth,
        ?int $requestedHeight,
        int $availableWidth
    ): array {
        if (!isset($imgAttrs['width'])) {
            return $imgAttrs;
        }
        if ($requestedWidth === null) {
            unset($imgAttrs['width']);
            return $imgAttrs;
        }

        $renderedWidth = min($requestedWidth, $availableWidth);
        $imgAttrs['width'] = $renderedWidth . 'px';
        if ($requestedHeight !== null && $renderedWidth < $requestedWidth) {
            $scaledHeight = max(
                1,
                (int) round($requestedHeight * ($renderedWidth / $requestedWidth))
            );
            $imgAttrs['height'] = $scaledHeight . 'px';
        }

        return $imgAttrs;
    }

    /**
     * @param array{0: int, 1: int}|null $imageSize
     */
    private static function resolveHeightOnlyImage(
        array $imgAttrs,
        ?array $imageSize,
        int $requestedHeight,
        int $availableWidth
    ): array {
        if ($imageSize === null) {
            unset($imgAttrs['height']);
            return $imgAttrs;
        }

        [$intrinsicWidth, $intrinsicHeight] = $imageSize;
        $renderedHeight = $requestedHeight;
        $renderedWidth = (int) round(
            $intrinsicWidth * ($renderedHeight / $intrinsicHeight)
        );
        if ($renderedWidth > $availableWidth) {
            $renderedHeight = max(
                1,
                (int) round($renderedHeight * ($availableWidth / $renderedWidth))
            );
            $renderedWidth = $availableWidth;
        }

        $imgAttrs['width'] = $renderedWidth . 'px';
        $imgAttrs['height'] = $renderedHeight . 'px';

        return $imgAttrs;
    }

    /**
     * @param array{0: int, 1: int} $imageSize
     */
    private static function applyImageAspectRatio(
        array $imgAttrs,
        array $imageSize,
        ?int $requestedWidth,
        int $availableWidth
    ): array {
        [$intrinsicWidth, $intrinsicHeight] = $imageSize;
        $renderedWidth = min($requestedWidth ?? $intrinsicWidth, $availableWidth);
        $renderedHeight = (int) round(
            $intrinsicHeight * ($renderedWidth / $intrinsicWidth)
        );

        $imgAttrs['width'] = $renderedWidth . 'px';
        $imgAttrs['height'] = $renderedHeight . 'px';

        return $imgAttrs;
    }

    private static function applyImagePresentationAttributes(
        array $imgAttrs,
        \DOMElement $image,
        array $attrs
    ): array {
        if (isset($attrs['href'])) {
            $imgAttrs['href'] = $attrs['href'];
        } else {
            $parent = $image->parentNode;
            if ($parent instanceof \DOMElement && $parent->nodeName === 'a') {
                $href = trim($parent->getAttribute('href'));
                if ($href !== '') {
                    $imgAttrs['href'] = $href;
                }
            }
        }

        if (
            isset($attrs['className'])
            && str_contains($attrs['className'], 'is-style-rounded')
        ) {
            $imgAttrs['border-radius'] = '999px';
        }

        return $imgAttrs;
    }

    private static function renderImageCaption(
        ?\DOMText $caption,
        array $attrs,
        string $fontFamily
    ): string {
        if ($caption === null) {
            return '';
        }

        $captionAttrs = [
            'align' => 'left',
            'font-size' => '14px',
            'line-height' => '1.4',
            'padding' => '16px 0',
            'font-family' => $fontFamily,
            'color' => $attrs['color'] ?? '#000000',
        ];

        return '<mj-text ' . AttributeHandler::arrayToAttributes($captionAttrs) . '>'
            . $caption->wholeText
            . '</mj-text>';
    }

    /**
     * Keeps block-specific attributes from leaking onto an mj-section.
     */
    private static function filterSectionAttributes(array $attrs): array
    {
        $allowed = [
            'background-color',
            'background-url',
            'background-repeat',
            'background-size',
            'border',
            'border-bottom',
            'border-left',
            'border-radius',
            'border-right',
            'border-top',
            'direction',
            'full-width',
            'padding',
            'padding-bottom',
            'padding-left',
            'padding-right',
            'padding-top',
            'text-align',
        ];

        return array_intersect_key($attrs, array_flip($allowed));
    }

    /**
     * Resolves an image's intrinsic dimensions from WordPress metadata, a
     * same-site local file, or a bounded and cached remote request.
     *
     * @return array{0: int, 1: int}|null
     */
    private static function getImageSize(string $imageUrl, array $attrs): ?array
    {
        Helper::debug('BlockProcessor.php | getImageSize() called');

        if ($imageUrl === '') {
            return null;
        }

        $cacheKey = implode('|', [
            $imageUrl,
            (string) ($attrs['id'] ?? ''),
            (string) ($attrs['sizeSlug'] ?? 'full'),
        ]);
        if (array_key_exists($cacheKey, self::$imageSizeCache)) {
            return self::$imageSizeCache[$cacheKey];
        }

        $attachmentId = attachment_url_to_postid($imageUrl);
        if (!$attachmentId && !empty($attrs['id'])) {
            $uploads = wp_upload_dir();
            $uploadsPath = wp_parse_url($uploads['baseurl'] ?? '', PHP_URL_PATH);
            $imagePath = wp_parse_url($imageUrl, PHP_URL_PATH);
            if (
                is_string($uploadsPath)
                && is_string($imagePath)
                && str_starts_with($imagePath, trailingslashit($uploadsPath))
            ) {
                $attachmentId = absint($attrs['id']);
            }
        }

        if ($attachmentId) {
            $sizeSlug = !empty($attrs['sizeSlug']) ? $attrs['sizeSlug'] : 'full';
            $attachment = wp_get_attachment_image_src($attachmentId, $sizeSlug);
            if ($attachment && !empty($attachment[1]) && !empty($attachment[2])) {
                return self::$imageSizeCache[$cacheKey] = [
                    (int) $attachment[1],
                    (int) $attachment[2],
                ];
            }
        }

        $localPath = self::getLocalImagePath($imageUrl);
        if ($localPath) {
            $imageSize = wp_getimagesize($localPath);
            if ($imageSize && !empty($imageSize[0]) && !empty($imageSize[1])) {
                return self::$imageSizeCache[$cacheKey] = [
                    (int) $imageSize[0],
                    (int) $imageSize[1],
                ];
            }
        }

        return self::$imageSizeCache[$cacheKey] = self::getRemoteImageSize($imageUrl);
    }

    /**
     * Retrieves external image dimensions through WordPress's SSRF-protected
     * HTTP client and caches the result to avoid repeated remote requests.
     *
     * @return array{0: int, 1: int}|null
     */
    private static function getRemoteImageSize(string $imageUrl): ?array
    {
        Helper::debug('BlockProcessor.php | getRemoteImageSize() called');

        if (!wp_http_validate_url($imageUrl)) {
            return null;
        }

        $transientKey = 'rrze_newsletter_image_size_' . md5($imageUrl);
        $cached = get_transient($transientKey);
        if (is_array($cached) && array_key_exists('size', $cached)) {
            return is_array($cached['size']) ? $cached['size'] : null;
        }

        if (self::$remoteImageLookups >= self::MAX_REMOTE_IMAGE_LOOKUPS) {
            return null;
        }
        self::$remoteImageLookups++;

        $response = wp_safe_remote_get($imageUrl, [
            'timeout' => self::REMOTE_IMAGE_TIMEOUT,
            'redirection' => 3,
            'limit_response_size' => 1024 * 1024,
        ]);
        if (is_wp_error($response)) {
            set_transient($transientKey, ['size' => null], HOUR_IN_SECONDS);
            return null;
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $contentType = wp_remote_retrieve_header($response, 'content-type');
        $body = wp_remote_retrieve_body($response);
        if (
            $statusCode < 200
            || $statusCode >= 300
            || !is_string($contentType)
            || !str_starts_with(strtolower($contentType), 'image/')
            || $body === ''
        ) {
            set_transient($transientKey, ['size' => null], HOUR_IN_SECONDS);
            return null;
        }

        $imageSize = @getimagesizefromstring($body);
        if (!$imageSize || empty($imageSize[0]) || empty($imageSize[1])) {
            set_transient($transientKey, ['size' => null], HOUR_IN_SECONDS);
            return null;
        }

        $size = [(int) $imageSize[0], (int) $imageSize[1]];
        set_transient($transientKey, ['size' => $size], DAY_IN_SECONDS);

        return $size;
    }

    private static function getLocalImagePath(string $imageUrl): ?string
    {
        Helper::debug('BlockProcessor.php | getLocalImagePath() called');

        $imageHost = wp_parse_url($imageUrl, PHP_URL_HOST);
        $contentHost = wp_parse_url(content_url('/'), PHP_URL_HOST);
        if (
            is_string($imageHost)
            && is_string($contentHost)
            && strcasecmp($imageHost, $contentHost) !== 0
        ) {
            return null;
        }

        $imagePath = wp_parse_url($imageUrl, PHP_URL_PATH);
        $contentPath = wp_parse_url(content_url('/'), PHP_URL_PATH);
        if (!is_string($imagePath) || !is_string($contentPath)) {
            return null;
        }

        $contentPath = trailingslashit($contentPath);
        if (!str_starts_with($imagePath, $contentPath)) {
            return null;
        }

        $relativePath = ltrim(substr($imagePath, strlen($contentPath)), '/');
        $localPath = wp_normalize_path(trailingslashit(WP_CONTENT_DIR) . $relativePath);
        $realLocalPath = realpath($localPath);
        $realContentDirectory = realpath(WP_CONTENT_DIR);

        if ($realLocalPath === false || $realContentDirectory === false) {
            return null;
        }

        $realLocalPath = wp_normalize_path($realLocalPath);
        $realContentDirectory = trailingslashit(wp_normalize_path($realContentDirectory));
        if (
            !str_starts_with($realLocalPath, $realContentDirectory)
            || !is_file($realLocalPath)
        ) {
            return null;
        }

        return $realLocalPath;
    }

    private static function resolveWidth(mixed $width, int $availableWidth): ?int
    {
        Helper::debug('BlockProcessor.php | resolveWidth() called');

        if (!is_string($width) && !is_numeric($width)) {
            return null;
        }

        $width = trim((string) $width);
        if (str_ends_with($width, '%')) {
            $percentage = (float) rtrim($width, '%');
            return $percentage > 0
                ? max(1, (int) round($availableWidth * $percentage / 100))
                : null;
        }

        return self::resolvePixelValue($width);
    }

    private static function resolvePixelValue(mixed $value): ?int
    {
        if (
            (!is_string($value) && !is_numeric($value))
            || !preg_match('/^\d+(?:\.\d+)?(?:px)?$/', trim((string) $value))
        ) {
            return null;
        }

        $pixels = (int) round((float) $value);
        return $pixels > 0 ? $pixels : null;
    }

    private static function subtractHorizontalPadding(int $width, string $padding): int
    {
        Helper::debug('BlockProcessor.php | subtractHorizontalPadding() called');

        $parts = preg_split('/\s+/', trim($padding)) ?: [];
        if (count($parts) === 1) {
            $right = $left = $parts[0];
        } elseif (count($parts) === 2 || count($parts) === 3) {
            $right = $left = $parts[1];
        } elseif (count($parts) >= 4) {
            $right = $parts[1];
            $left = $parts[3];
        } else {
            return $width;
        }

        return max(
            1,
            $width
                - (self::resolveWidth($left, $width) ?? 0)
                - (self::resolveWidth($right, $width) ?? 0)
        );
    }

    /**
     * Render separator block as mj-divider.
     * 
     * @param array $attrs The block attributes.
     * @param array $sectionAttrs The section attributes to modify if needed.
     * @return string Rendered MJML markup for the separator block.
     */
    private static function renderSeparatorBlock($attrs, &$sectionAttrs)
    {
        $isWide = isset($attrs['className']) && $attrs['className'] === 'is-style-wide';
        $dividerAttrs = [
            'padding' => '0',
            'border-width' => '1px',
            'width' => $isWide ? '100%' : '128px',
        ];
        unset($sectionAttrs['background-color']);
        if (isset($attrs['backgroundColor']) && Renderer::getColorFromPalette($attrs['backgroundColor'])) {
            $dividerAttrs['border-color'] = Renderer::getColorFromPalette($attrs['backgroundColor']);
        } else {
            $dividerAttrs['border-color'] = $attrs['background-color'] ?? $attrs['border-color'] ?? $attrs['color'] ?? '#000000';
        }
        return '<mj-divider ' . AttributeHandler::arrayToAttributes($dividerAttrs) . ' />';
    }

    /**
     * Render spacer block as mj-spacer.
     * 
     * @param array $attrs The block attributes.
     * @return string Rendered MJML markup for the spacer block.
     */
    private static function renderSpacerBlock($attrs)
    {
        $ary = explode('|', $attrs['height'] ?? '0');
        $spacerAttrs = [
            'height' => absint(end($ary)) . 'px',
        ];
        return '<mj-spacer ' . AttributeHandler::arrayToAttributes($spacerAttrs) . ' />';
    }

    /**
     * Render social links block as mj-social.
     * 
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @return string Rendered MJML markup for the social links block.
     */
    private static function renderSocialLinksBlock($attrs, $innerBlocks)
    {
        $wrapperAttrs = [
            'icon-size'     => '24px',
            'mode'          => 'horizontal',
            'padding'       => '0',
            'border-radius' => '999px',
            'icon-padding'  => '7px',
            'align'         => $attrs['align'] ?? 'left',
        ];
        $markup = '<mj-social ' . AttributeHandler::arrayToAttributes($wrapperAttrs) . '>';
        foreach ($innerBlocks as $LinkBlock) {
            if (isset($LinkBlock['attrs']['url'])) {
                $url = $LinkBlock['attrs']['url'];
                $serviceName = $LinkBlock['attrs']['service'];
                $socialIcon = SocialIcons::getIconAttributes($serviceName, $attrs);

                if (!empty($socialIcon)) {
                    $imgAttrs = [
                        'href' => $url,
                        'src' => plugins_url('assets/social-links/' . $socialIcon['icon'], plugin()->getBasename()),
                        'background-color' => $socialIcon['color'],
                        'css-class' => 'social-element',
                        'padding' => '2px',
                    ];
                    $markup .= '<mj-social-element ' . AttributeHandler::arrayToAttributes($imgAttrs) . ' />';
                }
            }
        }
        $markup .= '</mj-social>';
        return $markup;
    }

    /**
     * Render a single column block as mj-column with children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @param array $columnAttrs Column attributes for the mj-column.
     * @return string Rendered MJML markup for the column block.
     */
    private static function renderColumnBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $columnAttrs, $availableWidth)
    {
        if (isset($attrs['verticalAlignment'])) {
            if ($attrs['verticalAlignment'] === 'center') {
                $columnAttrs['vertical-align'] = 'middle';
            } else {
                $columnAttrs['vertical-align'] = $attrs['verticalAlignment'];
            }
        }
        if (isset($attrs['width'])) {
            $columnAttrs['width'] = $attrs['width'];
            $columnAttrs['css-class'] = 'mj-column-has-width';
        }
        $columnWidth = self::resolveWidth(
            $attrs['width'] ?? null,
            $availableWidth
        ) ?? $availableWidth;
        $columnWidth = self::subtractHorizontalPadding(
            $columnWidth,
            $columnAttrs['padding']
        );

        $markup = '<mj-column ' . AttributeHandler::arrayToAttributes($columnAttrs) . '>';
        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = $defaultAttrs;
            $hasOwnLinkColor =
                !empty($childBlock['attrs']['style']['elements']['link']['color']['text']) ||
                !empty($childBlock['attrs']['link']);
            if ($hasOwnLinkColor && isset($childDefaultAttrs['link'])) {
                unset($childDefaultAttrs['link']);
            }
            $markup .= self::renderMjmlComponent($postId, $childBlock, $childDefaultAttrs, true, false, false, $columnWidth);
        }
        $markup .= '</mj-column>';
        return $markup;
    }

    /**
     * Render columns block, distributing widths if needed, and rendering children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @return string Rendered MJML markup for the columns block.
     */
    private static function renderColumnsBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $availableWidth)
    {
        // Calculate widths for columns if not set
        $widthsSum = 0;
        $noWidthColsIndexes = [];
        foreach ($innerBlocks as $i => $colBlock) {
            if (isset($colBlock['attrs']['width'])) {
                $widthsSum += floatval($colBlock['attrs']['width']);
            } else {
                $noWidthColsIndexes[] = $i;
            }
        }
        if (count($noWidthColsIndexes)) {
            $autoWidth = (100 - $widthsSum) / count($noWidthColsIndexes) . '%';
            foreach ($noWidthColsIndexes as $idx) {
                $innerBlocks[$idx]['attrs']['width'] = $autoWidth;
            }
        }

        // if (isset($attrs['color'])) {
        //     $defaultAttrs['color'] = $attrs['color'];
        // }
        // if (isset($attrs['link'])) {
        //     $defaultAttrs['link'] = $attrs['link'];
        // }

        $isStackedOnMobile = !isset($attrs['isStackedOnMobile']) || $attrs['isStackedOnMobile'] === true;
        $markup = $isStackedOnMobile ? '' : '<mj-group>';

        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = $defaultAttrs;
            $hasOwnLinkColor =
                !empty($childBlock['attrs']['style']['elements']['link']['color']['text']) ||
                !empty($childBlock['attrs']['link']);
            if ($hasOwnLinkColor && isset($childDefaultAttrs['link'])) {
                unset($childDefaultAttrs['link']);
            }
            $markup .= self::renderMjmlComponent($postId, $childBlock, $childDefaultAttrs, true, false, false, $availableWidth);
        }

        if (!$isStackedOnMobile) {
            $markup .= '</mj-group>';
        }
        return $markup;
    }

    /**
     * Render group block as mj-wrapper with children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @return string Rendered MJML markup for the group block.
     */
    private static function renderGroupBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $availableWidth)
    {
        // Process group attributes
        $attrs = AttributeHandler::processAttributes($block['attrs'] ?? []);
        $attrs['padding'] = StyleProcessor::getPaddingFromAttributes($attrs) ?: '0';
        $innerWidth = self::subtractHorizontalPadding(
            $availableWidth,
            $attrs['padding']
        );

        if (self::isGridGroup($block)) {
            return self::renderGridGroupBlock(
                $postId,
                $block,
                $attrs,
                $defaultAttrs,
                $innerWidth
            );
        }

        // Prepare default attributes for children
        $color = $attrs['color'] ?? '#000000';
        $link = $attrs['link'] ?? '#000000';

        $markup = '<mj-wrapper ' . AttributeHandler::arrayToAttributes($attrs) . '>';

        // error_log('Rendering group block: ' . $block['blockName']);
        // error_log('Group block attributes: ' . print_r($attrs, true));

        foreach ($innerBlocks as $innerBlock) {
            $attrs = $innerBlock['attrs'] ?? [];
            // error_log('Processing inner block: ' . $innerBlock['blockName']);

            $attrs['color'] = $attrs['color'] ?? $color;
            $attrs['link'] = $attrs['link'] ?? $link;

            if (isset($attrs['textColor'])) {
                unset($attrs['color']);
            }

            if (!empty($attrs['style']['elements']['link']['color']['text'])) {
                $attrs['link'] = StyleProcessor::extractLinkColor($attrs['style']['elements']['link']['color']['text']);
            }

            // error_log($innerBlock['blockName'] . ' default attributes after processing: ' . print_r($attrs, true));
            // Render child block with conditional inherited attributes
            $markup .= self::renderMjmlComponent($postId, $innerBlock, $attrs, false, true, false, $innerWidth);
        }

        $markup .= '</mj-wrapper>';
        return $markup;
    }

    private static function isGridGroup(array $block): bool
    {
        return ($block['attrs']['layout']['type'] ?? null) === 'grid';
    }

    private static function getGridColumnCount(array $block, int $availableWidth): int
    {
        $layout = $block['attrs']['layout'] ?? [];
        $configuredCount = $layout['columnCount'] ?? null;
        $configuredCount = is_numeric($configuredCount) && (int) $configuredCount > 0
            ? (int) $configuredCount
            : null;

        $minimumWidth = $layout['minimumColumnWidth'] ?? null;
        if ($minimumWidth === null && $configuredCount === null) {
            $minimumWidth = '12rem';
        }

        $minimumWidthPixels = self::cssLengthToPixels($minimumWidth);
        if ($minimumWidthPixels === null) {
            return $configuredCount ?? 1;
        }

        $responsiveCount = max(1, (int) floor($availableWidth / $minimumWidthPixels));
        return $configuredCount !== null
            ? min($configuredCount, $responsiveCount)
            : $responsiveCount;
    }

    private static function cssLengthToPixels($value): ?float
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*(px|rem|em)?$/i', $value, $matches)) {
            return null;
        }

        $amount = (float) $matches[1];
        if ($amount <= 0) {
            return null;
        }

        $unit = strtolower($matches[2] ?? 'px');
        return in_array($unit, ['rem', 'em'], true) ? $amount * 16 : $amount;
    }

    private static function renderGridGroupBlock(
        int $postId,
        array $block,
        array $wrapperAttrs,
        array $defaultAttrs,
        int $availableWidth
    ): string {
        $columnCount = self::getGridColumnCount($block, $availableWidth);
        $columnWidthPercent = 100 / $columnCount;
        $columnWidth = max(1, (int) floor($availableWidth / $columnCount));
        $rows = array_chunk($block['innerBlocks'], $columnCount);
        $markup = '<mj-wrapper ' . AttributeHandler::arrayToAttributes($wrapperAttrs) . '>';

        foreach ($rows as $row) {
            $markup .= '<mj-section padding="0">';
            foreach ($row as $innerBlock) {
                $childDefaultAttrs = self::getGroupChildAttributes(
                    $innerBlock,
                    $wrapperAttrs,
                    $defaultAttrs
                );
                $columnAttrs = [
                    'padding' => '0',
                    'width' => rtrim(rtrim(number_format($columnWidthPercent, 6, '.', ''), '0'), '.') . '%',
                ];
                $contentWidth = $columnWidth;

                if (($innerBlock['blockName'] ?? null) === 'core/group') {
                    $groupAttrs = AttributeHandler::processAttributes($innerBlock['attrs'] ?? []);
                    $groupAttrs['padding'] = StyleProcessor::getPaddingFromAttributes($groupAttrs) ?: '0';
                    $columnAttrs = array_merge(
                        $columnAttrs,
                        self::filterGridColumnAttributes($groupAttrs)
                    );
                    $contentWidth = self::subtractHorizontalPadding(
                        $columnWidth,
                        $columnAttrs['padding']
                    );
                }

                $markup .= '<mj-column ' . AttributeHandler::arrayToAttributes($columnAttrs) . '>';
                $markup .= self::renderGridCellBlock(
                    $postId,
                    $innerBlock,
                    $childDefaultAttrs,
                    $contentWidth
                );
                $markup .= '</mj-column>';
            }
            $markup .= '</mj-section>';
        }

        return $markup . '</mj-wrapper>';
    }

    private static function renderGridCellBlock(
        int $postId,
        array $block,
        array $defaultAttrs,
        int $availableWidth
    ): string {
        if (($block['blockName'] ?? null) !== 'core/group') {
            return self::renderMjmlComponent(
                $postId,
                $block,
                $defaultAttrs,
                true,
                true,
                false,
                $availableWidth
            );
        }

        $groupAttrs = AttributeHandler::processAttributes($block['attrs'] ?? []);
        $markup = '';
        foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
            $childDefaultAttrs = self::getGroupChildAttributes(
                $innerBlock,
                $groupAttrs,
                $defaultAttrs
            );
            $markup .= self::renderGridCellBlock(
                $postId,
                $innerBlock,
                $childDefaultAttrs,
                $availableWidth
            );
        }

        return $markup;
    }

    private static function filterGridColumnAttributes(array $attrs): array
    {
        $allowed = [
            'background-color',
            'border',
            'border-bottom',
            'border-left',
            'border-radius',
            'border-right',
            'border-top',
            'padding',
            'padding-bottom',
            'padding-left',
            'padding-right',
            'padding-top',
            'vertical-align',
        ];

        return array_intersect_key($attrs, array_flip($allowed));
    }

    private static function getGroupChildAttributes(
        array $innerBlock,
        array $groupAttrs,
        array $defaultAttrs
    ): array {
        $attrs = array_merge($defaultAttrs, $innerBlock['attrs'] ?? []);
        $attrs['color'] = $attrs['color'] ?? $groupAttrs['color'] ?? '#000000';
        $attrs['link'] = $attrs['link'] ?? $groupAttrs['link'] ?? '#000000';

        if (isset($attrs['textColor'])) {
            unset($attrs['color']);
        }
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }

        return $attrs;
    }

    /**
     * Render newsletter RSS block as mj-text and store block meta.
     *
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $fontFamily The font family to use for the text.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the RSS newsletter block.
     */
    private static function renderNewsletterRssBlock($postId, $block, $attrs, $fontFamily, $columnAttrs)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor($attrs['style']['elements']['link']['color']['text']);
        }

        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $columnAttrs['padding'] = '0';
        $key = md5($attrs['feedURL']);
        $rssAttrs = get_post_meta($postId, 'rrze_newsletter_rss_attrs', true) ?: [];
        $rssAttrs[$key] = $attrs;
        update_post_meta($postId, 'rrze_newsletter_rss_attrs', $rssAttrs);
        $innerHtml = 'RSS_BLOCK_' . $key;

        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Render newsletter ICS block as mj-text and store block meta.
     *
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $fontFamily The font family to use for the text.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the ICS newsletter block.
     */
    private static function renderNewsletterIcsBlock($postId, $block, $attrs, $fontFamily, $columnAttrs)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor($attrs['style']['elements']['link']['color']['text']);
        }

        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $columnAttrs['padding'] = '0';
        $key = md5($attrs['feedURL']);
        $icsAttrs = get_post_meta($postId, 'rrze_newsletter_ics_attrs', true) ?: [];
        $icsAttrs[$key] = $attrs;
        update_post_meta($postId, 'rrze_newsletter_ics_attrs', $icsAttrs);
        $innerHtml = 'ICS_BLOCK_' . $key;

        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Render unknown or fallback block types.
     *
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the block.
     */
    private static function renderDefaultBlock($block, $attrs, $columnAttrs)
    {
        return ''; // Return empty string by default.
    }
}
