<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\StyleProcessor;

final class AttributeInheritance
{
    /**
     * Builds the effective attributes inherited by a child block.
     *
     * @param array<string, mixed> $block Child WordPress block.
     * @param array<string, mixed> $parentAttrs Processed parent attributes.
     * @param array<string, mixed> $defaultAttrs Existing inherited attributes.
     * @return array<string, mixed> Effective child attributes.
     */
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

    /**
     * Removes an inherited link color when the child defines its own.
     *
     * @param array<string, mixed> $defaultAttrs Existing inherited attributes.
     * @param array<string, mixed> $block Child WordPress block.
     * @return array<string, mixed> Attributes safe to inherit.
     */
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
