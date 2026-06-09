<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\StyleProcessor;

final class AttributeInheritance
{
    public static function forChild(
        array $block,
        array $parentAttrs,
        array $defaultAttrs
    ): array {
        $attrs = array_merge($defaultAttrs, $block['attrs'] ?? []);
        $attrs['color'] = $attrs['color']
            ?? $parentAttrs['color']
            ?? '#000000';
        $attrs['link'] = $attrs['link']
            ?? $parentAttrs['link']
            ?? '#000000';

        if (isset($attrs['textColor'])) {
            unset($attrs['color']);
        }
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $attrs['link'] = StyleProcessor::extractLinkColor(
                $attrs['style']['elements']['link']['color']['text']
            );
        }

        return $attrs;
    }

    public static function withoutParentLinkColor(
        array $defaultAttrs,
        array $block
    ): array {
        $hasOwnLinkColor =
            !empty($block['attrs']['style']['elements']['link']['color']['text'])
            || !empty($block['attrs']['link']);

        if ($hasOwnLinkColor) {
            unset($defaultAttrs['link']);
        }

        return $defaultAttrs;
    }
}
