<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

final class LayoutHelper
{
    /**
     * Keep only attributes supported by an MJML section.
     *
     * @param array<string, mixed> $attrs Candidate attributes.
     * @return array<string, mixed> Supported section attributes.
     */
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

    /**
     * Subtract horizontal CSS shorthand padding from a container width.
     *
     * @param int    $width   Container width in pixels.
     * @param string $padding CSS padding shorthand.
     * @return int Remaining content width in pixels.
     */
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

    /**
     * Resolve a pixel or percentage width against the available width.
     *
     * @param mixed $width          Candidate width.
     * @param int   $availableWidth Available width in pixels.
     * @return int|null Resolved width in pixels.
     */
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

    /**
     * Resolve a unitless or pixel CSS value to a positive integer.
     *
     * @param mixed $value Candidate value.
     * @return int|null Pixel value, or null when invalid.
     */
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
