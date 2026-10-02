<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Utils;

final class UtilsValidationTest extends TestCase
{
    public function testIntegerRangeIncludesBothBoundaries(): void
    {
        foreach (['1' => 1, '5' => 5, '10' => 10] as $input => $expected) {
            self::assertSame($expected, Utils::validateIntRange((string) $input, 7, 1, 10));
        }
    }

    public function testOutOfRangeIntegersUseTheProvidedDefault(): void
    {
        foreach (['-1', '0', '11', '999999'] as $input) {
            self::assertSame(7, Utils::validateIntRange($input, 7, 1, 10));
        }
    }

    public function testIntegerRangeAllowsZeroAndNegativeValuesWhenConfigured(): void
    {
        self::assertSame(0, Utils::validateIntRange('0', 99, -5, 5));
        self::assertSame(-5, Utils::validateIntRange('-5', 99, -5, 5));
        self::assertSame(99, Utils::validateIntRange('-6', 99, -5, 5));
        self::assertSame(3, Utils::validateIntRange('3', 99, 3, 3));
        self::assertSame(99, Utils::validateIntRange('4', 99, 3, 3));
    }

    public function testValidDatesIncludeLeapDayAndTimeBoundaries(): void
    {
        foreach (['2024-02-29 00:00:00', '2000-02-29 12:30:45', '2026-12-31 23:59:59'] as $date) {
            self::assertTrue(Utils::validateDate($date), $date);
        }
    }

    public function testDateValidationRejectsNormalizedCalendarAndTimeOverflows(): void
    {
        foreach (['2025-02-29 12:00:00', '1900-02-29 12:00:00', '2026-04-31 12:00:00', '2026-13-01 12:00:00', '2026-01-00 12:00:00', '2026-01-01 24:00:00', '2026-01-01 12:60:00', '2026-01-01 12:00:60'] as $date) {
            self::assertFalse(Utils::validateDate($date), $date);
        }
    }

    public function testDateValidationRequiresExactDefaultFormat(): void
    {
        foreach (['', 'not a date', '2026-01-01', '2026-1-1 12:00:00', '2026-01-01T12:00:00', ' 2026-01-01 12:00:00', '2026-01-01 12:00:00 trailing'] as $date) {
            self::assertFalse(Utils::validateDate($date), $date);
        }
    }

    public function testDateValidationSupportsExplicitCustomFormat(): void
    {
        self::assertTrue(Utils::validateDate('29.02.2024', 'd.m.Y'));
        self::assertFalse(Utils::validateDate('29.02.2025', 'd.m.Y'));
        self::assertTrue(Utils::validateDate('2026-09-15', 'Y-m-d'));
        self::assertFalse(Utils::validateDate('15.09.2026', 'Y-m-d'));
    }

    public function testRecursiveKeySearchYieldsAllMatchesInTraversalOrder(): void
    {
        $input = [
            'target' => 'root',
            'children' => [
                ['target' => 'first', 'child' => ['target' => 'nested']],
                ['target' => 'second'],
            ],
            'unrelated' => 'target',
        ];

        self::assertSame(['root', 'first', 'nested', 'second'], iterator_to_array(Utils::recursiveSearchArrayKey($input, 'target'), false));
    }

    public function testRecursiveKeySearchPreservesFalseyAndArrayValues(): void
    {
        $input = [
            ['target' => null], ['target' => false], ['target' => 0], ['target' => ''],
            ['target' => ['target' => 'nested']],
        ];

        self::assertSame([null, false, 0, '', ['target' => 'nested'], 'nested'], iterator_to_array(Utils::recursiveSearchArrayKey($input, 'target'), false));
    }

    public function testRecursiveKeySearchReturnsNothingForMissingOrDifferentCaseKeys(): void
    {
        foreach ([[], ['Target' => 'wrong case'], ['other' => ['value' => 'target']]] as $input) {
            self::assertSame([], iterator_to_array(Utils::recursiveSearchArrayKey($input, 'target'), false));
        }
    }
}
