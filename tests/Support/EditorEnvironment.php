<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class EditorEnvironment
    {
        public static array $calls = [];
        public static int $logoId = 0;
        public static array $patterns = [];
        public static array $excerptFilters = [];
        public static function reset(): void { self::$calls = self::$patterns = self::$excerptFilters = []; self::$logoId = 0; }

        /** Single-argument closure fixture, not a WordPress hook dispatcher. */
        public static function excerptLength(int $default): int
        {
            $filters = self::$excerptFilters;
            ksort($filters);
            foreach ($filters as $callbacks) {
                foreach ($callbacks as $callback) { $default = $callback($default); }
            }
            return $default;
        }
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\EditorEnvironment as Editor;
    use RRZE\Newsletter\Tests\Support\RequestEnvironment as Request;

    function remove_post_type_support(string $type, string $feature): void { Editor::$calls[] = ['remove_support', $type, $feature]; }
    function remove_action(string $hook, mixed $callback, int $priority = 10): bool { Editor::$calls[] = ['remove_action', $hook, $callback, $priority]; return true; }
    function remove_filter(string $hook, mixed $callback, int $priority = 10): bool
    {
        Editor::$calls[] = ['remove_filter', $hook, $callback, $priority];
        if ($hook === 'excerpt_length') {
            if (!$callback instanceof \Closure) { return false; }
            $id = spl_object_id($callback);
            $exists = isset(Editor::$excerptFilters[$priority][$id]);
            unset(Editor::$excerptFilters[$priority][$id]);
            if (empty(Editor::$excerptFilters[$priority])) { unset(Editor::$excerptFilters[$priority]); }
            return $exists;
        }
        return true;
    }
    function remove_editor_styles(): void { Editor::$calls[] = ['remove_editor_styles']; }
    function add_theme_support(mixed ...$args): void { Editor::$calls[] = ['theme_support', ...$args]; }
    function wp_register_style(mixed ...$args): void { Request::$assets[] = ['register_style', $args]; }
    function wp_style_add_data(mixed ...$args): void { Request::$assets[] = ['style_data', $args]; }
    function wp_localize_script(mixed ...$args): void { Request::$assets[] = ['localize', $args]; }
    function wp_set_script_translations(mixed ...$args): void { Request::$assets[] = ['translations', $args]; }
}

namespace RRZE\Newsletter\CPT {
    use RRZE\Newsletter\Tests\Support\EditorEnvironment as Editor;
    function get_bloginfo(string $name): string { return 'Test Site'; }
    function get_theme_mod(string $name): int { return Editor::$logoId; }
    function wp_get_attachment_image_src(int $id, string $size): array
    {
        Editor::$calls[] = ['image', $id, $size];
        return ['https://example.test/logo.png', 300, 100, true];
    }
    function get_site_url(): string { return 'https://example.test'; }
}

namespace RRZE\Newsletter\Patterns {
    use RRZE\Newsletter\Tests\Support\EditorEnvironment as Editor;
    function _x(string $text, string $context, string $domain): string { return $text; }
    function get_site_url(): string { return 'https://example.test'; }
    function register_block_pattern_category(string $name, array $args): void { Editor::$patterns['categories'][$name] = $args; }
    function register_block_pattern(string $name, array $args): void { Editor::$patterns['patterns'][$name] = $args; }
}
