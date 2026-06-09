<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

/**
 * Renders paragraph and heading blocks.
 */
final class ParagraphProcessor
{
    /**
     * Render paragraph or heading as mj-text.
     *
     * @param array<string, mixed> $block      Block data.
     * @param array<string, mixed> $attrs      Block attributes.
     * @param string               $innerHtml  Rendered block HTML.
     * @param bool                 $isInList   Whether the block is inside a list.
     * @param string               $fontFamily Text font family.
     * @return string Rendered MJML markup for the text block.
     */
    public static function render(
        array $block,
        array $attrs,
        string $innerHtml,
        bool $isInList,
        string $fontFamily
    ): string {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }

        $textAlign = $attrs['style']['typography']['textAlign'] ?? 'left';
        if (!in_array($textAlign, ['left', 'center', 'right', 'justify'], true)) {
            $textAlign = 'left';
        }
        $innerHtml = self::applyTextAlignment($innerHtml, $textAlign);

        $textAttrs = array_merge([
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
            'align'       => $textAlign,
        ], $attrs);

        if (isset($textAttrs['background-color'])) {
            $textAttrs['container-background-color'] = $textAttrs['background-color'];
            unset($textAttrs['background-color']);
        }

        $innerHtml = StyleProcessor::applyLinkColor($block, $attrs, $innerHtml);

        if ($isInList) {
            return $innerHtml;
        }

        return '<mj-text '
            . AttributeHandler::arrayToAttributes($textAttrs)
            . '>'
            . $innerHtml
            . '</mj-text>';
    }

    /**
     * Adds alignment directly to the rendered HTML element for email clients
     * that do not reliably inherit text alignment from the MJML container.
     *
     * @param string $innerHtml Rendered block HTML.
     * @param string $textAlign Validated text alignment.
     * @return string HTML with inline and legacy alignment attributes.
     */
    private static function applyTextAlignment(
        string $innerHtml,
        string $textAlign
    ): string {
        $processor = new \WP_HTML_Tag_Processor($innerHtml);
        if (!$processor->next_tag()) {
            return $innerHtml;
        }

        $style = (string) $processor->get_attribute('style');
        $style = preg_replace(
            '/(?:^|;)\s*text-align\s*:[^;]*/i',
            '',
            $style
        ) ?? $style;
        $style = trim($style, " \t\n\r\0\x0B;");
        $style = ($style !== '' ? $style . '; ' : '') . 'text-align: ' . $textAlign . ';';

        $processor->set_attribute('style', $style);
        $processor->set_attribute('align', $textAlign);

        return $processor->get_updated_html();
    }
}
