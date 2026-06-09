<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;

final class SeparatorProcessor
{
    /**
     * Render a core/separator block as an MJML divider.
     *
     * @param array<string, mixed> $attrs Block attributes.
     * @return string Rendered MJML divider.
     */
    public static function render(array $attrs): string
    {
        $dividerAttrs = [
            'padding' => '0',
            'border-width' => '1px',
            'width' => self::isWide($attrs) ? '100%' : '128px',
            'border-color' => self::getColor($attrs),
        ];

        return '<mj-divider '
            . AttributeHandler::arrayToAttributes($dividerAttrs)
            . ' />';
    }

    /**
     * Determine whether the separator uses the wide style.
     *
     * @param array<string, mixed> $attrs Block attributes.
     * @return bool True for a wide separator.
     */
    private static function isWide(array $attrs): bool
    {
        return ($attrs['className'] ?? '') === 'is-style-wide';
    }

    /**
     * Resolve the separator color from palette and processed attributes.
     *
     * @param array<string, mixed> $attrs Block attributes.
     * @return string Separator color.
     */
    private static function getColor(array $attrs): string
    {
        $paletteColor = isset($attrs['backgroundColor'])
            ? Renderer::getColorFromPalette($attrs['backgroundColor'])
            : '';

        return $paletteColor
            ?: $attrs['background-color']
            ?? $attrs['border-color']
            ?? $attrs['color']
            ?? '#000000';
    }
}
