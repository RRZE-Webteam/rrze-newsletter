<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /** Scripted WordPress boundaries; no real filters, sanitization or logging. */
    final class RecipientEnvironment
    {
        public static array $filters = [];
        public static array $filterCalls = [];
        public static array $actions = [];
        public static array $textResults = [];
        public static array $textareaResults = [];
        public static array $textCalls = [];
        public static array $textareaCalls = [];

        public static function reset(): void
        {
            self::$filters = [];
            self::$filterCalls = [];
            self::$actions = [];
            self::$textResults = [];
            self::$textareaResults = [];
            self::$textCalls = [];
            self::$textareaCalls = [];
        }
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\RecipientEnvironment;

    function sanitize_text_field(string $input): string
    {
        RecipientEnvironment::$textCalls[] = $input;
        // Most fixtures are already sanitized; specific tests script the result.
        return RecipientEnvironment::$textResults[$input] ?? $input;
    }

    function sanitize_textarea_field(string $input): string
    {
        RecipientEnvironment::$textareaCalls[] = $input;
        return RecipientEnvironment::$textareaResults[$input] ?? $input;
    }

    function apply_filters(string $hook, mixed $default): mixed
    {
        if (in_array($hook, ['rrze_newsletter_mail_queue_send_limit', 'rrze_newsletter_mail_queue_max_retries'], true)) {
            return \RRZE\Newsletter\Tests\Support\SettingsEnvironment::$filters[$hook] ?? $default;
        }
        if (!in_array($hook, ['rrze_newsletter_sender_allowed_domains', 'rrze_newsletter_recipient_allowed_domains'], true)) {
            throw new \LogicException('Unexpected recipient filter: ' . $hook);
        }
        RecipientEnvironment::$filterCalls[] = [$hook, $default];
        return RecipientEnvironment::$filters[$hook] ?? $default;
    }

    function do_action(string $hook, mixed ...$args): void
    {
        if ($hook !== 'rrze.log.error') {
            throw new \LogicException('Unexpected recipient action: ' . $hook);
        }
        RecipientEnvironment::$actions[] = [$hook, $args];
    }
}
