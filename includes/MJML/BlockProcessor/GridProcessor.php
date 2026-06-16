<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class GridProcessor
{
    private const DEFAULT_MINIMUM_COLUMN_WIDTH = '12rem';

    /**
     * Renders a WordPress grid group as MJML rows and columns.
     *
     * @param array<string, mixed> $block Parsed grid group block.
     * @param array<string, mixed> $groupAttrs Processed group attributes.
     * @param RenderContext $context Current rendering context.
     * @param bool $includeWrapper Whether to add an outer mj-wrapper.
     * @return string Rendered grid MJML.
     */
    public static function render(
        array $block,
        array $groupAttrs,
        RenderContext $context,
        bool $includeWrapper
    ): string {
        $columnCount = self::resolveColumnCount(
            $block['attrs']['layout'] ?? [],
            $context->availableWidth
        );
        $rows = array_chunk($block['innerBlocks'] ?? [], $columnCount);
        $markup = self::openWrapper($groupAttrs, $includeWrapper);

        foreach ($rows as $row) {
            $markup .= self::renderRow(
                $row,
                $groupAttrs,
                $context,
                $columnCount
            );
        }

        return $markup . ($includeWrapper ? '</mj-wrapper>' : '');
    }

    /**
     * Renders one grid row.
     *
     * @param array<int, array<string, mixed>> $blocks Blocks in the row.
     * @param array<string, mixed> $groupAttrs Processed grid attributes.
     * @param RenderContext $context Current rendering context.
     * @param int $columnCount Number of columns in the grid.
     * @return string Rendered mj-section.
     */
    private static function renderRow(
        array $blocks,
        array $groupAttrs,
        RenderContext $context,
        int $columnCount
    ): string {
        $columnWidth = max(
            1,
            (int) floor($context->availableWidth / $columnCount)
        );
        $columnWidthPercent = self::formatPercentage(100 / $columnCount);
        $markup = '<mj-section padding="0">';

        foreach ($blocks as $block) {
            $markup .= self::renderCell(
                $block,
                $groupAttrs,
                $context,
                $columnWidth,
                $columnWidthPercent
            );
        }

        return $markup . '</mj-section>';
    }

    /**
     * Renders one block inside an MJML grid column.
     *
     * @param array<string, mixed> $block Grid cell block.
     * @param array<string, mixed> $groupAttrs Processed grid attributes.
     * @param RenderContext $context Current rendering context.
     * @param int $columnWidth Column width in pixels.
     * @param string $columnWidthPercent Column width as an MJML percentage.
     * @return string Rendered mj-column.
     */
    private static function renderCell(
        array $block,
        array $groupAttrs,
        RenderContext $context,
        int $columnWidth,
        string $columnWidthPercent
    ): string {
        $defaultAttrs = AttributeInheritance::forChild(
            $block,
            $groupAttrs,
            $context->defaultAttrs
        );
        [$columnAttrs, $contentWidth] = self::getCellLayout(
            $block,
            $columnWidth,
            $columnWidthPercent
        );
        $cellContext = $context
            ->withDefaultAttrs($defaultAttrs)
            ->withAvailableWidth($contentWidth)
            ->insideColumn();

        return '<mj-column '
            . AttributeHandler::arrayToAttributes($columnAttrs)
            . '>'
            . self::renderCellContent($block, $cellContext)
            . '</mj-column>';
    }

    /**
     * Renders grid cell content while flattening nested group blocks.
     *
     * @param array<string, mixed> $block Grid cell block.
     * @param RenderContext $context Context scoped to the cell.
     * @return string Rendered cell content.
     */
    private static function renderCellContent(
        array $block,
        RenderContext $context
    ): string {
        if (($block['blockName'] ?? null) !== 'core/group') {
            return BlockProcessor::render($block, $context);
        }

        $groupAttrs = AttributeHandler::processAttributes(
            $block['attrs'] ?? []
        );
        $markup = '';
        foreach ($block['innerBlocks'] ?? [] as $innerBlock) {
            $defaultAttrs = AttributeInheritance::forChild(
                $innerBlock,
                $groupAttrs,
                $context->defaultAttrs
            );
            $markup .= self::renderCellContent(
                $innerBlock,
                $context->withDefaultAttrs($defaultAttrs)
            );
        }

        return $markup;
    }

    /**
     * Resolves MJML column attributes and the remaining content width.
     *
     * @param array<string, mixed> $block Grid cell block.
     * @param int $columnWidth Column width in pixels.
     * @param string $columnWidthPercent Column width as a percentage.
     * @return array{0: array<string, mixed>, 1: int}
     */
    private static function getCellLayout(
        array $block,
        int $columnWidth,
        string $columnWidthPercent
    ): array {
        $columnAttrs = [
            'padding' => '0',
            'width' => $columnWidthPercent,
        ];

        if (($block['blockName'] ?? null) !== 'core/group') {
            return [$columnAttrs, $columnWidth];
        }

        $groupAttrs = AttributeHandler::processAttributes(
            $block['attrs'] ?? []
        );
        $groupAttrs['padding'] =
            StyleProcessor::getPaddingFromAttributes($groupAttrs) ?: '0';
        $columnAttrs = array_merge(
            $columnAttrs,
            self::filterColumnAttributes($groupAttrs)
        );

        return [
            $columnAttrs,
            LayoutHelper::subtractHorizontalPadding(
                $columnWidth,
                $columnAttrs['padding']
            ),
        ];
    }

    /**
     * Opens the optional wrapper around grid rows.
     *
     * @param array<string, mixed> $groupAttrs Processed grid attributes.
     * @param bool $includeWrapper Whether a wrapper is required.
     * @return string Opening wrapper markup or an empty string.
     */
    private static function openWrapper(
        array $groupAttrs,
        bool $includeWrapper
    ): string {
        if (!$includeWrapper) {
            return '';
        }

        return '<mj-wrapper '
            . AttributeHandler::arrayToAttributes(
                LayoutHelper::filterSectionAttributes($groupAttrs)
            )
            . '>';
    }

    /**
     * Determines the effective desktop column count.
     *
     * @param array<string, mixed> $layout WordPress layout attributes.
     * @param int $availableWidth Available grid width in pixels.
     * @return int Number of columns, always at least one.
     */
    private static function resolveColumnCount(
        array $layout,
        int $availableWidth
    ): int {
        $configuredCount = self::positiveInteger(
            $layout['columnCount'] ?? null
        );
        $minimumWidth = $layout['minimumColumnWidth']
            ?? ($configuredCount === null
                ? self::DEFAULT_MINIMUM_COLUMN_WIDTH
                : null);
        $minimumWidthPixels = self::cssLengthToPixels($minimumWidth);

        if ($minimumWidthPixels === null) {
            return $configuredCount ?? 1;
        }

        $responsiveCount = max(
            1,
            (int) floor($availableWidth / $minimumWidthPixels)
        );

        return $configuredCount === null
            ? $responsiveCount
            : min($configuredCount, $responsiveCount);
    }

    /**
     * Converts a positive numeric value to an integer.
     *
     * @param mixed $value Candidate value.
     * @return int|null Positive integer or null.
     */
    private static function positiveInteger(mixed $value): ?int
    {
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Converts supported CSS lengths to pixels using a 16px em/rem base.
     *
     * @param mixed $value CSS length using px, em, rem, or no unit.
     * @return float|null Pixel value or null for unsupported input.
     */
    private static function cssLengthToPixels(mixed $value): ?float
    {
        if (
            (!is_string($value) && !is_numeric($value))
            || !preg_match(
                '/^(\d+(?:\.\d+)?)\s*(px|rem|em)?$/i',
                trim((string) $value),
                $matches
            )
        ) {
            return null;
        }

        $amount = (float) $matches[1];
        if ($amount <= 0) {
            return null;
        }

        return in_array(strtolower($matches[2] ?? 'px'), ['rem', 'em'], true)
            ? $amount * 16
            : $amount;
    }

    /**
     * Formats a percentage without unnecessary trailing zeroes.
     *
     * @param float $percentage Percentage value.
     * @return string MJML-compatible percentage.
     */
    private static function formatPercentage(float $percentage): string
    {
        return rtrim(
            rtrim(number_format($percentage, 6, '.', ''), '0'),
            '.'
        ) . '%';
    }

    /**
     * Keeps only attributes supported by mj-column.
     *
     * @param array<string, mixed> $attrs Processed block attributes.
     * @return array<string, mixed> Filtered column attributes.
     */
    private static function filterColumnAttributes(array $attrs): array
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
}
