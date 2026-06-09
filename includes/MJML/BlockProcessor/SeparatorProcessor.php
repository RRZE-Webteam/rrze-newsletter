<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;

final class SeparatorProcessor
{
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

    private static function isWide(array $attrs): bool
    {
        return ($attrs['className'] ?? '') === 'is-style-wide';
    }

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
