<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

/** Output-only spacing policy. Never changes serialized editor blocks. */
final class ManagedSpacing
{
    public static function globallyEnabled(): bool
    {
        $options = get_option('rrze_newsletter', []);
        return is_array($options) && ($options['design_managed_spacing'] ?? 'off') === 'on';
    }

    public static function forPost(int $postId): bool
    {
        return match (get_post_meta($postId, 'rrze_newsletter_spacing_mode', true)) {
            'managed' => true,
            'expert' => false,
            default => self::globallyEnabled(),
        };
    }

    /** Remove manual spacing from a copy, including groups flattened by the grid renderer. */
    public static function normalizeBlock(array $block): array
    {
        unset($block['attrs']['style']['spacing']);
        foreach (['padding', 'margin'] as $property) {
            unset($block['attrs'][$property]);
            foreach (['top', 'right', 'bottom', 'left'] as $side) {
                unset($block['attrs']["$property-$side"]);
            }
        }
        if (($block['blockName'] ?? '') === 'core/spacer') {
            $block['attrs']['height'] = '16px';
        }
        if (isset($block['innerBlocks'])) {
            $children = [];
            foreach ($block['innerBlocks'] as $child) {
                // Adjacent spacers should not create an arbitrarily large gap.
                if (($child['blockName'] ?? '') === 'core/spacer'
                    && $children !== []
                    && ($children[array_key_last($children)]['blockName'] ?? '') === 'core/spacer') {
                    continue;
                }
                $children[] = self::normalizeBlock($child);
            }
            $block['innerBlocks'] = $children;
        }
        return $block;
    }

    /** Only modify generated MJML component attributes, not user text or links. */
    public static function spaceComponents(string $markup): string
    {
        return preg_replace_callback('/<mj-(text|image|button|divider|social)(?=\s|\/?>)[^>]*>/', static function (array $match): string {
            $tag = preg_replace('/\s+padding(?:-(?:top|right|bottom|left))?="[^"]*"/', '', $match[0]);
            $ending = str_ends_with($tag, '/>') ? ' />' : '>';
            return preg_replace('/\s*\/?>$/', ' padding="0 0 16px 0"' . $ending, $tag);
        }, $markup);
    }

    public static function styles(): string
    {
        return '<mj-style inline="inline">'
            . 'p,h1,h2,h3,h4,h5,h6 {margin:0 !important;padding:0 !important;}'
            . 'ul,ol {margin:0 !important;padding:0 0 0 20px !important;}'
            . 'li {margin:0 !important;padding:0 !important;}'
            . '</mj-style><mj-style>'
            . '@media only screen and (max-width:479px) {'
            . '.rrze-managed-spacing>table>tbody>tr>td {padding-left:16px !important;padding-right:16px !important;}'
            . '}</mj-style>';
    }
}
