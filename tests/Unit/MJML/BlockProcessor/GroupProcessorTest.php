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

    public function testNestedBackgroundSurvivesTextAndSpacingOverridesInBothModes(): void
    {
        foreach ([false, true] as $managed) {
            foreach ([false, true] as $inColumn) {
                $dark = $this->container('core/group', [
                    $this->listBlock('Dark', ['style' => ['color' => ['text' => '#000000'], 'spacing' => ['padding' => ['top' => '20px']]]]),
                    $this->listBlock('Light', ['style' => ['color' => ['background' => '#ffffff', 'text' => '#000000']]]),
                    $this->container('core/group', [$this->listBlock('Deep', ['style' => ['typography' => ['fontWeight' => '700']]])]),
                ], ['style' => ['color' => ['background' => '#04316a']]]);
                $block = $this->container('core/group', [$dark, $this->listBlock('Sibling')], ['customBackgroundColor' => '#ffffff']);
                $before = $block;
                $context = RenderContext::root(42, managedSpacing: $managed);
                $xpath = $this->parseMjml(BlockProcessor::render($block, $inColumn ? $context->insideColumn() : $context));
                self::assertSame(['#04316a', '#ffffff', '#04316a', '#ffffff'], $this->values($xpath, '//mj-text/@container-background-color'));
                self::assertSame($before, $block, 'Source block colors must not change');
            }
        }
    }

    public function testGroupBackgroundDoesNotReplaceButtonFill(): void
    {
        $button = ['blockName' => 'core/button', 'attrs' => ['style' => ['typography' => ['fontWeight' => '700']]], 'innerHTML' => '<a href="https://example.test">Button</a>'];
        $block = $this->container('core/group', [$this->container('core/group', [$button], ['customBackgroundColor' => '#04316a'])]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)->insideColumn()));
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-button/@container-background-color'));
        self::assertSame(['#32373c'], $this->values($xpath, '//mj-button/@background-color'));
    }

    public function testFlattenedGroupBackdropAlsoCoversImagesCaptionsButtonsAndDecorations(): void
    {
        $button = ['blockName' => 'core/button', 'attrs' => [], 'innerHTML' => '<a href="https://example.test">Button</a>'];
        $children = [
            ['blockName' => 'core/image', 'attrs' => [], 'innerHTML' => '<figure><img src="https://example.test/image.png" width="200" height="100"/><figcaption>Caption</figcaption></figure>'],
            $this->container('core/buttons', [$button]),
            ['blockName' => 'core/spacer', 'attrs' => ['height' => '16px'], 'innerHTML' => '<div></div>'],
            ['blockName' => 'core/separator', 'attrs' => [], 'innerHTML' => '<hr/>'],
            $this->container('core/social-links', [['blockName' => 'core/social-link', 'attrs' => ['service' => 'github', 'url' => 'https://example.test']]]),
        ];
        $block = $this->container('core/group', $children, ['customBackgroundColor' => '#04316a']);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)->insideColumn()));
        self::assertSame(array_fill(0, 6, '#04316a'), $this->values($xpath, '/*/*/@container-background-color'));
    }
}
