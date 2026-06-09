<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class ImageProcessor
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
     * Render image block as mj-image and figcaption as mj-text.
     *
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the image block.
     */
    public static function render(
        array $attrs,
        string $innerHtml,
        string $fontFamily,
        int $availableWidth
    ): string
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

    private static function resolveWidth(mixed $width, int $availableWidth): ?int
    {
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

    /**
     * Resolves an image's intrinsic dimensions from WordPress metadata, a
     * same-site local file, or a bounded and cached remote request.
     *
     * @return array{0: int, 1: int}|null
     */
    private static function getImageSize(string $imageUrl, array $attrs): ?array
    {
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
}
