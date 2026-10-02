<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\AttributeHandler;

final class AttributeHandlerTest extends TestCase
{
    public function testCustomStylesBecomeMjmlAttributes(): void
    {
        $attrs = AttributeHandler::processAttributes([
            'customTextColor' => '#123456',
            'customBackgroundColor' => '#ffffff',
            'customFontSize' => 24,
            'fontSize' => 'small',
            'backgroundColor' => 'unused-preset',
            'padding' => '10px 20px',
        ]);

        self::assertSame([
            'padding' => '10px 20px',
            'textColor' => '#123456',
            'color' => '#123456',
            'background-color' => '#ffffff',
            'font-size' => '24px',
        ], $attrs);
    }

    public function testFullAlignmentBecomesFullWidth(): void
    {
        self::assertSame(
            ['full-width' => 'full-width'],
            AttributeHandler::processAttributes(['align' => 'full'])
        );
    }

    public function testRegularAlignmentAndExistingMjmlAttributesArePreserved(): void
    {
        $attrs = ['align' => 'center', 'width' => '50%', 'font-size' => '18px'];

        self::assertSame($attrs, AttributeHandler::processAttributes($attrs));
    }

    public function testNestedBlockStylesAreConvertedWithoutChangingInput(): void
    {
        $style = ['color' => ['text' => '#123456', 'background' => '#eeeeee']];
        $input = ['style' => $style, 'fontSize' => 'large'];

        $result = AttributeHandler::processAttributes($input);

        self::assertSame('#123456', $result['color']);
        self::assertSame('#eeeeee', $result['background-color']);
        self::assertSame('26px', $result['font-size']);
        self::assertSame(['style' => $style, 'fontSize' => 'large'], $input);
    }

    public function testEmptyAttributesDoNotIntroduceStyles(): void
    {
        self::assertSame([], AttributeHandler::processAttributes([]));
    }
}
