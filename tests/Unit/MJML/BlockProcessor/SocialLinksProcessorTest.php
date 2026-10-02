<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\BlockProcessor\SocialLinksProcessor;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class SocialLinksProcessorTest extends MjmlTestCase
{
    private const ASSET_BASE = 'https://example.test/wp-content/plugins/rrze-newsletter/assets/social-links/';

    public function testContainerHasExpectedDefaultLayout(): void
    {
        $xpath = $this->parseMjml(SocialLinksProcessor::render([], []));
        $this->assertNodes($xpath, '/test-root/mj-social', 1);
        $this->assertNodes($xpath, '//mj-social-element', 0);
        foreach (['icon-size' => '24px', 'mode' => 'horizontal', 'padding' => '0', 'border-radius' => '999px', 'icon-padding' => '7px', 'align' => 'left'] as $key => $value) {
            self::assertSame([$value], $this->values($xpath, '//mj-social/@' . $key));
        }
    }

    public function testExplicitAlignmentIsKept(): void
    {
        foreach (['left', 'center', 'right'] as $align) {
            $xpath = $this->parseMjml(SocialLinksProcessor::render(['align' => $align], []));
            self::assertSame([$align], $this->values($xpath, '//mj-social/@align'));
        }
    }

    public function testSocialElementUsesDirectUrlAndBundledIcon(): void
    {
        $xpath = $this->parseMjml(SocialLinksProcessor::render([], [$this->link('github', 'https://github.com/example')]));
        foreach ([
            'href' => 'https://github.com/example', 'src' => self::ASSET_BASE . 'white-github.png',
            'background-color' => '#24292d', 'css-class' => 'social-element', 'padding' => '2px',
        ] as $key => $value) {
            self::assertSame([$value], $this->values($xpath, '//mj-social-element/@' . $key));
        }
        // Without a service-name attribute MJML does not substitute a share URL.
        $this->assertNodes($xpath, '//mj-social-element/@name', 0);
    }

    public function testUnknownServicesRetainTheirLinksAndOrderWhileEmptyLinksAreSkipped(): void
    {
        $xpath = $this->parseMjml(SocialLinksProcessor::render([], [
            [], ['attrs' => []], $this->link('', 'https://example.test'),
            $this->link('github', 'https://example.test/first'),
            $this->link('github', ''), $this->link('unknown-service', 'https://example.test/unknown'),
            $this->link('youtube', 'https://example.test/second'),
        ]));

        self::assertSame(['https://example.test', 'https://example.test/first', 'https://example.test/unknown', 'https://example.test/second'], $this->values($xpath, '//mj-social-element/@href'));
        self::assertSame(['Link', '', 'unknown-service', ''], $this->values($xpath, '//mj-social-element'));
        self::assertSame([self::ASSET_BASE . 'black-chain.png', self::ASSET_BASE . 'white-github.png', self::ASSET_BASE . 'black-chain.png', self::ASSET_BASE . 'white-youtube.png'], $this->values($xpath, '//mj-social-element/@src'));
    }

    public function testEveryRegisteredServiceRendersWithItsOwnIconAndAccessibleName(): void
    {
        foreach (\RRZE\Newsletter\MJML\SocialIcons::getServices() as $service => $details) {
            $url = $service === 'mail' ? 'mailto:team@example.test' : 'https://example.test/' . $service;
            $xpath = $this->parseMjml(SocialLinksProcessor::render([], [$this->link($service, $url)]));
            self::assertSame([$url], $this->values($xpath, '//mj-social-element/@href'));
            self::assertSame([$details['name']], $this->values($xpath, '//mj-social-element/@alt'));
            self::assertSame([self::ASSET_BASE . $details['defaultIcon'] . '-' . $service . '.png'], $this->values($xpath, '//mj-social-element/@src'));
        }
    }

    public function testLabelsAreEscapedAndVisibleOnlyWhenRequestedOrForFallbacks(): void
    {
        $label = '<img src=x onerror="alert(1)"> & News';
        foreach (['github', '../new-service'] as $service) {
            $block = $this->link($service, 'https://example.test/?a=1&b=2');
            $block['attrs']['label'] = $label;
            foreach ([false, true] as $showLabels) {
                $xpath = $this->parseMjml(SocialLinksProcessor::render(['showLabels' => $showLabels], [$block]));
                self::assertSame([$label], $this->values($xpath, '//mj-social-element/@alt'));
                self::assertSame([($showLabels || $service !== 'github') ? $label : ''], $this->values($xpath, '//mj-social-element'));
                $this->assertNodes($xpath, '//img | //script', 0);
                self::assertStringNotContainsString('../', $this->values($xpath, '//mj-social-element/@src')[0]);
            }
        }
    }

    public function testUnsafeSchemesAndUnrelatedBlocksCannotBecomeFallbackLinks(): void
    {
        foreach (['github', 'new-service'] as $service) {
            foreach (['javascript:alert(1)', "java\nscript:alert(1)", 'javascript&#58;alert(1)', 'java&#x09;script&colon;alert(1)', 'DATA:text/html,test', 'vbscript:test'] as $url) {
                $xpath = $this->parseMjml(SocialLinksProcessor::render([], [$this->link($service, $url)]));
                $this->assertNodes($xpath, '//mj-social-element', 0);
            }
        }
        $block = $this->link('github', 'https://example.test');
        $block['blockName'] = 'core/paragraph';
        $this->assertNodes($this->parseMjml(SocialLinksProcessor::render([], [$block])), '//mj-social-element', 0);
    }

    public function testParentStyleControlsAllChildIcons(): void
    {
        $child = $this->link('github', 'https://example.test/github');
        $child['attrs']['className'] = 'is-style-circle-black';
        $xpath = $this->parseMjml(SocialLinksProcessor::render(['className' => 'is-style-circle-white'], [
            $child, $this->link('feed', 'https://example.test/feed'),
        ]));

        self::assertSame([self::ASSET_BASE . 'black-github.png', self::ASSET_BASE . 'black-feed.png'], $this->values($xpath, '//mj-social-element/@src'));
        self::assertSame(['#fff', '#fff'], $this->values($xpath, '//mj-social-element/@background-color'));
    }

    public function testUrlQueryCharactersSurviveAttributeSerialization(): void
    {
        $url = 'https://example.test/?one=1&label="quoted"';
        $xpath = $this->parseMjml(SocialLinksProcessor::render([], [$this->link('github', $url)]));

        self::assertSame([$url], $this->values($xpath, '//mj-social-element/@href'));
        $this->assertNodes($xpath, '//mj-social-element/@label', 0);
    }

    public function testUnrelatedBlockAttributesDoNotLeakOntoSocialContainer(): void
    {
        $xpath = $this->parseMjml(SocialLinksProcessor::render([
            'postId' => 42, 'className' => 'custom-class', 'font-size' => '99px',
            'style' => ['spacing' => ['padding' => '100px']],
        ], [$this->link('github', 'https://example.test')]));

        $this->assertNodes($xpath, '//mj-social/@postId | //mj-social/@className | //mj-social/@font-size | //mj-social/@style', 0);
        self::assertSame(['0'], $this->values($xpath, '//mj-social/@padding'));
    }

    public function testRootSocialBlockGetsOneSectionAndColumn(): void
    {
        $block = $this->container('core/social-links', [$this->link('github', 'https://example.test')]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-social/mj-social-element', 1);
        self::assertSame(['100%'], $this->values($xpath, '//mj-column/@width'));
        $this->assertNodes($xpath, '//mj-section/@postId', 0);
    }

    public function testSocialBlockInsideColumnHasNoDuplicateWrapper(): void
    {
        $block = $this->container('core/social-links', [$this->link('github', 'https://example.test')]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)->insideColumn()));

        $this->assertNodes($xpath, '/test-root/mj-social/mj-social-element', 1);
        $this->assertNodes($xpath, '//mj-section | //mj-column', 0);
    }

    private function link(string $service, string $url): array
    {
        return ['blockName' => 'core/social-link', 'attrs' => ['service' => $service, 'url' => $url]];
    }
}
