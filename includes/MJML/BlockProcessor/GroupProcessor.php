<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class GroupProcessor
{
    /**
     * Renders a group as an MJML wrapper or flattens it when nesting requires it.
     *
     * @param array<string, mixed> $block Parsed core/group block.
     * @param RenderContext $context Current rendering context.
     * @return string Rendered group MJML.
     */
    public static function render(
        array $block,
        RenderContext $context
    ): string {
        $innerBlocks = $block['innerBlocks'] ?? [];
        $attrs = AttributeHandler::processAttributes($block['attrs'] ?? []);
        $attrs['padding'] = StyleProcessor::getPaddingFromAttributes($attrs) ?: '0';
        $innerWidth = LayoutHelper::subtractHorizontalPadding(
            $context->availableWidth,
            $attrs['padding']
        );
        $childContext = $context
            ->withAvailableWidth($innerWidth)
            ->insideGroup();

        // MJML does not allow wrappers or nested columns inside an mj-column.
        if ($context->inColumn) {
            return self::renderFlattenedChildren(
                $innerBlocks,
                $attrs,
                $childContext
            );
        }

        if (self::isGrid($block)) {
            return GridProcessor::render(
                $block,
                $attrs,
                $childContext,
                !$context->inGroup
            );
        }

        if ($context->inGroup) {
            return self::renderFlattenedChildren(
                $innerBlocks,
                $attrs,
                $childContext
            );
        }

        $wrapperAttrs = LayoutHelper::filterSectionAttributes($attrs);
        $markup = '<mj-wrapper '
            . AttributeHandler::arrayToAttributes($wrapperAttrs)
            . '>';

        foreach ($innerBlocks as $innerBlock) {
            $childAttrs = AttributeInheritance::forChild(
                $innerBlock,
                $attrs,
                $context->defaultAttrs
            );
            $markup .= BlockProcessor::render(
                $innerBlock,
                $childContext->withDefaultAttrs($childAttrs)
            );
        }

        return $markup . '</mj-wrapper>';
    }

    /**
     * Renders group children without introducing another MJML wrapper.
     *
     * @param array<int, array<string, mixed>> $innerBlocks Child blocks.
     * @param array<string, mixed> $groupAttrs Processed group attributes.
     * @param RenderContext $context Context inherited by the children.
     * @return string Concatenated child MJML.
     */
    private static function renderFlattenedChildren(
        array $innerBlocks,
        array $groupAttrs,
        RenderContext $context
    ): string {
        $markup = '';
        foreach ($innerBlocks as $innerBlock) {
            $childAttrs = AttributeInheritance::forChild(
                $innerBlock,
                $groupAttrs,
                $context->defaultAttrs
            );
            $markup .= BlockProcessor::render(
                $innerBlock,
                $context->withDefaultAttrs($childAttrs)
            );
        }

        return $markup;
    }

    /**
     * Determines whether a group uses the WordPress grid layout.
     *
     * @param array<string, mixed> $block Parsed core/group block.
     * @return bool Whether the group is a grid.
     */
    private static function isGrid(array $block): bool
    {
        return ($block['attrs']['layout']['type'] ?? null) === 'grid';
    }
}
