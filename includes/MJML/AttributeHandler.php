<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

/**
 * Class AttributeHandler
 * 
 * Handles processing of block attributes for MJML rendering.
 * 
 * @package RRZE\Newsletter\MJML
 */
class AttributeHandler
{
    /**
     * Process block attributes for MJML
     *
     * @param array $attrs Block attributes
     * @return array Processed attributes
     */
    public static function processAttributes(array $attrs): array
    {
        $colors = StyleProcessor::getColors($attrs);

        // Merge colors without overriding existing values
        foreach ($colors as $key => $value) {
            if (empty($attrs[$key])) {
                $attrs[$key] = $value;
            }
        }

        // Set font size
        if ($fontSize = StyleProcessor::getFontSize($attrs)) {
            $attrs['font-size'] = $fontSize;
        }

        // Clean up unused attributes
        $attrs = self::cleanAttributes($attrs);

        // Handle full-width alignment
        if (isset($attrs['align']) && $attrs['align'] === 'full') {
            $attrs['full-width'] = 'full-width';
            unset($attrs['align']);
        }

        return $attrs;
    }

    /**
     * Convert array of attributes to HTML string
     * 
     * @param array $attrs Associative array of attributes
     * @return string HTML attributes string
     */
    public static function arrayToAttributes(array $attrs): string
    {
        return implode(' ', array_filter(array_map(
            fn($key) => isset($attrs[$key]) && (is_string($attrs[$key]) || is_numeric($attrs[$key]))
                ? $key . '="' . esc_attr($attrs[$key]) . '"'
                : '',
            array_keys($attrs)
        )));
    }

    /**
     * Clean up attributes by removing unnecessary keys.
     *
     * @param array $attrs Block attributes.
     * @return array Cleaned attributes.
     */
    private static function cleanAttributes(array $attrs): array
    {
        $removeKeys = [
            'customBackgroundColor',
            'customTextColor',
            'customFontSize',
            'fontSize',
            'backgroundColor'
        ];

        foreach ($removeKeys as $key) {
            unset($attrs[$key]);
        }

        return $attrs;
    }
}
