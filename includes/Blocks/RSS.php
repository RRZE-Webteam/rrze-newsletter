<?php

namespace RRZE\Newsletter\Blocks;

defined('ABSPATH') || exit;

final class RSS
{
    protected static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', [__CLASS__, 'register']);
    }

    /**
     * Registers the lock on server.
     */
    public static function register()
    {
        register_block_type(
            'rrze-newsletter/rss',
            [
                'api_version' => 2,
                'attributes' => [
                    'feedURL' => [
                        'type' => 'string',
                        'default' => '',
                    ],
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
                ],
                'render_callback' => [__CLASS__, 'renderHTML'],
            ]
        );
    }

    /**
     * Render the block on the server in HTML format.
     * @param array $atts The block attributes.
     * @return string Returns the block content with received rss items.
     */
    public static function renderHTML($atts)
    {
        $rss = fetch_feed($atts['feedURL']);

        if (is_wp_error($rss)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $rss->get_error_message() . '</div></div>';
        }

        if (!$rss->get_item_quantity()) {
            return '<div class="components-placeholder"><div class="notice notice-error">' . __('An error has occurred, which probably means the feed is down. Try again later.', 'rrze-newsletter') . '</div></div>';
        }

        $headingStyle = $atts['headingFontSize'] ? 'font-size:' . $atts['headingFontSize'] . 'px;' : '';
        $headingStyle .= $atts['headingColor'] ? 'color:' . $atts['headingColor'] : '';
        $headingStyle = $headingStyle ? ' style="' . $headingStyle . '"' : '';

        $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';            
        $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
        $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';

        $rssItems  = $rss->get_items(0, $atts['itemsToShow']);
        $listItems = '';
        foreach ($rssItems as $item) {            
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
                    $date = sprintf(
                        '<time datetime="%1$s">%2$s</time> ',
                        date_i18n(get_option('c'), $date),
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

            $listItems .= "{$title}{$date}{$author}{$excerpt}";
        }

        return sprintf('<div%s>%s</div>', $textStyle, $listItems);
    }

    /**
     * Render the block on the server in MJML format.
     * @param array $atts The block attributes.
     * @return string Returns the block content with received rss items.
     */
    public static function renderMJML($atts)
    {
        $rss = fetch_feed($atts['feedURL']);

        if (is_wp_error($rss)) {
            return '';
        }

        if (!$rss->get_item_quantity()) {
            return '';
        }

        $headingStyle = $atts['headingFontSize'] ? 'font-size:' . $atts['headingFontSize'] . 'px;' : '';
        $headingStyle .= $atts['headingColor'] ? 'color:' . $atts['headingColor'] : '';
        $headingStyle = $headingStyle ? ' style="' . $headingStyle . '"' : '';

        $textStyle = $atts['textFontSize'] ? 'font-size:' . $atts['textFontSize'] . 'px;' : '';            
        $textStyle .= $atts['textColor'] ? 'color:' . $atts['textColor'] : '';
        $textStyle = $textStyle ? ' style="' . $textStyle . '"' : '';

        $rssItems  = $rss->get_items(0, $atts['itemsToShow']);
        $listItems = '';
        foreach ($rssItems as $item) {
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
                    $date = sprintf(
                        '<span%1$s>%2$s</span> ',
                        $textStyle,
                        date_i18n(get_option('date_format'), $date)
                    );
                }
            }

            $author = '';
            if ($atts['displayAuthor']) {
                $author = $item->get_author();
                if (is_object($author)) {
                    $author = $author->get_name();
                    $author = "<span{$textStyle}>" . sprintf(
                        /* translators: %s: the author. */
                        __('by %s', 'rrze-newsletter'),
                        esc_html(strip_tags($author))
                    ) . '</span>';
                }
            }

            $excerpt = '';
            if ($atts['displayExcerpt'] && $item->get_description()) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $atts['excerptLength'], '&hellip;'));
                $excerpt = "<p{$textStyle}>" . esc_html($excerpt) . '</p>';
            }

            $listItems .= "{$title}{$date}{$author}{$excerpt}";
        }

        return $listItems;
    }
}
