<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use DateTime;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Recurrence;

final class RecurrenceTest extends TestCase
{
    public function testWeeklyRuleKeepsWeekdayAndTime(): void
    {
        $recurrence = new Recurrence(
            '2026-09-11 09:30:00',
            new DateTimeZone('UTC')
        );
        $recurrence
            ->rrule('FREQ=WEEKLY;BYDAY=FR;INTERVAL=1')
            ->count(3)
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-09-11 09:30:00',
                '2026-09-18 09:30:00',
                '2026-09-25 09:30:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testMonthlyDayRuleCrossesDifferentMonthLengths(): void
    {
        $recurrence = new Recurrence(
            '2026-01-01 14:00:00',
            new DateTimeZone('Europe/Berlin')
        );
        $recurrence
            ->rrule('FREQ=MONTHLY;BYMONTHDAY=15;INTERVAL=1')
            ->count(4)
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-01-15 14:00:00 Europe/Berlin',
                '2026-02-15 14:00:00 Europe/Berlin',
                '2026-03-15 14:00:00 Europe/Berlin',
                '2026-04-15 14:00:00 Europe/Berlin',
            ],
            $this->formatOccurrences($recurrence, 'Y-m-d H:i:s e')
        );
    }

    public function testExcludedStartDateIsNotReturned(): void
    {
        $start = new DateTime('2026-09-11 09:00:00', new DateTimeZone('UTC'));
        $recurrence = new Recurrence();
        $recurrence
            ->startDate($start)
            ->rrule('FREQ=WEEKLY;BYDAY=FR;INTERVAL=1')
            ->exclusions([$start])
            ->count(2)
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-09-18 09:00:00',
                '2026-09-25 09:00:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testDailyIntervalSkipsIntermediateDays(): void
    {
        $recurrence = new Recurrence(
            '2026-01-01 09:15:30',
            new DateTimeZone('UTC')
        );
        $recurrence
            ->rrule('FREQ=DAILY;INTERVAL=2;COUNT=4')
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-01-01 09:15:30',
                '2026-01-03 09:15:30',
                '2026-01-05 09:15:30',
                '2026-01-07 09:15:30',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testRuleCanGenerateMultipleTimesPerDay(): void
    {
        $recurrence = new Recurrence(
            '2026-01-01 01:30:00',
            new DateTimeZone('Europe/Berlin')
        );
        $recurrence
            ->rrule(
                'FREQ=DAILY;COUNT=4;BYHOUR=9,17;BYMINUTE=15;BYSECOND=0'
            )
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-01-01 09:15:00',
                '2026-01-01 17:15:00',
                '2026-01-02 09:15:00',
                '2026-01-02 17:15:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testYearlyLastDayOfFebruaryHandlesLeapYears(): void
    {
        $recurrence = new Recurrence(
            '2024-02-29 12:00:00',
            new DateTimeZone('UTC')
        );
        $recurrence
            ->rrule('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1;COUNT=4')
            ->generateOccurrences();

        self::assertSame(
            [
                '2024-02-29 12:00:00',
                '2025-02-28 12:00:00',
                '2026-02-28 12:00:00',
                '2027-02-28 12:00:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testNegativeWeekdaySelectsLastFridayOfMonth(): void
    {
        $recurrence = new Recurrence(
            '2026-01-01 09:00:00',
            new DateTimeZone('UTC')
        );
        $recurrence
            ->rrule('FREQ=MONTHLY;BYDAY=-1FR;COUNT=4')
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-01-30 09:00:00',
                '2026-02-27 09:00:00',
                '2026-03-27 09:00:00',
                '2026-04-24 09:00:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testDailyRuleKeepsLocalTimeAcrossDaylightSavingChange(): void
    {
        $recurrence = new Recurrence(
            '2026-03-27 09:00:00',
            new DateTimeZone('Europe/Berlin')
        );
        $recurrence
            ->rrule('FREQ=DAILY;COUNT=5')
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-03-27 09:00:00 +01:00',
                '2026-03-28 09:00:00 +01:00',
                '2026-03-29 09:00:00 +02:00',
                '2026-03-30 09:00:00 +02:00',
                '2026-03-31 09:00:00 +02:00',
            ],
            $this->formatOccurrences($recurrence, 'Y-m-d H:i:s P')
        );
    }

    public function testOccurrencesBetweenHonoursLimitWithoutMutatingRule(): void
    {
        $timezone = new DateTimeZone('UTC');
        $recurrence = new Recurrence('2026-09-10 09:00:00', $timezone);
        $recurrence->rrule('FREQ=DAILY');

        $occurrences = $recurrence->getOccurrencesBetween(
            new DateTime('2026-09-11 10:00:00', $timezone),
            new DateTime('2026-09-20 08:00:00', $timezone),
            2
        );

        self::assertSame(
            ['2026-09-12 09:00:00', '2026-09-13 09:00:00'],
            $this->formatDates($occurrences)
        );
        self::assertSame([], $recurrence->occurrences);
    }

    public function testNextOccurrenceCanIncludeOrExcludeGivenOccurrence(): void
    {
        $timezone = new DateTimeZone('UTC');
        $recurrence = new Recurrence('2026-09-10 09:00:00', $timezone);
        $recurrence->rrule('FREQ=DAILY');
        $current = new DateTime('2026-09-11 09:00:00', $timezone);

        $inclusive = $recurrence->getNextOccurrence($current, false);
        $strict = $recurrence->getNextOccurrence($current, true);

        self::assertInstanceOf(DateTime::class, $inclusive);
        self::assertInstanceOf(DateTime::class, $strict);
        self::assertSame('2026-09-11 09:00:00', $inclusive->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-12 09:00:00', $strict->format('Y-m-d H:i:s'));
    }

    public function testPreviousOccurrenceExcludesGivenOccurrence(): void
    {
        $timezone = new DateTimeZone('UTC');
        $recurrence = new Recurrence('2026-09-10 09:00:00', $timezone);
        $recurrence->rrule('FREQ=DAILY');

        $previous = $recurrence->getPrevOccurrence(
            new DateTime('2026-09-12 09:00:00', $timezone)
        );

        self::assertInstanceOf(DateTime::class, $previous);
        self::assertSame('2026-09-11 09:00:00', $previous->format('Y-m-d H:i:s'));
    }

    public function testRrulePrefixAndTrailingSeparatorAreAccepted(): void
    {
        $recurrence = new Recurrence(
            '2026-09-11 08:00:00',
            new DateTimeZone('UTC')
        );
        $recurrence
            ->rrule('RRULE:FREQ=HOURLY;INTERVAL=6;COUNT=3;')
            ->generateOccurrences();

        self::assertSame(
            [
                '2026-09-11 08:00:00',
                '2026-09-11 14:00:00',
                '2026-09-11 20:00:00',
            ],
            $this->formatOccurrences($recurrence)
        );
    }

    public function testInvalidFrequencyIsRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Accepts secondly, minutely, hourly');

        (new Recurrence())->freq('fortnightly');
    }

    public function testInvalidMinuteIsRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('between 0 and 59');

        (new Recurrence())->byminute(60);
    }

    public function testInvalidWeekdayIsRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('valid week day');

        (new Recurrence())->byday('XY');
    }

    public function testInvalidMonthDayIsRejected(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('between 1 and 31');

        (new Recurrence())->bymonthday(32);
    }

    private function formatOccurrences(
        Recurrence $recurrence,
        string $format = 'Y-m-d H:i:s'
    ): array {
        return $this->formatDates($recurrence->occurrences, $format);
    }

    private function formatDates(
        array $dates,
        string $format = 'Y-m-d H:i:s'
    ): array {
        return array_map(
            static fn (DateTime $date): string => $date->format($format),
            $dates
        );
    }
}
