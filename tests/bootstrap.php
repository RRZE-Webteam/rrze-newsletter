<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class WordPressState
    {
        public static array $posts = [];

        public static array $postTypes = [];

        public static array $postMeta = [];

        public static array $postUpdates = [];

        public static array $postMetaAdds = [];

        public static array $postMetaUpdates = [];

        public static ?array $lastPostsQuery = null;

        public static function reset(): void
        {
            self::$posts = [];
            self::$postTypes = [];
            self::$postMeta = [];
            self::$postUpdates = [];
            self::$postMetaAdds = [];
            self::$postMetaUpdates = [];
            self::$lastPostsQuery = null;
        }
    }

    final class PluginStub
    {
        public function getVersion(): string
        {
            return 'test';
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

    function wp_update_post(array $args): int
    {
        WordPressState::$postUpdates[] = $args;

        foreach (WordPressState::$posts as $post) {
            if ($post->ID === $args['ID'] && isset($args['post_status'])) {
                $post->post_status = $args['post_status'];
            }
        }

        return $args['ID'];
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
        return $show === 'name' ? 'Test Site' : '';
    }

    function site_url(): string
    {
        return 'https://example.test';
    }
}

namespace RRZE\Newsletter\MJML {
    // Only the numeric conversion boundary is needed by spacing unit tests.
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

    date_default_timezone_set('UTC');

    require dirname(__DIR__) . '/vendor/autoload.php';
}
