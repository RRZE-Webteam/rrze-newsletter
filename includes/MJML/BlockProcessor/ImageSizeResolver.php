<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

final class ImageSizeResolver
{
    private const MAX_REMOTE_LOOKUPS = 2;

    private const REMOTE_TIMEOUT = 1;

    private static int $remoteLookups = 0;

    private static array $cache = [];

    /**
     * Clear request-local dimension lookup state.
     */
    public static function reset(): void
    {
        self::$remoteLookups = 0;
        self::$cache = [];
    }

    /**
     * Resolve intrinsic image dimensions from WordPress, local, or remote data.
     *
     * @param string               $imageUrl Image URL.
     * @param array<string, mixed> $attrs    Image block attributes.
     * @return array{0: int, 1: int}|null Width and height in pixels.
     */
    public static function resolve(
        string $imageUrl,
        array $attrs
    ): ?array {
        if ($imageUrl === '') {
            return null;
        }

        $cacheKey = self::getCacheKey($imageUrl, $attrs);
        if (array_key_exists($cacheKey, self::$cache)) {
            return self::$cache[$cacheKey];
        }

        $size = self::getAttachmentSize($imageUrl, $attrs)
            ?? self::getLocalFileSize($imageUrl)
            ?? self::getRemoteSize($imageUrl);
        self::$cache[$cacheKey] = $size;

        return $size;
    }

    /**
     * Build a cache key that accounts for the requested attachment size.
     *
     * @param string               $imageUrl Image URL.
     * @param array<string, mixed> $attrs    Image block attributes.
     * @return string Cache key.
     */
    private static function getCacheKey(
        string $imageUrl,
        array $attrs
    ): string {
        return implode('|', [
            $imageUrl,
            (string) ($attrs['id'] ?? ''),
            (string) ($attrs['sizeSlug'] ?? 'full'),
        ]);
    }

    /**
     * Resolve dimensions from WordPress attachment metadata.
     *
     * @param string               $imageUrl Image URL.
     * @param array<string, mixed> $attrs    Image block attributes.
     * @return array{0: int, 1: int}|null Width and height in pixels.
     */
    private static function getAttachmentSize(
        string $imageUrl,
        array $attrs
    ): ?array {
        $attachmentId = attachment_url_to_postid($imageUrl);
        if (!$attachmentId && self::isUploadWithAttachmentId($imageUrl, $attrs)) {
            $attachmentId = absint($attrs['id']);
        }
        if (!$attachmentId) {
            return null;
        }

        $sizeSlug = !empty($attrs['sizeSlug'])
            ? $attrs['sizeSlug']
            : 'full';
        $attachment = wp_get_attachment_image_src(
            $attachmentId,
            $sizeSlug
        );
        if (!$attachment || empty($attachment[1]) || empty($attachment[2])) {
            return null;
        }

        return [(int) $attachment[1], (int) $attachment[2]];
    }

    /**
     * Determine whether an upload URL can use the block attachment ID.
     *
     * @param string               $imageUrl Image URL.
     * @param array<string, mixed> $attrs    Image block attributes.
     * @return bool True when the URL belongs to the uploads directory.
     */
    private static function isUploadWithAttachmentId(
        string $imageUrl,
        array $attrs
    ): bool {
        if (empty($attrs['id'])) {
            return false;
        }

        $uploads = wp_upload_dir();
        $uploadsPath = wp_parse_url(
            $uploads['baseurl'] ?? '',
            PHP_URL_PATH
        );
        $imagePath = wp_parse_url($imageUrl, PHP_URL_PATH);

        return is_string($uploadsPath)
            && is_string($imagePath)
            && str_starts_with($imagePath, trailingslashit($uploadsPath));
    }

    /**
     * Resolve dimensions from a readable file below the content directory.
     *
     * @param string $imageUrl Image URL.
     * @return array{0: int, 1: int}|null Width and height in pixels.
     */
    private static function getLocalFileSize(string $imageUrl): ?array
    {
        $localPath = self::getLocalPath($imageUrl);
        if ($localPath === null) {
            return null;
        }

        $imageSize = wp_getimagesize($localPath);
        if (!$imageSize || empty($imageSize[0]) || empty($imageSize[1])) {
            return null;
        }

        return [(int) $imageSize[0], (int) $imageSize[1]];
    }

    /**
     * Resolve dimensions through a bounded and cached remote request.
     *
     * @param string $imageUrl Image URL.
     * @return array{0: int, 1: int}|null Width and height in pixels.
     */
    private static function getRemoteSize(string $imageUrl): ?array
    {
        if (!wp_http_validate_url($imageUrl)) {
            return null;
        }

        $transientKey = 'rrze_newsletter_image_size_' . md5($imageUrl);
        $cached = get_transient($transientKey);
        if (is_array($cached) && array_key_exists('size', $cached)) {
            return is_array($cached['size']) ? $cached['size'] : null;
        }
        if (self::$remoteLookups >= self::MAX_REMOTE_LOOKUPS) {
            return null;
        }

        self::$remoteLookups++;
        $response = wp_safe_remote_get($imageUrl, [
            'timeout' => self::REMOTE_TIMEOUT,
            'redirection' => 3,
            'limit_response_size' => 1024 * 1024,
        ]);
        if (is_wp_error($response)) {
            self::cacheRemoteSize($transientKey, null);
            return null;
        }

        $size = self::extractRemoteSize($response);
        self::cacheRemoteSize($transientKey, $size);

        return $size;
    }

    /**
     * Extract dimensions from a successful image HTTP response.
     *
     * @param array<string, mixed> $response WordPress HTTP response.
     * @return array{0: int, 1: int}|null Width and height in pixels.
     */
    private static function extractRemoteSize(array $response): ?array
    {
        $statusCode = wp_remote_retrieve_response_code($response);
        $contentType = wp_remote_retrieve_header(
            $response,
            'content-type'
        );
        $body = wp_remote_retrieve_body($response);
        if (
            $statusCode < 200
            || $statusCode >= 300
            || !is_string($contentType)
            || !str_starts_with(strtolower($contentType), 'image/')
            || $body === ''
        ) {
            return null;
        }

        $imageSize = @getimagesizefromstring($body);
        if (!$imageSize || empty($imageSize[0]) || empty($imageSize[1])) {
            return null;
        }

        return [(int) $imageSize[0], (int) $imageSize[1]];
    }

    /**
     * Cache a successful or failed remote dimension lookup.
     *
     * @param string                       $transientKey Transient key.
     * @param array{0: int, 1: int}|null   $size         Resolved dimensions.
     */
    private static function cacheRemoteSize(
        string $transientKey,
        ?array $size
    ): void {
        set_transient(
            $transientKey,
            ['size' => $size],
            $size === null ? HOUR_IN_SECONDS : DAY_IN_SECONDS
        );
    }

    /**
     * Map a same-site content URL to a validated local file path.
     *
     * @param string $imageUrl Image URL.
     * @return string|null Local path, or null when the URL is not eligible.
     */
    private static function getLocalPath(string $imageUrl): ?string
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

        $relativePath = ltrim(
            substr($imagePath, strlen($contentPath)),
            '/'
        );
        $localPath = wp_normalize_path(
            trailingslashit(WP_CONTENT_DIR) . $relativePath
        );
        $realLocalPath = realpath($localPath);
        $realContentDirectory = realpath(WP_CONTENT_DIR);
        if ($realLocalPath === false || $realContentDirectory === false) {
            return null;
        }

        $realLocalPath = wp_normalize_path($realLocalPath);
        $realContentDirectory = trailingslashit(
            wp_normalize_path($realContentDirectory)
        );
        if (
            !str_starts_with($realLocalPath, $realContentDirectory)
            || !is_file($realLocalPath)
        ) {
            return null;
        }

        return $realLocalPath;
    }
}
