<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use WP_Post;

/**
 * Class LinkProcessor
 * 
 * Processes links in MJML content, replacing them with UTM-tagged URLs or special tokens.
 * 
 * @package RRZE\Newsletter\MJML
 */
class LinkProcessor
{
    /**
     * Processes all links in the given HTML content of a post.
     *
     * @param WP_Post $post WP_Post object.
     * @param string $html Input HTML.
     * @return string HTML with processed links.
     */
    public static function processLinks(WP_Post $post, string $html): string
    {
        if (empty($html)) {
            return $html;
        }

        if (!preg_match_all('/href="([^"]*)"/', $html, $matches, PREG_SET_ORDER)) {
            return $html;
        }

        foreach ($matches as $match) {
            $originalHref = $match[0];
            $url = $match[1];
            $newUrl = self::processUrl($post, $url);
            $html = self::replaceHrefInHtml($html, $originalHref, $newUrl);
        }

        return $html;
    }

    /**
     * Replace the given href in the HTML with the new URL.
     *
     * @param string $html The input HTML.
     * @param string $originalHref The original href attribute (e.g., href="https://...").
     * @param string $newUrl The new URL to set.
     * @return string The HTML with the replaced href.
     */
    protected static function replaceHrefInHtml(string $html, string $originalHref, string $newUrl): string
    {
        // Only replace the first occurrence to avoid replacing similar hrefs accidentally.
        return preg_replace('/' . preg_quote($originalHref, '/') . '/', 'href="' . $newUrl . '"', $html, 1);
    }

    /**
     * Returns the processed URL (with UTM params or special token).
     *
     * @param \WP_Post $post WP_Post object.
     * @param string $url Original URL.
     * @return string Processed URL.
     */
    protected static function processUrl(\WP_Post $post, string $url): string
    {
        $specialTokens = [
            '{{=UNSUB}}',
            '{{=UPDATE}}',
            '{{=ARCHIVE}}',
        ];

        foreach ($specialTokens as $token) {
            if (strpos($url, $token) !== false) {
                return $token;
            }
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return add_query_arg([
            'utm_campaign' => get_post_time('Y-m-d', false, $post),
            'utm_source'   => sanitize_title($post->post_title),
            'utm_medium'   => 'email',
        ], $url);
    }
}
