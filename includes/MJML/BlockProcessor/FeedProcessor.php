<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;

final class FeedProcessor
{
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
