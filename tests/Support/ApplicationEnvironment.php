<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /** Isolated call recorder. Never bootstraps WordPress or connects to storage. */
    final class ApplicationEnvironment
    {
        public static array $posts = [];
        public static array $meta = [];
        public static array $writes = [];
        public static array $registrations = [];
        public static array $hooks = [];
        public static array $filters = [];
        public static array $options = [];
        public static array $capabilities = [];
        public static array $capabilityCalls = [];
        public static array $blocks = [];
        public static array $terms = [];
        public static int $currentId = 42;
        public static string $queryStatus = '';
        public static bool $single = false;
        public static array $urlCalls = [];

        public static function reset(): void
        {
            self::$posts = self::$meta = self::$writes = self::$registrations = self::$hooks = [];
            self::$filters = self::$capabilities = self::$capabilityCalls = self::$blocks = self::$terms = [];
            self::$options = ['date_format' => 'Y-m-d', 'time_format' => 'H:i', 'blog_charset' => 'UTF-8', 'rrze_newsletter_color_palette' => '{}'];
            self::$currentId = 42;
            self::$queryStatus = '';
            self::$single = false;
            self::$urlCalls = [];
        }

        public static function post(mixed $id = null): ?object
        {
            return is_object($id) ? $id : (self::$posts[$id ?? self::$currentId] ?? null);
        }

        public static function record(string $kind, string $name, array $args): void
        {
            self::$registrations[$kind][$name] = $args;
        }
    }

    /** Minimal value-object doubles, not implementations of the WordPress APIs. */
    class PostDouble extends \stdClass
    {
        public function __construct(object $data)
        {
            foreach (array_replace([
                'ID' => 42, 'post_type' => 'newsletter', 'post_status' => 'publish',
                'post_title' => 'Test newsletter', 'post_content' => '', 'post_excerpt' => '',
                'post_date' => '2026-09-15 10:30:00', 'post_date_gmt' => '2026-09-15 08:30:00',
            ], (array) $data) as $key => $value) { $this->$key = $value; }
        }
    }

    class ErrorDouble
    {
        public function __construct(private string $code = '', private string $message = '', private mixed $data = null) {}
        public function get_error_code(): string { return $this->code; }
        public function get_error_message(): string { return $this->message; }
        public function get_error_data(): mixed { return $this->data; }
    }

    class RestServerDouble
    {
        public const READABLE = 'GET';
        public const EDITABLE = 'POST, PUT, PATCH';
    }
}

namespace {
    class_alias(\RRZE\Newsletter\Tests\Support\PostDouble::class, 'WP_Post');
    class_alias(\RRZE\Newsletter\Tests\Support\ErrorDouble::class, 'WP_Error');
    class_alias(\RRZE\Newsletter\Tests\Support\RestServerDouble::class, 'WP_REST_Server');
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;

    function get_post(mixed $id = null): ?object { return App::post($id); }
    function get_post_type(mixed $id = null): string|false { return App::post($id)->post_type ?? false; }
    function get_post_meta(int $id, string $key, bool $single = false): mixed { return App::$meta[$id][$key] ?? ($single ? '' : []); }
    function update_post_meta(int $id, string $key, mixed $value): bool { App::$writes[] = [$id, $key, $value]; App::$meta[$id][$key] = $value; return true; }
    function is_wp_error(mixed $value): bool { return $value instanceof \WP_Error; }
    function add_action(string $hook, mixed $callback, int $priority = 10, int $accepted = 1): void { App::$hooks[] = ['action', $hook, $callback, $priority, $accepted]; }
    function add_filter(string $hook, mixed $callback, int $priority = 10, int $accepted = 1): bool { App::$hooks[] = ['filter', $hook, $callback, $priority, $accepted]; return true; }
    function get_post_type_capabilities(object $args): object { App::$capabilityCalls[] = $args; return (object) $args->capabilities; }
    function current_user_can(string $capability): bool { App::$capabilityCalls[] = $capability; return App::$capabilities[$capability] ?? false; }
    function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
    function esc_html__(string $value, string $domain = ''): string { return esc_html($value); }
    function sanitize_email(string $value): string { return $value; }
    function rest_ensure_response(mixed $value): mixed { return $value; }
    function register_rest_route(string $namespace, string $route, array $args): void { App::record('routes', $namespace . '/' . $route, $args); }
    function register_rest_field(string $type, string $field, array $args): void { App::record('fields', $type . '/' . $field, $args); }
    function get_post_time(string $format, bool $gmt, mixed $post): string { return (new \DateTimeImmutable(App::post($post)->post_date))->format($format); }
    function wp_json_encode(mixed $value): string { return json_encode($value, JSON_THROW_ON_ERROR); }
    function get_the_author_meta(string $field, int $id): string { return 'Author ' . $id; }
    function get_author_posts_url(int $id): string { return 'https://example.test/author/' . $id; }
    function wp_strip_all_tags(string $value): string { return strip_tags($value); }
    function esc_url(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); }
    function add_query_arg(mixed $args, mixed $urlOrValue, ?string $base = null): string
    {
        $url = $base ?? $urlOrValue;
        $args = is_array($args) ? $args : [$args => $urlOrValue];
        App::$urlCalls[] = [$args, $url];
        $parts = explode('#', $url, 2);
        $queryParts = explode('?', $parts[0], 2);
        parse_str($queryParts[1] ?? '', $query);
        return $queryParts[0] . '?' . http_build_query(array_replace($query, $args)) . (isset($parts[1]) ? '#' . $parts[1] : '');
    }
}

namespace RRZE\Newsletter\CPT {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;

    function __(string $text, string $domain = ''): string { return $text; }
    function _x(string $text, string $context, string $domain): string { return $text; }
    function _n_noop(string $singular, string $plural, string $domain): array { return compact('singular', 'plural', 'domain'); }
    function add_action(string $hook, mixed $callback, int $priority = 10, int $accepted = 1): void { \RRZE\Newsletter\add_action($hook, $callback, $priority, $accepted); }
    function add_filter(string $hook, mixed $callback, int $priority = 10, int $accepted = 1): void { \RRZE\Newsletter\add_filter($hook, $callback, $priority, $accepted); }
    function apply_filters(string $hook, mixed $value): mixed { return App::$filters[$hook] ?? $value; }
    function register_post_type(string $name, array $args): void { App::record('post_types', $name, $args); }
    function register_meta(string $type, string $key, array $args): void { App::record('meta', $key, ['object_type' => $type] + $args); }
    function register_taxonomy(string $name, string $type, array $args): void { App::record('taxonomies', $name, ['object_type' => $type] + $args); }
    function register_post_status(string $name, array $args): void { App::record('statuses', $name, $args); }
    function get_post(mixed $id = null): ?object { return App::post($id); }
    function get_post_type(mixed $id = null): string|false { return \RRZE\Newsletter\get_post_type($id); }
    function get_post_status(int $id): string|false { return App::post($id)->post_status ?? false; }
    function get_post_status_object(string $status): object { return (object) ['name' => $status]; }
    function get_post_meta(int $id, string $key, bool $single = false): mixed { return \RRZE\Newsletter\get_post_meta($id, $key, $single); }
    function update_post_meta(int $id, string $key, mixed $value): bool { return \RRZE\Newsletter\update_post_meta($id, $key, $value); }
    function get_the_terms(int $id, string $taxonomy): mixed { return App::$terms[$id][$taxonomy] ?? false; }
    function get_the_time(string $format, mixed $post): string { return \RRZE\Newsletter\get_post_time($format, false, $post); }
    function is_wp_error(mixed $value): bool { return $value instanceof \WP_Error; }
    function absint(mixed $value): int { return abs((int) $value); }
    function get_option(string $key): mixed { return App::$options[$key] ?? ''; }
    function current_time(string $format): string { return (new \DateTimeImmutable('2026-09-15 12:30:00'))->format($format === 'mysql' ? 'Y-m-d H:i:s' : $format); }
    function date_i18n(string $format, mixed $timestamp = false, bool $gmt = false): string { return date($format, $timestamp ?: 1789475400); }
    function human_time_diff(mixed $from, mixed $to): string { return '2 hours'; }
    function is_single(): bool { return App::$single; }
    function esc_attr(mixed $value): string { return \RRZE\Newsletter\esc_attr($value); }
    function get_query_var(string $key): string { return App::$queryStatus; }
    function get_date_from_gmt(string $date): string { return (new \DateTimeImmutable($date, new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Berlin'))->format('Y-m-d H:i:s'); }
}

namespace RRZE\Newsletter\MJML {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
    function get_post_meta(int $id, string $key, bool $single = false): mixed { return \RRZE\Newsletter\get_post_meta($id, $key, $single); }
    function __(string $text, string $domain = ''): string { return $text; }
    function get_option(string $name, mixed $default = false): mixed { return App::$options[$name] ?? $default; }
    function wp_parse_args(array $args, array $defaults): array { return array_replace($defaults, $args); }
    function parse_blocks(string $content): array { return App::$blocks[$content] ?? []; }
    function get_post(int $id): ?object { return App::post($id); }
    function get_post_time(string $format, bool $gmt, mixed $post): string { return \RRZE\Newsletter\get_post_time($format, $gmt, $post); }
    function sanitize_title(string $title): string { return strtolower(str_replace(' ', '-', $title)); }
    function add_query_arg(array $args, string $url): string { return \RRZE\Newsletter\add_query_arg($args, $url); }
}
