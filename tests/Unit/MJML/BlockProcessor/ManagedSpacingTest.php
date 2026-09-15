<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\ManagedSpacing;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class ManagedSpacingTest extends MjmlTestCase
{
    private function image(): array
    {
        return ['blockName' => 'core/image', 'attrs' => [], 'innerHTML' => '<figure><img src="https://example.test/image.png" width="1200" height="600" /></figure>'];
    }

    public function testDeepGroupsDoNotAccumulateGuttersOrChangeSource(): void
    {
        foreach ([1, 2, 5, 12] as $depth) {
            $block = $this->image();
            for ($level = 0; $level < $depth; $level++) {
                $block = $this->container('core/group', [$block], [
                    'customBackgroundColor' => '#abcdef',
                    'style' => ['spacing' => ['padding' => array_fill_keys(['top', 'right', 'bottom', 'left'], 'var:preset|spacing|80')]],
                ]);
            }
            $original = $block;
            $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42, managedSpacing: true)));
            self::assertSame(['632px'], $this->values($xpath, '//mj-image/@width'));
            self::assertSame(['0 0 16px 0'], $this->values($xpath, '//mj-image/@padding'));
            self::assertSame(['#abcdef'], $this->values($xpath, '//mj-wrapper/@background-color'));
            $this->assertNodes($xpath, '//mj-wrapper', 1);
            $this->assertNodes($xpath, '//*[@css-class="rrze-managed-spacing"]', 1);
            self::assertSame($original, $block);
        }
    }

    public function testRootAndFullBleedImagesUseTheirActualAvailableWidth(): void
    {
        foreach (['' => '632px', 'full' => '680px'] as $align => $width) {
            $block = $this->image();
            $block['attrs']['align'] = $align;
            $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42, managedSpacing: true)));
            self::assertSame([$width], $this->values($xpath, '//mj-image/@width'));
        }
    }

    public function testColumnsAndGridCellsKeepOnlyOneSetOfCellGutters(): void
    {
        $nested = $this->container('core/group', [$this->container('core/group', [$this->image()])]);
        $layouts = [
            $this->container('core/group', [$nested, $nested], ['layout' => ['type' => 'grid', 'columnCount' => 2]]),
            $this->container('core/columns', [
                $this->container('core/column', [$nested]),
                $this->container('core/column', [$nested]),
            ]),
        ];
        foreach ($layouts as $block) {
            $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42, managedSpacing: true)));
            self::assertSame(['300px', '300px'], $this->values($xpath, '//mj-image/@width'));
            self::assertSame(['0 8px', '0 8px'], $this->values($xpath, '//mj-column/@padding'));
            $this->assertNodes($xpath, '//mj-column//mj-wrapper | //mj-column//mj-column', 0);
        }
    }

    public function testNormalizationRemovesSpacingButPreservesOtherDesignAndContent(): void
    {
        $spacer = ['blockName' => 'core/spacer', 'attrs' => ['height' => '900px'], 'innerHTML' => '<div></div>'];
        $block = $this->container('core/group', [$spacer, $spacer, $this->listBlock('Keep me')], [
            'style' => ['spacing' => ['margin' => ['top' => '-500px']], 'color' => ['background' => '#123456']],
            'padding-left' => '100px',
        ]);
        $normalized = ManagedSpacing::normalizeBlock($block);
        self::assertCount(2, $normalized['innerBlocks']);
        self::assertSame('16px', $normalized['innerBlocks'][0]['attrs']['height']);
        self::assertArrayNotHasKey('spacing', $normalized['attrs']['style']);
        self::assertArrayNotHasKey('padding-left', $normalized['attrs']);
        self::assertSame('#123456', $normalized['attrs']['style']['color']['background']);
        self::assertSame($block['innerHTML'], $normalized['innerHTML']);
        self::assertSame($normalized, ManagedSpacing::normalizeBlock($normalized));
    }

    public function testImmutableContextRetainsPolicyThroughEveryTransition(): void
    {
        $context = RenderContext::root(42, managedSpacing: true);
        self::assertTrue($context->withDefaultAttrs([])->insideColumn()->insideGroup()->insideList()->withAvailableWidth(300)->managedSpacing);
        self::assertSame(680, $context->availableWidth);
        self::assertFalse(RenderContext::root(42)->managedSpacing);
    }

    public function testComponentSpacingPreservesSelfClosingMarkupAndDoesNotAlterSocialItems(): void
    {
        $markup = '<mj-social padding="80px"><mj-social-element padding="4px" name="facebook">Social</mj-social-element></mj-social><mj-divider padding-left="120px" />';
        $spaced = ManagedSpacing::spaceComponents($markup);
        $xpath = $this->parseMjml($spaced);
        self::assertSame(['0 0 16px 0'], $this->values($xpath, '//mj-social/@padding'));
        self::assertSame(['4px'], $this->values($xpath, '//mj-social-element/@padding'));
        self::assertSame(['0 0 16px 0'], $this->values($xpath, '//mj-divider/@padding'));
        $this->assertNodes($xpath, '//mj-divider/@padding-left', 0);
        self::assertSame($spaced, ManagedSpacing::spaceComponents($spaced));
    }
}
