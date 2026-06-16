<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class ColumnProcessor
{
    /**
     * Renders one core/column block and its children.
     *
     * @param array<string, mixed> $attrs Processed column attributes.
     * @param array<int, array<string, mixed>> $innerBlocks Child blocks.
     * @param array<string, mixed> $columnAttrs Initial MJML column attributes.
     * @param RenderContext $context Current rendering context.
     * @return string Rendered mj-column.
     */
    public static function renderColumn(
        array $attrs,
        array $innerBlocks,
        array $columnAttrs,
        RenderContext $context
    ): string {
        if (isset($attrs['verticalAlignment'])) {
            $columnAttrs['vertical-align'] = $attrs['verticalAlignment'] === 'center'
                ? 'middle'
                : $attrs['verticalAlignment'];
        }
        if (isset($attrs['width'])) {
            $columnAttrs['width'] = $attrs['width'];
            $columnAttrs['css-class'] = 'mj-column-has-width';
        }

        $columnWidth = LayoutHelper::resolveWidth(
            $attrs['width'] ?? null,
            $context->availableWidth
        ) ?? $context->availableWidth;
        $columnWidth = LayoutHelper::subtractHorizontalPadding(
            $columnWidth,
            $columnAttrs['padding']
        );

        $markup = '<mj-column '
            . AttributeHandler::arrayToAttributes($columnAttrs)
            . '>';
        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = AttributeInheritance::withoutParentLinkColor(
                $context->defaultAttrs,
                $childBlock
            );
            $markup .= BlockProcessor::render(
                $childBlock,
                $context
                    ->withDefaultAttrs($childDefaultAttrs)
                    ->withAvailableWidth($columnWidth)
                    ->insideColumn()
            );
        }

        return $markup . '</mj-column>';
    }

    /**
     * Renders the children of a core/columns block.
     *
     * @param array<string, mixed> $attrs Processed columns attributes.
     * @param array<int, array<string, mixed>> $innerBlocks Column blocks.
     * @param RenderContext $context Current rendering context.
     * @return string Rendered columns markup.
     */
    public static function renderColumns(
        array $attrs,
        array $innerBlocks,
        RenderContext $context
    ): string {
        $innerBlocks = self::assignAutomaticWidths($innerBlocks);
        $isStackedOnMobile = !isset($attrs['isStackedOnMobile'])
            || $attrs['isStackedOnMobile'] === true;
        $markup = $isStackedOnMobile ? '' : '<mj-group>';

        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = AttributeInheritance::withoutParentLinkColor(
                $context->defaultAttrs,
                $childBlock
            );
            $markup .= BlockProcessor::render(
                $childBlock,
                $context
                    ->withDefaultAttrs($childDefaultAttrs)
                    ->insideColumn()
            );
        }

        return $markup . ($isStackedOnMobile ? '' : '</mj-group>');
    }

    /**
     * Distributes remaining percentage width among columns without a width.
     *
     * @param array<int, array<string, mixed>> $innerBlocks Column blocks.
     * @return array<int, array<string, mixed>> Blocks with explicit widths.
     */
    private static function assignAutomaticWidths(array $innerBlocks): array
    {
        $widthsSum = 0.0;
        $automaticIndexes = [];

        foreach ($innerBlocks as $index => $columnBlock) {
            if (isset($columnBlock['attrs']['width'])) {
                $widthsSum += (float) $columnBlock['attrs']['width'];
            } else {
                $automaticIndexes[] = $index;
            }
        }

        if ($automaticIndexes === []) {
            return $innerBlocks;
        }

        $automaticWidth = max(0, 100 - $widthsSum)
            / count($automaticIndexes)
            . '%';
        foreach ($automaticIndexes as $index) {
            $innerBlocks[$index]['attrs']['width'] = $automaticWidth;
        }

        return $innerBlocks;
    }
}
