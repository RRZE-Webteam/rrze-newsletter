<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

final class Blocks
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
        add_action('init', [__CLASS__, 'registerBlockRSS']);
    }

    /**
     * Registers the `newsletter/rss` block on server.
     */
    public static function registerBlockRSS()
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
                    ]
                ],
                'render_callback' => [__CLASS__, 'renderBlockRSS'],
            ]
        );
    }

    /**
     * Renders the `rrze-newsletter/rss` block on server.
     * @param array $atts The block attributes.
     * @return string Returns the block content with received rss items.
     */
    public static function renderBlockRSS($atts)
    {
        $rss = fetch_feed($atts['feedURL']);

        if (is_wp_error($rss)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $rss->get_error_message() . '</div></div>';
        }

        if (!$rss->get_item_quantity()) {
            return '<div class="components-placeholder"><div class="notice notice-error">' . __('An error has occurred, which probably means the feed is down. Try again later.', 'rrze-newsletter') . '</div></div>';
        }

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
                $title = "<a href='{$link}'>{$title}</a>";
            }
            $title = "<h3>{$title}</h3>";

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
            if ($atts['displayExcerpt']) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $atts['excerptLength'], ' [&hellip;]'));

                // Change existing [...] to [&hellip;].
                if ('[...]' === substr($excerpt, -5)) {
                    $excerpt = substr($excerpt, 0, -5) . '[&hellip;]';
                }

                $excerpt = '<div class="wp-block-rss__item-excerpt">' . esc_html($excerpt) . '</div>';
            }

            $listItems .= "{$title}{$date}{$author}{$excerpt}";
        }

        $classnames = [];
        $wrapperAtts = get_block_wrapper_attributes(['class' => implode(' ', $classnames)]);

        return sprintf('<div %s>%s</div>', $wrapperAtts, $listItems);
    }

    /**
     * Renders the `rrze-newsletter/rss` block in a MJML format.
     * @param array $atts The block attributes.
     * @return string Returns the block content with received rss items.
     */
    public static function mjmlRenderBlockRSS($atts)
    {
        $rss = fetch_feed($atts['feedURL']);

        if (is_wp_error($rss)) {
            return '';
        }

        if (!$rss->get_item_quantity()) {
            return '';
        }

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
                $title = "<a href='{$link}'>{$title}</a>";
            }
            $title = "<h3>{$title}</h3>";

            $date = '';
            if ($atts['displayDate']) {
                $date = $item->get_date('U');

                if ($date) {
                    $date = sprintf(
                        '<span>%2$s</span> ',
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
                    $author = '<span class="wp-block-rss__item-author">' . sprintf(
                        /* translators: %s: the author. */
                        __('by %s', 'rrze-newsletter'),
                        esc_html(strip_tags($author))
                    ) . '</span>';
                }
            }

            $excerpt = '';
            if ($atts['displayExcerpt']) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $atts['excerptLength'], ' [&hellip;]'));

                // Change existing [...] to [&hellip;].
                if ('[...]' === substr($excerpt, -5)) {
                    $excerpt = substr($excerpt, 0, -5) . '[&hellip;]';
                }

                $excerpt = '<p>' . esc_html($excerpt) . '</p>';
            }

            $listItems .= "{$title}{$date}{$author}{$excerpt}";
        }

        return $listItems;
    }
}
