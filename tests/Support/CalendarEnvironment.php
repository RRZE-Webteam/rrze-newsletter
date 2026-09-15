<?php
declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support {
    final class CalendarEnvironment
    {
        public static string $locale = 'en_US';
        public static array $formatCalls = [];
        public static function reset(): void { self::$locale = 'en_US'; self::$formatCalls = []; }
    }
}

namespace RRZE\Newsletter\Blocks\ICS {
    use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
    use RRZE\Newsletter\Tests\Support\CalendarEnvironment as Calendar;

    function get_option(string $name): mixed { return App::$options[$name] ?? ''; }
    function get_locale(): string { return Calendar::$locale; }
    function wp_timezone(): \DateTimeZone { return new \DateTimeZone(App::$options['timezone_string'] ?? 'UTC'); }
    function wp_maybe_decline_date(string $date, string $format): string { Calendar::$formatCalls[] = [$date, $format]; return $date; }
    function esc_url(string $url): string { return \RRZE\Newsletter\esc_url($url); }
    function absint(mixed $value): int { return abs((int) $value); }
    // These presentation boundaries are identity fixtures, not WP typography tests.
    function wptexturize(string $value): string { Calendar::$formatCalls[] = ['wptexturize', $value]; return $value; }
    function convert_chars(string $value): string { Calendar::$formatCalls[] = ['convert_chars', $value]; return $value; }
    function wpautop(string $value): string { Calendar::$formatCalls[] = ['wpautop', $value]; return $value; }
    function make_clickable(string $value): string { Calendar::$formatCalls[] = ['make_clickable', $value]; return $value; }
    function wp_trim_words(string $value, int $length, string $more): string
    {
        Calendar::$formatCalls[] = ['wp_trim_words', $value, $length, $more];
        return 'Trimmed fixture' . $more;
    }
}
