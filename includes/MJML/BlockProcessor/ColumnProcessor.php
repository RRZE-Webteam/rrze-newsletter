<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class ColumnProcessor
{
    public static function renderColumn(
        int $postId,
        array $attrs,
        array $innerBlocks,
        array $defaultAttrs,
        array $columnAttrs,
        int $availableWidth
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
            $availableWidth
        ) ?? $availableWidth;
        $columnWidth = LayoutHelper::subtractHorizontalPadding(
            $columnWidth,
            $columnAttrs['padding']
        );

        $markup = '<mj-column '
            . AttributeHandler::arrayToAttributes($columnAttrs)
            . '>';
        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = self::withoutInheritedLinkColor(
                $defaultAttrs,
                $childBlock
            );
            $markup .= BlockProcessor::renderMjmlComponent(
                $postId,
                $childBlock,
                $childDefaultAttrs,
                true,
                false,
                false,
                $columnWidth
            );
        }

        return $markup . '</mj-column>';
    }

    public static function renderColumns(
        int $postId,
        array $attrs,
        array $innerBlocks,
        array $defaultAttrs,
        int $availableWidth
    ): string {
        $innerBlocks = self::assignAutomaticWidths($innerBlocks);
        $isStackedOnMobile = !isset($attrs['isStackedOnMobile'])
            || $attrs['isStackedOnMobile'] === true;
        $markup = $isStackedOnMobile ? '' : '<mj-group>';

        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = self::withoutInheritedLinkColor(
                $defaultAttrs,
                $childBlock
            );
            $markup .= BlockProcessor::renderMjmlComponent(
                $postId,
                $childBlock,
                $childDefaultAttrs,
                true,
                false,
                false,
                $availableWidth
            );
        }

        return $markup . ($isStackedOnMobile ? '' : '</mj-group>');
    }

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

    private static function withoutInheritedLinkColor(
        array $defaultAttrs,
        array $block
    ): array {
        $hasOwnLinkColor =
            !empty($block['attrs']['style']['elements']['link']['color']['text'])
            || !empty($block['attrs']['link']);
        if ($hasOwnLinkColor) {
            unset($defaultAttrs['link']);
        }

        return $defaultAttrs;
    }
}
