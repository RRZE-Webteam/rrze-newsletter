<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class BlockProcessorTest extends MjmlTestCase
{
    public function testEmptyAndUnsupportedBlocksProduceNoMarkup(): void
    {
        foreach ([
            [],
            ['blockName' => null, 'innerHTML' => 'Freeform text'],
            ['blockName' => 'core/paragraph', 'innerHTML' => ''],
            ['blockName' => 'unknown/block', 'innerHTML' => '<p>Unsupported</p>'],
        ] as $block) {
            self::assertSame('', BlockProcessor::render($block, RenderContext::root(42)));
        }
    }

    public function testRootListGetsOneSectionAndOneFullWidthColumn(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render(
            $this->listBlock('News'),
            RenderContext::root(42)
        ));

        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-text/ul/li', 1);
        self::assertSame(['100%'], $this->values($xpath, '//mj-column/@width'));
        $this->assertNodes($xpath, '//mj-section/@postId', 0);
    }

    public function testSpacerHeightSurvivesRootAndColumnRendering(): void
    {
        $block = [
            'blockName' => 'core/spacer',
            'attrs' => ['height' => 'var:preset|spacing|40'],
            'innerHTML' => '<div style="height:40px"></div>',
        ];
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));
        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-spacer', 1);
        self::assertSame(['40px'], $this->values($xpath, '//mj-spacer/@height'));

        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)->insideColumn()));
        $this->assertNodes($xpath, '/test-root/mj-spacer', 1);
        $this->assertNodes($xpath, '//mj-section | //mj-column', 0);
    }

    public function testSeparatorColorDoesNotBecomeSectionBackground(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render([
            'blockName' => 'core/separator',
            'attrs' => ['customBackgroundColor' => '#04316a', 'className' => 'is-style-wide'],
            'innerHTML' => '<hr />',
        ], RenderContext::root(42)));

        $this->assertNodes($xpath, '/test-root/mj-section/mj-divider', 1);
        $this->assertNodes($xpath, '//mj-section/@background-color | //mj-column', 0);
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-divider/@border-color'));
        self::assertSame(['100%'], $this->values($xpath, '//mj-divider/@width'));
    }
}
