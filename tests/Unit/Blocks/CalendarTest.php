<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Unit\Blocks;

use RRZE\Newsletter\Blocks\ICS\ICS;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\CalendarEnvironment as Calendar;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;

final class CalendarTest extends ApplicationTestCase
{
    private array $server;
    private mixed $locale;
    private bool $hadLocale;

    protected function setUp(): void
    {
        parent::setUp();
        Calendar::reset();
        App::$options['timezone_string'] = 'Europe/Berlin';
        $this->server = $_SERVER;
        $_SERVER['SERVER_NAME'] = 'example.test';
        $this->hadLocale = array_key_exists('wp_locale', $GLOBALS);
        $this->locale = $GLOBALS['wp_locale'] ?? null;
        $GLOBALS['wp_locale'] = null;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        if ($this->hadLocale) { $GLOBALS['wp_locale'] = $this->locale; } else { unset($GLOBALS['wp_locale']); }
        Calendar::reset();
        parent::tearDown();
    }

    public function testTwelveHourFormatsKeepPaddingCapitalizationAndSpacing(): void
    {
        foreach ([
            'g:i a' => '1:05&nbsp;pm', 'g:ia' => '1:05pm', 'g:i A' => '1:05&nbsp;PM', 'g:iA' => '1:05PM',
            'h:i a' => '01:05&nbsp;pm', 'h:ia' => '01:05pm', 'h:i A' => '01:05&nbsp;PM', 'h:iA' => '01:05PM',
        ] as $format => $expected) {
            self::assertSame($expected, CalendarHarness::time('13:05', $format), $format);
        }
    }

    public function testTwentyFourHourAndHourOnlyFormats(): void
    {
        foreach ([
            'H:i' => '09:05', 'G:i' => '9:05', 'Gi' => '905', 'Hi' => '0905',
            'H:00' => '09:00', 'h:00' => '09:00', 'H00' => '0900', 'g a' => '9 am', 'g A' => '9 AM',
        ] as $format => $expected) {
            self::assertSame($expected, CalendarHarness::time('9:05', $format), $format);
        }
    }

    public function testLiteralHourMinuteFormats(): void
    {
        foreach ([
            'G \\h i \\m\\i\\n' => '9&nbsp;h&nbsp;05&nbsp;min', 'G\\h i\\m\\i\\n' => '9h&nbsp;05min',
            'G\\hi\\m\\i\\n' => '9h05min', 'G \\h i \\m' => '9&nbsp;h&nbsp;05&nbsp;m',
            'G\\h i\\m' => '9h&nbsp;05m', 'G\\hi\\m' => '9h05m',
            'H \\h i \\m\\i\\n' => '09&nbsp;h&nbsp;05&nbsp;min', 'H\\h i\\m\\i\\n' => '09h&nbsp;05min',
            'H\\hi\\m\\i\\n' => '09h05min', 'H \\h i \\m' => '09&nbsp;h&nbsp;05&nbsp;m',
            'H\\h i\\m' => '09h&nbsp;05m', 'H\\hi\\m' => '09h05m',
        ] as $format => $expected) {
            self::assertSame($expected, CalendarHarness::time('09:05', $format), $format);
        }
    }

    public function testTimeParsingHandlesAmPmMidnightAndConfiguredDefault(): void
    {
        self::assertSame('13:05', CalendarHarness::time('1:05 pm', 'H:i'));
        self::assertSame('00:05', CalendarHarness::time('12:05 am', 'H:i'));
        self::assertSame('12:05&nbsp;am', CalendarHarness::time('00:05', 'g:i a'));
        self::assertSame('09:05', CalendarHarness::time('09:05'));
        self::assertSame('09:05', CalendarHarness::time('09:05', 'unsupported'));
    }

    public function testGreekLocaleUsesLocalizedMeridiem(): void
    {
        Calendar::$locale = 'el';
        self::assertSame('9:05&nbsp;πμ', CalendarHarness::time('09:05', 'g:i a'));
        self::assertSame('1:05&nbsp;μμ', CalendarHarness::time('13:05', 'g:i a'));
    }

    public function testDateFormattingAcceptsTimezoneObjectsStringsAndOffsets(): void
    {
        self::assertSame('2026-03-29 10:00 +02:00', CalendarHarness::date('Y-m-d H:i P', '2026-03-28 10:00:00', 'Europe/Berlin', '+1 day'));
        self::assertSame('2026-09-15 10:00 +00:00', CalendarHarness::date('Y-m-d H:i P', '2026-09-15 10:00:00', new \DateTimeZone('UTC')));
        self::assertSame('2026-09-14', CalendarHarness::date('Y-m-d', '2026-09-15', null, '+-1 day'));
        self::assertSame('2026-09-16', CalendarHarness::date('Y-m-d', '2026-09-15', null, '--1 day'));
        self::assertSame('2026-01-01 00:00:00', CalendarHarness::date('Y-m-d H:i:s', '1767225600'));
        App::$options['timezone_string'] = '';
        self::assertSame('+00:00', CalendarHarness::date('P', '2026-09-15'));
    }

    public function testFeedTimezoneUsesPerFeedFirstValueOrSiteFallback(): void
    {
        self::assertSame('America/New_York', CalendarHarness::timezone(['Europe/Berlin', 'America/New_York'], 1)->getName());
        self::assertSame('Europe/Berlin', CalendarHarness::timezone(['Europe/Berlin'], 5)->getName());
        self::assertSame('America/New_York', CalendarHarness::timezone('America/New_York', 0)->getName());
        foreach (['Unknown/Zone', '', null] as $timezone) {
            self::assertSame('Europe/Berlin', CalendarHarness::timezone($timezone, 0)->getName());
        }
    }

    public function testLocaleNamesAreEscapedBeforeDateFormatting(): void
    {
        $GLOBALS['wp_locale'] = new CalendarLocale();
        self::assertSame('TueLocal TuesdayLocal SepLocal SeptemberLocal morningLocal MORNINGLOCAL literal', CalendarHarness::date('D l M F a A \\l\\i\\t\\e\\r\\a\\l', '2026-09-15 09:05'));
        self::assertCount(1, Calendar::$formatCalls);
    }

    public function testEmptyContentIgnoresWhitespaceButPreservesMedia(): void
    {
        foreach (['', '   ', '<p>&nbsp;</p>'] as $content) { self::assertTrue(CalendarHarness::empty($content)); }
        foreach (['Text', '<img src="image.png">', '<iframe></iframe>', '<audio></audio>', '<video></video>'] as $content) { self::assertFalse(CalendarHarness::empty($content)); }
    }

    public function testLabelsAddBreakOpportunitiesAndExternalLinkAttributes(): void
    {
        self::assertSame('One/<wbr />Two & Three', CalendarHarness::label(['label' => 'One/Two &amp; Three']));
        $external = CalendarHarness::label(['label' => 'Event', 'url' => 'https://other.test/event']);
        self::assertStringContainsString('target="_blank" rel="noopener noreferrer nofollow"', $external);
        self::assertStringNotContainsString('target=', CalendarHarness::label(['label' => 'Event', 'url' => 'https://example.test/event']));
    }

    public function testOrganizerSupportsNamedMailtoScalarAndFallbackArray(): void
    {
        self::assertSame('<p >Plain organizer</p>', CalendarHarness::organizer('Plain organizer'));
        self::assertSame('<p >Fallback</p>', CalendarHarness::organizer([[], 'Fallback']));
        self::assertSame('<p ></p>', CalendarHarness::organizer([]));
        self::assertSame('<p ><a href="mailto:ada@example.test" rel="noopener noreferrer nofollow">Ada Lovelace</a></p>', CalendarHarness::organizer([['CN' => 'Ada%20Lovelace'], 'mailto:ada@example.test']));
    }

    public function testDescriptionOptionsControlLocationOrganizerAndTrimming(): void
    {
        $event = ['location' => 'Room 42', 'organizer' => 'Team', 'eventdesc' => 'Long description'];
        $attrs = ['displayLocation' => false, 'displayOrganizer' => false, 'displayDescription' => false, 'descriptionLimit' => false, 'descriptionLength' => 3];
        self::assertSame('', CalendarHarness::description($attrs, $event));
        $attrs['displayLocation'] = $attrs['displayOrganizer'] = $attrs['displayDescription'] = true;
        self::assertSame('<p >Room 42</p><p >Team</p>Long description', CalendarHarness::description($attrs, $event));
        $attrs['descriptionLimit'] = true;
        self::assertSame('<p >Room 42</p><p >Team</p>Trimmed fixture [&hellip;]', CalendarHarness::description($attrs, $event));
        self::assertContains(['wp_trim_words', 'Long description', 3, ' [&hellip;]'], Calendar::$formatCalls);
    }

    public function testRenderedEventsRespectItemLimitAndDeduplicateUids(): void
    {
        $event = ['uid' => 'one', 'label' => 'First event', 'start' => '09:00', 'end' => '10:00'];
        $feed = ['earliest' => '202609', 'latest' => '202609', 'events' => [2026 => ['09' => [15 => ['0900' => [$event, $event, array_replace($event, ['uid' => 'two', 'label' => 'Second event'])]]]]]];
        $attrs = ['itemsToShow' => 0, 'displayLocation' => false, 'displayOrganizer' => false, 'displayDescription' => false];
        $markup = CalendarHarness::renderItems($attrs, $feed);
        self::assertSame(1, substr_count($markup, 'First event'));
        self::assertSame(1, substr_count($markup, 'Second event'));
        self::assertStringContainsString('2026-09-15 09:00 &#8211; 10:00', $markup);
        $attrs['itemsToShow'] = 1;
        $markup = CalendarHarness::renderItems($attrs, $feed);
        self::assertStringContainsString('First event', $markup);
        self::assertStringNotContainsString('Second event', $markup);
    }

    public function testRenderingHonorsDateRangeAndStyleAttributes(): void
    {
        $feed = ['earliest' => '202609', 'latest' => '202609', 'events' => [2026 => [
            '08' => [15 => ['all-day' => [['uid' => 'old', 'label' => 'Old']]]],
            '09' => [15 => ['all-day' => [['uid' => 'current', 'label' => 'Current']]]],
            '10' => [15 => ['all-day' => [['uid' => 'future', 'label' => 'Future']]]],
        ]]];
        $attrs = ['headingFontSize' => '24px', 'headingColor' => '#123456', 'textFontSize' => '16px', 'textColor' => '#654321', 'displayLocation' => false, 'displayOrganizer' => false, 'displayDescription' => false];
        $markup = CalendarHarness::renderItems($attrs, $feed);
        self::assertStringContainsString('Current', $markup);
        self::assertStringNotContainsString('Old', $markup);
        self::assertStringNotContainsString('Future', $markup);
        self::assertStringContainsString('font-size:24px;color:#123456;', $markup);
        self::assertStringContainsString('font-size:16px;color:#654321;', $markup);
        self::assertSame('', CalendarHarness::renderItems($attrs, ['earliest' => '202609', 'latest' => '202609', 'events' => []]));
    }
}

final class CalendarHarness extends ICS
{
    public static function time(string $value, ?string $format = null): string { return parent::timeFormat($value, $format); }
    public static function date(string $format, mixed $value, mixed $timezone = null, ?string $offset = null): string { return parent::dateFormat($format, $value, $timezone, $offset); }
    public static function timezone(mixed $timezone, int $index): \DateTimeZone { return parent::getFeedTz(['tz' => $timezone], $index); }
    public static function empty(string $value): bool { return parent::emptyContent($value); }
    public static function label(array $event): string { return parent::eventLabelHtml($event); }
    public static function organizer(mixed $value): string { return parent::eventOrganizerHtml($value, ''); }
    public static function description(array $attrs, array $event): string { return parent::eventDescriptionHtml($attrs, $event, ''); }
    public static function renderItems(array $attrs, array $events): string { return parent::render($attrs, $events); }
}

final class CalendarLocale
{
    public array $month = ['fixture'];
    public array $weekday = ['fixture'];
    public function get_month(string $month): string { return 'SeptemberLocal'; }
    public function get_weekday(string $weekday): string { return 'TuesdayLocal'; }
    public function get_weekday_abbrev(string $weekday): string { return 'TueLocal'; }
    public function get_month_abbrev(string $month): string { return 'SepLocal'; }
    public function get_meridiem(string $meridiem): string { return $meridiem === 'am' ? 'morningLocal' : 'MORNINGLOCAL'; }
}
