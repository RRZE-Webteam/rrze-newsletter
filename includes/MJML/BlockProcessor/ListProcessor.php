<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class ListProcessor
{
    /**
     * Render a list or list-item as mj-text and recursively render children.
     *
     * @param array<string, mixed>             $attrs        Block attributes.
     * @param array<int, array<string, mixed>> $innerBlocks  Child blocks.
     * @param array<int, string|null>          $innerContent Interleaved block content.
     * @param string                           $fontFamily   Text font family.
     * @param RenderContext                    $context      Current render context.
     * @return string Rendered MJML markup for the list block.
     */
    public static function render(
        array $attrs,
        array $innerBlocks,
        array $innerContent,
        string $fontFamily,
        RenderContext $context
    ): string {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }
        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $markup = '';
        if (!$context->inList) {
            $markup .= '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>';
        }

        $markup .= $innerContent[0];
        if (!empty($innerBlocks) && count($innerContent) > 1) {
            foreach ($innerBlocks as $innerBlock) {
                $markup .= BlockProcessor::render(
                    $innerBlock,
                    $context
                        ->withDefaultAttrs([])
                        ->insideList()
                );
            }
            $markup .= $innerContent[count($innerContent) - 1];
        }

        if (!$context->inList) {
            $markup .= '</mj-text>';
        }

        return $markup;
    }
}
