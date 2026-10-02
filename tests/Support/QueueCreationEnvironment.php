<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class QueueCreationEnvironment
    {
        public static array $inserts = [];
        public static array $insertResults = [];
        public static array $taxonomyCalls = [];
        public static function reset(): void { self::$inserts = self::$insertResults = self::$taxonomyCalls = []; }
    }
}

namespace RRZE\Newsletter\Mail {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
    use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment as Subscription;
    use RRZE\Newsletter\Tests\Support\QueueCreationEnvironment as Creation;

    function get_post(int $id): ?object { return App::post($id); }
    function is_wp_error(mixed $value): bool { return $value instanceof \WP_Error; }
    function apply_filters(string $hook, mixed $default): mixed { return App::$filters[$hook] ?? $default; }
    function sanitize_textarea_field(string $text): string { return \RRZE\Newsletter\sanitize_textarea_field($text); }
    function get_term_meta(int $id, string $key, bool $single = false): mixed { return Subscription::$termMeta[$id][$key] ?? ''; }
    function do_action(string $hook, mixed ...$args): void { \RRZE\Newsletter\do_action($hook, ...$args); }
    function get_object_taxonomies(string $type): array { Creation::$taxonomyCalls[] = ['taxonomies', $type]; return ['newsletter_mailing_list']; }
    function wp_get_object_terms(int $id, string $taxonomy, array $args): array { Creation::$taxonomyCalls[] = ['terms', $id, $taxonomy, $args]; return [112, 134]; }
    function wp_update_term_count(array $ids, string $taxonomy): void { Creation::$taxonomyCalls[] = ['count', $ids, $taxonomy]; }
    function wp_insert_post(array $args): int|\WP_Error
    {
        Creation::$inserts[] = $args;
        return array_shift(Creation::$insertResults) ?? (100 + count(Creation::$inserts));
    }
}
