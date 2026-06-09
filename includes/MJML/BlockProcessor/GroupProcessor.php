<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class GroupProcessor
{
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

    private static function isGrid(array $block): bool
    {
        return ($block['attrs']['layout']['type'] ?? null) === 'grid';
    }
}
