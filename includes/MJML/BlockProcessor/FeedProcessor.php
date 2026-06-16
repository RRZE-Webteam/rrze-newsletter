<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class FeedProcessor
{
    /**
     * Persist and render an RSS feed placeholder.
     *
     * @param int                  $postId     Newsletter post ID.
     * @param array<string, mixed> $attrs      Feed block attributes.
     * @param string               $fontFamily Placeholder font family.
     * @return string Rendered MJML placeholder.
     */
    public static function renderRss(
        int $postId,
        array $attrs,
        string $fontFamily
    ): string {
        return self::renderFeedPlaceholder(
            $postId,
            $attrs,
            $fontFamily,
            'rrze_newsletter_rss_attrs',
            'RSS_BLOCK_'
        );
    }

    /**
     * Persist and render an ICS feed placeholder.
     *
     * @param int                  $postId     Newsletter post ID.
     * @param array<string, mixed> $attrs      Feed block attributes.
     * @param string               $fontFamily Placeholder font family.
     * @return string Rendered MJML placeholder.
     */
    public static function renderIcs(
        int $postId,
        array $attrs,
        string $fontFamily
    ): string {
        return self::renderFeedPlaceholder(
            $postId,
            $attrs,
            $fontFamily,
            'rrze_newsletter_ics_attrs',
            'ICS_BLOCK_'
        );
    }

    /**
     * Store feed attributes and render the replacement token.
     *
     * @param int                  $postId           Newsletter post ID.
     * @param array<string, mixed> $attrs            Feed block attributes.
     * @param string               $fontFamily       Placeholder font family.
     * @param string               $metaKey          Post-meta key for block data.
     * @param string               $placeholderPrefix Replacement-token prefix.
     * @return string Rendered MJML placeholder.
     */
    private static function renderFeedPlaceholder(
        int $postId,
        array $attrs,
        string $fontFamily,
        string $metaKey,
        string $placeholderPrefix
    ): string {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }

        $textAttrs = array_merge([
            'padding' => '0',
            'line-height' => '1.5',
            'font-size' => '16px',
            'font-family' => $fontFamily,
        ], $attrs);

        $key = md5((string) ($attrs['feedURL'] ?? ''));
        $savedAttrs = get_post_meta($postId, $metaKey, true) ?: [];
        $savedAttrs[$key] = $attrs;
        update_post_meta($postId, $metaKey, $savedAttrs);

        return '<mj-text '
            . AttributeHandler::arrayToAttributes($textAttrs)
            . '>'
            . $placeholderPrefix
            . $key
            . '</mj-text>';
    }
}
