<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Renderer;

use WP_Post;
use WP_Theme_JSON_Resolver;

class StyleProcessor
{
    /**
     * Get font size based on block attributes.
     *
     * @param array $blockAttrs Block attributes.
     * @return string|null Font size in pixels or null if not set.
     */
    public static function getFontSize(array $blockAttrs): ?string
    {
        if (isset($blockAttrs['customFontSize'])) {
            return $blockAttrs['customFontSize'] . 'px';
        }

        if (isset($blockAttrs['fontSize'])) {
            $sizes = [
                'small' => '14px',
                'normal' => '16px',
                'medium' => '22px',
                'large' => '26px',
                'huge' => '30px',
            ];
            return $sizes[$blockAttrs['fontSize']] ?? null;
        }

        return null;
    }

    /**
     * Get colors based on block attributes.
     *
     * @param array $blockAttrs Block attributes.
     * @return array Array of color attributes for MJML component.
     */
    public static function getColors(array $blockAttrs): array
    {
        $colors = [
            'color'            => self::getTextColor($blockAttrs),
            'background-color' => self::getBackgroundColor($blockAttrs),
            'border-color'     => self::getBorderColor($blockAttrs),
            'link'             => self::getLinkColor($blockAttrs),
        ];

        foreach ($colors as $k => $v) {
            if ($v && strpos($v, '!important') === false) {
                $colors[$k] .= ' !important';
            }
        }

        if (isset($colors['link']) && strpos($colors['link'], 'var(') === 0) {
            $colors['link'] = self::extractFallbackCssVar($colors['link']);
        }

        return array_filter($colors);
    }

    /**
     * Extracts and formats padding values from block attributes for use in MJML.
     *
     * @param array $attributes Block attributes.
     * @return string Padding values as a string, e.g. "20px 10px 20px 10px". Returns an empty string if all are zero.
     */
    public static function getPaddingFromAttributes(array $attributes): string
    {
        $sides = ['top', 'right', 'bottom', 'left'];
        $padding = [];

        // Extract and process each padding side value
        foreach ($sides as $side) {
            $value = $attributes['style']['spacing']['padding'][$side] ?? 0;
            $padding[$side] = self::normalizePaddingValue($value);
        }

        // Return empty string if all values are zero
        if (self::allValuesAreZero($padding)) {
            return '';
        }

        return implode(' ', $padding);
    }

    /**
     * Applies the link color to the HTML content of a block.
     *
     * @param mixed $block The block object.
     * @param array $attrs Block attributes.
     * @param string $html The HTML content of the block.
     * @return string HTML with the link color applied.
     */
    public static function applyLinkColor($block, $attrs, $html)
    {
        if (!empty($attrs['link'])) {
            $html = preg_replace(
                '/<a([^>]*?)>/i',
                '<a$1 style="color: ' . esc_attr($attrs['link']) . ' !important;">',
                $html
            );
        }
        return $html;
    }

    /**
     * Normalizes a padding value: processes a string with pipes or returns '0'.
     *
     * @param mixed $value The original padding value.
     * @return string Normalized padding value (e.g., "20px" or "0").
     */
    private static function normalizePaddingValue($value): string
    {
        if ($value === 0 || $value === '0' || $value === null) {
            return '0';
        }
        $parts = explode('|', (string) $value);
        $raw = absint(end($parts));
        $val = ($raw - 10) * 2;
        return $val > 0 ? "{$val}px" : '0';
    }

    /**
     * Checks if all values in an array are zero (as int).
     *
     * @param array $array Input array.
     * @return bool True if all values are zero, false otherwise.
     */
    private static function allValuesAreZero(array $array): bool
    {
        // Convert all values to int and filter out non-zero
        return count(array_filter($array, fn($v) => (int)$v !== 0)) === 0;
    }

    /**
     * Get text color from block attributes.
     *
     * @param array $attrs Block attributes.
     * @return string|null Text color in hex format or null if not set.
     */
    private static function getTextColor(array $attrs): ?string
    {
        if (!empty($attrs['customTextColor'])) {
            return $attrs['customTextColor'];
        }
        if (!empty($attrs['textColor']) && Renderer::getColorFromPalette($attrs['textColor'])) {
            return Renderer::getColorFromPalette($attrs['textColor']);
        }
        if (!empty($attrs['style']['color']['text'])) {
            return $attrs['style']['color']['text'];
        }
        if (!empty($attrs['color'])) {
            return $attrs['color'];
        }
        return null;
    }

    /**
     * Get background color from block attributes.
     *
     * @param array $attrs Block attributes.
     * @return string|null Background color in hex format or null if not set.
     */
    private static function getBackgroundColor(array $attrs): ?string
    {
        if (!empty($attrs['customBackgroundColor'])) {
            return $attrs['customBackgroundColor'];
        }
        if (!empty($attrs['backgroundColor']) && Renderer::getColorFromPalette($attrs['backgroundColor'])) {
            return Renderer::getColorFromPalette($attrs['backgroundColor']);
        }
        if (!empty($attrs['style']['color']['background'])) {
            return $attrs['style']['color']['background'];
        }
        if (!empty($attrs['background-color'])) {
            return $attrs['background-color'];
        }
        return null;
    }

    /**
     * Get border color from block attributes.
     *
     * @param array $attrs Block attributes.
     * @return string|null Border color in hex format or null if not set.
     */
    private static function getBorderColor(array $attrs): ?string
    {
        if (!empty($attrs['customColor'])) {
            return $attrs['customColor'];
        }
        if (!empty($attrs['color']) && Renderer::getColorFromPalette($attrs['color'])) {
            return Renderer::getColorFromPalette($attrs['color']);
        }
        if (!empty($attrs['style']['color']['border'])) {
            return $attrs['style']['color']['border'];
        }
        if (!empty($attrs['border-color'])) {
            return $attrs['border-color'];
        }
        return null;
    }

    /**
     * Processes all links in the given HTML content of a post.
     *
     * @param WP_Post $post WP_Post object.
     * @param string $html Input HTML.
     * @return string HTML with processed links.
     */
    private static function getLinkColor(array $attrs): ?string
    {
        if (!empty($attrs['style']['elements']['link']['color']['text'])) {
            $linkColor = $attrs['style']['elements']['link']['color']['text'];
            if (strpos($linkColor, 'var:preset|color|') === 0) {
                $colorName = str_replace('var:preset|color|', '', $linkColor);
                $themeJson = WP_Theme_JSON_Resolver::get_theme_data();
                $colorPalette = $themeJson->get_settings()['color']['palette']['theme'] ?? [];
                foreach ($colorPalette as $color) {
                    if (isset($color['slug']) && $color['slug'] === $colorName) {
                        return $color['color'];
                    }
                }
                return '#000000';
            }
            return $linkColor;
        }
        if (!empty($attrs['link'])) {
            return $attrs['link'];
        }
        return null;
    }

    /**
     * Extracts the fallback color from a CSS variable string.
     *
     * @param string $cssVar The CSS variable string.
     * @return string The fallback color in hex format.
     */
    private static function extractFallbackCssVar(string $cssVar): string
    {
        if (preg_match('/, ?(#\w{3,6})\)/', $cssVar, $m)) {
            return $m[1];
        }
        return $cssVar;
    }
}
