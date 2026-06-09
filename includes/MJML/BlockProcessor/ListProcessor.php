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
     * @param int $postId The post ID.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $innerContent The inner content of the block.
     * @param bool $isInList Whether the block is inside a list.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the list block.
     */
    public static function render(
        int $postId,
        array $attrs,
        array $innerBlocks,
        array $innerContent,
        bool $isInList,
        string $fontFamily,
        int $availableWidth
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
        if (!$isInList) {
            $markup .= '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>';
        }

        $markup .= $innerContent[0];
        if (!empty($innerBlocks) && count($innerContent) > 1) {
            foreach ($innerBlocks as $innerBlock) {
                $markup .= BlockProcessor::renderMjmlComponent(
                    $postId,
                    $innerBlock,
                    [],
                    false,
                    false,
                    true,
                    $availableWidth
                );
            }
            $markup .= $innerContent[count($innerContent) - 1];
        }

        if (!$isInList) {
            $markup .= '</mj-text>';
        }

        return $markup;
    }
}
