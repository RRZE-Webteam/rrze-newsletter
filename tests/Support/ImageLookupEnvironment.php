<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /**
     * Fixed responses at the resolver's WordPress boundaries, not a WP emulator.
     * HTTP is allowlisted per test and cannot reach the network.
     */
    final class ImageLookupEnvironment
    {
        public static array $attachmentIds = [];
        public static array $attachments = [];
        public static array $attachmentLookups = [];
        public static array $attachmentSizeLookups = [];
        public static array $localLookups = [];
        public static array $localSizes = [];
        public static array $allowedUrls = [];
        public static array $responses = [];
        public static array $requests = [];
        public static array $transients = [];
        public static array $transientWrites = [];
        public static string $contentUrl = 'https://example.test/wp-content';
        public static string $uploadsUrl = 'https://example.test/wp-content/uploads';

        public static function reset(): void
        {
            self::$attachmentIds = [];
            self::$attachments = [];
            self::$attachmentLookups = [];
            self::$attachmentSizeLookups = [];
            self::$localLookups = [];
            self::$localSizes = [];
            self::$allowedUrls = [];
            self::$responses = [];
            self::$requests = [];
            self::$transients = [];
            self::$transientWrites = [];
            self::$contentUrl = 'https://example.test/wp-content';
            self::$uploadsUrl = 'https://example.test/wp-content/uploads';
        }
    }

    // Marker returned by the fake HTTP boundary when WordPress reports an error.
    final class ImageLookupError
    {
    }
}

namespace RRZE\Newsletter\MJML\BlockProcessor {
    use RRZE\Newsletter\Tests\Support\ImageLookupEnvironment as Environment;
    use RRZE\Newsletter\Tests\Support\ImageLookupError;

    function attachment_url_to_postid(string $url): int
    {
        Environment::$attachmentLookups[] = $url;
        return Environment::$attachmentIds[$url] ?? 0;
    }

    function wp_get_attachment_image_src(int $id, string $size): array|false
    {
        Environment::$attachmentSizeLookups[] = [$id, $size];
        return Environment::$attachments[$id][$size] ?? false;
    }

    function wp_upload_dir(): array
    {
        return ['baseurl' => Environment::$uploadsUrl];
    }

    function wp_parse_url(string $url, int $component = -1): mixed
    {
        return parse_url($url, $component);
    }

    function content_url(string $path = ''): string
    {
        return Environment::$contentUrl . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }

    function trailingslashit(string $value): string
    {
        return rtrim($value, '/\\') . '/';
    }

    function wp_normalize_path(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    function wp_getimagesize(string $path): array|false
    {
        Environment::$localLookups[] = $path;
        if (array_key_exists($path, Environment::$localSizes)) {
            return Environment::$localSizes[$path];
        }
        return getimagesize($path);
    }

    function wp_http_validate_url(string $url): string|false
    {
        // Only supplies a validation result; does not implement WP's SSRF checks.
        return in_array($url, Environment::$allowedUrls, true) ? $url : false;
    }

    function get_transient(string $key): mixed
    {
        return Environment::$transients[$key] ?? false;
    }

    function set_transient(string $key, mixed $value, int $expiration): bool
    {
        Environment::$transients[$key] = $value;
        Environment::$transientWrites[] = [$key, $value, $expiration];
        return true;
    }

    function wp_safe_remote_get(string $url, array $args): array|ImageLookupError
    {
        if (!array_key_exists($url, Environment::$responses)) {
            throw new \LogicException('Unexpected image HTTP request: ' . $url);
        }
        Environment::$requests[] = [$url, $args];
        return Environment::$responses[$url];
    }

    function is_wp_error(mixed $value): bool
    {
        return $value instanceof ImageLookupError;
    }

    function wp_remote_retrieve_response_code(array $response): int
    {
        return $response['response']['code'] ?? 0;
    }

    function wp_remote_retrieve_header(array $response, string $header): mixed
    {
        return $response['headers'][$header] ?? '';
    }

    function wp_remote_retrieve_body(array $response): string
    {
        return $response['body'] ?? '';
    }
}
