<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use RRZE\Newsletter\MJML\ManagedSpacing;
use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;

final class ManagedSpacingTest extends ApplicationTestCase
{
    public static function modes(): array
    {
        return [
            ['off', '', false], ['on', '', true],
            ['off', 'inherit', false], ['on', 'inherit', true],
            ['off', 'managed', true], ['on', 'managed', true],
            ['off', 'expert', false], ['on', 'expert', false],
            ['on', 'invalid', true], ['off', 'invalid', false],
        ];
    }

    public function testEffectiveModeControlsTheActualRenderer(): void
    {
        foreach (self::modes() as [$global, $mode, $expected]) {
            $this->assertMode($global, $mode, $expected);
        }
    }

    private function assertMode(string $global, string $mode, bool $expected): void
    {
        App::$options['rrze_newsletter'] = ['design_managed_spacing' => $global];
        App::$meta[42]['rrze_newsletter_spacing_mode'] = $mode;
        $post = $this->post(['post_content' => 'spacing-fixture']);
        $html = '<ul style="margin:80px"><li>Unchanged text</li></ul>';
        $blocks = [['blockName' => 'core/list', 'attrs' => [], 'innerHTML' => $html, 'innerContent' => [$html], 'innerBlocks' => []]];
        App::$blocks['spacing-fixture'] = $blocks;
        self::assertSame($expected, ManagedSpacing::forPost(42));
        $markup = Renderer::fromPost($post);
        self::assertSame($expected, str_contains($markup, 'rrze-managed-spacing'));
        self::assertSame($blocks, App::$blocks['spacing-fixture']);
        self::assertSame('spacing-fixture', $post->post_content);
        if (!$expected) {
            App::$options['rrze_newsletter'] = [];
            App::$meta[42]['rrze_newsletter_spacing_mode'] = 'inherit';
            self::assertSame(Renderer::fromPost($post), $markup);
        }
    }

    public function testMissingOrMalformedGlobalSettingIsOptIn(): void
    {
        foreach ([null, '', 'on', [], ['design_managed_spacing' => 'off']] as $option) {
            App::$options['rrze_newsletter'] = $option;
            self::assertFalse(ManagedSpacing::globallyEnabled());
        }
    }

    public function testArrayRenderingFollowsGlobalDefault(): void
    {
        App::$options['rrze_newsletter'] = ['design_managed_spacing' => 'on'];
        self::assertStringContainsString('rrze-managed-spacing', Renderer::fromAry([]));
    }
}
