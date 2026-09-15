<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\StyleProcessor;

final class StyleProcessorTest extends TestCase
{
    public function testFontPresetsResolveToNewsletterSizes(): void
    {
        foreach ([
            'small' => '14px',
            'normal' => '16px',
            'medium' => '22px',
            'large' => '26px',
            'huge' => '30px',
        ] as $preset => $expected) {
            self::assertSame(
                $expected,
                StyleProcessor::getFontSize(['fontSize' => $preset]),
                $preset
            );
        }
    }

    public function testCustomFontSizeOverridesPreset(): void
    {
        self::assertSame('18.5px', StyleProcessor::getFontSize([
            'customFontSize' => 18.5,
            'fontSize' => 'huge',
        ]));
    }

    public function testMissingOrUnknownFontSizeLeavesInheritanceIntact(): void
    {
        self::assertNull(StyleProcessor::getFontSize([]));
        self::assertNull(StyleProcessor::getFontSize(['fontSize' => 'unknown']));
    }

    public function testCustomColorsOverrideNestedAndExistingColors(): void
    {
        $colors = StyleProcessor::getColors([
            'customTextColor' => '#111111',
            'customBackgroundColor' => '#222222',
            'customColor' => '#333333',
            'color' => '#aaaaaa',
            'background-color' => '#bbbbbb',
            'border-color' => '#cccccc',
            'style' => ['color' => [
                'text' => '#dddddd',
                'background' => '#eeeeee',
                'border' => '#ffffff',
            ]],
        ]);

        self::assertSame([
            'customTextColor' => '#111111',
            'textColor' => '#111111',
            'color' => '#111111',
            'background-color' => '#222222',
            'border-color' => '#333333',
        ], $colors);
    }

    public function testNestedColorsOverrideExistingMjmlColors(): void
    {
        $colors = StyleProcessor::getColors([
            'color' => '#aaaaaa',
            'background-color' => '#bbbbbb',
            'border-color' => '#cccccc',
            'link' => '#dddddd',
            'style' => [
                'color' => [
                    'text' => '#111111',
                    'background' => '#222222',
                    'border' => '#333333',
                ],
                'elements' => ['link' => ['color' => ['text' => '#444444']]],
            ],
        ]);

        self::assertSame('#111111', $colors['color']);
        self::assertSame('#222222', $colors['background-color']);
        self::assertSame('#333333', $colors['border-color']);
        self::assertSame('#444444', $colors['link']);
    }

    public function testDirectMjmlColorsAreRetained(): void
    {
        $colors = StyleProcessor::getColors([
            'color' => '#123456',
            'background-color' => '#ffffff',
            'border-color' => '#abcdef',
            'link' => '#654321',
        ]);

        self::assertSame('#123456', $colors['color']);
        self::assertSame('#ffffff', $colors['background-color']);
        self::assertSame('#abcdef', $colors['border-color']);
        self::assertSame('#654321', $colors['link']);
        self::assertSame([], StyleProcessor::getColors([]));
    }

    public function testLinkCssVariableUsesHexFallbackWhenAvailable(): void
    {
        foreach ([
            'var(--newsletter-link, #123456)' => '#123456',
            'var(--newsletter-link,#abc)' => '#abc',
            'var(--newsletter-link)' => 'var(--newsletter-link)',
        ] as $input => $expected) {
            self::assertSame(
                $expected,
                StyleProcessor::getColors(['link' => $input])['link'],
                $input
            );
        }
    }

    public function testSpacingPresetsProduceTopRightBottomLeftPadding(): void
    {
        self::assertSame('20px 40px 60px 80px', StyleProcessor::getPaddingFromAttributes([
            'style' => ['spacing' => ['padding' => [
                'left' => 'var:preset|spacing|50',
                'bottom' => 'var:preset|spacing|40',
                'right' => 'var:preset|spacing|30',
                'top' => 'var:preset|spacing|20',
            ]]],
        ]));
    }

    public function testUnspecifiedPaddingSidesRemainZero(): void
    {
        self::assertSame('0 0 40px 0', StyleProcessor::getPaddingFromAttributes([
            'style' => ['spacing' => ['padding' => [
                'bottom' => 'var:preset|spacing|30',
            ]]],
        ]));
    }

    public function testAbsentAndZeroPaddingDoNotOverrideInheritedSpacing(): void
    {
        self::assertSame('', StyleProcessor::getPaddingFromAttributes([]));
        self::assertSame('', StyleProcessor::getPaddingFromAttributes([
            'style' => ['spacing' => ['padding' => [
                'top' => 0,
                'right' => '0',
                'bottom' => null,
                'left' => 'var:preset|spacing|10',
            ]]],
        ]));
    }

    public function testLinkPresetExtractionPreservesLiteralColors(): void
    {
        self::assertSame('fau', StyleProcessor::extractLinkColor('var:preset|color|fau'));
        self::assertSame('#04316a', StyleProcessor::extractLinkColor('#04316a'));
    }
}
