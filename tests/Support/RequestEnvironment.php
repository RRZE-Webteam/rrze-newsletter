<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /** Stops at the redirect boundary, before production's exit; no HTTP response is sent. */
    final class RedirectRecorded extends \RuntimeException {}

    final class RequestEnvironment
    {
        public static bool $admin = false;
        public static bool $page = true;
        public static bool $nonceValid = false;
        public static array $calls = [];
        public static array $transientWrites = [];
        public static array $assets = [];

        public static function reset(): void
        {
            self::$admin = false;
            self::$page = true;
            self::$nonceValid = false;
            self::$calls = self::$transientWrites = self::$assets = [];
        }
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
    use RRZE\Newsletter\Tests\Support\RequestEnvironment as Request;
    use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment as Subscription;
    use RRZE\Newsletter\Tests\Support\RedirectRecorded;

    function is_admin(): bool { return Request::$admin; }
    function is_page(): bool { return Request::$page; }
    function get_post_field(string $field, int $id): mixed { return App::post($id)->$field ?? ''; }
    function get_page_link(int $id): string { return 'https://example.test/' . get_post_field('post_name', $id) . '/'; }
    function get_page_by_path(string $slug): ?object
    {
        if ($slug === '') { return null; }
        foreach (App::$posts as $post) { if (($post->post_name ?? '') === $slug) { return $post; } }
        return null;
    }
    function site_url(string $path = ''): string { return 'https://example.test' . ($path === '' ? '' : '/' . ltrim($path, '/')); }
    function get_bloginfo(string $field): string { return $field === 'name' ? 'Test Site' : ''; }
    function untrailingslashit(string $url): string { return rtrim($url, '/\\'); }
    function get_the_time(string $format, mixed $post): string { return get_post_time($format, false, $post); }
    function wp_verify_nonce(string $nonce, string $action): bool { Request::$calls[] = ['nonce', $nonce, $action]; return Request::$nonceValid; }
    function wp_nonce_field(string $action, string $name): string
    {
        Request::$calls[] = ['nonce_field', $action, $name];
        // Like WP's default echo=true, this both emits and returns the fixture.
        $field = '<input type="hidden" name="' . $name . '" value="fixture-nonce" />';
        echo $field;
        return $field;
    }
    function set_transient(string $key, mixed $value, int $expiration): bool
    {
        Request::$transientWrites[] = [$key, $value, $expiration];
        Subscription::$transients[$key] = $value;
        return true;
    }
    function wp_redirect(string $url): bool { Request::$calls[] = ['redirect', $url]; throw new RedirectRecorded($url); }
    function nocache_headers(): void { Request::$calls[] = ['nocache']; }
    function plugins_url(string $path, string $plugin): string { return 'https://example.test/wp-content/plugins/rrze-newsletter/' . $path; }
    function wp_enqueue_style(mixed ...$args): void { Request::$assets[] = ['style', $args]; }
    function wp_enqueue_script(mixed ...$args): void { Request::$assets[] = ['script', $args]; }
}

namespace RRZE\Newsletter\Mail {
    function wp_parse_args(array $args, array $defaults): array { return array_replace($defaults, $args); }
    function __(string $text, string $domain = ''): string { return $text; }
}
