<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

final class LayoutHelper
{
    public static function filterSectionAttributes(array $attrs): array
    {
        $allowed = [
            'background-color',
            'background-url',
            'background-repeat',
            'background-size',
            'border',
            'border-bottom',
            'border-left',
            'border-radius',
            'border-right',
            'border-top',
            'direction',
            'full-width',
            'padding',
            'padding-bottom',
            'padding-left',
            'padding-right',
            'padding-top',
            'text-align',
        ];

        return array_intersect_key($attrs, array_flip($allowed));
    }

    public static function subtractHorizontalPadding(int $width, string $padding): int
    {
        $parts = preg_split('/\s+/', trim($padding)) ?: [];
        if (count($parts) === 1) {
            $right = $left = $parts[0];
        } elseif (count($parts) === 2 || count($parts) === 3) {
            $right = $left = $parts[1];
        } elseif (count($parts) >= 4) {
            $right = $parts[1];
            $left = $parts[3];
        } else {
            return $width;
        }

        return max(
            1,
            $width
                - (self::resolveWidth($left, $width) ?? 0)
                - (self::resolveWidth($right, $width) ?? 0)
        );
    }

    public static function resolveWidth(mixed $width, int $availableWidth): ?int
    {
        if (!is_string($width) && !is_numeric($width)) {
            return null;
        }

        $width = trim((string) $width);
        if (str_ends_with($width, '%')) {
            $percentage = (float) rtrim($width, '%');
            return $percentage > 0
                ? max(1, (int) round($availableWidth * $percentage / 100))
                : null;
        }

        return self::resolvePixelValue($width);
    }

    public static function resolvePixelValue(mixed $value): ?int
    {
        if (
            (!is_string($value) && !is_numeric($value))
            || !preg_match('/^\d+(?:\.\d+)?(?:px)?$/', trim((string) $value))
        ) {
            return null;
        }

        $pixels = (int) round((float) $value);
        return $pixels > 0 ? $pixels : null;
    }
}
