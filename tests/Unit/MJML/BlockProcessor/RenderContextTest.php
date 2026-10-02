<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\Renderer;

final class RenderContextTest extends TestCase
{
    public function testRootStartsWithNewsletterWidthAndNoContainerState(): void
    {
        self::assertEquals(
            new RenderContext(42, [], false, false, false, Renderer::EMAIL_WIDTH),
            RenderContext::root(42)
        );
        self::assertSame(600, RenderContext::root(42, 600)->availableWidth);
    }

    public function testNestedContextRetainsParentValuesWithoutChangingParent(): void
    {
        $parent = new RenderContext(42, ['color' => '#04316a'], availableWidth: 600);
        $before = clone $parent;

        $nested = $parent->insideColumn()->insideGroup()->insideList();

        self::assertEquals(
            new RenderContext(42, ['color' => '#04316a'], true, true, true, 600),
            $nested
        );
        self::assertEquals($before, $parent);
        self::assertNotSame($parent, $nested);
    }

    public function testSiblingColumnsDoNotShareWidthOrStyleChanges(): void
    {
        $parent = RenderContext::root(42, 600)->withDefaultAttrs(['color' => '#111111']);

        $left = $parent->insideColumn()
            ->withAvailableWidth(200)
            ->withDefaultAttrs(['color' => '#ff0000']);
        $right = $parent->insideColumn()->withAvailableWidth(400);

        self::assertEquals(new RenderContext(42, ['color' => '#ff0000'], true, false, false, 200), $left);
        self::assertEquals(new RenderContext(42, ['color' => '#111111'], true, false, false, 400), $right);
        self::assertEquals(new RenderContext(42, ['color' => '#111111'], false, false, false, 600), $parent);
    }

    public function testInheritedAttributesCanBeClearedWithoutLosingContainerState(): void
    {
        $context = new RenderContext(42, ['color' => '#111111'], true, true, true, 200);

        self::assertEquals(
            new RenderContext(42, [], true, true, true, 200),
            $context->withDefaultAttrs([])
        );
        self::assertSame(['color' => '#111111'], $context->defaultAttrs);
    }
}
