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
            'displayDate' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'displayContent' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'excerptLimit' => [
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
        $feedItems = '';

        $atts = self::parseAtts($atts);

        global $post;
        if (
            is_a($post, '\WP_Post')
            && Newsletter::POST_TYPE === get_post_type($post->ID)
        ) {
            $atts['postId'] = $post->ID;
        } else {
            return '';
        }

        $feed = self::fetchFeed($atts['feedURL']);

        if (is_wp_error($feed)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $feed->get_error_message() . '</div></div>';
        }

        if ($feed->get_item_quantity()) {
            $feedItems = self::render($atts, $feed, true);
        }

        if (!$feedItems) {
            $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';
            $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
            $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';
            $feedItems = sprintf('<div%1$s>%2$s</div>', $textStyle, __('There are no items available.', 'rrze-newsletter'));
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
        $feedItems = '';

        $atts = self::parseAtts($atts);

        $feed = self::fetchFeed($atts['feedURL']);

        if (!is_wp_error($feed) && $feed->get_item_quantity()) {
            $feedItems = self::render($atts, $feed, true);
        }

        if (!$feedItems) {
            $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';
            $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
            $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';
            $feedItems = sprintf('<p%1$s>%2$s</p>', $textStyle, __('There are no items available.', 'rrze-newsletter'));
        } else {
            wp_cache_set('rrze_newsletter_rss_block_not_empty', 1, $atts['postId']);
        }

        return $feedItems;
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
                $readMoreLink = sprintf(
                    '<p class="has-large-padding"><a href="%s">%s</a></p>',
                    $link,
                    sprintf(
                        /* translators: %s: article title. */
                        __('Continue reading "%s"&hellip;', 'rrze-newsletter'),
                        $title
                    )
                );
            } elseif ($link) {
                $title = "<a{$headingStyle} href='{$link}'>{$title}</a>";
            }
            $title = '<h2 class="has-normal-padding"' . $headingStyle . '>' . $title . '</h2>';

            $date = '';
            if ($atts['displayDate']) {
                if ($timestamp) {
                    $mjml ?
                        $date = sprintf(
                            '<p class="has-small-padding"%1$s>%2$s</p> ',
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

            $content = '';
            if ($atts['displayContent'] && !empty($item->get_content())) {
                $content = html_entity_decode($item->get_content(), ENT_QUOTES, get_option('blog_charset'));
                if ($atts['excerptLimit']) {
                    $content = wp_trim_words($content, absint($atts['excerptLength']), ' [&hellip;]');
                    // Change existing [...] to [&hellip;].
                    if ('[...]' === substr($content, -5)) {
                        $content = substr($content, 0, -5) . '[&hellip;]';
                    }
                }
                $content = $mjml ?
                    '<p class="has-normal-padding">' . $content . $readMoreLink . '</p>'
                    :
                    '<div>' . $content . $readMoreLink . '</div>';
            }

            $listItems .= $title . $date . $content;
        }

        if ($listItems) {
            return $mjml ? $listItems : sprintf('<div%1$s>%2$s</div>', $textStyle, $listItems);
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

    /**
     * Fetch Atom & RSS Feeds.
     *
     * @param string $url
     * @return object \WP_Error|\SimplePie
     */
    public static function fetchFeed($url)
    {
        if (!class_exists('\SimplePie', false)) {
            require_once ABSPATH . WPINC . '/class-simplepie.php';
        }
        if (!class_exists('\WP_SimplePie_Sanitize_KSES', false)) {
            require_once ABSPATH . WPINC . '/class-wp-simplepie-sanitize-kses.php';
        }
        if (!class_exists('\WP_SimplePie_File', false)) {
            require_once ABSPATH . WPINC . '/class-wp-simplepie-file.php';
        }

        $feed = new \SimplePie();

        $feed->set_sanitize_class('WP_SimplePie_Sanitize_KSES');

        $feed->sanitize = new \WP_SimplePie_Sanitize_KSES();

        $feed->set_file_class('WP_SimplePie_File');

        $feed->set_feed_url($url);

        $feed->enable_cache(false);

        $feed->init();
        $feed->set_output_encoding(get_option('blog_charset'));

        if ($feed->error()) {
            return new \WP_Error('simplepie-error', $feed->error());
        }

        return $feed;
    }
}
