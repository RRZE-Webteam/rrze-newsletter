<?php

namespace RRZE\Newsletter\Blocks;

defined('ABSPATH') || exit;

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
            'feedURL' => [
                'type' => 'string',
                'default' => '',
            ],
            'itemsToShow' => [
                'type' => 'number',
                'default' => 5,
            ],
            'displayExcerpt' => [
                'type' => 'boolean',
                'default' => false,
            ],
            'displayAuthor' => [
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
        $atts = self::parseAtts($atts);

        $feed = fetch_feed($atts['feedURL']);

        if (is_wp_error($feed)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $feed->get_error_message() . '</div></div>';
        }

        if (!$feed->get_item_quantity()) {
            return '<div class="components-placeholder"><div class="notice notice-error">' . __('An error has occurred, which probably means the feed is down. Try again later.', 'rrze-newsletter') . '</div></div>';
        }

        return self::render($atts, $feed);
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
        $atts = self::parseAtts($atts);

        $feed = fetch_feed($atts['feedURL']);

        if (is_wp_error($feed)) {
            return '';
        }

        if (!$feed->get_item_quantity()) {
            return '';
        }

        return self::render($atts, $feed);
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

        $feedItems  = $feed->get_items(0, $atts['itemsToShow']);
        $listItems = '';
        foreach ($feedItems as $item) {
            $title = esc_html(trim(strip_tags($item->get_title())));
            if (empty($title)) {
                $title = __('(no title)', 'rrze-newsletter');
            }
            $link = $item->get_link();
            $link = esc_url($link);
            if ($link) {
                $title = "<a{$headingStyle} href='{$link}'>{$title}</a>";
            }
            $title = "<h3{$headingStyle}>{$title}</h3>";

            $date = '';
            if ($atts['displayDate']) {
                $date = $item->get_date('U');

                if ($date) {
                    $mjml ?
                        $date = sprintf(
                            '<span%1$s>%2$s</span> ',
                            $textStyle,
                            date_i18n(get_option('date_format'), $date)
                        ) :
                        $date = sprintf(
                            '<time datetime="%1$s">%2$s</time> ',
                            date('Y-m-d H:i:s', $date),
                            date_i18n(get_option('date_format'), $date)
                        );
                }
            }

            $author = '';
            if ($atts['displayAuthor']) {
                $author = $item->get_author();
                if (is_object($author)) {
                    $author = $author->get_name();
                    $author = '<span>' . sprintf(
                        /* translators: %s: the author. */
                        __('by %s', 'rrze-newsletter'),
                        esc_html(strip_tags($author))
                    ) . '</span>';
                }
            }

            $excerpt = '';
            if ($atts['displayExcerpt'] && !empty($item->get_description())) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $atts['excerptLength'], '&hellip;'));
                $excerpt = "<div>" . esc_html($excerpt) . '</div>';
            }

            $listItems .= $title . $date . $author . $excerpt;
        }

        return sprintf('<div%s>%s</div>', $textStyle, $listItems);
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
