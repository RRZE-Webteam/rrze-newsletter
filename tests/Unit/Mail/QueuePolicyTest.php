<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Tests\Support\QueueEnvironment;
use RRZE\Newsletter\Tests\Support\WordPressState;

final class QueuePolicyTest extends TestCase
{
    private const RSS_KEY = 'rrze_newsletter_rss_block_not_empty';
    private const ICS_KEY = 'rrze_newsletter_ics_block_not_empty';

    protected function setUp(): void
    {
        WordPressState::reset();
        QueueEnvironment::reset();
    }

    protected function tearDown(): void
    {
        WordPressState::reset();
        QueueEnvironment::reset();
    }

    public function testNewsletterWithoutConditionalsDoesNotReadOrClearContentCache(): void
    {
        QueueEnvironment::$cache[42] = [self::RSS_KEY => true, self::ICS_KEY => true];

        self::assertFalse((new PolicyQueue())->shouldSkip(42));
        self::assertSame([], QueueEnvironment::$cacheReads);
        self::assertSame([], QueueEnvironment::$cacheDeletes);
        self::assertSame([self::RSS_KEY => true, self::ICS_KEY => true], QueueEnvironment::$cache[42]);
    }

    public function testEveryEnabledContentConditionMustHaveContent(): void
    {
        // enabled RSS, enabled ICS, RSS content, ICS content, expected skip
        $cases = [
            [false, false, false, false, false],
            [false, false, true, true, false],
            [true, false, false, true, true],
            [true, false, true, false, false],
            [false, true, true, false, true],
            [false, true, false, true, false],
            [true, true, false, false, true],
            [true, true, true, false, true],
            [true, true, false, true, true],
            [true, true, true, true, false],
        ];
        foreach ($cases as [$rssEnabled, $icsEnabled, $rssContent, $icsContent, $expected]) {
            QueueEnvironment::reset();
            WordPressState::$postMeta[42] = [
                'rrze_newsletter_has_conditionals' => '1',
                'rrze_newsletter_conditionals_rss_block' => $rssEnabled ? '1' : '',
                'rrze_newsletter_conditionals_ics_block' => $icsEnabled ? '1' : '',
            ];
            QueueEnvironment::$cache[42] = [self::RSS_KEY => $rssContent, self::ICS_KEY => $icsContent];

            self::assertSame($expected, (new PolicyQueue())->shouldSkip(42), json_encode([$rssEnabled, $icsEnabled, $rssContent, $icsContent]));
            self::assertSame([[self::RSS_KEY, 42], [self::ICS_KEY, 42]], QueueEnvironment::$cacheReads);
            self::assertSame([[self::RSS_KEY, 42], [self::ICS_KEY, 42]], QueueEnvironment::$cacheDeletes);
            self::assertSame([], QueueEnvironment::$cache[42]);
        }
    }

    public function testMissingCachedContentSkipsOnlyTheRelevantNewsletter(): void
    {
        WordPressState::$postMeta[42] = [
            'rrze_newsletter_has_conditionals' => '1',
            'rrze_newsletter_conditionals_rss_block' => '1',
        ];
        QueueEnvironment::$cache[99] = [self::RSS_KEY => true, self::ICS_KEY => true];

        self::assertTrue((new PolicyQueue())->shouldSkip(42));
        self::assertSame([self::RSS_KEY => true, self::ICS_KEY => true], QueueEnvironment::$cache[99]);
    }

    public function testContentResultCannotLeakIntoTheNextSkipCheck(): void
    {
        WordPressState::$postMeta[42] = [
            'rrze_newsletter_has_conditionals' => '1',
            'rrze_newsletter_conditionals_rss_block' => '1',
        ];
        QueueEnvironment::$cache[42] = [self::RSS_KEY => true];
        $queue = new PolicyQueue();

        self::assertFalse($queue->shouldSkip(42));
        self::assertTrue($queue->shouldSkip(42));
    }

    public function testReschedulingRequiresBothConditionalAndRecurringFlags(): void
    {
        foreach ([[false, false], [false, true], [true, false]] as [$conditional, $recurring]) {
            WordPressState::$postMeta[42] = [
                'rrze_newsletter_has_conditionals' => $conditional,
                'rrze_newsletter_is_recurring' => $recurring,
                'rrze_newsletter_recurrence_repeat' => 'DAILY',
            ];

            self::assertFalse((new PolicyQueue())->reschedule(42));
            self::assertSame([], WordPressState::$postUpdates);
        }
    }

    public function testHourlyRecurrenceCrossesMidnightAndStoresUtcDate(): void
    {
        QueueEnvironment::$siteNow = '2026-09-15 23:30:00';
        $this->assertRescheduled('HOURLY', '2026-09-16 00:30:00', '2026-09-15 22:30:00');
    }

    public function testDailyRecurrencePreservesLocalTimeAcrossDstChange(): void
    {
        QueueEnvironment::$siteNow = '2026-03-28 10:30:00';
        $this->assertRescheduled('DAILY', '2026-03-29 10:30:00', '2026-03-29 08:30:00');
    }

    public function testWeeklyRecurrenceUsesNextMatchingWeekdayAtMidnight(): void
    {
        // Weekly/monthly Queue policies currently use date-only start values.
        $this->assertRescheduled('WEEKLY', '2026-09-22 00:00:00', '2026-09-21 22:00:00');
    }

    public function testMonthlyDayRecurrenceUsesSameCalendarDayNextMonth(): void
    {
        $this->assertRescheduled('MONTHLY', '2026-10-15 00:00:00', '2026-10-14 22:00:00', 'BYMONTHDAY');
    }

    public function testMonthlyWeekdayRecurrenceUsesSameOrdinalWeekdayNextMonth(): void
    {
        // September 15 is the third Tuesday; October's third Tuesday is the 20th.
        $this->assertRescheduled('MONTHLY', '2026-10-20 00:00:00', '2026-10-19 22:00:00', 'BYSETPOS');
    }

    public function testAsapRecurrenceAddsFiveMinutesAcrossYearBoundary(): void
    {
        QueueEnvironment::$siteNow = '2026-12-31 23:58:00';
        $this->assertRescheduled('ASAP', '2027-01-01 00:03:00', '2026-12-31 23:03:00');
    }

    public function testMissingRepeatFallsBackToFiveMinutes(): void
    {
        $this->assertRescheduled('', '2026-09-15 10:35:00', '2026-09-15 08:35:00');
    }

    private function assertRescheduled(string $repeat, string $localDate, string $utcDate, string $monthly = ''): void
    {
        WordPressState::$postMeta[42] = [
            'rrze_newsletter_has_conditionals' => '1',
            'rrze_newsletter_is_recurring' => '1',
            'rrze_newsletter_recurrence_repeat' => $repeat,
            'rrze_newsletter_recurrence_monthly' => $monthly,
        ];

        self::assertSame(42, (new PolicyQueue())->reschedule(42));
        self::assertSame([[
            'ID' => 42,
            'post_status' => 'future',
            'post_date' => $localDate,
            'post_date_gmt' => $utcDate,
        ]], WordPressState::$postUpdates);
        self::assertSame([], WordPressState::$postMetaUpdates);
        self::assertSame([], WordPressState::$postMetaAdds);
    }
}

/** Exposes protected decisions without constructing SMTP or loading settings. */
final class PolicyQueue extends Queue
{
    public function __construct()
    {
    }

    public function shouldSkip(int $postId): bool
    {
        return $this->maybeSkipped($postId);
    }

    public function reschedule(int $postId): mixed
    {
        return $this->maybeSetRecurrence($postId);
    }
}
