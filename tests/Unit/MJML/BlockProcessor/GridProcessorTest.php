<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class GridProcessorTest extends MjmlTestCase
{
    private function grid(array $layout, int $items = 5): array
    {
        $children = [];
        for ($index = 1; $index <= $items; $index++) {
            $children[] = $this->listBlock('Item ' . $index);
        }
        return $this->container('core/group', $children, [
            'layout' => ['type' => 'grid'] + $layout,
        ]);
    }

    public function testGridDistributesItemsIntoRowsWithoutStretchingLastRow(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render(
            $this->grid(['columnCount' => 2]),
            RenderContext::root(42, 600)
        ));

        $this->assertNodes($xpath, '/test-root/mj-wrapper/mj-section', 3);
        $this->assertNodes($xpath, '//mj-section[1]/mj-column', 2);
        $this->assertNodes($xpath, '//mj-section[2]/mj-column', 2);
        $this->assertNodes($xpath, '//mj-section[3]/mj-column', 1);
        self::assertSame(array_fill(0, 5, '50%'), $this->values($xpath, '//mj-column/@width'));
        self::assertSame(['Item 1', 'Item 2', 'Item 3', 'Item 4', 'Item 5'], $this->values($xpath, '//li'));
        $this->assertNodes($xpath, '//mj-column//mj-column | //mj-column//mj-section', 0);
    }

    public function testDefaultResponsiveGridAdaptsToAvailableWidth(): void
    {
        foreach ([[600, '33.333333%', 2], [400, '50%', 3], [100, '100%', 5]] as [$width, $percentage, $rows]) {
            $xpath = $this->parseMjml(BlockProcessor::render(
                $this->grid([]),
                RenderContext::root(42, $width)
            ));
            self::assertSame(array_fill(0, 5, $percentage), $this->values($xpath, '//mj-column/@width'));
            $this->assertNodes($xpath, '//mj-section', $rows);
        }
    }

    public function testMinimumColumnWidthSupportsPixelAndFontRelativeUnits(): void
    {
        foreach (['200px', '12.5rem', '12.5em', 200, ' 200 PX '] as $minimum) {
            $xpath = $this->parseMjml(BlockProcessor::render(
                $this->grid(['minimumColumnWidth' => $minimum], 3),
                RenderContext::root(42, 600)
            ));
            self::assertSame(
                array_fill(0, 3, '33.333333%'),
                $this->values($xpath, '//mj-column/@width'),
                (string) $minimum
            );
        }
    }

    public function testConfiguredCountCapsResponsiveColumnCount(): void
    {
        foreach ([400, 900] as $width) {
            $xpath = $this->parseMjml(BlockProcessor::render(
                $this->grid(['columnCount' => 2, 'minimumColumnWidth' => '150px'], 2),
                RenderContext::root(42, $width)
            ));
            self::assertSame(['50%', '50%'], $this->values($xpath, '//mj-column/@width'));
        }

        $xpath = $this->parseMjml(BlockProcessor::render(
            $this->grid(['columnCount' => 4, 'minimumColumnWidth' => '300px'], 2),
            RenderContext::root(42, 600)
        ));
        self::assertSame(['50%', '50%'], $this->values($xpath, '//mj-column/@width'));
    }

    public function testUnsupportedMinimumWidthFallsBackToConfiguredCountOrSingleColumn(): void
    {
        foreach (['50%', 'auto', '0px', -20, []] as $minimum) {
            foreach ([[2, '50%'], [0, '100%']] as [$count, $percentage]) {
                $xpath = $this->parseMjml(BlockProcessor::render(
                    $this->grid(['columnCount' => $count, 'minimumColumnWidth' => $minimum], 2),
                    RenderContext::root(42)
                ));
                self::assertSame(
                    [$percentage, $percentage],
                    $this->values($xpath, '//mj-column/@width'),
                    var_export([$count, $minimum], true)
                );
            }
        }
    }

    public function testGridNestedInGroupReusesOuterWrapper(): void
    {
        $block = $this->container('core/group', [$this->grid(['columnCount' => 2], 2)]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        $this->assertNodes($xpath, '//mj-wrapper', 1);
        $this->assertNodes($xpath, '/test-root/mj-wrapper/mj-section/mj-column', 2);
        self::assertSame(['Item 1', 'Item 2'], $this->values($xpath, '//li'));
    }

    public function testGroupCellsKeepBackgroundAndPaddingWhileFlatteningInnerGroups(): void
    {
        $cell = $this->container('core/group', [
            $this->container('core/group', [$this->listBlock('Nested news')]),
        ], [
            'customBackgroundColor' => '#eeeeee',
            'customTextColor' => '#04316a',
            'style' => ['spacing' => ['padding' => [
                'left' => 'var:preset|spacing|20',
                'right' => 'var:preset|spacing|20',
            ]]],
        ]);
        $block = $this->container('core/group', [$cell], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));

        self::assertSame(['50%'], $this->values($xpath, '//mj-column/@width'));
        self::assertSame(['#eeeeee'], $this->values($xpath, '//mj-column/@background-color'));
        self::assertSame(['0 20px 0 20px'], $this->values($xpath, '//mj-column/@padding'));
        $this->assertNodes($xpath, '//mj-column/@color | //mj-column/@style', 0);
        $this->assertNodes($xpath, '//mj-column//mj-wrapper | //mj-column//mj-column', 0);
        self::assertSame(['Nested news'], $this->values($xpath, '//mj-column/mj-text/ul/li'));
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-text/@color'));
    }

    public function testGroupPaddingReducesResponsiveGridWidth(): void
    {
        $block = $this->grid(['minimumColumnWidth' => '200px'], 2);
        $block['attrs']['style']['spacing']['padding'] = [
            'left' => 'var:preset|spacing|30',
            'right' => 'var:preset|spacing|30',
        ];
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42, 600)));

        // 600px minus 80px of padding fits two 200px columns, not three.
        self::assertSame(['50%', '50%'], $this->values($xpath, '//mj-column/@width'));
        self::assertSame(['0 40px 0 40px'], $this->values($xpath, '//mj-wrapper/@padding'));
    }
}
