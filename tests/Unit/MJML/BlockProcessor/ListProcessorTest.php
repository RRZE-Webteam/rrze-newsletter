<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ListProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class ListProcessorTest extends MjmlTestCase
{
    public function testStandaloneListHasOneTextContainerAndPreservesContent(): void
    {
        $html = '<ol start="3"><li>First <strong>news</strong></li><li>Second</li></ol>';
        $markup = ListProcessor::render([], [], [$html], 'Georgia', RenderContext::root(42));
        $xpath = $this->parseMjml($markup);

        $this->assertNodes($xpath, '/test-root/mj-text', 1);
        self::assertSame(['Georgia'], $this->values($xpath, '//mj-text/@font-family'));
        self::assertSame(['16px'], $this->values($xpath, '//mj-text/@font-size'));
        self::assertSame(['0'], $this->values($xpath, '//mj-text/@padding'));
        self::assertSame(['1.5'], $this->values($xpath, '//mj-text/@line-height'));
        self::assertStringContainsString($html, $markup);
    }

    public function testExplicitTypographyOverridesListDefaults(): void
    {
        $xpath = $this->parseMjml(ListProcessor::render(
            ['font-size' => '22px', 'line-height' => '2', 'padding' => '10px'],
            [],
            ['<ul><li>News</li></ul>'],
            'Arial',
            RenderContext::root(42)
        ));

        self::assertSame(['22px'], $this->values($xpath, '//mj-text/@font-size'));
        self::assertSame(['2'], $this->values($xpath, '//mj-text/@line-height'));
        self::assertSame(['10px'], $this->values($xpath, '//mj-text/@padding'));
    }

    public function testListInsideListReturnsHtmlWithoutMjmlWrapper(): void
    {
        $html = '<ul><li>Nested news</li></ul>';
        self::assertSame($html, ListProcessor::render(
            [], [], [$html], 'Arial', RenderContext::root(42)->insideList()
        ));
    }

    public function testRecursiveListItemsKeepOrderAndUseOnlyOneTextContainer(): void
    {
        $nestedList = $this->listBlock('Nested');
        $first = [
            'blockName' => 'core/list-item',
            'attrs' => [],
            'innerHTML' => '<li>First</li>',
            'innerContent' => ['<li>First', null, '</li>'],
            'innerBlocks' => [$nestedList],
        ];
        $second = [
            'blockName' => 'core/list-item',
            'attrs' => [],
            'innerHTML' => '<li>Second</li>',
            'innerContent' => ['<li>Second</li>'],
            'innerBlocks' => [],
        ];
        $block = [
            'blockName' => 'core/list',
            'attrs' => [],
            'innerHTML' => '<ol></ol>',
            'innerContent' => ['<ol>', null, null, '</ol>'],
            'innerBlocks' => [$first, $second],
        ];

        $markup = BlockProcessor::render($block, RenderContext::root(42)->insideColumn());
        $xpath = $this->parseMjml($markup);

        $this->assertNodes($xpath, '//mj-text', 1);
        $this->assertNodes($xpath, '//mj-column | //mj-section', 0);
        self::assertStringContainsString(
            '<ol><li>First<ul><li>Nested</li></ul></li><li>Second</li></ol>',
            $markup
        );
    }

    public function testExplicitLinkStyleOverridesInheritedLinkAttribute(): void
    {
        $xpath = $this->parseMjml(ListProcessor::render(
            [
                'link' => '#111111',
                'style' => ['elements' => ['link' => ['color' => ['text' => '#04316a']]]],
            ],
            [],
            ['<ul><li>News</li></ul>'],
            'Arial',
            RenderContext::root(42)
        ));

        self::assertSame(['#04316a'], $this->values($xpath, '//mj-text/@link'));
        $this->assertNodes($xpath, '//mj-text/@style', 0);
    }
}
