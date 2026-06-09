<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\MJML\StyleProcessor;

/**
 * Coordinates block rendering and applies the surrounding MJML layout.
 */
final class BlockProcessor
{
    public static function beginRender(): void
    {
        ImageProcessor::beginRender();
    }

    public static function renderMjmlComponent(
        int $postId,
        array $block,
        array $defaultAttrs = [],
        bool $isInColumn = false,
        bool $isInGroup = false,
        bool $isInList = false,
        int $availableWidth = Renderer::EMAIL_WIDTH
    ): string {
        $blockName = (string) ($block['blockName'] ?? '');
        $blockAttrs = $block['attrs'] ?? [];
        $innerBlocks = $block['innerBlocks'] ?? [];
        $innerHtml = (string) ($block['innerHTML'] ?? '');
        $innerContent = $block['innerContent'] ?? [$innerHtml];

        if (
            !isset($blockAttrs['innerBlocksToInsert'])
            && self::isEmptyBlock($block)
        ) {
            return '';
        }

        $defaultAttrs['postId'] = $postId;
        $attrs = AttributeHandler::processAttributes(
            array_merge($defaultAttrs, $blockAttrs)
        );
        $padding = StyleProcessor::getPaddingFromAttributes($attrs);
        $sectionAttrs = array_merge($attrs, ['padding' => '0']);
        $columnAttrs = ['padding' => $padding ?: '0'];
        $fontFamily = $blockName === 'core/heading'
            ? Renderer::getFontHeader()
            : Renderer::getFontBody();

        $markup = self::renderBlock(
            $postId,
            $blockName,
            $block,
            $blockAttrs,
            $attrs,
            $innerBlocks,
            $innerHtml,
            $innerContent,
            $defaultAttrs,
            $columnAttrs,
            $sectionAttrs,
            $fontFamily,
            $isInColumn,
            $isInGroup,
            $isInList,
            $availableWidth
        );

        if ($markup === '') {
            return '';
        }

        return self::wrapMarkup(
            $blockName,
            $markup,
            $columnAttrs,
            $sectionAttrs,
            $padding,
            $isInColumn,
            $isInList
        );
    }

    public static function isEmptyBlock(array $block): bool
    {
        $blockName = (string) ($block['blockName'] ?? '');
        $blocksWithoutInnerHtml = [
            'rrze-newsletter/rss',
            'rrze-newsletter/ics',
        ];

        return $blockName === ''
            || (
                !in_array($blockName, $blocksWithoutInnerHtml, true)
                && empty($block['innerHTML'])
            );
    }

    private static function renderBlock(
        int $postId,
        string $blockName,
        array $block,
        array $blockAttrs,
        array $attrs,
        array $innerBlocks,
        string $innerHtml,
        array $innerContent,
        array $defaultAttrs,
        array $columnAttrs,
        array &$sectionAttrs,
        string $fontFamily,
        bool $isInColumn,
        bool $isInGroup,
        bool $isInList,
        int $availableWidth
    ): string {
        switch ($blockName) {
            case 'core/paragraph':
            case 'core/heading':
                return ParagraphProcessor::render(
                    $block,
                    $attrs,
                    $innerHtml,
                    $isInList,
                    $fontFamily
                );

            case 'core/list':
            case 'core/list-item':
                return ListProcessor::render(
                    $postId,
                    $attrs,
                    $innerBlocks,
                    $innerContent,
                    $isInList,
                    $fontFamily,
                    $availableWidth
                );

            case 'core/image':
                $sectionAttrs = LayoutHelper::filterSectionAttributes(
                    $sectionAttrs
                );
                return ImageProcessor::render(
                    $attrs,
                    $innerHtml,
                    $fontFamily,
                    LayoutHelper::subtractHorizontalPadding(
                        $availableWidth,
                        $columnAttrs['padding']
                    )
                );

            case 'core/separator':
                return BasicBlockProcessor::renderSeparator(
                    $attrs,
                    $sectionAttrs
                );

            case 'core/spacer':
                return BasicBlockProcessor::renderSpacer($attrs);

            case 'core/social-links':
                return BasicBlockProcessor::renderSocialLinks(
                    $attrs,
                    $innerBlocks
                );

            case 'core/buttons':
                $sectionAttrs = LayoutHelper::filterSectionAttributes(
                    $sectionAttrs
                );
                return ButtonProcessor::renderButtons(
                    $attrs,
                    $innerBlocks,
                    $fontFamily,
                    $availableWidth
                );

            case 'core/button':
                $sectionAttrs = LayoutHelper::filterSectionAttributes(
                    $sectionAttrs
                );
                return ButtonProcessor::renderButton(
                    AttributeHandler::processAttributes($blockAttrs),
                    $innerHtml,
                    $fontFamily,
                    'left',
                    $availableWidth
                );

            case 'core/column':
                return ColumnProcessor::renderColumn(
                    $postId,
                    $attrs,
                    $innerBlocks,
                    $defaultAttrs,
                    $columnAttrs,
                    $availableWidth
                );

            case 'core/columns':
                return ColumnProcessor::renderColumns(
                    $postId,
                    $attrs,
                    $innerBlocks,
                    $defaultAttrs,
                    LayoutHelper::subtractHorizontalPadding(
                        $availableWidth,
                        StyleProcessor::getPaddingFromAttributes($attrs)
                    )
                );

            case 'core/group':
                return GroupProcessor::render(
                    $postId,
                    $block,
                    $innerBlocks,
                    $defaultAttrs,
                    $availableWidth,
                    $isInColumn,
                    $isInGroup
                );

            case 'rrze-newsletter/rss':
                return FeedProcessor::renderRss(
                    $postId,
                    $attrs,
                    $fontFamily
                );

            case 'rrze-newsletter/ics':
                return FeedProcessor::renderIcs(
                    $postId,
                    $attrs,
                    $fontFamily
                );

            default:
                return '';
        }
    }

    private static function wrapMarkup(
        string $blockName,
        string $markup,
        array $columnAttrs,
        array $sectionAttrs,
        string $padding,
        bool $isInColumn,
        bool $isInList
    ): string {
        $isPostInserterBlock =
            $blockName === 'rrze-newsletter/post-inserter';
        $isGroupBlock = $blockName === 'core/group';
        $hasOwnColumn = in_array(
            $blockName,
            ['core/columns', 'core/column', 'core/separator'],
            true
        );

        if (
            !$isInColumn
            && !$isInList
            && !$isGroupBlock
            && !$hasOwnColumn
            && !$isPostInserterBlock
        ) {
            $columnAttrs['width'] = '100%';
            $markup = '<mj-column '
                . AttributeHandler::arrayToAttributes($columnAttrs)
                . '>'
                . $markup
                . '</mj-column>';
        }

        if (
            $isInColumn
            || $isInList
            || $isGroupBlock
            || $isPostInserterBlock
        ) {
            return $markup;
        }

        if ($padding !== '' && $blockName === 'core/columns') {
            $sectionAttrs['padding'] = $padding;
        }
        $sectionAttrs = LayoutHelper::filterSectionAttributes($sectionAttrs);

        return '<mj-section '
            . AttributeHandler::arrayToAttributes($sectionAttrs)
            . '>'
            . $markup
            . '</mj-section>';
    }
}
