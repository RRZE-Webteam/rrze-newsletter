<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\BlockProcessor\LayoutHelper;

final class LayoutHelperTest extends TestCase
{
    public function testSectionAttributesExcludeChildOnlyAndEditorAttributes(): void
    {
        $section = [
            'background-color' => '#ffffff',
            'background-url' => 'https://example.test/header.png',
            'padding' => '10px 20px',
            'border-radius' => '4px',
            'direction' => 'rtl',
            'full-width' => 'full-width',
            'text-align' => 'center',
        ];

        self::assertSame($section, LayoutHelper::filterSectionAttributes($section + [
            'font-size' => '16px',
            'href' => 'https://example.test',
            'width' => '50%',
            'className' => 'editor-only',
            'style' => ['spacing' => []],
        ]));
        self::assertSame([], LayoutHelper::filterSectionAttributes([]));
    }

    public function testCssPaddingShorthandSubtractsOnlyHorizontalSides(): void
    {
        foreach ([
            '20px' => 640,
            '10px 20px' => 640,
            '10px 20px 30px' => 640,
            '10px 20px 30px 40px' => 620,
            "  10px\t20px\n30px   40px  " => 620,
            '' => 680,
            '0' => 680,
        ] as $padding => $expected) {
            self::assertSame(
                $expected,
                LayoutHelper::subtractHorizontalPadding(680, (string) $padding),
                'padding: ' . $padding
            );
        }
    }

    public function testPercentagePaddingUsesContainerWidth(): void
    {
        self::assertSame(544, LayoutHelper::subtractHorizontalPadding(680, '0 10%'));
        self::assertSame(592, LayoutHelper::subtractHorizontalPadding(680, '0 10% 0 20px'));
    }

    public function testOversizedPaddingLeavesPositiveContentWidth(): void
    {
        self::assertSame(1, LayoutHelper::subtractHorizontalPadding(100, '80px'));
        self::assertSame(1, LayoutHelper::subtractHorizontalPadding(100, '50%'));
    }

    public function testPixelWidthsAcceptNumbersAndRoundFractionalPixels(): void
    {
        foreach ([
            [240, 240],
            ['240', 240],
            [' 240px ', 240],
            [240.6, 241],
            ['240.4px', 240],
        ] as [$input, $expected]) {
            self::assertSame($expected, LayoutHelper::resolvePixelValue($input), (string) $input);
            self::assertSame($expected, LayoutHelper::resolveWidth($input, 680), (string) $input);
        }
    }

    public function testPercentageWidthsResolveAgainstAvailableSpace(): void
    {
        self::assertSame(340, LayoutHelper::resolveWidth('50%', 680));
        self::assertSame(167, LayoutHelper::resolveWidth('33.3%', 500));
        self::assertSame(1, LayoutHelper::resolveWidth('0.1%', 100));
    }

    public function testInvalidAndUnsupportedWidthsAreRejected(): void
    {
        foreach ([null, false, [], new \stdClass(), '', 'auto', '2rem', 'calc(100% - 20px)', '-10px', 0] as $input) {
            $label = var_export($input, true);
            self::assertNull(LayoutHelper::resolvePixelValue($input), $label);
            self::assertNull(LayoutHelper::resolveWidth($input, 680), $label);
        }
        self::assertNull(LayoutHelper::resolvePixelValue('50%'));
        self::assertNull(LayoutHelper::resolveWidth('0%', 680));
        self::assertNull(LayoutHelper::resolveWidth('-10%', 680));
    }
}
