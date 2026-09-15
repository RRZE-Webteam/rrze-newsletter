<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /** Records boundary calls; deliberately does not implement WordPress hook dispatch. */
    final class MailEnvironment
    {
        public static array $hooks = [];
        public static array $removedHooks = [];
        public static array $messages = [];
        public static array $optionReads = [];
        public static bool $result = true;
        public static ?\Closure $duringSend = null;

        public static function reset(): void
        {
            self::$hooks = [];
            self::$removedHooks = [];
            self::$messages = [];
            self::$optionReads = [];
            self::$result = true;
            self::$duringSend = null;
        }
    }

    /** Records SMTP configuration without loading PHPMailer or opening a connection. */
    final class MailerSpy extends \stdClass
    {
        public int $smtpCalls = 0;
        public array $embeddedImages = [];

        public function IsSMTP(): void
        {
            $this->smtpCalls++;
        }

        public function AddEmbeddedImage(string $path, string $cid): void
        {
            $this->embeddedImages[] = [$path, $cid];
        }
    }
}

namespace RRZE\Newsletter\Mail {
    use RRZE\Newsletter\Tests\Support\MailEnvironment;

    function add_action(string $hook, callable $callback): bool
    {
        MailEnvironment::$hooks['action'][$hook][] = $callback;
        return true;
    }

    function add_filter(string $hook, callable $callback): bool
    {
        MailEnvironment::$hooks['filter'][$hook][] = $callback;
        return true;
    }

    function remove_action(string $hook, callable $callback): bool
    {
        return remove_mail_test_hook('action', $hook, $callback);
    }

    function remove_filter(string $hook, callable $callback): bool
    {
        return remove_mail_test_hook('filter', $hook, $callback);
    }

    function remove_mail_test_hook(string $type, string $hook, callable $callback): bool
    {
        MailEnvironment::$removedHooks[] = [$type, $hook, $callback];
        foreach (MailEnvironment::$hooks[$type][$hook] ?? [] as $index => $registered) {
            if ($registered === $callback) {
                unset(MailEnvironment::$hooks[$type][$hook][$index]);
                return true;
            }
        }
        return false;
    }

    function wp_mail(string $to, string $subject, string $body, array $headers): bool
    {
        MailEnvironment::$messages[] = compact('to', 'subject', 'body', 'headers');
        if (MailEnvironment::$duringSend !== null) {
            (MailEnvironment::$duringSend)();
        }
        return MailEnvironment::$result;
    }

    function get_option(string $name): mixed
    {
        MailEnvironment::$optionReads[] = $name;
        if ($name !== 'admin_email') {
            throw new \LogicException('Unexpected mail option: ' . $name);
        }
        return 'admin@example.test';
    }
}

namespace RRZE\Newsletter {
    // Synthetic namespace-local credentials, never the live site's WordPress keys.
    const AUTH_KEY = 'newsletter-unit-test-key';
    const AUTH_SALT = 'newsletter-unit-test-salt';
}
