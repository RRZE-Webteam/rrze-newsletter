<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class WordPressState
    {
        public static array $posts = [];

        public static array $postTypes = [];

        public static array $postMeta = [];

        public static array $postUpdates = [];

        public static array $postUpdateResults = [];

        public static array $postMetaAdds = [];

        public static array $postMetaUpdates = [];

        public static ?array $lastPostsQuery = null;
        public static array $postsQueries = [];
        public static array $scheduledEvents = [];
        public static array $scheduleCalls = [];
        public static array $scheduleResults = [];
        public static bool $coreSchedules = true;

        public static function reset(): void
        {
            self::$posts = [];
            self::$postTypes = [];
            self::$postMeta = [];
            self::$postUpdates = [];
            self::$postUpdateResults = [];
            self::$postMetaAdds = [];
            self::$postMetaUpdates = [];
            self::$lastPostsQuery = null;
            self::$postsQueries = self::$scheduledEvents = self::$scheduleCalls = self::$scheduleResults = [];
            self::$coreSchedules = true;
            RecurringLockEnvironment::reset();
        }
    }

    final class PluginStub
    {
        public function getDirectory(): string
        {
            return dirname(__DIR__) . '/';
        }

        public function getBaseName(): string
        {
            return 'rrze-newsletter/rrze-newsletter.php';
        }

        public function getVersion(): string
        {
            return 'test';
        }

        public function getPath(string $path = ''): string
        {
            return dirname(__DIR__) . '/' . trim($path, '/') . '/';
        }
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\PluginStub;

    function plugin(): PluginStub
    {
        return new PluginStub();
    }
}

namespace RRZE\Newsletter\Mail {
    use RRZE\Newsletter\Tests\Support\WordPressState;

    function absint(mixed $value): int
    {
        return abs((int) $value);
    }

    function get_posts(array $args): array
    {
        WordPressState::$lastPostsQuery = $args;
        WordPressState::$postsQueries[] = $args;

        if ($args['post_type'] === 'newsletter') {
            $posts = array_filter(\RRZE\Newsletter\Tests\Support\ApplicationEnvironment::$posts, static fn($post) =>
                $post->post_type === 'newsletter' && in_array($post->post_status, $args['post_status'], true)
                && !empty(WordPressState::$postMeta[$post->ID]['rrze_newsletter_has_conditionals'])
                && !empty(WordPressState::$postMeta[$post->ID]['rrze_newsletter_is_recurring']));
            ksort($posts);
            return array_slice(array_values($posts), $args['offset'], $args['numberposts']);
        }
        if (($args['fields'] ?? '') === 'ids') {
            return array_map(static fn($post) => $post->ID, array_values(array_filter(WordPressState::$posts, static fn($post) =>
                (WordPressState::$postMeta[$post->ID][$args['meta_key']] ?? null) === $args['meta_value']
                && in_array($post->post_status, $args['post_status'], true)
                && $post->post_date_gmt === $args['date_query'][0]['after'])));
        }

        return WordPressState::$posts;
    }

    function get_post_type(int $postId): string|false
    {
        return WordPressState::$postTypes[$postId] ?? false;
    }

    function get_post_meta(int $postId, string $key, bool $single = false): mixed
    {
        return WordPressState::$postMeta[$postId][$key] ?? ($single ? '' : []);
    }

    function wp_update_post(array $args, bool $wpError = false): int|\WP_Error
    {
        WordPressState::$postUpdates[] = $args;
        if (WordPressState::$postUpdateResults !== []) {
            $result = array_shift(WordPressState::$postUpdateResults);
            if ($result === 0 || $result instanceof \WP_Error) { return $result; }
        }

        foreach (WordPressState::$posts as $post) {
            if ($post->ID === $args['ID'] && isset($args['post_status'])) {
                $post->post_status = $args['post_status'];
            }
        }

        if (isset(\RRZE\Newsletter\Tests\Support\ApplicationEnvironment::$posts[$args['ID']])) {
            $post = clone \RRZE\Newsletter\Tests\Support\ApplicationEnvironment::$posts[$args['ID']];
            foreach ($args as $key => $value) { $post->$key = $value; }
            \RRZE\Newsletter\Tests\Support\ApplicationEnvironment::$posts[$args['ID']] = $post;
            if (($args['post_status'] ?? '') === 'future') {
                unset(WordPressState::$scheduledEvents[$post->ID]);
                if (WordPressState::$coreSchedules) {
                    WordPressState::$scheduledEvents[$post->ID] = strtotime($post->post_date_gmt . ' UTC');
                }
            }
        }

        return $args['ID'];
    }

    function wp_next_scheduled(string $hook, array $args): int|false
    {
        return WordPressState::$scheduledEvents[$args[0]] ?? false;
    }

    function wp_schedule_single_event(int $timestamp, string $hook, array $args, bool $wpError = false): bool|\WP_Error
    {
        WordPressState::$scheduleCalls[] = [$timestamp, $hook, $args, $wpError];
        $result = array_shift(WordPressState::$scheduleResults) ?? true;
        if ($result === true) { WordPressState::$scheduledEvents[$args[0]] = $timestamp; }
        return $result;
    }

    function add_post_meta(int $postId, string $key, mixed $value, bool $unique = false): int
    {
        WordPressState::$postMetaAdds[] = [$postId, $key, $value, $unique];
        WordPressState::$postMeta[$postId][$key] = $value;

        return 1;
    }

    function update_post_meta(int $postId, string $key, mixed $value): int
    {
        WordPressState::$postMetaUpdates[] = [$postId, $key, $value];
        WordPressState::$postMeta[$postId][$key] = $value;

        return 1;
    }

    function get_bloginfo(string $show): string
    {
        return $show === 'name' ? \RRZE\Newsletter\Tests\Support\QueueEnvironment::$blogName : '';
    }

    function site_url(string $path = ''): string
    {
        return 'https://example.test' . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }
}

namespace RRZE\Newsletter\MJML {
    // Only the numeric conversion boundary is needed by spacing unit tests.
    function absint(mixed $value): int
    {
        return abs((int) $value);
    }

    // Enough for fixture serialization; not a replacement for WP escaping tests.
    function esc_attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}

namespace RRZE\Newsletter\MJML\BlockProcessor {
    // Deterministic plugin asset URLs only; not WordPress URL/filter behavior.
    function plugins_url(string $path, string $plugin): string
    {
        if ($plugin !== 'rrze-newsletter/rrze-newsletter.php') {
            throw new \LogicException('Unexpected plugin asset base: ' . $plugin);
        }
        return 'https://example.test/wp-content/plugins/rrze-newsletter/' . $path;
    }

    function absint(mixed $value): int
    {
        return abs((int) $value);
    }
}

namespace {
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__DIR__) . '/');
    }

    if (!defined('MINUTE_IN_SECONDS')) {
        define('MINUTE_IN_SECONDS', 60);
    }
    if (!defined('HOUR_IN_SECONDS')) {
        define('HOUR_IN_SECONDS', 3600);
    }
    if (!defined('DAY_IN_SECONDS')) {
        define('DAY_IN_SECONDS', 86400);
    }
    if (!defined('WP_CONTENT_DIR')) {
        // A read-only fixture root, never the developer's live wp-content directory.
        define('WP_CONTENT_DIR', dirname(__DIR__) . '/assets');
    }

    date_default_timezone_set('UTC');

    require dirname(__DIR__) . '/vendor/autoload.php';
    require __DIR__ . '/Support/MjmlTestCase.php';
    require __DIR__ . '/Support/ImageLookupEnvironment.php';
    require __DIR__ . '/Support/QueueEnvironment.php';
    require __DIR__ . '/Support/MailEnvironment.php';
    require __DIR__ . '/Support/RecipientEnvironment.php';
    require __DIR__ . '/Support/SettingsEnvironment.php';
    require __DIR__ . '/Support/SettingsTestCase.php';
    require __DIR__ . '/Support/SubscriptionEnvironment.php';
    require __DIR__ . '/Support/ApplicationEnvironment.php';
    require __DIR__ . '/Support/ApplicationTestCase.php';
    require __DIR__ . '/Support/CalendarEnvironment.php';
    require __DIR__ . '/Support/RequestEnvironment.php';
    require __DIR__ . '/Support/EditorEnvironment.php';
    require __DIR__ . '/Support/QueueCreationEnvironment.php';
    require __DIR__ . '/Support/FeedEnvironment.php';
    require __DIR__ . '/Support/RecurringLockEnvironment.php';
    require __DIR__ . '/Support/MediaTextEnvironment.php';
}
