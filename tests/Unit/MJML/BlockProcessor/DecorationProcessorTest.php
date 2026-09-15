<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\SeparatorProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\SpacerProcessor;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class DecorationProcessorTest extends MjmlTestCase
{
    public function testSpacerAcceptsPixelAndPresetHeightsWithZeroDefault(): void
    {
        foreach ([
            [[], '0px'],
            [['height' => 24], '24px'],
            [['height' => '32px'], '32px'],
            [['height' => 'var:preset|spacing|40'], '40px'],
        ] as [$attrs, $height]) {
            $xpath = $this->parseMjml(SpacerProcessor::render($attrs));
            $this->assertNodes($xpath, '/test-root/mj-spacer', 1);
            self::assertSame([$height], $this->values($xpath, '//mj-spacer/@height'));
        }
    }

    public function testSeparatorDefaultsToShortBlackOnePixelLine(): void
    {
        $xpath = $this->parseMjml(SeparatorProcessor::render([]));

        self::assertSame(['128px'], $this->values($xpath, '//mj-divider/@width'));
        self::assertSame(['1px'], $this->values($xpath, '//mj-divider/@border-width'));
        self::assertSame(['#000000'], $this->values($xpath, '//mj-divider/@border-color'));
        self::assertSame(['0'], $this->values($xpath, '//mj-divider/@padding'));
    }

    public function testSeparatorColorFallsBackThroughProcessedAttributes(): void
    {
        foreach ([
            [['background-color' => '#111111', 'border-color' => '#222222', 'color' => '#333333'], '#111111'],
            [['border-color' => '#222222', 'color' => '#333333'], '#222222'],
            [['color' => '#333333'], '#333333'],
            [['backgroundColor' => 'missing-preset', 'color' => '#333333'], '#333333'],
        ] as [$attrs, $expected]) {
            $xpath = $this->parseMjml(SeparatorProcessor::render($attrs));
            self::assertSame([$expected], $this->values($xpath, '//mj-divider/@border-color'));
        }
    }
}
