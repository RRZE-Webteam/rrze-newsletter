<?php

namespace RRZE\Newsletter\Blocks\RSS;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;

class RSS
{
    /**
     * attributes
     * Default block attributes.
     *
     * @return array
     */
    protected static function attributes()
    {
        return [
            'postId' => [
                'type' => 'number',
                'default' => 0,
            ],
            'feedURL' => [
                'type' => 'string',
                'default' => '',
            ],
            'itemsToShow' => [
                'type' => 'number',
                'default' => 5,
            ],
            'sinceLastSend' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'displayExcerpt' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'displayDate' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'excerptLength' => [
                'type' => 'number',
                'default' => 25,
            ],
            'displayReadMore' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'textFontSize' => [
                'type' => 'number',
                'default' => 16
            ],
            'headingFontSize' => [
                'type' => 'number',
                'default' => 25
            ],
            'textColor' => [
                'type' => 'string',
                'default' => '#000'
            ],
            'headingColor' => [
                'type' => 'string',
                'default' => '#000'
            ]
        ];
    }

    /**
     * register
     * Registers the block on server.
     */
    public static function register()
    {
        register_block_type(
            'rrze-newsletter/rss',
            [
                'api_version' => 2,
                'attributes' => self::attributes(),
                'render_callback' => [__CLASS__, 'renderHTML'],
            ]
        );
    }

    /**
     * renderHTML
     * Render the block on the server in HTML format.
     * @param array $atts The block attributes.
     * 
     * @return string Returns the block content.
     */
    public static function renderHTML(array $atts): string
    {
        add_filter('wp_feed_cache_transient_lifetime', function ($lifetime) {
            return 0;
        });

        $atts = self::parseAtts($atts);

        global $post;
        if (
            is_a($post, '\WP_Post')
            && Newsletter::POST_TYPE == get_post_type($post->ID)
        ) {
            $atts['postId'] = $post->ID;
        }

        $feed = fetch_feed($atts['feedURL']);

        if (is_wp_error($feed)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $feed->get_error_message() . '</div></div>';
        }

        $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';
        $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
        $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';

        if (!$feed->get_item_quantity()) {
            return sprintf('<div%1$s>%2$s</div>', $textStyle, __('There are no items available.', 'rrze-newsletter'));
        }

        $feedItems = self::render($atts, $feed);
        if (!$feedItems) {
            return sprintf('<div%1$s>%2$s</div>', $textStyle, __('There are no items available.', 'rrze-newsletter'));
        }
        return $feedItems;
    }

    /**
     * renderMJML
     * Render the block on the server in MJML format.
     * 
     * @param array $atts The block attributes.
     * @return string Returns the block content.
     */
    public static function renderMJML(array $atts): string
    {
        add_filter('wp_feed_cache_transient_lifetime', function ($lifetime) {
            return 0;
        });

        $atts = self::parseAtts($atts);

        $feed = fetch_feed($atts['feedURL']);

        if (is_wp_error($feed)) {
            return '';
        }

        if (!$feed->get_item_quantity()) {
            return '';
        }

        return self::render($atts, $feed, true);
    }

    /**
     * render
     * Render the block on the server.
     *
     * @param array $atts
     * @param object $feed
     * @param boolean $mjml
     * @return string
     */
    protected static function render(array $atts, $feed, $mjml = false)
    {
        $headingStyle = $atts['headingFontSize'] ? 'font-size:' . $atts['headingFontSize'] . 'px;' : '';
        $headingStyle .= $atts['headingColor'] ? 'color:' . $atts['headingColor'] : '';
        $headingStyle = $headingStyle ? ' style="' . $headingStyle . '"' : '';

        $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';
        $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
        $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';

        $sinceLastSendGmt = Newsletter::getLastSendDateGmt($atts['postId']);

        $feedItems  = $feed->get_items(0, $atts['itemsToShow']);
        $listItems = '';

        foreach ($feedItems as $item) {
            $timestamp = $item->get_date('U');
            if ($atts['sinceLastSend'] && $timestamp < strtotime($sinceLastSendGmt)) {
                continue;
            }

            $title = esc_html(trim(strip_tags($item->get_title())));
            if (empty($title)) {
                $title = __('(no title)', 'rrze-newsletter');
            }
            $link = $item->get_link();
            $link = esc_url($link);
            $readMoreLink = '';
            if ($link && $atts['displayReadMore']) {
                $readMoreLink = ' ' . sprintf(__('Continue reading "%s"&hellip;', 'rrze-newsletter'), "<a href='{$link}'>{$title}</a>");
            } elseif ($link) {
                $title = "<a{$headingStyle} href='{$link}'>{$title}</a>";
            }
            $title = "<h3{$headingStyle}>{$title}</h3>";

            $date = '';
            if ($atts['displayDate']) {
                if ($timestamp) {
                    $mjml ?
                        $date = sprintf(
                            '<p%1$s>%2$s</p> ',
                            $textStyle,
                            date_i18n(get_option('date_format'), $timestamp)
                        ) :
                        $date = sprintf(
                            '<time datetime="%1$s">%2$s</time> ',
                            date('Y-m-d H:i:s', $timestamp),
                            date_i18n(get_option('date_format'), $timestamp)
                        );
                }
            }

            $excerpt = '';
            if ($atts['displayExcerpt'] && !empty($item->get_description())) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $atts['excerptLength'], '&hellip;'));
                $excerpt = "<div>" . esc_html($excerpt) . $readMoreLink . '</div>';
            }

            $listItems .= $title . $date . $excerpt;
        }

        if ($listItems) {
            return sprintf('<div%1$s>%2$s</div>', $textStyle, $listItems);
        }
        return '';
    }

    /**
     * parseAtts
     * Parse block attributes.
     *
     * @param array $atts
     * @return array
     */
    protected static function parseAtts(array $atts): array
    {
        $default = [];
        $attributes = self::attributes();
        foreach ($attributes as $key => $value) {
            $default[$key] = $value['default'];
        }

        $atts = wp_parse_args($atts, $default);
        $atts = array_intersect_key($atts, $default);

        return $atts;
    }
}
