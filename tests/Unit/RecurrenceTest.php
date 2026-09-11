<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use DateTime;
use DateTimeZone;
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

    private function formatOccurrences(
        Recurrence $recurrence,
        string $format = 'Y-m-d H:i:s'
    ): array {
        return array_map(
            static fn (DateTime $date): string => $date->format($format),
            $recurrence->occurrences
        );
    }
}
