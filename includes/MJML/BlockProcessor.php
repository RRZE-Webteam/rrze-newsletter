<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;
use RRZE\Newsletter\MJML\SocialIcons;

use function RRZE\Newsletter\plugin;

/**
 * Class BlockProcessor
 * 
 * Processes MJML blocks for rendering in newsletters.
 * 
 * @package RRZE\Newsletter\MJML
 */
class BlockProcessor
{
    /**
     * Renders a MJML component based on the block data.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $defaultAttrs Default attributes to apply to the block.
     * @param bool $isInColumn Whether the block is inside a column.
     * @param bool $isInGroup Whether the block is inside a group.
     * @param bool $isInList Whether the block is inside a list.
     * @return string Rendered MJML markup for the block.
     */
    public static function renderMjmlComponent(
        $postId,
        $block,
        $defaultAttrs = [],
        $isInColumn = false,
        $isInGroup = false,
        $isInList = false
    ) {
        $blockName    = $block['blockName'];
        $attrs        = $block['attrs'];
        $innerBlocks  = $block['innerBlocks'];
        $innerHtml    = $block['innerHTML'];
        $innerContent = $block['innerContent'] ?? [$innerHtml];

        // If the block is empty and not a container, return nothing.
        if (!isset($attrs['innerBlocksToInsert']) && self::isEmptyBlock($block)) {
            return '';
        }

        $defaultAttrs['postId'] = $postId;
        $attrs = array_merge($defaultAttrs, $attrs);
        $attrs = AttributeHandler::processAttributes($attrs);

        $padding = StyleProcessor::getPaddingFromAttributes($attrs);

        $sectionAttrs = array_merge($attrs, ['padding' => '0']);
        $columnAttrs  = ['padding' => $padding ?: '0'];

        $fontFamily = $blockName === 'core/heading' ? Renderer::getFontHeader() : Renderer::getFontBody();

        // Switch dispatches to helper methods
        switch ($blockName) {
            case 'core/paragraph':
            case 'core/heading':
                $markup = self::renderTextBlock($block, $attrs, $innerHtml, $isInList, $fontFamily);
                break;

            case 'core/list':
            case 'core/list-item':
                $markup = self::renderListBlock($postId, $block, $attrs, $innerBlocks, $innerContent, $isInList, $fontFamily);
                break;

            case 'core/image':
                $markup = self::renderImageBlock($block, $attrs, $innerHtml, $fontFamily, $columnAttrs);
                break;

            case 'core/separator':
                $markup = self::renderSeparatorBlock($attrs, $sectionAttrs);
                break;

            case 'core/spacer':
                $markup = self::renderSpacerBlock($attrs);
                break;

            case 'core/social-links':
                $markup = self::renderSocialLinksBlock($attrs, $innerBlocks);
                break;

            case 'core/column':
                $markup = self::renderColumnBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $columnAttrs);
                break;

            case 'core/columns':
                $markup = self::renderColumnsBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs);
                break;

            case 'core/group':
                $markup = self::renderGroupBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs);
                break;

            case 'rrze-newsletter/rss':
                $markup = self::renderNewsletterRssBlock($postId, $block, $attrs, $fontFamily, $columnAttrs);
                break;

            case 'rrze-newsletter/ics':
                $markup = self::renderNewsletterIcsBlock($postId, $block, $attrs, $fontFamily, $columnAttrs);
                break;

            default:
                $markup = self::renderDefaultBlock($block, $attrs, $columnAttrs);
                break;
        }

        // Wrapping logic for columns/sections
        $isPostInserterBlock = $blockName === 'rrze-newsletter/post-inserter';
        $isGroupBlock = $blockName === 'core/group';

        if (
            !$isInColumn &&
            !$isInList &&
            !$isGroupBlock &&
            !in_array($blockName, ['core/columns', 'core/column', 'core/separator'], true) &&
            !$isPostInserterBlock
        ) {
            $columnAttrs['width'] = '100%';
            $markup = '<mj-column ' . AttributeHandler::arrayToAttributes($columnAttrs) . '>' . $markup . '</mj-column>';
        }

        if (
            !$isInColumn &&
            !$isInList &&
            !$isGroupBlock &&
            !$isPostInserterBlock
        ) {
            if ($padding && $blockName === 'core/columns') {
                $sectionAttrs['padding'] = $padding;
            }
            return '<mj-section ' . AttributeHandler::arrayToAttributes($sectionAttrs) . '>' . $markup . '</mj-section>';
        }

        return $markup;
    }

    /**
     * Determines whether the block is empty.
     *
     * @param WP_Block $block The block.
     * @return bool Whether the block is empty.
     */
    public static function isEmptyBlock($block)
    {
        $blocksWithoutInnerHtml = [
            'rrze-newsletter/rss',
            'rrze-newsletter/ics',
        ];

        $emptyBlockName = empty($block['blockName']);
        $emptyHtml = !in_array($block['blockName'], $blocksWithoutInnerHtml, true) && empty($block['innerHTML']);

        return $emptyBlockName || $emptyHtml;
    }

    /**
     * Render paragraph or heading as mj-text.
     * 
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param bool $isInList Whether the block is inside a list.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the text block.
     */
    private static function renderTextBlock($block, $attrs, $innerHtml, $isInList, $fontFamily)
    {
        // Inherit link color if available
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = $attrs['style']['elements']['link']['color']['text'];
        }
        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        if (isset($textAttrs['background-color'])) {
            $textAttrs['container-background-color'] = $textAttrs['background-color'];
            unset($textAttrs['background-color']);
        }

        $innerHtml = StyleProcessor::applyLinkColor($block, $attrs, $innerHtml);

        if ($isInList) {
            return $innerHtml;
        }
        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Render a list or list-item as mj-text and recursively render children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $innerContent The inner content of the block.
     * @param bool $isInList Whether the block is inside a list.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the list block.
     */
    private static function renderListBlock($postId, $block, $attrs, $innerBlocks, $innerContent, $isInList, $fontFamily)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = $attrs['style']['elements']['link']['color']['text'];
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
                $markup .= self::renderMjmlComponent($postId, $innerBlock, [], false, false, true);
            }
            $markup .= $innerContent[count($innerContent) - 1];
        }

        if (!$isInList) {
            $markup .= '</mj-text>';
        }
        return $markup;
    }

    /**
     * Render image block as mj-image and figcaption as mj-text.
     * 
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param string $fontFamily The font family to use for the text.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the image block.
     */
    private static function renderImageBlock($block, $attrs, $innerHtml, $fontFamily, $columnAttrs)
    {
        $columnAttrs['width'] = '100%';

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $innerHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXpath($dom);
        $img = $xpath->query('//img')[0];
        $imgSrc = $img ? $img->getAttribute('src') : '';
        $figcaption = $xpath->query('//figcaption/text()')[0];

        if ($imgSrc && strpos($imgSrc, 'http') !== 0) {
            $imgSrc = home_url($imgSrc);
        }

        $imgAttrs = [
            'padding' => '0',
            'align'   => $attrs['align'] ?? 'left',
            'src'     => $imgSrc,
        ];

        if (isset($attrs['sizeSlug'])) {
            if ('medium' == $attrs['sizeSlug']) {
                $imgAttrs['width'] = '300px';
            }
            if ('thumbnail' == $attrs['sizeSlug']) {
                $imgAttrs['width'] = '150px';
            }
        } elseif (isset($attrs['className'])) {
            if ('size-medium' == $attrs['className']) {
                $imgAttrs['width'] = '300px';
            }
            if ('size-thumbnail' == $attrs['className']) {
                $imgAttrs['width'] = '150px';
            }
        }
        if (isset($attrs['width'])) {
            $imgAttrs['width'] = $attrs['width'];
        }
        if (isset($attrs['height'])) {
            $imgAttrs['height'] = $attrs['height'];
        }
        if (isset($attrs['href'])) {
            $imgAttrs['href'] = $attrs['href'];
        } else {
            $maybeLink = $img->parentNode;
            if ($maybeLink && 'a' === $maybeLink->nodeName && $maybeLink->getAttribute('href')) {
                $imgAttrs['href'] = trim($maybeLink->getAttribute('href'));
            }
        }
        if (isset($attrs['className']) && strpos($attrs['className'], 'is-style-rounded') !== false) {
            $imgAttrs['border-radius'] = '999px';
        }

        $markup = '<mj-image ' . AttributeHandler::arrayToAttributes($imgAttrs) . ' />';

        if ($figcaption) {
            $captionAttrs = [
                'align' => 'left',
                'font-size' => '14px',
                'line-height' => '1.4',
                'padding' => '16px 0',
                'font-family' => $fontFamily,
            ];
            if (isset($attrs['color'])) {
                $captionAttrs['color'] = $attrs['color'];
            }
            $markup .= '<mj-text ' . AttributeHandler::arrayToAttributes($captionAttrs) . '>' . $figcaption->wholeText . '</mj-text>';
        }
        return $markup;
    }

    /**
     * Render separator block as mj-divider.
     * 
     * @param array $attrs The block attributes.
     * @param array $sectionAttrs The section attributes.
     * @return string Rendered MJML markup for the separator block.
     */
    private static function renderSeparatorBlock($attrs, $sectionAttrs)
    {
        $isWide = isset($attrs['className']) && $attrs['className'] === 'is-style-wide';
        $dividerAttrs = [
            'padding' => '0',
            'border-width' => '1px',
            'width' => $isWide ? '100%' : '128px',
        ];
        unset($sectionAttrs['background-color']);
        if (isset($attrs['backgroundColor']) && Renderer::getColorFromPalette($attrs['backgroundColor'])) {
            $dividerAttrs['border-color'] = Renderer::getColorFromPalette($attrs['backgroundColor']);
        }
        if (isset($attrs['color'])) {
            $dividerAttrs['border-color'] = $attrs['color'];
        }
        return '<mj-divider ' . AttributeHandler::arrayToAttributes($dividerAttrs) . ' />';
    }

    /**
     * Render spacer block as mj-spacer.
     * 
     * @param array $attrs The block attributes.
     * @return string Rendered MJML markup for the spacer block.
     */
    private static function renderSpacerBlock($attrs)
    {
        $ary = explode('|', $attrs['height'] ?? '0');
        $attrs['height'] = absint(end($ary)) . 'px';
        return '<mj-spacer ' . AttributeHandler::arrayToAttributes($attrs) . ' />';
    }

    /**
     * Render social links block as mj-social.
     * 
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @return string Rendered MJML markup for the social links block.
     */
    private static function renderSocialLinksBlock($attrs, $innerBlocks)
    {
        $wrapperAttrs = [
            'icon-size'     => '24px',
            'mode'          => 'horizontal',
            'padding'       => '0',
            'border-radius' => '999px',
            'icon-padding'  => '7px',
            'align'         => $attrs['align'] ?? 'left',
        ];
        $markup = '<mj-social ' . AttributeHandler::arrayToAttributes($wrapperAttrs) . '>';
        foreach ($innerBlocks as $LinkBlock) {
            if (isset($LinkBlock['attrs']['url'])) {
                $url = $LinkBlock['attrs']['url'];
                $serviceName = $LinkBlock['attrs']['service'];
                $socialIcon = SocialIcons::getIconAttributes($serviceName, $attrs);

                if (!empty($socialIcon)) {
                    $imgAttrs = [
                        'href' => $url,
                        'src' => plugins_url('assets/social-links/' . $socialIcon['icon'], plugin()->getBasename()),
                        'background-color' => $socialIcon['color'],
                        'css-class' => 'social-element',
                        'padding' => '2px',
                    ];
                    $markup .= '<mj-social-element ' . AttributeHandler::arrayToAttributes($imgAttrs) . ' />';
                }
            }
        }
        $markup .= '</mj-social>';
        return $markup;
    }

    /**
     * Render a single column block as mj-column with children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @param array $columnAttrs Column attributes for the mj-column.
     * @return string Rendered MJML markup for the column block.
     */
    private static function renderColumnBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs, $columnAttrs)
    {
        if (isset($attrs['verticalAlignment'])) {
            if ($attrs['verticalAlignment'] === 'center') {
                $columnAttrs['vertical-align'] = 'middle';
            } else {
                $columnAttrs['vertical-align'] = $attrs['verticalAlignment'];
            }
        }
        if (isset($attrs['width'])) {
            $columnAttrs['width'] = $attrs['width'];
            $columnAttrs['css-class'] = 'mj-column-has-width';
        }

        $markup = '<mj-column ' . AttributeHandler::arrayToAttributes($columnAttrs) . '>';
        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = $defaultAttrs;
            $hasOwnLinkColor =
                !empty($childBlock['attrs']['style']['elements']['link']['color']['text']) ||
                !empty($childBlock['attrs']['link']);
            if ($hasOwnLinkColor && isset($childDefaultAttrs['link'])) {
                unset($childDefaultAttrs['link']);
            }
            $markup .= self::renderMjmlComponent($postId, $childBlock, $childDefaultAttrs, true, false);
        }
        $markup .= '</mj-column>';
        return $markup;
    }

    /**
     * Render columns block, distributing widths if needed, and rendering children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @return string Rendered MJML markup for the columns block.
     */
    private static function renderColumnsBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs)
    {
        // Calculate widths for columns if not set
        $widthsSum = 0;
        $noWidthColsIndexes = [];
        foreach ($innerBlocks as $i => $colBlock) {
            if (isset($colBlock['attrs']['width'])) {
                $widthsSum += floatval($colBlock['attrs']['width']);
            } else {
                $noWidthColsIndexes[] = $i;
            }
        }
        if (count($noWidthColsIndexes)) {
            $autoWidth = (100 - $widthsSum) / count($noWidthColsIndexes) . '%';
            foreach ($noWidthColsIndexes as $idx) {
                $innerBlocks[$idx]['attrs']['width'] = $autoWidth;
            }
        }

        if (isset($attrs['color'])) {
            $defaultAttrs['color'] = $attrs['color'];
        }
        if (isset($attrs['link'])) {
            $defaultAttrs['link'] = $attrs['link'];
        }

        $isStackedOnMobile = !isset($attrs['isStackedOnMobile']) || $attrs['isStackedOnMobile'] === true;
        $markup = $isStackedOnMobile ? '' : '<mj-group>';

        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = $defaultAttrs;
            $hasOwnLinkColor =
                !empty($childBlock['attrs']['style']['elements']['link']['color']['text']) ||
                !empty($childBlock['attrs']['link']);
            if ($hasOwnLinkColor && isset($childDefaultAttrs['link'])) {
                unset($childDefaultAttrs['link']);
            }
            $markup .= self::renderMjmlComponent($postId, $childBlock, $childDefaultAttrs, true, false);
        }

        if (!$isStackedOnMobile) {
            $markup .= '</mj-group>';
        }
        return $markup;
    }

    /**
     * Render group block as mj-wrapper with children.
     * 
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $innerBlocks The inner blocks to render.
     * @param array $defaultAttrs Default attributes to apply to children.
     * @return string Rendered MJML markup for the group block.
     */
    private static function renderGroupBlock($postId, $block, $attrs, $innerBlocks, $defaultAttrs)
    {
        if (isset($attrs['color'])) {
            $defaultAttrs['color'] = $attrs['color'];
        }
        if (isset($attrs['link'])) {
            $defaultAttrs['link'] = $attrs['link'];
        }

        $markup = '<mj-wrapper ' . AttributeHandler::arrayToAttributes($attrs) . '>';
        foreach ($innerBlocks as $childBlock) {
            $childDefaultAttrs = $defaultAttrs;
            $hasOwnLinkColor =
                !empty($childBlock['attrs']['style']['elements']['link']['color']['text']) ||
                !empty($childBlock['attrs']['link']);
            if ($hasOwnLinkColor && isset($childDefaultAttrs['link'])) {
                unset($childDefaultAttrs['link']);
            }
            $markup .= self::renderMjmlComponent($postId, $childBlock, $childDefaultAttrs, false, true);
        }
        $markup .= '</mj-wrapper>';
        return $markup;
    }

    /**
     * Render newsletter RSS block as mj-text and store block meta.
     *
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $fontFamily The font family to use for the text.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the RSS newsletter block.
     */
    private static function renderNewsletterRssBlock($postId, $block, $attrs, $fontFamily, $columnAttrs)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = $attrs['style']['elements']['link']['color']['text'];
        }
        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $columnAttrs['padding'] = '0';
        $key = md5($attrs['feedURL']);
        $rssAttrs = get_post_meta($postId, 'rrze_newsletter_rss_attrs', true) ?: [];
        $rssAttrs[$key] = $attrs;
        update_post_meta($postId, 'rrze_newsletter_rss_attrs', $rssAttrs);
        $innerHtml = 'RSS_BLOCK_' . $key;

        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Render newsletter ICS block as mj-text and store block meta.
     *
     * @param int $postId The post ID.
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param string $fontFamily The font family to use for the text.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the ICS newsletter block.
     */
    private static function renderNewsletterIcsBlock($postId, $block, $attrs, $fontFamily, $columnAttrs)
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = $attrs['style']['elements']['link']['color']['text'];
        }
        $textAttrs = array_merge([
            'padding'     => '0',
            'line-height' => '1.5',
            'font-size'   => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $columnAttrs['padding'] = '0';
        $key = md5($attrs['feedURL']);
        $icsAttrs = get_post_meta($postId, 'rrze_newsletter_ics_attrs', true) ?: [];
        $icsAttrs[$key] = $attrs;
        update_post_meta($postId, 'rrze_newsletter_ics_attrs', $icsAttrs);
        $innerHtml = 'ICS_BLOCK_' . $key;

        return '<mj-text ' . AttributeHandler::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';
    }

    /**
     * Render unknown or fallback block types.
     *
     * @param array $block The block data.
     * @param array $attrs The block attributes.
     * @param array $columnAttrs The column attributes.
     * @return string Rendered MJML markup for the block.
     */
    private static function renderDefaultBlock($block, $attrs, $columnAttrs)
    {
        return ''; // Return empty string by default.
    }
}
