<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ColumnProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class ColumnProcessorTest extends MjmlTestCase
{
    public function testAutomaticColumnsSplitWidthAndPreserveOrder(): void
    {
        $block = $this->container('core/columns', [
            $this->container('core/column', [$this->listBlock('Left')]),
            $this->container('core/column', [$this->listBlock('Right')]),
        ]);
        $before = $block;
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        self::assertSame(['50%', '50%'], $this->values($xpath, '/test-root/mj-section/mj-column/@width'));
        self::assertSame(['Left', 'Right'], $this->values($xpath, '//li'));
        $this->assertNodes($xpath, '//mj-column//mj-column | //mj-column//mj-section', 0);
        $this->assertNodes($xpath, '//mj-group', 0);
        self::assertSame($before, $block);
    }

    public function testAutomaticColumnsShareRemainingWidthAfterExplicitColumn(): void
    {
        $block = $this->container('core/columns', [
            $this->container('core/column', [$this->listBlock('First')], ['width' => '40%']),
            $this->container('core/column', [$this->listBlock('Second')]),
            $this->container('core/column', [$this->listBlock('Third')]),
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        self::assertSame(['40%', '30%', '30%'], $this->values($xpath, '//mj-column/@width'));
        self::assertSame(['First', 'Second', 'Third'], $this->values($xpath, '//li'));
    }

    public function testExplicitWidthsAndMobileNonStackingArePreserved(): void
    {
        $block = $this->container('core/columns', [
            $this->container('core/column', [$this->listBlock('Left')], ['width' => '25%']),
            $this->container('core/column', [$this->listBlock('Right')], ['width' => '75%']),
        ], ['isStackedOnMobile' => false]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        $this->assertNodes($xpath, '/test-root/mj-section/mj-group', 1);
        self::assertSame(['25%', '75%'], $this->values($xpath, '//mj-group/mj-column/@width'));
    }

    public function testColumnMapsAlignmentAndKeepsItsPadding(): void
    {
        foreach (['center' => 'middle', 'top' => 'top', 'bottom' => 'bottom'] as $input => $expected) {
            $xpath = $this->parseMjml(ColumnProcessor::renderColumn(
                ['verticalAlignment' => $input, 'width' => '40%'],
                [$this->listBlock('News')],
                ['padding' => '10px 20px'],
                RenderContext::root(42, 600)
            ));

            self::assertSame([$expected], $this->values($xpath, '/test-root/mj-column/@vertical-align'));
            self::assertSame(['40%'], $this->values($xpath, '//mj-column/@width'));
            self::assertSame(['10px 20px'], $this->values($xpath, '//mj-column/@padding'));
            self::assertSame(['mj-column-has-width'], $this->values($xpath, '//mj-column/@css-class'));
            $this->assertNodes($xpath, '/test-root/mj-column/mj-text', 1);
        }
    }

    public function testChildLinkColorDoesNotLeakToSibling(): void
    {
        $context = RenderContext::root(42)->withDefaultAttrs(['link' => '#111111']);
        $xpath = $this->parseMjml(ColumnProcessor::renderColumn(
            [],
            [$this->listBlock('Custom', ['link' => '#222222']), $this->listBlock('Inherited')],
            ['padding' => '0'],
            $context
        ));

        self::assertSame(['#222222', '#111111'], $this->values($xpath, '//mj-text/@link'));
        self::assertSame(['link' => '#111111'], $context->defaultAttrs);
        self::assertFalse($context->inColumn);
    }

    public function testColumnsPaddingBelongsOnSection(): void
    {
        $block = $this->container('core/columns', [
            $this->container('core/column', [$this->listBlock('News')]),
        ], ['style' => ['spacing' => ['padding' => [
            'left' => 'var:preset|spacing|30',
            'right' => 'var:preset|spacing|30',
        ]]]]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        self::assertSame(['0 40px 0 40px'], $this->values($xpath, '/test-root/mj-section/@padding'));
        self::assertSame(['0'], $this->values($xpath, '//mj-column/@padding'));
    }

    public function testOwnColumnBackgroundWinsOverOuterGroupWithoutLeakingToSibling(): void
    {
        $columns = $this->container('core/columns', [
            $this->container('core/column', [$this->listBlock('Dark', ['style' => ['color' => ['text' => '#000000']]])], ['customBackgroundColor' => '#04316a']),
            $this->container('core/column', [$this->listBlock('Light')]),
        ]);
        $block = $this->container('core/group', [$columns], ['customBackgroundColor' => '#ffffff']);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));
        self::assertSame(['#04316a', '#ffffff'], $this->values($xpath, '//mj-column/@background-color'));
        self::assertSame(['#04316a', '#ffffff'], $this->values($xpath, '//mj-text/@container-background-color'));
    }
}
