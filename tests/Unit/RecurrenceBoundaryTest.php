<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use DateTime;
use DateTimeZone;
use Exception;
use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Recurrence;

final class RecurrenceBoundaryTest extends TestCase
{
    public function testDateSettersCloneTheirArguments(): void
    {
        $start = $this->date('2026-01-01 09:00:00');
        $end = $this->date('2026-01-03 09:00:00');
        $rule = $this->rule();
        self::assertSame($rule, $rule->startDate($start)->until($end));
        $start->modify('+1 year');
        $end->modify('+1 year');
        self::assertSame('2026-01-01 09:00:00', $rule->startDate->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-03 09:00:00', $rule->until->format('Y-m-d H:i:s'));
    }

    public function testInvalidDateAndNumericSettersThrowUsefulExceptions(): void
    {
        foreach (['startDate' => 'tomorrow', 'until' => 'tomorrow', 'count' => 'many', 'interval' => 'often', 'wkst' => 'XX', 'exclusions' => [false]] as $method => $value) {
            try {
                $this->rule()->$method($value);
                self::fail($method . ' should reject its invalid input');
            } catch (Exception $exception) {
                self::assertStringContainsString('::' . $method . ': Accepts', $exception->getMessage());
            }
        }
    }

    public function testNumericListSettersAcceptBoundariesAndCustomDelimiters(): void
    {
        $cases = [
            ['bysecond', 'byseconds', '0|60', [0, 60]],
            ['byminute', 'byminutes', '0|59', [0, 59]],
            ['byhour', 'byhours', '0|23', [0, 23]],
            ['bymonthday', 'bymonthdays', '-31|31', [-31, 31]],
            ['byyearday', 'byyeardays', '-366|366', [-366, 366]],
            ['byweekno', 'byweeknos', '-53|53', [-53, 53]],
            ['bymonth', 'bymonths', '1|12', [1, 12]],
            ['bysetpos', 'bysetpos', '-366|366', [-366, 366]],
        ];
        foreach ($cases as [$method, $property, $input, $expected]) {
            $rule = $this->rule();
            self::assertSame($rule, $rule->$method('|' . $input . '|', '|'));
            self::assertSame($expected, $rule->$property, $method);
        }
    }

    public function testNumericListSettersRejectOutOfBoundsAndEmptyLists(): void
    {
        foreach (['bysecond' => [-1, 61], 'byminute' => [-1, 60], 'byhour' => [-1, 24], 'bymonthday' => [0, 32], 'byyearday' => [0, 367], 'byweekno' => [0, 54], 'bymonth' => [0, 13], 'bysetpos' => [0, 367]] as $method => $invalid) {
            foreach (array_merge($invalid, [[]]) as $value) {
                try {
                    $this->rule()->$method($value);
                    self::fail($method . ' accepted invalid input');
                } catch (Exception $exception) {
                    self::assertStringContainsString('::' . $method . ': Accepts', $exception->getMessage());
                }
            }
        }
    }

    public function testWeekdayListsNormalizeCaseOrdinalsAndDelimiters(): void
    {
        $rule = $this->rule();
        self::assertSame($rule, $rule->byday('|MO|+2Tu|-1FR|', '|')->wkst('SU'));
        self::assertSame(['0mo', '2tu', '-1fr'], $rule->bydays);
        self::assertSame('su', $rule->wkst);
        $rule->byday('WE');
        self::assertSame(['0we'], $rule->bydays);
    }

    public function testWeekdayOrdinalsOutsideRangeAreRejected(): void
    {
        foreach (['0MO', '54MO', '-54FR'] as $value) {
            try {
                $this->rule()->byday($value);
                self::fail('Invalid ordinal accepted');
            } catch (Exception $exception) {
                self::assertStringContainsString('valid week day', $exception->getMessage());
            }
        }
    }

    public function testStringExclusionsRemoveDatesFromGeneratedSeries(): void
    {
        foreach (['2026-01-02 09:00:00', '|2026-01-02 09:00:00|2026-01-03 09:00:00|'] as $excluded) {
            $rule = $this->rule()->rrule('FREQ=DAILY;COUNT=2')->exclusions($excluded, '|');
            $rule->generateOccurrences();
            $last = str_contains($excluded, '01-03') ? '2026-01-04 09:00:00' : '2026-01-03 09:00:00';
            self::assertSame(['2026-01-01 09:00:00', $last], $this->format($rule->occurrences));
        }
    }

    public function testRuleParsesExplicitStartAndIncludesExactUntil(): void
    {
        $rule = $this->rule()->rrule('DTSTART=20260102T090000Z;FREQ=DAILY;UNTIL=20260104T090000Z');
        self::assertTrue($rule->occursOn($this->date('2026-01-04 09:00:00')));
        $rule->generateOccurrences();
        self::assertSame(['2026-01-02 09:00:00', '2026-01-03 09:00:00', '2026-01-04 09:00:00'], $this->format($rule->occurrences));
    }

    public function testExactUntilIsIncludedAcrossAllFrequencies(): void
    {
        foreach ([
            'yearly' => ['2027-01-01 09:00:00', '2028-01-01 09:00:00'],
            'monthly' => ['2026-02-01 09:00:00', '2026-03-01 09:00:00'],
            'weekly' => ['2026-01-08 09:00:00', '2026-01-15 09:00:00'],
            'daily' => ['2026-01-02 09:00:00', '2026-01-03 09:00:00'],
            'hourly' => ['2026-01-01 10:00:00', '2026-01-01 11:00:00'],
            'minutely' => ['2026-01-01 09:01:00', '2026-01-01 09:02:00'],
            'secondly' => ['2026-01-01 09:00:01', '2026-01-01 09:00:02'],
        ] as $frequency => [$middle, $end]) {
            $rule = $this->rule()->freq($frequency)->until($this->date($end));
            $rule->generateOccurrences();
            self::assertSame(['2026-01-01 09:00:00', $middle, $end], $this->format($rule->occurrences), $frequency);
        }
    }

    public function testDailyUntilDistinguishesOneSecondBeforeExactAndOneSecondAfter(): void
    {
        foreach (['08:59:59' => false, '09:00:00' => true, '09:00:01' => true] as $time => $includesLast) {
            $rule = $this->rule()->freq('daily')->until($this->date('2026-01-03 ' . $time));
            $rule->generateOccurrences();
            $expected = ['2026-01-01 09:00:00', '2026-01-02 09:00:00'];
            if ($includesLast) { $expected[] = '2026-01-03 09:00:00'; }
            self::assertSame($expected, $this->format($rule->occurrences), $time);
        }
    }

    public function testUntilEqualToStartIncludesStartOnlyOnce(): void
    {
        $rule = $this->rule()->freq('daily')->until($this->date('2026-01-01 09:00:00'));
        $rule->generateOccurrences();
        self::assertSame(['2026-01-01 09:00:00'], $this->format($rule->occurrences));
    }

    public function testInclusiveUntilStillHonorsExclusionsCountAndInterval(): void
    {
        $end = $this->date('2026-01-03 09:00:00');
        $excluded = $this->rule()->freq('daily')->until($end)->exclusions([$end]);
        $excluded->generateOccurrences();
        self::assertSame(['2026-01-01 09:00:00', '2026-01-02 09:00:00'], $this->format($excluded->occurrences));

        $limited = $this->rule()->freq('daily')->until($end)->count(2);
        $limited->generateOccurrences();
        self::assertSame(['2026-01-01 09:00:00', '2026-01-02 09:00:00'], $this->format($limited->occurrences));

        $interval = $this->rule()->freq('daily')->interval(2)->until($end);
        $interval->generateOccurrences();
        self::assertSame(['2026-01-01 09:00:00', '2026-01-03 09:00:00'], $this->format($interval->occurrences));
        $nonMatching = $this->rule()->freq('daily')->interval(2)->until($this->date('2026-01-04 09:00:00'));
        $nonMatching->generateOccurrences();
        self::assertSame(['2026-01-01 09:00:00', '2026-01-03 09:00:00'], $this->format($nonMatching->occurrences));
    }

    public function testUtcUntilMatchesLocalOccurrenceAcrossDaylightSavingChange(): void
    {
        $rule = new Recurrence('2026-03-28 09:00:00', new DateTimeZone('Europe/Berlin'));
        $rule->rrule('FREQ=DAILY;UNTIL=20260330T070000Z')->generateOccurrences();
        self::assertSame([
            '2026-03-28 09:00:00 +01:00',
            '2026-03-29 09:00:00 +02:00',
            '2026-03-30 09:00:00 +02:00',
        ], array_map(static fn (DateTime $date): string => $date->format('Y-m-d H:i:s P'), $rule->occurrences));
    }

    public function testWindowIncludesExactUntilWithoutChangingOriginalRule(): void
    {
        $rule = $this->rule()->rrule('FREQ=DAILY;UNTIL=20260103T090000Z');
        self::assertSame(['2026-01-02 09:00:00', '2026-01-03 09:00:00'], $this->format(
            $rule->getOccurrencesBetween($this->date('2026-01-02 09:00:00'), $this->date('2026-01-04 09:00:00'))
        ));
        self::assertSame(['2026-01-03 09:00:00'], $this->format(
            $rule->getOccurrencesBetween($this->date('2026-01-03 09:00:00'), $this->date('2026-01-04 09:00:00'))
        ));
        self::assertSame('2026-01-01 09:00:00', $rule->startDate->format('Y-m-d H:i:s'));
        self::assertSame('2026-01-03 09:00:00', $rule->until->format('Y-m-d H:i:s'));
        self::assertSame([], $rule->occurrences);
    }

    public function testOccurrenceDateChecksStartAndEndBoundaries(): void
    {
        $rule = $this->rule()->freq('daily')->until($this->date('2026-01-03 09:00:00'));
        self::assertFalse($rule->occursOn($this->date('2026-01-01 08:59:59')));
        self::assertTrue($rule->occursOn($this->date('2026-01-01 09:00:00')));
        self::assertTrue($rule->occursOn($this->date('2026-01-03 09:00:00')));
        self::assertFalse($rule->occursOn($this->date('2026-01-03 09:00:01')));
    }

    public function testOccurrenceTimeChecksAllThreeTimeParts(): void
    {
        $rule = $this->rule()->byhour(9)->byminute(15)->bysecond(30);
        self::assertTrue($rule->occursAt($this->date('2026-01-01 09:15:30')));
        foreach (['08:15:30', '09:14:30', '09:15:29'] as $time) {
            self::assertFalse($rule->occursAt($this->date('2026-01-01 ' . $time)));
        }
        self::assertTrue($this->rule()->occursAt($this->date('2026-01-01 23:59:59')));
    }

    public function testYearAndWeekNumberFiltersMatchOnlySelectedDates(): void
    {
        $year = $this->rule()->byyearday('-1');
        self::assertTrue($year->occursOn($this->date('2026-12-31 09:00:00')));
        self::assertFalse($year->occursOn($this->date('2026-12-30 09:00:00')));
        $week = $this->rule()->byweekno('2');
        self::assertTrue($week->occursOn($this->date('2026-01-05 09:00:00')));
        self::assertFalse($week->occursOn($this->date('2026-01-12 09:00:00')));
    }

    public function testIntervalsAreMeasuredInTheirFrequencyUnits(): void
    {
        foreach ([
            ['yearly', '2028-01-01 09:00:00', '2027-01-01 09:00:00'],
            ['monthly', '2026-03-01 09:00:00', '2026-02-01 09:00:00'],
            ['weekly', '2026-01-15 09:00:00', '2026-01-08 09:00:00'],
            ['daily', '2026-01-03 09:00:00', '2026-01-02 09:00:00'],
            ['hourly', '2026-01-01 11:00:00', '2026-01-01 10:00:00'],
            ['minutely', '2026-01-01 09:02:00', '2026-01-01 09:01:00'],
            ['secondly', '2026-01-01 09:00:02', '2026-01-01 09:00:01'],
        ] as [$frequency, $included, $excluded]) {
            $rule = $this->rule()->freq($frequency)->interval(2);
            self::assertTrue($rule->occursOn($this->date($included)), $frequency);
            self::assertFalse($rule->occursOn($this->date($excluded)), $frequency);
        }
    }

    public function testBiweeklyRuleHonorsSundayWeekStart(): void
    {
        $rule = $this->rule()->rrule('FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=SU;COUNT=3');
        $rule->generateOccurrences();
        self::assertSame(['2026-01-11 09:00:00', '2026-01-25 09:00:00', '2026-02-08 09:00:00'], $this->format($rule->occurrences));
    }

    public function testMinuteAndSecondSeriesCrossHourBoundaries(): void
    {
        foreach (['MINUTELY' => ['2026-01-01 09:59:58', '2026-01-01 10:01:58', '2026-01-01 10:03:58'], 'SECONDLY' => ['2026-01-01 09:59:58', '2026-01-01 10:00:00', '2026-01-01 10:00:02']] as $frequency => $expected) {
            $rule = new Recurrence('2026-01-01 09:59:58', new DateTimeZone('UTC'));
            $rule->rrule('FREQ=' . $frequency . ';INTERVAL=2;COUNT=3')->generateOccurrences();
            self::assertSame($expected, $this->format($rule->occurrences));
        }
    }

    public function testYearlyWeekdayRuleSelectsFirstMondayOfFebruary(): void
    {
        $rule = $this->rule()->rrule('FREQ=YEARLY;BYMONTH=2;BYDAY=1MO;COUNT=3');
        $rule->generateOccurrences();
        self::assertSame(['2026-02-02 09:00:00', '2027-02-01 09:00:00', '2028-02-07 09:00:00'], $this->format($rule->occurrences));
    }

    public function testStrictModeRejectsNonMatchingStart(): void
    {
        $rule = $this->rule()->rrule('FREQ=DAILY;BYMONTH=2;COUNT=1');
        $rule->RFC5545_COMPLIANT = Recurrence::EXCEPTION;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid startDate');
        $rule->generateOccurrences();
    }

    public function testMissingFrequencyIsRejectedBeforeGeneration(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Frequency required');
        $this->rule()->generateOccurrences();
    }

    public function testEmptyReversedAndExpiredWindowsReturnNoOccurrences(): void
    {
        $rule = $this->rule()->rrule('FREQ=DAILY;UNTIL=20260103T090000Z');
        foreach ([['2026-01-02', '2026-01-02'], ['2026-01-03', '2026-01-02'], ['2026-01-04', '2026-01-05']] as [$start, $end]) {
            self::assertSame([], $rule->getOccurrencesBetween($this->date($start), $this->date($end)));
        }
    }

    public function testExhaustedCountHasNoLaterOccurrence(): void
    {
        $rule = $this->rule()->rrule('FREQ=DAILY;COUNT=2');
        self::assertSame([], $rule->getOccurrencesBetween($this->date('2026-01-10'), $this->date('2026-01-11')));
        self::assertFalse($rule->getNextOccurrence($this->date('2026-01-10')));
        self::assertFalse($rule->getPrevOccurrence($this->date('2025-12-01')));
    }

    public function testWindowIncludesMatchingStartAndStopsAfterLastMatchingDate(): void
    {
        $rule = $this->rule()->rrule('FREQ=DAILY;COUNT=5');
        self::assertSame(['2026-01-02 09:00:00', '2026-01-03 09:00:00'], $this->format($rule->getOccurrencesBetween($this->date('2026-01-02 09:00:00'), $this->date('2026-01-03 09:00:00'))));
    }

    private function rule(): Recurrence
    {
        return new Recurrence('2026-01-01 09:00:00', new DateTimeZone('UTC'));
    }

    private function date(string $date): DateTime
    {
        return new DateTime($date, new DateTimeZone('UTC'));
    }

    private function format(array $dates): array
    {
        return array_map(static fn (DateTime $date): string => $date->format('Y-m-d H:i:s'), $dates);
    }
}
