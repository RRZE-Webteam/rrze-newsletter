<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Templates;
use function RRZE\Newsletter\plugin;

final class Render
{
    /**
     * The color palette to be used.
     *
     * @var object
     */
    public static $colorPalette = null;

    /**
     * The header font.
     *
     * @var string
     */
    protected static $fontHeader = null;

    /**
     * The body font.
     *
     * @var string
     */
    protected static $fontBody = null;

    /**
     * Supported fonts.
     *
     * @var array
     */
    public static $supportedFonts = [
        'Arial, Helvetica, sans-serif',
        'Calibri, sans-serif',
        'Tahoma, sans-serif',
        'Trebuchet MS, sans-serif',
        'Verdana, sans-serif',
        'Cambria, serif',
        'Georgia, serif',
        'Palatino, serif',
        'Times New Roman, serif',
        'Courier, monospace',
    ];

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
     * Convert an array of attributes to a HTML attributes as a string.
     *
     * @param array $attributes Array of attributes.
     * @return string HTML attributes as a string.
     */
    private static function arrayToAttributes(array $attributes): string
    {
        return implode(
            ' ',
            array_map(
                function ($key) use ($attributes) {
                    if (
                        isset($attributes[$key]) &&
                        (is_string($attributes[$key]) || is_numeric($attributes[$key]))
                    ) {
                        return $key . '="' . esc_attr($attributes[$key]) . '"';
                    } else {
                        return '';
                    }
                },
                array_keys($attributes)
            )
        );
    }

    /**
     * Extracts padding values from a given set of attributes 
     * and converts them into a string format suitable for use in MJML
     *
     * @return string padding values as a string
     */
    private static function getPaddingFromAttributes($attributes)
    {
        $paddingStr = '';
        $padding['top'] = $attributes['style']['spacing']['padding']['top'] ?? 0;
        $padding['right'] = $attributes['style']['spacing']['padding']['right'] ?? 0;
        $padding['bottom'] = $attributes['style']['spacing']['padding']['bottom'] ?? 0;
        $padding['left'] = $attributes['style']['spacing']['padding']['left'] ?? 0;

        $aryStr = [];
        foreach ($padding as $value) {
            if ($value !== 0) {
                $ary = explode('|', $value);
                $val = (absint(end($ary)) - 10) * 2;
                $aryStr[] = $val > 0 ? $val . 'px' : 0;
            } else {
                $aryStr[] = '0';
            }
        }
        if (!self::allValuesAreZero($padding)) {
            $paddingStr = implode(' ', $aryStr);
        }

        return $paddingStr;
    }

    /**
     * Check if all values in an array are zero.
     *
     * @param array $array Array to check.
     * @return bool Whether all values are zero.
     */
    private static function allValuesAreZero(array $array): bool
    {
        $nonZeroValues = array_filter($array, function ($value) {
            return $value !== 0;
        });
        return empty($nonZeroValues);
    }

    /**
     * Get font size based on block attributes.
     *
     * @param array $blockAttrs Block attributes.
     * @return string font size.
     */
    private static function getFontSize($blockAttrs)
    {
        if (isset($blockAttrs['customFontSize'])) {
            return $blockAttrs['customFontSize'] . 'px';
        }
        if (isset($blockAttrs['fontSize'])) {
            // Default font size presets
            // https://github.com/WordPress/gutenberg/blob/dd31d299c047cffed6ea862278ba0e05ccc8d6a8/packages/block-editor/src/store/defaults.js#L103
            $sizes = [
                'small'   => '14px',
                'normal'  => '16px',
                'medium'  => '22px',
                'large'   => '26px',
                'huge'    => '30px',
            ];
            return $sizes[$blockAttrs['fontSize']];
        }
    }

    /**
     * Get social icons colors.
     *
     * @return array Array of social icons colors.
     */
    private static function getSocialIconsColors()
    {
        return [
            'facebook'  => '#1977f2',
            'instagram' => '#f00075',
            'linkedin'  => '#0577b5',
            'tiktok'    => '#000000',
            'tumblr'    => '#011835',
            'twitter'   => '#21a1f3',
            'wordpress' => '#3499cd',
            'youtube'   => '#ff0100',
            'x'         => '#000000',
        ];
    }

    /**
     * Get social icon.
     *
     * @param string $serviceName Service name.
     * @param array $blockAttrs Block attributes.
     * @return array Array of social icon attributes.
     */
    private static function getSocialIcon($serviceName, $blockAttrs)
    {
        $servicesColors = self::getSocialIconsColors();
        if (!isset($servicesColors[$serviceName])) {
            return [];
        }
        $icon  = 'white';
        $color = $servicesColors[$serviceName];
        if (isset($blockAttrs['className'])) {
            if ('is-style-filled-black' === $blockAttrs['className'] || 'is-style-circle-white' === $blockAttrs['className']) {
                $icon = 'black';
            }
            if ('is-style-filled-black' === $blockAttrs['className'] || 'is-style-filled-white' === $blockAttrs['className'] || 'is-style-filled-primary-text' === $blockAttrs['className']) {
                $color = 'transparent';
            } elseif ('is-style-circle-black' === $blockAttrs['className']) {
                $color = '#000';
            } elseif ('is-style-circle-white' === $blockAttrs['className']) {
                $color = '#fff';
            }
        }
        return [
            'icon'  => sprintf('%s-%s.png', $icon, $serviceName),
            'color' => $color,
        ];
    }

    /**
     * Get colors based on block attributes.
     *
     * @param array $blockAttrs Block attributes.
     * @return array Array of color attributes for MJML component.
     */
    private static function getColors($blockAttrs)
    {
        $colors = [];

        // For text.
        if (isset($blockAttrs['textColor'])) {
            $colors['color'] = $blockAttrs['textColor'];
        }
        // customTextColor is set inline, but it's passed here for consistency.
        if (isset($blockAttrs['customTextColor'])) {
            $colors['color'] = $blockAttrs['customTextColor'];
        }
        if (isset($blockAttrs['backgroundColor'])) {
            $colors['background-color'] = $blockAttrs['backgroundColor'];
        }
        // customBackgroundColor is set inline, but not on mjml wrapper element.
        if (isset($blockAttrs['customBackgroundColor'])) {
            $colors['background-color'] = $blockAttrs['customBackgroundColor'];
        }

        // For separators.
        if (isset($blockAttrs['color'], self::$colorPalette[$blockAttrs['color']])) {
            $colors['border-color'] = $blockAttrs['color'];
        }
        if (isset($blockAttrs['customColor'])) {
            $colors['border-color'] = $blockAttrs['customColor'];
        }

        // Custom color handling.
        if (isset($blockAttrs['style'])) {
            if (isset($blockAttrs['style']['color']['background'])) {
                $colors['background-color'] = $blockAttrs['style']['color']['background'];
            }
            if (isset($blockAttrs['style']['color']['text'])) {
                $colors['color'] = $blockAttrs['style']['color']['text'];
            }
        }

        return $colors;
    }

    /**
     * Add color attributes and a padding,if component has a background color.
     *
     * @param array $attrs Block attributes.
     * @return array MJML component attributes.
     */
    private static function processAttributes($attrs)
    {
        $attrs = array_merge(
            $attrs,
            self::getColors($attrs)
        );
        $fontSize = self::getFontSize($attrs);
        if (isset($fontSize)) {
            $attrs['font-size'] = $fontSize;
        }

        // Remove block-only attributes.
        array_map(
            function ($key) use (&$attrs) {
                if (isset($attrs[$key])) {
                    unset($attrs[$key]);
                }
            },
            ['customBackgroundColor', 'customTextColor', 'customFontSize', 'fontSize', 'backgroundColor']
        );

        if (isset($attrs['background-color'])) {
            $attrs['padding'] = '0';
        }

        if (isset($attrs['align']) && 'full' == $attrs['align']) {
            $attrs['full-width'] = 'full-width';
            unset($attrs['align']);
        }

        if (isset($attrs['full-width']) && 'full-width' == $attrs['full-width'] && isset($attrs['background-color'])) {
            $attrs['padding'] = '12px 0';
        }

        return $attrs;
    }

    /**
     * Append UTM (Urchin Tracking Module) param to links.
     *
     * @param object $post Maybe a \WP_Post object.
     * @param string $html input HTML.
     * @return string HTML with processed links.
     */
    protected static function processLinks(object $post, string $html)
    {
        preg_match_all('/href="([^"]*)"/', $html, $matches);
        $hrefParams = $matches[0];
        $urls = $matches[1];
        foreach ($urls as $index => $url) {
            $skipUrlWithParams = false;
            if (strpos($url, '{{=UNSUB}}') !== false) {
                $url = '{{=UNSUB}}';
                $skipUrlWithParams = true;
            } elseif (strpos($url, '{{=UPDATE}}') !== false) {
                $url = '{{=UPDATE}}';
                $skipUrlWithParams = true;
            } elseif (strpos($url, '{{=ARCHIVE}}') !== false) {
                $url = '{{=ARCHIVE}}';
                $skipUrlWithParams = true;
            } elseif (filter_var($url, FILTER_VALIDATE_URL) === false) {
                $skipUrlWithParams = true;
            }
            if (!$skipUrlWithParams) {
                $url = add_query_arg(
                    [
                        'utm_campaign' => get_post_time('Y-m-d', false, $post),
                        'utm_source' => sanitize_title($post->post_title),
                        'utm_medium' => 'email',
                    ],
                    $url
                );
            }
            $html = str_replace($hrefParams[$index], 'href="' . $url . '"', $html);
        }
        return $html;
    }

    /**
     * Convert a Gutenberg block to an MJML component.
     * MJML component will be put in an mj-column in an mj-section for consistent layout,
     * unless it's a group or a columns block.
     *
     * @param integer $postId Maybe a \WP_Post ID.
     * @param array $block The block.
     * @param array $defaultAttrs Default attributes for the component. 
     * @param bool $isInColumn Whether the component is a child of a column component.
     * @param bool $isInGroup Whether the component is a child of a group component.
     * @param bool $isInList Whether the component is a child of a list.
     * @return string MJML component.
     */
    public static function renderMjmlComponent($postId, $block, $defaultAttrs = [], $isInColumn = false, $isInGroup = false, $isInList = false)
    {
        $blockName = $block['blockName'];
        $attrs = $block['attrs'];
        $innerBlocks = $block['innerBlocks'];
        $innerHtml = $block['innerHTML'];
        $innerContent = isset($block['innerContent']) ? $block['innerContent'] : [$innerHtml];

        if (!isset($attrs['innerBlocksToInsert']) && self::isEmptyBlock($block)) {
            return '';
        }

        $defaultAttrs['postId'] = $postId;

        $blockMjmlMarkup = '';
        $attrs = self::processAttributes(array_merge($defaultAttrs, $attrs));
        $padding = self::getPaddingFromAttributes($attrs);

        // Default attributes for the section which will envelop the mj-column.
        $sectionAttrs = array_merge(
            $attrs,
            [
                'padding' => '0',
            ]
        );

        // Default attributes for the column which will envelop the component.
        $columnAttrs = [
            'padding' => $padding ?: '0'
        ];

        $fontFamily = 'core/heading' === $blockName ? self::$fontHeader : self::$fontBody;

        switch ($blockName) {
                // Paragraph, List, Heading blocks.
            case 'core/paragraph':
            case 'core/heading':
            case 'rrze-newsletter/rss':
            case 'rrze-newsletter/ics':
                $textAttrs = array_merge(
                    [
                        'padding'     => '0',
                        'line-height' => '18px',
                        'font-size'   => '16px',
                        'font-family' => $fontFamily,
                    ],
                    $attrs
                );

                // Only mj-text has to use container-background-color attr for background color.
                if (isset($textAttrs['background-color'])) {
                    $textAttrs['container-background-color'] = $textAttrs['background-color'];
                    unset($textAttrs['background-color']);
                }

                // core/heading attributes normalization
                if (isset($textAttrs['textAlign'])) {
                    $textAttrs['align'] = $textAttrs['textAlign'];
                    unset($textAttrs['textAlign']);
                }

                // Render rrze-newsletter/rss block.
                if ($blockName == 'rrze-newsletter/rss') {
                    $key = md5($attrs['feedURL']);
                    if (!($rssAttrs = get_post_meta($postId, 'rrze_newsletter_rss_attrs', true))) {
                        $rssAttrs = [];
                    }
                    $rssAttrs[$key] = $attrs;
                    update_post_meta($postId, 'rrze_newsletter_rss_attrs', $rssAttrs);
                    $innerHtml = 'RSS_BLOCK_' . $key;
                }

                // Render rrze-newsletter/ics block.
                if ($blockName == 'rrze-newsletter/ics') {
                    $key = md5($attrs['feedURL']);
                    if (!($icsAttrs = get_post_meta($postId, 'rrze_newsletter_ics_attrs', true))) {
                        $icsAttrs = [];
                    }
                    $icsAttrs[$key] = $attrs;
                    update_post_meta($postId, 'rrze_newsletter_ics_attrs', $icsAttrs);
                    $innerHtml = 'ICS_BLOCK_' . $key;
                }

                $blockMjmlMarkup = '<mj-text ' . self::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';

                break;

                // List and list-item blocks.
                // These blocks may or may not contain innerBlocks with their actual content.
            case 'core/list':
            case 'core/list-item':
                $textAttrs = array_merge(
                    [
                        'padding'     => '0',
                        'line-height' => '1.4',
                        'font-size'   => '16px',
                        'font-family' => $fontFamily,
                    ],
                    $attrs
                );

                // If a wrapper block, wrap in mj-text.
                if (!$isInList) {
                    $blockMjmlMarkup .= '<mj-text ' . self::arrayToAttributes($textAttrs) . '>';
                }

                $blockMjmlMarkup .= $innerContent[0];
                if (!empty($innerBlocks) && 1 < count($innerContent)) {
                    foreach ($innerBlocks as $innerBlock) {
                        $blockMjmlMarkup .= self::renderMjmlComponent($postId, $innerBlock, [], false, false, true);
                    }
                    $blockMjmlMarkup .= $innerContent[count($innerContent) - 1];
                }

                if (!$isInList) {
                    $blockMjmlMarkup .= '</mj-text>';
                }

                break;

                // Image block.
            case 'core/image':
                $columnAttrs['width'] = '100%';

                // Parse block content.
                $dom = new \DomDocument();
                @$dom->loadHtml(mb_convert_encoding($innerHtml, 'HTML-ENTITIES', "UTF-8"));
                $xpath = new \DOMXpath($dom);
                $img = $xpath->query('//img')[0];
                $imgSrc = $img ? $img->getAttribute('src') : '';
                $figcaption = $xpath->query('//figcaption/text()')[0];

                // Check if $imgSrc is a relative URL
                if ($imgSrc && strpos($imgSrc, 'http') !== 0) {
                    $imgSrc = home_url($imgSrc);
                }

                $imgAttrs = [
                    'padding' => '0',
                    'align'   => isset($attrs['align']) ? $attrs['align'] : 'left',
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

                $markup = '<mj-image ' . self::arrayToAttributes($imgAttrs) . ' />';

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

                    $markup .= '<mj-text ' . self::arrayToAttributes($captionAttrs) . '>' . $figcaption->wholeText . '</mj-text>';
                }

                $blockMjmlMarkup = $markup;
                break;

                // Separator block.
            case 'core/separator':
                $isWide = isset($attrs['className']) && 'is-style-wide' === $attrs['className'];
                $dividerAttrs = [
                    'padding' => '0',
                    'border-width' => '1px',
                    'width' => $isWide ? '100%' : '128px'
                ];
                // Remove colors from section attrs.
                unset($sectionAttrs['background-color']);
                if (isset($attrs['backgroundColor']) && isset(self::$colorPalette[$attrs['backgroundColor']])) {
                    $dividerAttrs['border-color'] = self::$colorPalette[$attrs['backgroundColor']];
                }
                if (isset($attrs['style']['color']['background'])) {
                    $dividerAttrs['border-color'] = $attrs['style']['color']['background'];
                }
                $blockMjmlMarkup .= '<mj-divider ' . self::arrayToAttributes($dividerAttrs) . '/>';
                break;

                // Spacer block.
            case 'core/spacer':
                $ary = explode('|', $attrs['height'] ?? '0');
                $attrs['height'] = absint(end($ary)) . 'px';
                $blockMjmlMarkup .= '<mj-spacer ' . self::arrayToAttributes($attrs) . '/>';
                break;

                // Social links block.
            case 'core/social-links':
                $wrapperAttrs = [
                    'icon-size'     => '24px',
                    'mode'          => 'horizontal',
                    'padding'       => '0',
                    'border-radius' => '999px',
                    'icon-padding'  => '7px',
                ];
                if (isset($attrs['align'])) {
                    $wrapperAttrs['align'] = $attrs['align'];
                } else {
                    $wrapperAttrs['align'] = 'left';
                }
                $markup = '<mj-social ' . self::arrayToAttributes($wrapperAttrs) . '>';
                foreach ($innerBlocks as $LinkBlock) {
                    if (isset($LinkBlock['attrs']['url'])) {
                        $url = $LinkBlock['attrs']['url'];
                        $serviceName = $LinkBlock['attrs']['service'];
                        $socialIcon = self::getSocialIcon($serviceName, $attrs);

                        if (!empty($socialIcon)) {
                            $imgAttrs = array(
                                'href' => $url,
                                'src' => plugins_url('assets/social-links/' . $socialIcon['icon'], plugin()->getBasename()),
                                'background-color' => $socialIcon['color'],
                                'css-class' => 'social-element',
                                'padding' => '2px',
                            );

                            $markup .= '<mj-social-element ' . self::arrayToAttributes($imgAttrs) . '/>';
                        }
                    }
                }
                $blockMjmlMarkup .= $markup . '</mj-social>';
                break;

                // Single Column block.
            case 'core/column':
                if (isset($attrs['verticalAlignment'])) {
                    if ('center' === $attrs['verticalAlignment']) {
                        $columnAttrs['vertical-align'] = 'middle';
                    } else {
                        $columnAttrs['vertical-align'] = $attrs['verticalAlignment'];
                    }
                }

                if (isset($attrs['width'])) {
                    $columnAttrs['width'] = $attrs['width'];
                    $columnAttrs['css-class'] = 'mj-column-has-width';
                }

                $markup = '<mj-column ' . self::arrayToAttributes($columnAttrs) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, $defaultAttrs, true, false);
                }
                $blockMjmlMarkup = $markup . '</mj-column>';
                break;

                // Columns block.
            case 'core/columns':
                // Some columns might have no width set.
                $widthsSum = 0;
                $noWidthColsIndexes = [];
                foreach ($innerBlocks as $i => $block) {
                    if (isset($block['attrs']['width'])) {
                        $widthsSum += floatval($block['attrs']['width']);
                    } else {
                        array_push($noWidthColsIndexes, $i);
                    }
                };
                foreach ($noWidthColsIndexes as $noWidthColsIndex) {
                    $innerBlocks[$noWidthColsIndex]['attrs']['width'] = (100 - $widthsSum) / count($noWidthColsIndexes) . '%';
                };

                if (isset($attrs['color'])) {
                    $defaultAttrs['color'] = $attrs['color'];
                }
                $isStackedOnMobile = !isset($attrs['isStackedOnMobile']) || true === $attrs['isStackedOnMobile'];
                if (!$isStackedOnMobile) {
                    $markup = '<mj-group>';
                } else {
                    $markup = '';
                }
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, $defaultAttrs, true, false);
                }
                if (!$isStackedOnMobile) {
                    $markup .= '</mj-group>';
                }
                $blockMjmlMarkup = $markup;
                break;

                // Group block.
            case 'core/group':
                // There's no color attribute on mj-wrapper, so it has to be passed to children.
                // https://github.com/mjmlio/mjml/issues/1881
                if (isset($attrs['color'])) {
                    $defaultAttrs['color'] = $attrs['color'];
                }
                $markup = '<mj-wrapper ' . self::arrayToAttributes($attrs) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, $defaultAttrs, false, true);
                }
                $blockMjmlMarkup = $markup . '</mj-wrapper>';
                break;
        }

        $isPostInserterBlock = 'rrze-newsletter/post-inserter' == $blockName;
        $isGroupBlock = in_array($blockName, ['core/group'], true);

        if (
            !$isInColumn &&
            !$isInList &&
            !$isGroupBlock &&
            'core/columns' != $blockName &&
            'core/column' != $blockName &&
            'core/separator' != $blockName &&
            !$isPostInserterBlock
        ) {
            $columnAttrs['width'] = '100%';
            $blockMjmlMarkup = '<mj-column ' . self::arrayToAttributes($columnAttrs) . '>' . $blockMjmlMarkup . '</mj-column>';
        }

        if (
            $isInColumn ||
            $isInList ||
            $isGroupBlock ||
            $isPostInserterBlock
        ) {
            // Render a nested block without a wrapping section.
            return $blockMjmlMarkup;
        } else {
            return '<mj-section ' . self::arrayToAttributes($sectionAttrs) . '>' . $blockMjmlMarkup . '</mj-section>';
        }
    }

    /**
     * Get total length of newsletter's content.
     *
     * @param array $blocks Array of post blocks.
     * @return string Total length of the newsletter content.
     */
    private static function getTotalCharacterLength($blocks)
    {
        return array_reduce(
            $blocks,
            function ($length, $block) {
                $innerBlocks = $block['innerBlocks'] ?? [];
                if (count($innerBlocks)) {
                    $length += self::getTotalCharacterLength($innerBlocks);
                } else {
                    $length += strlen(wp_strip_all_tags($block['innerHTML']));
                }
                return $length;
            },
            0
        );
    }

    /** Convert a WP post to an array of non-empty blocks.
     *
     * @param WP_Post $post The post.
     * @return array Blocks.
     */
    private static function getValidPostBlocks($post)
    {
        return array_filter(
            parse_blocks($post->post_content),
            function ($block) {
                return null !== $block['blockName'];
            }
        );
    }

    /**
     * Convert a string or an \WP_Post object content to MJML components.
     *
     * @param object $post Maybe a \WP_Post object.
     * @param string $content The content.
     * @param boolean $processLink Are the links processed?
     * @return string MJML markup to be injected into the template.
     */
    private static function postToMjmlComponents(object $post, string $content, bool $processLinks)
    {
        $postId = is_a($post, '\WP_Post') ? $post->ID : 0;

        $body = '';
        $validBlocks = array_filter(
            parse_blocks($content),
            function ($block) {
                return null !== $block['blockName'];
            }
        );

        // Build MJML body.
        foreach ($validBlocks as $block) {
            $blockContent = '';

            // Convert reusable block to group block.
            // Reusable blocks are CPTs, where the block's ref attribute is the post ID.
            if ('core/block' === $block['blockName'] && isset($block['attrs']['ref'])) {
                $reusableBlockPost = get_post($block['attrs']['ref']);
                if (!empty($reusableBlockPost)) {
                    $block['blockName'] = 'core/group';
                    $block['innerBlocks'] = self::getValidPostBlocks($reusableBlockPost);
                    $block['innerHTML'] = $reusableBlockPost->post_content;
                    $block['innerContent'] = $reusableBlockPost->post_content;
                }
            }

            if ('core/group' === $block['blockName']) {
                $defaultAttrs = [];
                $attrs = self::processAttributes($block['attrs']);
                // There's no color attribute on mj-wrapper, so it has to be passed to children.
                // https://github.com/mjmlio/mjml/issues/1881                
                if (isset($attrs['color'])) {
                    $defaultAttrs['color'] = $attrs['color'];
                }
                $padding = self::getPaddingFromAttributes($attrs);
                $attrs['padding'] = $padding ?: '0';
                $mjmlMarkup = '<mj-wrapper ' . self::arrayToAttributes($attrs) . '>';
                foreach ($block['innerBlocks'] as $block) {
                    $innerBlockContent = self::renderMjmlComponent($postId, $block, $defaultAttrs, false, true);
                    $mjmlMarkup .= $innerBlockContent;
                }
                $blockContent = $mjmlMarkup . '</mj-wrapper>';
            } else {
                $blockContent = self::renderMjmlComponent($postId, $block);
            }

            $body .= $blockContent;
        }

        return $processLinks ? self::processLinks($post, $body) : $body;
    }

    /**
     * Convert an \WP_Post object to MJML markup.
     *
     * @param \WP_Post $post The post.
     * @return string MJML markup.
     */
    public static function fromPost($post)
    {
        self::$colorPalette = json_decode(get_option('rrze_newsletter_color_palette', false), true);
        self::$fontHeader = get_post_meta($post->ID, 'rrze_newsletter_font_header', true);
        self::$fontBody = get_post_meta($post->ID, 'rrze_newsletter_font_body', true);
        if (!in_array(self::$fontHeader, self::$supportedFonts)) {
            self::$fontHeader = 'Arial';
        }
        if (!in_array(self::$fontBody, self::$supportedFonts)) {
            self::$fontBody = 'Arial';
        }

        $previewText = get_post_meta($post->ID, 'rrze_newsletter_preview_text', true);
        $backgroundColor = get_post_meta($post->ID, 'rrze_newsletter_background_color', true);

        $data = [
            'title' => $post->post_title,
            'preview_text' => $previewText ? $previewText : '',
            'background_color' => $backgroundColor ? $backgroundColor : '#f0f0f0',
            'body' => self::postToMjmlComponents($post, $post->post_content, true)
        ];

        $tpl = preg_replace('/\s+/', ' ', Templates::getContent('newsletter.mjml', $data));

        return str_replace(PHP_EOL, '', $tpl);
    }

    /**
     * Convert an Array of arguments to MJML markup.
     *
     * @param array $args The arguments.
     * @return string MJML markup.
     */
    public static function fromAry(array $args)
    {
        $default = [
            'title' => '',
            'preview_text' => '',
            'background_color' => '#ffffff',
            'content' => ''
        ];
        $args = wp_parse_args($args, $default);
        $args = array_intersect_key($args, $default);

        extract($args);

        self::$fontHeader = 'Arial';
        self::$fontBody = 'Arial';

        $data = [
            'title' => $title,
            'preview_text' => $preview_text,
            'background_color' => $background_color,
            'body' => self::postToMjmlComponents(new \stdClass, $content, false)
        ];

        $tpl = preg_replace('/\s+/', ' ', Templates::getContent('newsletter.mjml', $data));

        return str_replace(PHP_EOL, '', $tpl);
    }

    /**
     * Retrieve email-compliant HTML for a newsletter CPT.
     *
     * @param \WP_Post $post The post.
     * @return object|string \WP_Error or email-compliant HTML.
     */
    public static function retrieveEmailHtml($post)
    {
        $emailHtml = get_post_meta($post->ID, 'rrze_newsletter_email_html', true);
        if (empty($emailHtml)) {
            return new \WP_Error(
                'rrze_newsletter_mjml_render_error',
                __('MJML rendering error.', 'rrze-newsletter')
            );
        }
        return $emailHtml;
    }
}
