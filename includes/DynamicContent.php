<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Blocks\ICS\ICS;
use RRZE\Newsletter\Mail\Contrast;

/** Resolve feeds once for mail output or a read-only preview. */
final class DynamicContent
{
    /** Previews must not update the feed-empty flags used by queue creation. */
    public static function resolve(string $html, int $postId, bool $trackAvailability = true): string
    {
        $fragments = [];
        foreach (['rss' => RSS::class, 'ics' => ICS::class] as $type => $renderer) {
            $attributes = get_post_meta($postId, 'rrze_newsletter_' . $type . '_attrs', true);
            foreach (is_array($attributes) ? $attributes : [] as $key => $attrs) {
                $placeholder = strtoupper($type) . '_BLOCK_' . $key;
                if (str_contains($html, $placeholder)) {
                    $fragments[$placeholder] = $renderer::renderMJML($attrs, $trackAvailability);
                }
            }
        }
        if (!$fragments) {
            return $html;
        }

        // An absent setting uses the default (enabled); WordPress stores false as ''.
        $enabled = !metadata_exists('post', $postId, 'rrze_newsletter_contrast_protection')
            || (bool) get_post_meta($postId, 'rrze_newsletter_contrast_protection', true);
        return $enabled ? Contrast::replace($html, $fragments) : strtr($html, $fragments);
    }
}
