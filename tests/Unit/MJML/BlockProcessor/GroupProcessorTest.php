<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class GroupProcessorTest extends MjmlTestCase
{
    public function testRootGroupHasOneWrapperWithSectionAttributesOnly(): void
    {
        $block = $this->container('core/group', [$this->listBlock('News')], [
            'customBackgroundColor' => '#eeeeee',
            'customTextColor' => '#04316a',
            'className' => 'editor-only',
            'style' => ['spacing' => ['padding' => ['top' => 'var:preset|spacing|20']]],
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        $this->assertNodes($xpath, '/test-root/mj-wrapper/mj-section/mj-column/mj-text', 1);
        self::assertSame(['#eeeeee'], $this->values($xpath, '/test-root/mj-wrapper/@background-color'));
        self::assertSame(['20px 0 0 0'], $this->values($xpath, '/test-root/mj-wrapper/@padding'));
        $this->assertNodes($xpath, '//mj-wrapper/@color | //mj-wrapper/@className | //mj-wrapper/@style', 0);
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-text/@color'));
    }

    public function testNestedGroupsAreFlattenedAndChildrenKeepOrder(): void
    {
        $block = $this->container('core/group', [
            $this->listBlock('First'),
            $this->container('core/group', [
                $this->listBlock('Second'),
                $this->container('core/group', [$this->listBlock('Third')]),
            ]),
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        $this->assertNodes($xpath, '//mj-wrapper', 1);
        $this->assertNodes($xpath, '/test-root/mj-wrapper/mj-section', 3);
        self::assertSame(['First', 'Second', 'Third'], $this->values($xpath, '//li'));
    }

    public function testGroupInsideColumnDoesNotIntroduceWrappersOrColumns(): void
    {
        $block = $this->container('core/group', [
            $this->listBlock('First'),
            $this->container('core/group', [$this->listBlock('Second')]),
        ]);
        $context = RenderContext::root(42)->insideColumn();
        $xpath = $this->parseMjml(BlockProcessor::render($block, $context));

        $this->assertNodes($xpath, '//mj-wrapper | //mj-section | //mj-column', 0);
        $this->assertNodes($xpath, '/test-root/mj-text', 2);
        self::assertSame(['First', 'Second'], $this->values($xpath, '//li'));
        self::assertFalse($context->inGroup);
    }

    public function testChildColorOverridesGroupColorWithoutAffectingSibling(): void
    {
        $block = $this->container('core/group', [
            $this->listBlock('Custom', ['customTextColor' => '#ff0000']),
            $this->listBlock('Inherited'),
        ], ['customTextColor' => '#04316a']);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        self::assertSame(['#ff0000', '#04316a'], $this->values($xpath, '//mj-text/@color'));
    }
}
