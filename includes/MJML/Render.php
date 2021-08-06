<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Blocks\ICS\ICS;
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
        if (!is_array($attributes)) return $attributes;
        return implode(
            ' ',
            array_map(
                function ($key) use ($attributes) {
                    if (isset($attributes[$key]) && !is_array($attributes[$key])) {
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
     * @param array $blockAttrs Block attributes.
     * @return string font size.
     */
    private static function getFontSize($blockAttrs)
    {
        if (isset($blockAttrs['customFontSize'])) {
            return $blockAttrs['customFontSize'] . 'px';
        }
        if (isset($blockAttrs['fontSize'])) {
            // Gutenberg's default font size presets.
            // https://github.com/WordPress/gutenberg/blob/359858da0675943d8a759a0a7c03e7b3846536f5/packages/block-editor/src/store/defaults.js#L87-L113 .
            $sizes = [
                'small'  => '13px',
                'normal' => '16px',
                'medium' => '20px',
                'large'  => '36px',
                'huge'   => '48px',
            ];
            return $sizes[$blockAttrs['fontSize']];
        }
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
        if (isset($blockAttrs['color'])) {
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
     * Add color attributes and a padding, if component has a background color.
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
            ['customBackgroundColor', 'customTextColor', 'customFontSize', 'fontSize', 'backgroundColor', 'style']
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
            } elseif (strpos($url, '{{=UPDATE}}') !== false) {
                $url = '{{=UPDATE}}';
                $skipUrlWithParams = true;
            } elseif (strpos($url, '{{=PERMALINK}}') !== false) {
                $url = '{{=PERMALINK}}';
                $skipUrlWithParams = true;
            } elseif (strpos($url, '{{=ARCHIVE}}') !== false) {
                $url = '{{=ARCHIVE}}';
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
     * @param integer $postId Maybe a \WP_Post ID.
     * @param array $block The block.
     * @param bool $isInColumn Whether the component is a child of a column component.
     * @param bool $isInGroup Whether the component is a child of a group component.
     * @param array $defaultAttrs Default attributes for the component.
     * @return string MJML component.
     */
    public static function renderMjmlComponent($postId, $block, $isInColumn = false, $isInGroup = false, $defaultAttrs = [])
    {
        $blockName = $block['blockName'];
        $attrs = $block['attrs'];
        $innerBlocks = $block['innerBlocks'];
        $innerHtml = $block['innerHTML'];

        if (empty($blockName)) {
            return '';
        }

        $defaultAttrs['postId'] = $postId;

        $blockMjmlMarkup = '';
        $attrs = self::processAttributes(array_merge($defaultAttrs, $attrs));

        // Default attributes for the section which will envelop the mj-column.
        $sectionAttrs = array_merge(
            $attrs,
            [
                'padding' => '0',
            ]
        );

        // Default attributes for the column which will envelop the component.
        $columnAttrs = array_merge(
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
            case 'rrze-newsletter/rss':
            case 'rrze-newsletter/ics':
                $textAttrs = array_merge(
                    [
                        'line-height' => '1.8',
                        'font-size' => '16px',
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
                    $innerHtml = RSS::renderMJML($attrs);
                }

                // Render rrze-newsletter/ics block.
                if ($blockName == 'rrze-newsletter/ics') {
                    $innerHtml = ICS::renderMJML($attrs);
                }

                $blockMjmlMarkup = '<mj-text ' . self::arrayToAttributes($textAttrs) . '>' . $innerHtml . '</mj-text>';

                break;

                // Image block.
            case 'core/image':
                // Parse block content.
                $dom = new \DomDocument();
                @$dom->loadHtml(mb_convert_encoding($innerHtml, 'HTML-ENTITIES', "UTF-8"));
                $xpath = new \DOMXpath($dom);
                $img = $xpath->query('//img')[0];
                $imgSrc = $img ? $img->getAttribute('src') : '';
                $figcaption = $xpath->query('//figcaption/text()')[0];

                $imgAttrs = array(
                    'padding' => '0',
                    'align'   => isset($attrs['align']) ? $attrs['align'] : 'left',
                    'src'     => $imgSrc,
                );

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
                    $imgAttrs['width'] = $attrs['width'] . 'px';
                }
                if (isset($attrs['height'])) {
                    $imgAttrs['height'] = $attrs['height'] . 'px';
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
                    $caption_attrs = array(
                        'align' => 'center',
                        'color' => '#555d66',
                        'font-size' => '13px',
                        'font-family' => $fontFamily,
                    );
                    $markup .= '<mj-text ' . self::arrayToAttributes($caption_attrs) . '>' . $figcaption->wholeText . '</mj-text>';
                }

                $blockMjmlMarkup = $markup;
                break;

                // Buttons block.
            case 'core/buttons':
                foreach ($innerBlocks as $buttonBlock) {
                    // Parse block content.
                    $dom = new \DomDocument();
                    @$dom->loadHtml(mb_convert_encoding($buttonBlock['innerHTML'], 'HTML-ENTITIES', "UTF-8"));
                    $xpath = new \DOMXpath($dom);
                    $anchor = $xpath->query('//a')[0];
                    $attrs = $buttonBlock['attrs'];
                    $text = $anchor ? $anchor->textContent : '';
                    $borderRadius = isset($attrs['borderRadius']) ? $attrs['borderRadius'] : 5;
                    $isOutlined = isset($attrs['className']) && 'is-style-outline' == $attrs['className'];
                    $defaultButtonAttrs = [
                        'padding'       => '0',
                        'inner-padding' => '12px 24px',
                        'line-height'   => '1.8',
                        'href'          => $anchor ? $anchor->getAttribute('href') : '',
                        'border-radius' => $borderRadius . 'px',
                        'font-size'     => '18px',
                        'font-family'   => $fontFamily,
                        'font-weight'   => 'bold',
                        // Default color - will be replaced by getColors if there are colors set.
                        'color'         => $isOutlined ? '#32373c' : '#fff !important',
                    ];
                    if ($isOutlined) {
                        $defaultButtonAttrs['background-color'] = 'transparent';
                    } else {
                        $defaultButtonAttrs['background-color'] = '#32373c';
                    }
                    $colors = self::getColors($attrs);
                    if (isset($colors['color'])) {
                        $colors['color'] = $colors['color'] . ' !important';
                    }
                    $buttonAttrs = array_merge(
                        $defaultButtonAttrs,
                        $colors
                    );
                    if ($isOutlined) {
                        $buttonAttrs['css-class'] = $attrs['className'];
                    }
                    if (isset($attrs['width'])) {
                        $columnAttrs['width'] = $attrs['width'] . '%';
                    }

                    $blockMjmlMarkup .= '<mj-column ' . self::arrayToAttributes($columnAttrs) . '><mj-button ' . self::arrayToAttributes($buttonAttrs) . ">$text</mj-button></mj-column>";
                }
                break;

                // Separator block.
            case 'core/separator':
                $isStyleDefault = isset($attrs['className']) ? 'is-style-default' == $attrs['className'] : true;
                $dividerAttrs = array_merge(
                    [
                        'padding' => '0',
                        'border-width' => $isStyleDefault ? '2px' : '1px',
                        'width' => $isStyleDefault ? '100px' : '100%',
                        // Default color - will be replaced by getColors if there are colors set.
                        'border-color' => '#8f98a1',
                    ],
                    self::getColors($attrs)
                );
                $blockMjmlMarkup .= '<mj-divider ' . self::arrayToAttributes($dividerAttrs) . '/>';
                break;

                // Spacer block.
            case 'core/spacer':
                $attrs['height'] = $attrs['height'] . 'px';
                $blockMjmlMarkup .= '<mj-spacer ' . self::arrayToAttributes($attrs) . '/>';
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

                $socialWrapperAttrs = [
                    'icon-size'     => '22px',
                    'mode'          => 'horizontal',
                    'padding'       => '0',
                    'border-radius' => '999px',
                    'icon-padding'  => '8px',
                ];
                if (isset($attrs['align'])) {
                    $socialWrapperAttrs['align'] = $attrs['align'];
                } else {
                    $socialWrapperAttrs['align'] = 'left';
                }
                $markup = '<mj-social ' . self::arrayToAttributes($socialWrapperAttrs) . '>';
                foreach ($innerBlocks as $linkBlock) {
                    if (isset($linkBlock['attrs']['url'])) {
                        $url = $linkBlock['attrs']['url'];
                        // Handle older version of the block, where innner blocks are named `core/social-link-<service>`.
                        $serviceName = isset($linkBlock['attrs']['service']) ? $linkBlock['attrs']['service'] : str_replace('core/social-link-', '', $linkBlock['blockName']);

                        if (isset($socialIcons[$serviceName])) {
                            $imgAttrs = [
                                'href' => $url,
                                'src' => plugins_url('assets/icons/' . $socialIcons[$serviceName]['icon'], plugin()->getBasename()),
                                'background-color' => $socialIcons[$serviceName]['color'],
                                'css-class' => 'social-element',
                            ];

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
                    $columnAttrs['width']     = $attrs['width'];
                    $columnAttrs['css-class'] = 'mj-column-has-width';
                }

                $markup = '<mj-column ' . self::arrayToAttributes($columnAttrs) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, true, false, $defaultAttrs);
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
                $markup = '';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, true, false, $defaultAttrs);
                }
                $blockMjmlMarkup = $markup;
                break;

                // Group block.
            case 'core/group':
                // There's no color attribute on mj-wrapper, so it has to be passed to children.
                // https://github.com/mjmlio/mjml/issues/1881 .
                if (isset($attrs['color'])) {
                    $defaultAttrs['color'] = $attrs['color'];
                }
                $markup = '<mj-wrapper ' . self::arrayToAttributes($attrs) . '>';
                foreach ($innerBlocks as $block) {
                    $markup .= self::renderMjmlComponent($postId, $block, false, true, $defaultAttrs);
                }
                $blockMjmlMarkup = $markup . '</mj-wrapper>';
                break;
        }

        $isPostInserterBlock = 'rrze-newsletter/post-inserter' == $blockName;
        $isGroupBlock = 'core/group' == $blockName;

        if (
            !$isInColumn &&
            !$isGroupBlock &&
            'core/columns' != $blockName &&
            'core/column' != $blockName &&
            'core/buttons' != $blockName &&
            !$isPostInserterBlock
        ) {
            $columnAttrs['width'] = '100%';
            $blockMjmlMarkup = '<mj-column ' . self::arrayToAttributes($columnAttrs) . '>' . $blockMjmlMarkup . '</mj-column>';
        }
        if ($isInColumn || $isGroupBlock || $isPostInserterBlock) {
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

            if ('core/group' === $block['blockName']) {
                $defaultAttrs = [];
                $attrs = self::processAttributes($block['attrs']);
                if (isset($attrs['color'])) {
                    $defaultAttrs['color'] = $attrs['color'];
                }
                $mjmlMarkup = '<mj-wrapper ' . self::arrayToAttributes($attrs) . '>';
                foreach ($block['innerBlocks'] as $block) {
                    $innerBlockContent = self::renderMjmlComponent($postId, $block, false, true, $defaultAttrs);
                    $mjmlMarkup .= $innerBlockContent;
                }
                $blockContent = $mjmlMarkup . '</mj-wrapper>';
            } else {
                $blockContent = self::renderMjmlComponent($postId, $block);
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
            'body' => self::postToMjmlComponents($post, $post->post_content, true)
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
            'body' => self::postToMjmlComponents(new \stdClass, $content, false)
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
