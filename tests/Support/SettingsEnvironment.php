<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class SettingsEnvironment
    {
        public static array $fields = [];
        public static array $stored = [];
        public static array $filters = [];
        public static array $errors = [];
        public static array $dropdowns = [];

        public static function reset(): void
        {
            self::$fields = [
                'mail_server' => [
                    ['name' => 'host', 'label' => 'Host', 'default' => 'localhost', 'required' => true, 'sanitize_callback' => static fn ($value) => trim($value)],
                    ['name' => 'sender', 'label' => 'Sender', 'default' => 'admin@example.test', 'sanitize_callback' => [\RRZE\Newsletter\Utils::class, 'sanitizeSenderEmail']],
                    ['name' => 'auth', 'label' => 'Authentication'],
                ],
                'mail_queue' => [
                    ['name' => 'send_limit', 'label' => 'Limit', 'default' => '15', 'sanitize_callback' => static fn ($value) => \RRZE\Newsletter\Utils::validateIntRange($value, 15, 1, 60)],
                    ['name' => 'max_retries', 'label' => 'Retries', 'default' => '1'],
                ],
            ];
            self::$stored = [];
            self::$filters = [];
            self::$errors = [];
            self::$dropdowns = [];
        }
    }
}

namespace RRZE\Newsletter\Config {
    // Controlled schemas test Settings itself, not the production config file.
    function getFields(): array
    {
        return \RRZE\Newsletter\Tests\Support\SettingsEnvironment::$fields;
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\SettingsEnvironment;

    function wp_parse_args(array $args, array $defaults = []): array
    {
        return array_merge($defaults, $args);
    }

    function esc_attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    function esc_textarea(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }

    function checked(mixed $value, mixed $expected, bool $echo = true): string
    {
        $result = (string) $value === (string) $expected ? ' checked="checked"' : '';
        if ($echo) { echo $result; }
        return $result;
    }

    function selected(mixed $value, mixed $expected, bool $echo = true): string
    {
        $result = (string) $value === (string) $expected ? ' selected="selected"' : '';
        if ($echo) { echo $result; }
        return $result;
    }

    function add_settings_error(string $settings, string $code, string $message, string $type): void
    {
        SettingsEnvironment::$errors[] = compact('settings', 'code', 'message', 'type');
    }

    function wp_dropdown_pages(array $args): string
    {
        SettingsEnvironment::$dropdowns[] = $args;
        return '<select data-test="pages"></select>';
    }
}
