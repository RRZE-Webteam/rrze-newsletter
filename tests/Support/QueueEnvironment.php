<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    /** In-memory boundaries only; not a WordPress database or object-cache emulator. */
    final class QueueEnvironment
    {
        public static int $timestamp = 1767225600;
        public static array $microtimes = [];
        public static string $siteNow = '2026-09-15 10:30:00';
        public static string $timezone = 'Europe/Berlin';
        public static string $blogName = 'Test Site';
        public static array $cache = [];
        public static array $cacheReads = [];
        public static array $cacheDeletes = [];

        public static function reset(): void
        {
            self::$timestamp = 1767225600;
            self::$microtimes = [];
            self::$siteNow = '2026-09-15 10:30:00';
            self::$timezone = 'Europe/Berlin';
            self::$blogName = 'Test Site';
            self::$cache = [];
            self::$cacheReads = [];
            self::$cacheDeletes = [];
        }
    }
}

namespace RRZE\Newsletter\Mail {
    use RRZE\Newsletter\Tests\Support\QueueEnvironment;

    function time(): int
    {
        return QueueEnvironment::$timestamp;
    }

    function microtime(bool $asFloat = false): float|string
    {
        if (!$asFloat) {
            throw new \LogicException('Queue tests only support a numeric clock.');
        }
        return array_shift(QueueEnvironment::$microtimes) ?? 0.0;
    }

    function current_time(string $format, bool $gmt = false): string
    {
        $date = new \DateTimeImmutable(QueueEnvironment::$siteNow, new \DateTimeZone(QueueEnvironment::$timezone));
        if ($gmt) {
            $date = $date->setTimezone(new \DateTimeZone('UTC'));
        }
        return $date->format($format === 'mysql' ? 'Y-m-d H:i:s' : $format);
    }

    function get_gmt_from_date(string $date): string
    {
        return (new \DateTimeImmutable($date, new \DateTimeZone(QueueEnvironment::$timezone)))
            ->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    function wp_cache_get(string $key, int $group): mixed
    {
        QueueEnvironment::$cacheReads[] = [$key, $group];
        return QueueEnvironment::$cache[$group][$key] ?? false;
    }

    function wp_cache_delete(string $key, int $group): bool
    {
        QueueEnvironment::$cacheDeletes[] = [$key, $group];
        $exists = array_key_exists($key, QueueEnvironment::$cache[$group] ?? []);
        unset(QueueEnvironment::$cache[$group][$key]);
        return $exists;
    }
}

namespace RRZE\Newsletter {
    use RRZE\Newsletter\Tests\Support\QueueEnvironment;

    function get_option(string $name, mixed $default = false): mixed
    {
        return match ($name) {
            'rrze_newsletter_unit' => \RRZE\Newsletter\Tests\Support\SettingsEnvironment::$stored,
            'timezone_string' => QueueEnvironment::$timezone,
            'gmt_offset' => 0,
            default => throw new \LogicException('Unexpected option lookup: ' . $name),
        };
    }

    // Identity translation for recurrence labels, not an internationalization test.
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}
