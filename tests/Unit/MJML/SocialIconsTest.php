<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\SocialIcons;

final class SocialIconsTest extends TestCase
{
    private const COLORS = [
        'bluesky' => '#0a7aff', 'facebook' => '#1977f2', 'feed' => '#f0f0f0',
        'github' => '#24292d', 'instagram' => '#f00075', 'linkedin' => '#0577b5',
        'mastodon' => '#3288d4', 'tiktok' => '#000000', 'tumblr' => '#011835',
        'twitter' => '#21a1f3', 'wordpress' => '#3499cd', 'youtube' => '#ff0100',
        'x' => '#000000',
    ];

    public function testSupportedServicesUseTheirBrandColorAndWhiteIconByDefault(): void
    {
        foreach (self::COLORS as $service => $color) {
            self::assertSame(['icon' => 'white-' . $service . '.png', 'color' => $color], SocialIcons::getIconAttributes($service, []));
        }
    }

    public function testBothIconVariantsExistForEverySupportedService(): void
    {
        foreach (array_keys(self::COLORS) as $service) {
            foreach (['is-style-filled-black' => 'black', 'is-style-filled-white' => 'white'] as $style => $variant) {
                $icon = SocialIcons::getIconAttributes($service, ['className' => $style]);
                self::assertSame($variant . '-' . $service . '.png', $icon['icon']);
                self::assertFileExists(dirname(__DIR__, 3) . '/assets/social-links/' . $icon['icon']);
            }
        }
    }

    public function testUnsupportedServicesHaveNoIcon(): void
    {
        foreach (['', 'unknown-service', 'Facebook', '../github'] as $service) {
            self::assertSame([], SocialIcons::getIconAttributes($service, ['className' => 'is-style-circle-black']));
        }
    }

    public function testFilledStylesHaveTransparentBackgrounds(): void
    {
        foreach (['is-style-filled-black' => 'black', 'is-style-filled-white' => 'white', 'is-style-filled-primary-text' => 'white'] as $style => $variant) {
            self::assertSame(['icon' => $variant . '-github.png', 'color' => 'transparent'], SocialIcons::getIconAttributes('github', ['className' => $style]));
        }
    }

    public function testCircleStylesUseContrastingIconColors(): void
    {
        self::assertSame(['icon' => 'white-github.png', 'color' => '#000'], SocialIcons::getIconAttributes('github', ['className' => 'is-style-circle-black']));
        self::assertSame(['icon' => 'black-github.png', 'color' => '#fff'], SocialIcons::getIconAttributes('github', ['className' => 'is-style-circle-white']));
    }

    public function testExplicitDefaultStyleUsesBlackIconOnlyForFeed(): void
    {
        foreach (self::COLORS as $service => $color) {
            $variant = $service === 'feed' ? 'black' : 'white';
            self::assertSame(['icon' => $variant . '-' . $service . '.png', 'color' => $color], SocialIcons::getIconAttributes($service, ['className' => 'is-style-default']));
        }
    }

    public function testStyleIsRecognizedAmongUnrelatedClasses(): void
    {
        self::assertSame(['icon' => 'black-github.png', 'color' => '#fff'], SocialIcons::getIconAttributes('github', ['className' => 'custom-layout is-style-circle-white another-class']));
        foreach (['', 'unrelated-class'] as $className) {
            self::assertSame(['icon' => 'white-github.png', 'color' => '#24292d'], SocialIcons::getIconAttributes('github', ['className' => $className]));
        }
    }
}
