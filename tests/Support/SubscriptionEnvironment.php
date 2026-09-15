<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class SubscriptionEnvironment
    {
        public static array $terms = [];
        public static array $termMeta = [];
        public static array $queries = [];
        public static array $termWrites = [];
        public static array $optionWrites = [];
        public static array $transients = [];
        public static array $deletedTransients = [];

        public static function reset(): void
        {
            self::$terms = [];
            self::$termMeta = [];
            self::$queries = [];
            self::$termWrites = [];
            self::$optionWrites = [];
            self::$transients = [];
            self::$deletedTransients = [];
        }
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment;

    function absint(mixed $value): int
    {
        return abs((int) $value);
    }

    function get_terms(array $args): array
    {
        SubscriptionEnvironment::$queries[] = $args;
        return SubscriptionEnvironment::$terms;
    }

    function get_term_meta(int $id, string $key, bool $single = false): mixed
    {
        return SubscriptionEnvironment::$termMeta[$id][$key] ?? ($single ? '' : []);
    }

    function update_term_meta(int $id, string $key, mixed $value): bool
    {
        SubscriptionEnvironment::$termWrites[] = [$id, $key, $value];
        SubscriptionEnvironment::$termMeta[$id][$key] = $value;
        return true;
    }

    function update_option(string $name, mixed $value): bool
    {
        SubscriptionEnvironment::$optionWrites[] = [$name, is_object($value) ? clone $value : $value];
        return true;
    }

    function get_transient(string $key): mixed
    {
        return SubscriptionEnvironment::$transients[$key] ?? false;
    }

    function delete_transient(string $key): bool
    {
        SubscriptionEnvironment::$deletedTransients[] = $key;
        $exists = array_key_exists($key, SubscriptionEnvironment::$transients);
        unset(SubscriptionEnvironment::$transients[$key]);
        return $exists;
    }
}

namespace {
    if (!defined('ARRAY_N')) {
        define('ARRAY_N', 'ARRAY_N');
    }
}
