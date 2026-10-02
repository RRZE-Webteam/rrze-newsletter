<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Blocks;

use ICal\ICal;
use PHPUnit\Framework\TestCase;

final class IcsParserTest extends TestCase
{
    public function testTimeZoneValidationAndRecurringEventsAcrossDstAreDeprecationFree(): void
    {
        set_error_handler(static function ($severity, $message, $file, $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        }, E_DEPRECATED | E_USER_DEPRECATED);
        try {
            // PHP 8.5 reports null timezone_id array keys while validating this zone
            // with older parser versions, before any calendar content is parsed.
            $parser = new ICal(false, ['defaultTimeZone' => 'Europe/Berlin', 'disableCharacterReplacement' => true]);
            $parser->initString(implode("\r\n", [
                'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//RRZE//Parser regression//EN',
                'BEGIN:VEVENT', 'UID:dst-regression@example.test', 'DTSTAMP:20261001T080000Z',
                'DTSTART;TZID=Europe/Berlin:20261024T100000', 'DTEND;TZID=Europe/Berlin:20261024T110000',
                'RRULE:FREQ=DAILY;COUNT=3', 'SUMMARY:Zeitumstellung – Grüße', 'END:VEVENT', 'END:VCALENDAR', '',
            ]));
            $events = $parser->events();
            self::assertCount(3, $events);
            $timestamps = [];
            foreach ($events as $event) {
                self::assertSame('Zeitumstellung – Grüße', $event->summary);
                $timestamps[] = (int) $event->dtstart_array[2];
                self::assertSame(3600, $event->dtend_array[2] - $event->dtstart_array[2]);
            }
            sort($timestamps);
            self::assertSame([
                strtotime('2026-10-24 08:00:00 UTC'),
                strtotime('2026-10-25 09:00:00 UTC'),
                strtotime('2026-10-26 09:00:00 UTC'),
            ], $timestamps);
        } finally {
            restore_error_handler();
        }
    }
}
