<?php

namespace RRZE\Newsletter\Blocks\RSS;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

class RSS
{
    /**
     * register
     * Registers the block on server.
     */
    public static function register()
    {
        register_block_type(
            plugin()->getPath('build/editor/blocks/rss') . 'block.json',
            [
                'render_callback' => [__CLASS__, 'renderHTML'],
            ]
        );
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
        $defaultAtts = [];
        $metaDataFile = plugin()->getPath('build/editor/blocks/rss') . 'block.json';
        if (
            file_exists($metaDataFile)
            && !is_null($metaData = wp_json_file_decode($metaDataFile, ['associative' => true]))
        ) {
            foreach ($metaData['attributes'] as $key => $value) {
                $defaultAtts[$key] = $value['default'];
            }
        }
        $atts = wp_parse_args($atts, $defaultAtts);
        return $atts;
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
            $feedItems = self::render($atts, $feed);
        }

        if (!$feedItems) {
            $feedItems = sprintf('<div class="rrze-newsletter-rss"><p>%s</p></div>', __('There are no items available.', 'rrze-newsletter'));
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
            $feedItems = self::render($atts, $feed);
        }

        if (!$feedItems) {
            $feedItems = sprintf('<div class="rrze-newsletter-rss"><p>%s</p></div>', __('There are no items available.', 'rrze-newsletter'));
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
    protected static function render(array $atts, $feed)
    {
        $headingStyle = !empty($atts['headingFontSize']) ? 'font-size:' . $atts['headingFontSize'] . ';' : '';
        $headingStyle .= !empty($atts['headingColor']) ? 'color:' . $atts['headingColor'] . ';' : '';
        $headingStyle = $headingStyle ? ' style="' . $headingStyle . '"' : '';

        $textStyle = !empty($atts['textFontSize']) ? 'font-size:' . $atts['textFontSize'] . ';' : '';
        $textStyle .= !empty($atts['textColor']) ? 'color:' . $atts['textColor'] . ';' : '';
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
                    '<a href="%1$s">%2$s</a>',
                    $link,
                    sprintf(
                        /* translators: %s: article title. */
                        __('Continue reading "%s"&hellip;', 'rrze-newsletter'),
                        $title
                    )
                );
            } elseif ($link) {
                $title = "<a href='{$link}'>{$title}</a>";
            }
            $title = '<h3 ' . $headingStyle . '>' . $title . '</h3>';

            $date = '';
            if ($atts['displayDate']) {
                if ($timestamp) {
                    $date = sprintf(
                        '<p ' . $textStyle . '>%s</p> ',
                        date_i18n(get_option('date_format'), $timestamp)
                    );
                }
            }

            $content = '';
            if ($atts['displayContent'] && !empty($item->get_content())) {
                $content = self::filterTheContent($item->get_content());
                if ($atts['excerptLimit']) {
                    $content = wp_trim_words($content, absint($atts['excerptLength']), ' [&hellip;]');
                    // Change existing [...] to [&hellip;].
                    if ('[...]' === substr($content, -5)) {
                        $content = substr($content, 0, -5) . '[&hellip;]';
                    }
                }
                $content = '<p ' . $textStyle . '>' . $content . '</p>';
                if ($readMoreLink) {
                    $content .= '<p ' . $textStyle . '>' . $readMoreLink . '</p>';
                }
            }

            $listItems .= $title . $date . $content;
        }

        if ($listItems) {
            return '<div class="rrze-newsletter-rss" ' . $textStyle . '>' . $listItems . '</p>';
        }
        return '';
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

    /**
     * filterTheContent
     * Skips some of the functions WP normally runs on 'the_content'.
     *
     * @param string $content
     * @return void
     */
    protected static function filterTheContent($content)
    {
        $content = html_entity_decode($content, ENT_QUOTES, get_option('blog_charset'));
        return wpautop(convert_chars(wptexturize($content)));
    }
}
