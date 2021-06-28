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
            array(
                'api_version' => 2,
                'attributes'      => array(
                    'feedURL' => array(
                        'type'      => 'string',
                        'default'   => '',
                    ),
                    'feedURL' => array(
                        'type'      => 'string',
                        'default'   => '',
                    ),
                    'itemsToShow' => array(
                        'type'      => 'number',
                        'default'   => 5,
                    ),
                    'displayExcerpt' => array(
                        'type'      => 'boolean',
                        'default'   => false,
                    ),
                    'displayAuthor' => array(
                        'type'      => 'boolean',
                        'default'   => false,
                    ),
                    'displayDate' => array(
                        'type'      => 'boolean',
                        'default'   => false,
                    ),
                    'excerptLength' => array(
                        'type'      => 'number',
                        'default'   => 25,
                    ),
                ),
                'render_callback' => [__CLASS__, 'render_block_rss'],
            )
        );
    }

    /**
     * Renders the `rrze-newsletter/rss` block on server.
     * @param array $attributes The block attributes.
     * @return string Returns the block content with received rss items.
     */
    public static function render_block_rss($attributes)
    {
        $rss = fetch_feed($attributes['feedURL']);

        if (is_wp_error($rss)) {
            return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __('RSS Error:', 'rrze-newsletter') . '</strong> ' . $rss->get_error_message() . '</div></div>';
        }

        if (!$rss->get_item_quantity()) {
            return '<div class="components-placeholder"><div class="notice notice-error">' . __('An error has occurred, which probably means the feed is down. Try again later.', 'rrze-newsletter') . '</div></div>';
        }

        $rss_items  = $rss->get_items(0, $attributes['itemsToShow']);
        $list_items = '';
        foreach ($rss_items as $item) {
            $title = esc_html(trim(strip_tags($item->get_title())));
            if (empty($title)) {
                $title = __('(no title)');
            }
            $link = $item->get_link();
            $link = esc_url($link);
            if ($link) {
                $title = "<a href='{$link}'>{$title}</a>";
            }
            $title = "<div class='wp-block-rss__item-title'>{$title}</div>";

            $date = '';
            if ($attributes['displayDate']) {
                $date = $item->get_date('U');

                if ($date) {
                    $date = sprintf(
                        '<time datetime="%1$s" class="wp-block-rss__item-publish-date">%2$s</time> ',
                        date_i18n(get_option('c'), $date),
                        date_i18n(get_option('date_format'), $date)
                    );
                }
            }

            $author = '';
            if ($attributes['displayAuthor']) {
                $author = $item->get_author();
                if (is_object($author)) {
                    $author = $author->get_name();
                    $author = '<span class="wp-block-rss__item-author">' . sprintf(
                        /* translators: %s: the author. */
                        __('by %s'),
                        esc_html(strip_tags($author))
                    ) . '</span>';
                }
            }

            $excerpt = '';
            if ($attributes['displayExcerpt']) {
                $excerpt = html_entity_decode($item->get_description(), ENT_QUOTES, get_option('blog_charset'));
                $excerpt = esc_attr(wp_trim_words($excerpt, $attributes['excerptLength'], ' [&hellip;]'));

                // Change existing [...] to [&hellip;].
                if ('[...]' === substr($excerpt, -5)) {
                    $excerpt = substr($excerpt, 0, -5) . '[&hellip;]';
                }

                $excerpt = '<div class="wp-block-rss__item-excerpt">' . esc_html($excerpt) . '</div>';
            }

            $list_items .= "<li class='wp-block-rss__item'>{$title}{$date}{$author}{$excerpt}</li>";
        }

        $classnames = [];
        $wrapper_attributes = get_block_wrapper_attributes(['class' => implode(' ', $classnames)]);

        return sprintf('<ul %s>%s</ul>', $wrapper_attributes, $list_items);
    }
}
