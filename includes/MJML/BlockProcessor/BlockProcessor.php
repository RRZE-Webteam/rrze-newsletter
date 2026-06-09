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
    private const BLOCKS_WITHOUT_INNER_HTML = [
        'rrze-newsletter/rss',
        'rrze-newsletter/ics',
    ];

    private const BLOCKS_WITH_OWN_COLUMN = [
        'core/columns',
        'core/column',
        'core/separator',
    ];

    public static function beginRender(): void
    {
        ImageProcessor::beginRender();
    }

    public static function render(
        array $block,
        RenderContext $context
    ): string {
        $blockName = (string) ($block['blockName'] ?? '');
        $blockAttrs = $block['attrs'] ?? [];

        if (
            !isset($blockAttrs['innerBlocksToInsert'])
            && self::isEmptyBlock($block)
        ) {
            return '';
        }

        $defaultAttrs = $context->defaultAttrs;
        $defaultAttrs['postId'] = $context->postId;
        $attrs = AttributeHandler::processAttributes(
            array_merge($defaultAttrs, $blockAttrs)
        );
        $padding = StyleProcessor::getPaddingFromAttributes($attrs);
        $sectionAttrs = array_merge($attrs, ['padding' => '0']);
        if ($blockName === 'core/separator') {
            unset($sectionAttrs['background-color']);
        }
        $columnAttrs = ['padding' => $padding ?: '0'];
        $fontFamily = $blockName === 'core/heading'
            ? Renderer::getFontHeader()
            : Renderer::getFontBody();

        $markup = self::renderBlock(
            $blockName,
            $block,
            $attrs,
            $columnAttrs,
            $fontFamily,
            $context->withDefaultAttrs($defaultAttrs)
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
            $context
        );
    }

    private static function isEmptyBlock(array $block): bool
    {
        $blockName = (string) ($block['blockName'] ?? '');

        return $blockName === ''
            || (
                !in_array(
                    $blockName,
                    self::BLOCKS_WITHOUT_INNER_HTML,
                    true
                )
                && empty($block['innerHTML'])
            );
    }

    private static function renderBlock(
        string $blockName,
        array $block,
        array $attrs,
        array $columnAttrs,
        string $fontFamily,
        RenderContext $context
    ): string {
        $innerBlocks = $block['innerBlocks'] ?? [];
        $innerHtml = (string) ($block['innerHTML'] ?? '');
        $innerContent = $block['innerContent'] ?? [$innerHtml];

        switch ($blockName) {
            case 'core/paragraph':
            case 'core/heading':
                return ParagraphProcessor::render(
                    $block,
                    $attrs,
                    $innerHtml,
                    $context->inList,
                    $fontFamily
                );

            case 'core/list':
            case 'core/list-item':
                return ListProcessor::render(
                    $attrs,
                    $innerBlocks,
                    $innerContent,
                    $fontFamily,
                    $context
                );

            case 'core/image':
                return ImageProcessor::render(
                    $attrs,
                    $innerHtml,
                    $fontFamily,
                    LayoutHelper::subtractHorizontalPadding(
                        $context->availableWidth,
                        $columnAttrs['padding']
                    )
                );

            case 'core/separator':
                return SeparatorProcessor::render($attrs);

            case 'core/spacer':
                return SpacerProcessor::render($attrs);

            case 'core/social-links':
                return SocialLinksProcessor::render(
                    $attrs,
                    $innerBlocks
                );

            case 'core/buttons':
                return ButtonProcessor::renderButtons(
                    $attrs,
                    $innerBlocks,
                    $fontFamily,
                    $context->availableWidth
                );

            case 'core/button':
                return ButtonProcessor::renderButton(
                    AttributeHandler::processAttributes($block['attrs'] ?? []),
                    $innerHtml,
                    $fontFamily,
                    'left',
                    $context->availableWidth
                );

            case 'core/column':
                return ColumnProcessor::renderColumn(
                    $attrs,
                    $innerBlocks,
                    $columnAttrs,
                    $context
                );

            case 'core/columns':
                return ColumnProcessor::renderColumns(
                    $attrs,
                    $innerBlocks,
                    $context->withAvailableWidth(
                        LayoutHelper::subtractHorizontalPadding(
                            $context->availableWidth,
                            StyleProcessor::getPaddingFromAttributes($attrs)
                        )
                    )
                );

            case 'core/group':
                return GroupProcessor::render(
                    $block,
                    $context
                );

            case 'rrze-newsletter/rss':
                return FeedProcessor::renderRss(
                    $context->postId,
                    $attrs,
                    $fontFamily
                );

            case 'rrze-newsletter/ics':
                return FeedProcessor::renderIcs(
                    $context->postId,
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
        RenderContext $context
    ): string {
        $isPostInserterBlock =
            $blockName === 'rrze-newsletter/post-inserter';
        $isGroupBlock = $blockName === 'core/group';
        $hasOwnColumn = in_array(
            $blockName,
            self::BLOCKS_WITH_OWN_COLUMN,
            true
        );

        if (
            !$context->inColumn
            && !$context->inList
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
            $context->inColumn
            || $context->inList
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
