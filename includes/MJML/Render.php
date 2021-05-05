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
        'Tahoma, sans-serif',
        'Trebuchet MS, sans-serif',
        'Verdana, sans-serif',
        'Georgia, serif',
        'Palatino, serif',
        'Times New Roman, serif',
        'Courier, monospace',
    ];

    /**
     * Convert a list to HTML attributes.
     *
     * @param array $attributes Array of attributes.
     * @return string HTML attributes as a string.
     */
    private static function arrayToAttributes($attributes)
    {
        return implode(
            ' ',
            array_map(
                function ($key) use ($attributes) {
                    if (isset($attributes[$key])) {
                        return $key . '="' . $attributes[$key] . '"';
                    } else {
                        return '';
                    }
                },
                array_keys($attributes)
            )
        );
    }

    /**
     * Get font size based on block attributes.
     *
     * @param array $blockAtts Block attributes.
     * @return string font size.
     */
    private static function getFontSize($blockAtts)
    {
        if (isset($blockAtts['customFontSize'])) {
            return $blockAtts['customFontSize'] . 'px';
        }
        if (isset($blockAtts['fontSize'])) {
            // Gutenberg's default font size presets.
            // https://github.com/WordPress/gutenberg/blob/359858da0675943d8a759a0a7c03e7b3846536f5/packages/block-editor/src/store/defaults.js#L87-L113 .
            $sizes = [
                'small'  => '13px',
                'normal' => '16px',
                'medium' => '20px',
                'large'  => '36px',
                'huge'   => '48px',
            ];
            return $sizes[$blockAtts['fontSize']];
        }
    }

    /**
     * Get colors based on block attributes.
     *
     * @param array $blockAtts Block attributes.
     * @return array Array of color attributes for MJML component.
     */
    private static function getColors($blockAtts)
    {
        $colors = [];

        // For text.
        if (isset($blockAtts['textColor'], self::$colorPalette[$blockAtts['textColor']])) {
            $colors['color'] = self::$colorPalette[$blockAtts['textColor']];
        }
        // customTextColor is set inline, but it's passed here for consistency.
        if (isset($blockAtts['customTextColor'])) {
            $colors['color'] = $blockAtts['customTextColor'];
        }
        if (isset($blockAtts['backgroundColor'], self::$colorPalette[$blockAtts['backgroundColor']])) {
            $colors['background-color'] = self::$colorPalette[$blockAtts['backgroundColor']];
        }
        // customBackgroundColor is set inline, but not on mjml wrapper element.
        if (isset($blockAtts['customBackgroundColor'])) {
            $colors['background-color'] = $blockAtts['customBackgroundColor'];
        }

        // For separators.
        if (isset($blockAtts['color'], self::$colorPalette[$blockAtts['color']])) {
            $colors['border-color'] = self::$colorPalette[$blockAtts['color']];
        }
        if (isset($blockAtts['customColor'])) {
            $colors['border-color'] = $blockAtts['customColor'];
        }

        // Custom color handling.
        if (isset($blockAtts['style'])) {
            if (isset($blockAtts['style']['color']['background'])) {
                $colors['background-color'] = $blockAtts['style']['color']['background'];
            }
            if (isset($blockAtts['style']['color']['text'])) {
                $colors['color'] = $blockAtts['style']['color']['text'];
            }
        }

        return $colors;
    }

    /**
     * Add color attributes and a padding, if component has a background color.
     *
     * @param array $atts Block attributes.
     * @return array MJML component attributes.
     */
    private static function processAttributes($atts)
    {
        $atts = array_merge(
            $atts,
            self::getColors($atts)
        );
        $fontSize = self::getFontSize($atts);
        if (isset($fontSize)) {
            $atts['font-size'] = $fontSize;
        }

        // Remove block-only attributes.
        array_map(
            function ($key) use (&$atts) {
                if (isset($atts[$key])) {
                    unset($atts[$key]);
                }
            },
            ['customBackgroundColor', 'customTextColor', 'customFontSize', 'fontSize', 'backgroundColor', 'style']
        );

        if (isset($atts['background-color'])) {
            $atts['padding'] = '0';
        }

        if (isset($atts['align']) && 'full' == $atts['align']) {
            $atts['full-width'] = 'full-width';
            unset($atts['align']);
        }

        if (isset($atts['full-width']) && 'full-width' == $atts['full-width'] && isset($atts['background-color'])) {
            $atts['padding'] = '12px 0';
        }

        return $atts;
    }

    /**
     * Append UTM (Urchin Tracking Module) param to links.
     *
     * @param string $html input HTML.
     * @return string HTML with processed links.
     */
    public static function processLinks($html)
    {
        preg_match_all('/href="([^"]*)"/', $html, $matches);
        $hrefParams = $matches[0];
        $urls = $matches[1];
        foreach ($urls as $index => $url) {
            $skipUrlWithParams = false;
            if (strpos($url, '{{=UNSUB}}') !== false) {
                $url = '{{=UNSUB}}';
                $skipUrlWithParams = true;
            } elseif (strpos($url, '{{=PERMALINK}}') !== false) {
                $url = '{{=PERMALINK}}';
                $skipUrlWithParams = true;
            }
            if (!$skipUrlWithParams) {
                $url = add_query_arg(
                    [
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
     * @param array $block The block.
     * @param bool $isInColumn Whether the component is a child of a column component.
     * @param bool $isInGroup Whether the component is a child of a group component.
     * @param array $defaultAtts Default attributes for the component.
     * @return string MJML component.
     */
    public static function renderMjmlComponent($block, $isInColumn = false, $isInGroup = false, $defaultAtts = [])
    {
        $blockName = $block['blockName'];
        $atts = $block['attrs'];
        $innerBlocks = $block['innerBlocks'];
        $innerHtml = $block['innerHTML'];

        if (!isset($atts['innerBlocksToInsert']) && (empty($blockName) || empty($innerHtml))) {
            return '';
        }

        $blockMjmlMarkup = '';
        $atts = self::processAttributes(array_merge($defaultAtts, $atts));

        // Default attributes for the section which will envelop the mj-column.
        $sectionAtts = array_merge(
            $atts,
            [
                'padding' => '0',
            ]
        );

        // Default attributes for the column which will envelop the component.
        $columnAtts = array_merge(
            [
                'padding' => '12px',
            ]
        );

        $fontFamily = 'core/heading' === $blockName ? self::$fontHeader : self::$fontBody;

        switch ($blockName) {
                // Paragraph, List, Heading blocks.
            case 'core/paragraph':
            case 'core/list':
            case 'core/heading':
            case 'core/quote':
                $textAtts = array_merge(
                    [
                        'padding' => '0',
                        'line-height' => '1.8',
                        'font-size' => '16px',
                        'font-family' => $fontFamily,
                    ],
                    $atts
                );

                // Only mj-text has to use container-background-color attr for background color.
                if (isset($textAtts['background-color'])) {
                    $textAtts['container-background-color'] = $textAtts['background-color'];
                    unset($textAtts['background-color']);
                }

                // core/heading attributes normalization
                if (isset($textAtts['textAlign'])) {
                    $textAtts['align'] = $textAtts['textAlign'];
                    unset($textAtts['textAlign']);
                }

                $blockMjmlMarkup = '<mj-text ' . self::arrayToAttributes($textAtts) . '>' . $innerHtml . '</mj-text>';
                break;

                // Image block.
            case 'core/image':
                // Parse block content.
                $dom = new \DomDocument();
                @$dom->loadHTML($innerHtml);
                $xpath = new \DOMXpath($dom);
                $img = $xpath->query('//img')[0];
                $imgSrc = $img->getAttribute('src');
                $figcaption = $xpath->query('//figcaption/text()')[0];

                $imgAtts = [
                    'padding' => '0',
                    'align' => isset($atts['align']) ? $atts['align'] : 'left',
                    'src' => $imgSrc,
                ];

                if (isset($atts['sizeSlug'])) {
                    if ('medium' == $atts['sizeSlug']) {
                        $imgAtts['width'] = '300px';
                    }
                    if ('thumbnail' == $atts['sizeSlug']) {
                        $imgAtts['width'] = '150px';
                    }
                } elseif (isset($atts['className'])) {
                    if ('size-medium' == $atts['className']) {
                        $imgAtts['width'] = '300px';
                    }
                    if ('size-thumbnail' == $atts['className']) {
                        $imgAtts['width'] = '150px';
                    }
                }
                if (isset($atts['width'])) {
                    $imgAtts['width'] = $atts['width'] . 'px';
                }
                if (isset($atts['height'])) {
                    $imgAtts['height'] = $atts['height'] . 'px';
                }
                if (isset($atts['linkDestination'])) {
                    $imgAtts['href'] = $atts['linkDestination'];
                } else {
                    $maybeLink = $img->parentNode;
                    if ($maybeLink && 'a' === $maybeLink->nodeName && $maybeLink->getAttribute('href')) {
                        $imgAtts['href'] = trim($maybeLink->getAttribute('href'));
                    }
                }
                if (isset($atts['className']) && strpos($atts['className'], 'is-style-rounded') !== false) {
                    $imgAtts['border-radius'] = '999px';
                }
                $markup = '<mj-image ' . self::arrayToAttributes($imgAtts) . ' />';

                if ($figcaption) {
                    $captionAtts = [
                        'align' => 'center',
                        'color' => '#555d66',
                        'font-size' => '13px',
                        'font-family' => $fontFamily,
                    ];
                    $markup .= '<mj-text ' . self::arrayToAttributes($captionAtts) . '>' . $figcaption->wholeText . '</mj-text>';
                }

                $blockMjmlMarkup = $markup;
                break;

                // Buttons block.
            case 'core/buttons':
                foreach ($innerBlocks as $buttonBlock) {
                    // Parse block content.
                    $dom = new \DomDocument();
                    @$dom->loadHTML($buttonBlock['innerHTML']);
                    $xpath = new \DOMXpath($dom);
                    $anchor = $xpath->query('//a')[0];
                    $atts = $buttonBlock['attrs'];
                    $text = $anchor->textContent;
                    $borderRadius = isset($atts['borderRadius']) ? $atts['borderRadius'] : 5;
                    $isOutlined = isset($atts['className']) && 'is-style-outline' == $atts['className'];

                    $defaultButtonAtts = [
                        'padding' => '0',
                        'inner-padding' => '12px 24px',
                        'line-height' => '1.8',
                        'href' => $anchor->getAttribute('href'),
                        'border-radius' => $borderRadius . 'px',
                        'font-size' => '18px',
                        'font-family' => $fontFamily,
                        // Default color - will be replaced by getColors if there are colors set.
                        'color' => $isOutlined ? '#32373c' : '#fff !important',
                    ];
                    if ($isOutlined) {
                        $defaultButtonAtts['background-color'] = 'transparent';
                    } else {
                        $defaultButtonAtts['background-color'] = '#32373c';
                    }
                    $buttonAtts = array_merge(
                        $defaultButtonAtts,
                        self::getColors($atts)
                    );

                    if ($isOutlined) {
                        $buttonAtts['css-class'] = $atts['className'];
                    }

                    $blockMjmlMarkup .= '<mj-column ' . self::arrayToAttributes($columnAtts) . '><mj-button ' . self::arrayToAttributes($buttonAtts) . ">$text</mj-button></mj-column>";
                }
                break;

                // Separator block.
            case 'core/separator':
                $isStyleDefault = isset($atts['className']) ? 'is-style-default' == $atts['className'] : true;
                $dividerAtts = array_merge(
                    [
                        'padding' => '0',
                        'border-width' => $isStyleDefault ? '2px' : '1px',
                        'width' => $isStyleDefault ? '100px' : '100%',
                        // Default color - will be replaced by getColors if there are colors set.
                        'border-color' => '#8f98a1',
                    ],
                    self::getColors($atts)
                );
                $blockMjmlMarkup .= '<mj-divider ' . self::arrayToAttributes($dividerAtts) . '/>';
                break;

                // Spacer block.
            case 'core/spacer':
                $atts['height'] = $atts['height'] . 'px';
                $blockMjmlMarkup .= '<mj-spacer ' . self::arrayToAttributes($atts) . '/>';
                break;

                // Social links block.
            case 'core/social-links':
                $socialIcons = [
                    'wordpress' => [
                        'color' => '#3499cd',
                        'icon'  => 'wordpress.png',
                    ],
                    'facebook'  => [
                        'color' => '#1977f2',
                        'icon'  => 'facebook.png',
                    ],
                    'twitter'   => [
                        'color' => '#21a1f3',
                        'icon'  => 'twitter.png',
                    ],
                    'instagram' => [
                        'color' => '#f00075',
                        'icon'  => 'instagram.png',
                    ],
                    'linkedin'  => [
                        'color' => '#0577b5',
                        'icon'  => 'linkedin.png',
                    ],
                    'youtube'   => [
                        'color' => '#ff0100',
                        'icon'  => 'youtube.png',
                    ],
                ];

                $socialWrapperAtts = [
                    'icon-size'     => '22px',
                    'mode'          => 'horizontal',
                    'padding'       => '0',
                    'border-radius' => '999px',
                    'icon-padding'  => '8px',
                ];
                if (isset($atts['align'])) {
                    $socialWrapperAtts['align'] = $atts['align'];
                } else {
                    $socialWrapperAtts['align'] = 'left';
                }
                $markup = '<mj-social ' . self::arrayToAttributes($socialWrapperAtts) . '>';
                foreach ($innerBlocks as $linkBlock) {
                    if (isset($linkBlock['attrs']['url'])) {
                        $url = $linkBlock['attrs']['url'];
                        // Handle older version of the block, where innner blocks we named `core/social-link-<service>`.
                        $serviceName = isset($linkBlock['attrs']['service']) ? $linkBlock['attrs']['service'] : str_replace('core/social-link-', '', $linkBlock['blockName']);

                        if (isset($socialIcons[$serviceName])) {
                            $imgAtts = [
                                'href' => $url,
                                'src' => plugins_url('assets/icons/' . $socialIcons[$serviceName]['icon'], plugin()->getBasename()),
                                'background-color' => $socialIcons[$serviceName]['color'],
                                'css-class' => 'social-element',
                            ];

                            $markup .= '<mj-social-element ' . self::arrayToAttributes($imgAtts) . '/>';
                        }
                    }
                }
                $blockMjmlMarkup .= $markup . '</mj-social>';
                break;

                // Single Column block.
            case 'core/column':
                if (isset($atts['verticalAlignment'])) {
                    if ('center' === $atts['verticalAlignment']) {
                        $columnAtts['vertical-align'] = 'middle';
                    } else {
                        $columnAtts['vertical-align'] = $atts['verticalAlignment'];
                    }
                }

                if (isset($atts['width'])) {
                    preg_match_all('/^(\d+)(\w+|%)$/', $atts['width'], $matches);
                    $defaultUnit = empty($matches[2][0]) ? '%' : '';
                    $columnAtts['width'] = $atts['width'] . $defaultUnit;
                    $columnAtts['css-class'] = 'mj-column-has-width';
                }

                $markup = '<mj-column ' . self::arrayToAttributes($columnAtts) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($block, true, false, $defaultAtts);
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
                    $innerBlocks[$noWidthColsIndex]['attrs']['width'] = (100 - $widthsSum) / count($noWidthColsIndexes);
                };

                if (isset($atts['color'])) {
                    $defaultAtts['color'] = $atts['color'];
                }
                $markup = '';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($block, true, false, $defaultAtts);
                }
                $blockMjmlMarkup = $markup;
                break;

                // Group block.
            case 'core/group':
                // There's no color attribute on mj-wrapper, so it has to be passed to children.
                // https://github.com/mjmlio/mjml/issues/1881 .
                if (isset($atts['color'])) {
                    $defaultAtts['color'] = $atts['color'];
                }
                $markup = '<mj-wrapper ' . self::arrayToAttributes($atts) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($block, false, true, $defaultAtts);
                }
                $blockMjmlMarkup = $markup . '</mj-wrapper>';
                break;
        }

        $isGroupBlock = 'core/group' == $blockName;

        if (
            !$isInColumn &&
            !$isGroupBlock &&
            'core/columns' != $blockName &&
            'core/column' != $blockName &&
            'core/buttons' != $blockName
        ) {
            $columnAtts['width'] = '100%';
            $blockMjmlMarkup     = '<mj-column ' . self::arrayToAttributes($columnAtts) . '>' . $blockMjmlMarkup . '</mj-column>';
        }
        if ($isInColumn || $isGroupBlock) {
            // Render a nested block without a wrapping section.
            return $blockMjmlMarkup;
        } else {
            return '<mj-section ' . self::arrayToAttributes($sectionAtts) . '>' . $blockMjmlMarkup . '</mj-section>';
        }
    }

    /**
     * Get total length of newsletter's content.
     *
     * @param array $blocks Array of post blocks.
     * @return number Total length of the newsletter content.
     */
    private static function getTotalCharacterLength($blocks)
    {
        return array_reduce(
            $blocks,
            function ($length, $block) {
                if (isset($block['innerBlocks']) && count($block['innerBlocks'])) {
                    $length += self::getTotalCharacterLength($block['innerBlocks']);
                } else {
                    $length += strlen(wp_strip_all_tags($block['innerHTML']));
                }
                return $length;
            },
            0
        );
    }

    /**
     * Convert a string or an \WP_Post object content to MJML components.
     *
     * @param string $content The content.
     * @param boolean $processLink Are the links processed?
     * @return string MJML markup to be injected into the template.
     */
    private static function postToMjmlComponents(string $content, bool $processLinks)
    {
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

            if ('core/group' === $block['blockName']) {
                $defaultAtts = [];
                $atts = self::processAttributes($block['attrs']);
                if (isset($atts['color'])) {
                    $defaultAtts['color'] = $atts['color'];
                }
                $mjmlMarkup = '<mj-wrapper ' . self::arrayToAttributes($atts) . '>';
                foreach ($block['innerBlocks'] as $block) {
                    $innerBlockContent = self::renderMjmlComponent($block, false, true, $defaultAtts);
                    $mjmlMarkup .= $innerBlockContent;
                }
                $blockContent = $mjmlMarkup . '</mj-wrapper>';
            } else {
                $blockContent = self::renderMjmlComponent($block);
            }

            $body .= $blockContent;
        }

        return $processLinks ? self::processLinks($body) : $body;
    }

    /**
     * Convert an \WP_Post object to MJML markup.
     *
     * @param \WP_Post $post The post.
     * @return string MJML markup.
     */
    private static function fromPost($post)
    {
        self::$colorPalette = json_decode(get_option('rrze_newsletter_color_palette', false), true);
        self::$fontHeader = get_post_meta($post->ID, 'rrze_newsletter_font_header', true);
        self::$fontBody = get_post_meta($post->ID, 'rrze_newsletter_font_body', true);
        if (!in_array(self::$fontHeader, self::$supportedFonts)) {
            self::$fontHeader = 'Arial';
        }
        if (!in_array(self::$fontBody, self::$supportedFonts)) {
            self::$fontBody = 'Georgia';
        }

        $previewText = get_post_meta($post->ID, 'rrze_newsletter_preview_text', true);
        $backgroundColor = get_post_meta($post->ID, 'rrze_newsletter_background_color', true);

        $data = [
            'title' => $post->post_title,
            'preview_text' => $previewText ? $previewText : '',
            'background_color' => $backgroundColor ? $backgroundColor : '#ffffff',
            'body' => self::postToMjmlComponents($post->post_content, true)
        ];

        return str_replace(PHP_EOL, '', Templates::getContent('newsletter.mjml', $data));
    }

    /**
     * Convert an Array of arguments to MJML markup.
     *
     * @param array $args The arguments.
     * @return string MJML markup.
     */
    private static function fromAry(array $args)
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
        self::$fontBody = 'Georgia';

        $data = [
            'title' => $title,
            'preview_text' => $preview_text,
            'background_color' => $background_color,
            'body' => self::postToMjmlComponents($content, false)
        ];

        return str_replace(PHP_EOL, '', Templates::getContent('newsletter.mjml', $data));
    }

    /**
     * Convert an array of arguments or an \WP_Post object to email-compliant HTML.
     *
     * @param array|\WP_Post $input An array of arguments or an \WP_Post object.
     * @return string|\WP_Error Email-compliant HTML or \WP_Error otherwise.
     * @throws \Exception Error message.
     */
    public static function toHtml($input)
    {
        $credentials = Api::credentials();
        if (is_wp_error($credentials)) {
            return $credentials;
        }
        if (is_a($input, '\WP_Post')) {
            $markup = self::fromPost($input);
        } elseif (is_array($input)) {
            $markup = self::fromAry($input);
        } else {
            return new \WP_Error(
                'rrze_newsletter_mjml_render_error',
                __('MJML rendering error.', 'rrze-newsletter')
            );
        }
        $respond = Api::request($markup);

        if (intval($respond['response']['code']) != 200) {
            return new \WP_Error(
                'rrze_newsletter_mjml_render_error',
                __('MJML rendering error.', 'rrze-newsletter')
            );
        }

        if (is_wp_error($respond)) {
            return $respond;
        }

        $body = json_decode($respond['body'], true);
        if (empty($body['html'])) {
            return new \WP_Error(
                'rrze_newsletter_mjml_render_error',
                __('MJML rendering error.', 'rrze-newsletter')
            );
        }

        return $body['html'];
    }
}
