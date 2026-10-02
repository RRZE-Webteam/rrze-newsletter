<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class RecurringLockEnvironment
    {
        public static array $options = [];
        public static array $cacheDeletes = [];

        public static function reset(): void
        {
            self::$options = self::$cacheDeletes = [];
            $GLOBALS['wpdb'] = new class {
                public string $options = 'unit_options';
                public function delete(string $table, array $where, array $formats): int
                {
                    if ((RecurringLockEnvironment::$options[$where['option_name']] ?? null) !== $where['option_value']) {
                        return 0;
                    }
                    unset(RecurringLockEnvironment::$options[$where['option_name']]);
                    return 1;
                }
            };
        }
    }
}

namespace RRZE\Newsletter\Scheduling {
    use RRZE\Newsletter\Tests\Support\RecurringLockEnvironment as State;
    use RRZE\Newsletter\Tests\Support\QueueEnvironment as Clock;

    function time(): int { return Clock::$timestamp; }
    function get_option(string $key, mixed $default = false): mixed { return State::$options[$key] ?? $default; }
    function add_option(string $key, mixed $value, string $deprecated, bool $autoload): bool
    {
        if (array_key_exists($key, State::$options)) { return false; }
        State::$options[$key] = $value;
        return true;
    }
    function wp_cache_delete(string $key, string $group): void { State::$cacheDeletes[] = [$key, $group]; }
}
