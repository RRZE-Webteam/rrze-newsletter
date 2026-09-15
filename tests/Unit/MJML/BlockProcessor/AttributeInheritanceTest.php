<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\BlockProcessor\AttributeInheritance;

final class AttributeInheritanceTest extends TestCase
{
    public function testChildAttributesOverrideDefaultsWhileMissingColorsComeFromParent(): void
    {
        self::assertSame([
            'font-size' => '24px',
            'padding' => '10px',
            'color' => '#111111',
            'link' => '#222222',
        ], AttributeInheritance::forChild(
            ['attrs' => ['font-size' => '24px']],
            ['color' => '#111111', 'link' => '#222222'],
            ['font-size' => '16px', 'padding' => '10px']
        ));
    }

    public function testExplicitChildColorsWinOverInheritedColors(): void
    {
        self::assertSame(
            ['color' => '#111111', 'link' => '#222222'],
            AttributeInheritance::forChild(
                ['attrs' => ['color' => '#111111', 'link' => '#222222']],
                ['color' => '#333333', 'link' => '#444444'],
                ['color' => '#555555', 'link' => '#666666']
            )
        );
    }

    public function testBlockWithoutAttributesReceivesSafeDefaultColors(): void
    {
        self::assertSame(
            ['color' => '#000000', 'link' => '#000000'],
            AttributeInheritance::forChild([], [], [])
        );
    }

    public function testGroupBackgroundSurvivesChildStylesWithoutInheritingGroupPaddingOrButtonFill(): void
    {
        $child = ['attrs' => ['style' => ['color' => ['text' => '#000000'], 'spacing' => ['padding' => ['top' => '8px']]]]];
        $defaults = ['style' => ['color' => ['background' => '#eeeeee']], 'customBackgroundColor' => '#eeeeee'];
        $before = $defaults;
        $attrs = AttributeInheritance::forChild($child, ['background-color' => '#04316a', 'padding' => '100px'], $defaults);
        self::assertSame('#04316a', $attrs['container-background-color']);
        self::assertArrayNotHasKey('background-color', $attrs);
        self::assertArrayNotHasKey('customBackgroundColor', $attrs);
        self::assertSame($child['attrs']['style'], $attrs['style']);
        self::assertSame($before, $defaults);
    }

    public function testTransparentIntermediateGroupsKeepTheNearestBackgroundAndChildBackgroundStillWins(): void
    {
        $defaults = ['container-background-color' => '#04316a', 'backgroundColor' => 'old-preset', 'style' => ['color' => ['background' => '#ff0000']]];
        foreach ([['customBackgroundColor' => '#ffffff'], ['style' => ['color' => ['background' => '#ffffff']]], ['background-color' => '#ffffff']] as $own) {
            $attrs = AttributeInheritance::forChild(['attrs' => $own], [], $defaults);
            self::assertSame('#04316a', $attrs['container-background-color']);
            self::assertSame('#ffffff', \RRZE\Newsletter\MJML\StyleProcessor::getColors($attrs)['background-color']);
        }
    }

    public function testChildTextPresetRemovesInheritedLiteralColor(): void
    {
        $attrs = AttributeInheritance::forChild(
            ['attrs' => ['textColor' => 'fau']],
            ['color' => '#111111'],
            ['color' => '#222222']
        );

        self::assertSame('fau', $attrs['textColor']);
        self::assertArrayNotHasKey('color', $attrs);
    }

    public function testChildLinkStyleOverridesInheritedLink(): void
    {
        foreach (['var:preset|color|fau' => 'fau', '#04316a' => '#04316a'] as $input => $expected) {
            $attrs = AttributeInheritance::forChild(
                ['attrs' => ['style' => ['elements' => ['link' => ['color' => ['text' => $input]]]]]],
                ['link' => '#111111'],
                ['link' => '#222222']
            );

            self::assertSame($expected, $attrs['link'], $input);
        }
    }

    public function testOwnLinkColorRemovesOnlyInheritedLinkAndPreservesParentDefaults(): void
    {
        $defaults = ['link' => '#111111', 'color' => '#222222', 'padding' => '10px'];
        $children = [
            ['attrs' => ['link' => '#333333']],
            ['attrs' => ['style' => ['elements' => ['link' => ['color' => ['text' => '#444444']]]]]],
        ];

        foreach ($children as $child) {
            self::assertSame(
                ['color' => '#222222', 'padding' => '10px'],
                AttributeInheritance::withoutParentLinkColor($defaults, $child)
            );
        }
        self::assertSame('#111111', $defaults['link']);
        self::assertSame($defaults, AttributeInheritance::withoutParentLinkColor($defaults, []));
    }
}
