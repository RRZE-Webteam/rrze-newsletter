<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class FeedEnvironment
    {
        public static array $calls = [];
        public static string $trimmed = 'Excerpt [...]';
        public static function reset(): void { self::$calls = []; self::$trimmed = 'Excerpt [...]'; }
    }
}

namespace RRZE\Newsletter\Blocks\RSS {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
    use RRZE\Newsletter\Tests\Support\FeedEnvironment as Feed;
    function get_option(string $key): mixed { return App::$options[$key] ?? ''; }
    function get_post_type(int $id): string|false { return \RRZE\Newsletter\get_post_type($id); }
    function __(string $text, string $domain = ''): string { return $text; }
    function esc_html(string $text): string { return \RRZE\Newsletter\esc_html($text); }
    function esc_url(string $url): string { return \RRZE\Newsletter\esc_url($url); }
    function absint(mixed $value): int { return abs((int) $value); }
    function date_i18n(string $format, int|string $timestamp): string { return gmdate($format, (int) $timestamp); }
    function wptexturize(string $value): string { Feed::$calls[] = ['texturize', $value]; return $value; }
    function convert_chars(string $value): string { Feed::$calls[] = ['convert_chars', $value]; return $value; }
    function wpautop(string $value): string { Feed::$calls[] = ['autop', $value]; return $value; }
    function wp_trim_words(string $value, int $length, string $more): string { Feed::$calls[] = ['trim', $value, $length, $more]; return Feed::$trimmed; }
    function wp_json_file_decode(string $path, array $args): array { return json_decode(file_get_contents($path), $args['associative'], 512, JSON_THROW_ON_ERROR); }
    function wp_parse_args(array $args, array $defaults): array { return array_replace($defaults, $args); }
    function register_block_type(string $path, array $args): void { Feed::$calls[] = ['register', $path, $args]; }
}

namespace RRZE\Newsletter\MJML\BlockProcessor {
    function get_post_meta(int $id, string $key, bool $single = false): mixed { return \RRZE\Newsletter\get_post_meta($id, $key, $single); }
    function update_post_meta(int $id, string $key, mixed $value): bool { return \RRZE\Newsletter\update_post_meta($id, $key, $value); }
}
