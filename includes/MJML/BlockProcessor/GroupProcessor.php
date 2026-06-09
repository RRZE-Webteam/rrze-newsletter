<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class GroupProcessor
{
    public static function render(
        int $postId,
        array $block,
        array $innerBlocks,
        array $defaultAttrs,
        int $availableWidth,
        bool $isInColumn,
        bool $isInGroup
    ): string {
        $attrs = AttributeHandler::processAttributes($block['attrs'] ?? []);
        $attrs['padding'] = StyleProcessor::getPaddingFromAttributes($attrs) ?: '0';
        $innerWidth = LayoutHelper::subtractHorizontalPadding(
            $availableWidth,
            $attrs['padding']
        );

        // MJML does not allow wrappers or nested columns inside an mj-column.
        if ($isInColumn) {
            return self::renderFlattenedChildren(
                $postId,
                $innerBlocks,
                $attrs,
                $defaultAttrs,
                $innerWidth,
                true
            );
        }

        if (self::isGrid($block)) {
            return self::renderGrid(
                $postId,
                $block,
                $attrs,
                $defaultAttrs,
                $innerWidth,
                !$isInGroup
            );
        }

        if ($isInGroup) {
            return self::renderFlattenedChildren(
                $postId,
                $innerBlocks,
                $attrs,
                $defaultAttrs,
                $innerWidth,
                false
            );
        }

        $wrapperAttrs = LayoutHelper::filterSectionAttributes($attrs);
        $markup = '<mj-wrapper '
            . AttributeHandler::arrayToAttributes($wrapperAttrs)
            . '>';

        foreach ($innerBlocks as $innerBlock) {
            $childAttrs = self::getChildAttributes(
                $innerBlock,
                $attrs,
                $defaultAttrs
            );
            $markup .= BlockProcessor::renderMjmlComponent(
                $postId,
                $innerBlock,
                $childAttrs,
                false,
                true,
                false,
                $innerWidth
            );
        }

        return $markup . '</mj-wrapper>';
    }

    private static function renderFlattenedChildren(
        int $postId,
        array $innerBlocks,
        array $groupAttrs,
        array $defaultAttrs,
        int $availableWidth,
        bool $isInColumn
    ): string {
        $markup = '';
        foreach ($innerBlocks as $innerBlock) {
            $childAttrs = self::getChildAttributes(
                $innerBlock,
                $groupAttrs,
                $defaultAttrs
            );
            $markup .= BlockProcessor::renderMjmlComponent(
                $postId,
                $innerBlock,
                $childAttrs,
                $isInColumn,
                true,
                false,
                $availableWidth
            );
        }

        return $markup;
    }

    private static function isGrid(array $block): bool
    {
        return ($block['attrs']['layout']['type'] ?? null) === 'grid';
    }

    private static function getGridColumnCount(
        array $block,
        int $availableWidth
    ): int {
        $layout = $block['attrs']['layout'] ?? [];
        $configuredCount = $layout['columnCount'] ?? null;
        $configuredCount = is_numeric($configuredCount)
            && (int) $configuredCount > 0
            ? (int) $configuredCount
            : null;

        $minimumWidth = $layout['minimumColumnWidth'] ?? null;
        if ($minimumWidth === null && $configuredCount === null) {
            $minimumWidth = '12rem';
        }

        $minimumWidthPixels = self::cssLengthToPixels($minimumWidth);
        if ($minimumWidthPixels === null) {
            return $configuredCount ?? 1;
        }

        $responsiveCount = max(
            1,
            (int) floor($availableWidth / $minimumWidthPixels)
        );
        return $configuredCount !== null
            ? min($configuredCount, $responsiveCount)
            : $responsiveCount;
    }

    private static function cssLengthToPixels(mixed $value): ?float
    {
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);
        if (!preg_match(
            '/^(\d+(?:\.\d+)?)\s*(px|rem|em)?$/i',
            $value,
            $matches
        )) {
            return null;
        }

        $amount = (float) $matches[1];
        if ($amount <= 0) {
            return null;
        }

        $unit = strtolower($matches[2] ?? 'px');
        return in_array($unit, ['rem', 'em'], true)
            ? $amount * 16
            : $amount;
    }

    private static function renderGrid(
        int $postId,
        array $block,
        array $groupAttrs,
        array $defaultAttrs,
        int $availableWidth,
        bool $includeWrapper
    ): string {
        $columnCount = self::getGridColumnCount($block, $availableWidth);
        $columnWidthPercent = 100 / $columnCount;
        $columnWidth = max(
            1,
            (int) floor($availableWidth / $columnCount)
        );
        $rows = array_chunk($block['innerBlocks'] ?? [], $columnCount);
        $wrapperAttrs = LayoutHelper::filterSectionAttributes($groupAttrs);
        $markup = $includeWrapper
            ? '<mj-wrapper '
                . AttributeHandler::arrayToAttributes($wrapperAttrs)
                . '>'
            : '';

        foreach ($rows as $row) {
            $markup .= '<mj-section padding="0">';
            foreach ($row as $innerBlock) {
                $childDefaultAttrs = self::getChildAttributes(
                    $innerBlock,
                    $groupAttrs,
                    $defaultAttrs
                );
                $columnAttrs = [
                    'padding' => '0',
                    'width' => self::formatPercentage($columnWidthPercent),
                ];
                $contentWidth = $columnWidth;

                if (($innerBlock['blockName'] ?? null) === 'core/group') {
                    $cellAttrs = AttributeHandler::processAttributes(
                        $innerBlock['attrs'] ?? []
                    );
                    $cellAttrs['padding'] =
                        StyleProcessor::getPaddingFromAttributes($cellAttrs)
                        ?: '0';
                    $columnAttrs = array_merge(
                        $columnAttrs,
                        self::filterGridColumnAttributes($cellAttrs)
                    );
                    $contentWidth = LayoutHelper::subtractHorizontalPadding(
                        $columnWidth,
                        $columnAttrs['padding']
                    );
                }

                $markup .= '<mj-column '
                    . AttributeHandler::arrayToAttributes($columnAttrs)
                    . '>';
                $markup .= self::renderGridCell(
                    $postId,
                    $innerBlock,
                    $childDefaultAttrs,
                    $contentWidth
                );
                $markup .= '</mj-column>';
            }
            $markup .= '</mj-section>';
        }

        return $markup . ($includeWrapper ? '</mj-wrapper>' : '');
    }

    private static function formatPercentage(float $percentage): string
    {
        return rtrim(
            rtrim(number_format($percentage, 6, '.', ''), '0'),
            '.'
        ) . '%';
    }

    private static function renderGridCell(
        int $postId,
        array $block,
        array $defaultAttrs,
        int $availableWidth
    ): string {
        if (($block['blockName'] ?? null) !== 'core/group') {
            return BlockProcessor::renderMjmlComponent(
                $postId,
                $block,
                $defaultAttrs,
                true,
                true,
                false,
                $availableWidth
            );
        }

        $groupAttrs = AttributeHandler::processAttributes(
            $block['attrs'] ?? []
        );
        $markup = '';
        foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
            $childDefaultAttrs = self::getChildAttributes(
                $innerBlock,
                $groupAttrs,
                $defaultAttrs
            );
            $markup .= self::renderGridCell(
                $postId,
                $innerBlock,
                $childDefaultAttrs,
                $availableWidth
            );
        }

        return $markup;
    }

    private static function filterGridColumnAttributes(array $attrs): array
    {
        $allowed = [
            'background-color',
            'border',
            'border-bottom',
            'border-left',
            'border-radius',
            'border-right',
            'border-top',
            'padding',
            'padding-bottom',
            'padding-left',
            'padding-right',
            'padding-top',
            'vertical-align',
        ];

        return array_intersect_key($attrs, array_flip($allowed));
    }

    private static function getChildAttributes(
        array $innerBlock,
        array $groupAttrs,
        array $defaultAttrs
    ): array {
        $attrs = array_merge($defaultAttrs, $innerBlock['attrs'] ?? []);
        $attrs['color'] = $attrs['color']
            ?? $groupAttrs['color']
            ?? '#000000';
        $attrs['link'] = $attrs['link']
            ?? $groupAttrs['link']
            ?? '#000000';

        if (isset($attrs['textColor'])) {
            unset($attrs['color']);
        }
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }

        return $attrs;
    }
}
