<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;

final class ButtonProcessor
{
    /**
     * Render the supported button children of a core/buttons block.
     *
     * @param array<string, mixed>              $attrs          Container attributes.
     * @param array<int, array<string, mixed>>  $innerBlocks    Child blocks.
     * @param string                            $fontFamily     Button font family.
     * @param int                               $availableWidth Maximum available width.
     * @return string Rendered MJML button markup.
     */
    public static function renderButtons(
        array $attrs,
        array $innerBlocks,
        string $fontFamily,
        int $availableWidth
    ): string {
        $align = self::getAlignment($attrs);
        $markup = '';

        foreach ($innerBlocks as $buttonBlock) {
            if (($buttonBlock['blockName'] ?? null) !== 'core/button') {
                continue;
            }

            $buttonAttrs = AttributeHandler::processAttributes(
                self::getButtonAttributes(
                    $attrs,
                    $buttonBlock['attrs'] ?? []
                )
            );
            $markup .= self::renderButton(
                $buttonAttrs,
                $buttonBlock['innerHTML'] ?? '',
                $fontFamily,
                $align,
                $availableWidth
            );
        }

        return $markup;
    }

    /**
     * Render a single core/button block.
     *
     * @param array<string, mixed> $attrs          Button attributes.
     * @param string               $innerHtml      Rendered button HTML.
     * @param string               $fontFamily     Button font family.
     * @param string               $align          Horizontal alignment.
     * @param int                  $availableWidth Maximum available width.
     * @return string Rendered MJML button, or an empty string.
     */
    public static function renderButton(
        array $attrs,
        string $innerHtml,
        string $fontFamily,
        string $align = 'left',
        int $availableWidth = Renderer::EMAIL_WIDTH
    ): string {
        $content = self::parseContent($attrs, $innerHtml);
        if ($content['text'] === '') {
            return '';
        }

        $buttonAttrs = self::buildAttributes(
            $attrs,
            $content,
            $fontFamily,
            $align,
            $availableWidth
        );

        return '<mj-button '
            . AttributeHandler::arrayToAttributes($buttonAttrs)
            . '>'
            . $content['text']
            . '</mj-button>';
    }

    /**
     * Inherit typography defaults from a core/buttons container.
     *
     * @param array<string, mixed> $containerAttrs Container attributes.
     * @param array<string, mixed> $buttonAttrs    Child button attributes.
     * @return array<string, mixed> Merged button attributes.
     */
    private static function getButtonAttributes(
        array $containerAttrs,
        array $buttonAttrs
    ): array {
        if (!empty($containerAttrs['style']['typography'])) {
            $buttonAttrs['style']['typography'] = array_merge(
                $containerAttrs['style']['typography'],
                $buttonAttrs['style']['typography'] ?? []
            );
        }

        if (
            empty($buttonAttrs['fontSize'])
            && empty($buttonAttrs['customFontSize'])
            && empty($buttonAttrs['style']['typography']['fontSize'])
            && !empty($containerAttrs['font-size'])
        ) {
            $buttonAttrs['customFontSize'] = (float) $containerAttrs['font-size'];
        }

        return $buttonAttrs;
    }

    /**
     * Resolve a supported horizontal button alignment.
     *
     * @param array<string, mixed> $attrs Block attributes.
     * @return string One of left, center, or right.
     */
    private static function getAlignment(array $attrs): string
    {
        $align = $attrs['layout']['justifyContent']
            ?? $attrs['contentJustification']
            ?? $attrs['align']
            ?? 'left';

        return in_array($align, ['left', 'center', 'right'], true)
            ? $align
            : 'left';
    }

    /**
     * Extract button text and link properties from attributes and HTML.
     *
     * @param array<string, mixed> $attrs     Button attributes.
     * @param string               $innerHtml Rendered button HTML.
     * @return array{text: string, url: string, target: string, rel: string, title: string} Button content.
     */
    private static function parseContent(array $attrs, string $innerHtml): array
    {
        $content = [
            'text' => isset($attrs['text']) ? trim((string) $attrs['text']) : '',
            'url' => isset($attrs['url']) ? trim((string) $attrs['url']) : '',
            'target' => isset($attrs['linkTarget'])
                ? trim((string) $attrs['linkTarget'])
                : '',
            'rel' => isset($attrs['rel']) ? trim((string) $attrs['rel']) : '',
            'title' => isset($attrs['title']) ? trim((string) $attrs['title']) : '',
        ];

        if ($innerHtml === '') {
            return $content;
        }

        $dom = new \DOMDocument();
        $previousErrorMode = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML(
                '<?xml encoding="UTF-8">' . $innerHtml,
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorMode);
        }

        $xpath = new \DOMXPath($dom);
        $element = $xpath->query('//a | //button')[0] ?? null;
        if (!$element instanceof \DOMElement) {
            return $content;
        }

        if ($content['text'] === '') {
            $buttonHtml = '';
            foreach ($element->childNodes as $childNode) {
                $buttonHtml .= $dom->saveHTML($childNode);
            }
            $content['text'] = trim($buttonHtml);
        }

        foreach ([
            'url' => 'href',
            'target' => 'target',
            'rel' => 'rel',
            'title' => 'title',
        ] as $key => $attribute) {
            if ($content[$key] === '') {
                $content[$key] = trim($element->getAttribute($attribute));
            }
        }

        return $content;
    }

    /**
     * Build MJML attributes for a button.
     *
     * @param array<string, mixed> $attrs Button attributes.
     * @param array{text: string, url: string, target: string, rel: string, title: string} $content Button content.
     * @param string $fontFamily Button font family.
     * @param string $align Horizontal alignment.
     * @param int $availableWidth Maximum available width.
     * @return array<string, mixed> MJML button attributes.
     */
    private static function buildAttributes(
        array $attrs,
        array $content,
        string $fontFamily,
        string $align,
        int $availableWidth
    ): array {
        $isOutline = str_contains(
            (string) ($attrs['className'] ?? ''),
            'is-style-outline'
        );
        $textColor = $attrs['color']
            ?? ($isOutline ? '#32373c' : '#ffffff');
        $backgroundColor = $attrs['background-color'] ?? '#32373c';

        $buttonAttrs = [
            'align' => $align,
            'background-color' => $isOutline
                ? 'transparent'
                : $backgroundColor,
            'color' => $textColor,
            'font-family' => $fontFamily,
            'font-size' => $attrs['font-size'] ?? '16px',
            'font-weight' => $attrs['style']['typography']['fontWeight'] ?? 'normal',
            'inner-padding' => self::getInnerPadding($attrs),
            'padding' => '0 0 10px',
            'border-radius' => self::getBorderRadius($attrs),
        ];

        $buttonUrl = self::sanitizeUrl($content['url']);
        if ($buttonUrl !== '') {
            $buttonAttrs['href'] = $buttonUrl;
        }
        foreach (['target', 'rel', 'title'] as $attribute) {
            if ($content[$attribute] !== '') {
                $buttonAttrs[$attribute] = $content[$attribute];
            }
        }

        $border = self::getBorder($attrs, $isOutline, $textColor);
        if ($border !== null) {
            $buttonAttrs['border'] = $border;
        }

        $width = self::getWidth($attrs, $availableWidth);
        if ($width !== null) {
            $buttonAttrs['width'] = $width;
        }

        foreach ([
            'line-height' => 'lineHeight',
            'letter-spacing' => 'letterSpacing',
            'text-decoration' => 'textDecoration',
            'text-transform' => 'textTransform',
        ] as $mjmlAttribute => $styleKey) {
            if (!empty($attrs['style']['typography'][$styleKey])) {
                $buttonAttrs[$mjmlAttribute] =
                    $attrs['style']['typography'][$styleKey];
            }
        }

        return $buttonAttrs;
    }

    /**
     * Sanitize a button URL against supported email link schemes.
     *
     * @param string $url Candidate URL.
     * @return string Sanitized URL, or an empty string.
     */
    private static function sanitizeUrl(string $url): string
    {
        if ($url === '') {
            return '';
        }
        if (!function_exists('esc_url_raw')) {
            return $url;
        }

        return esc_url_raw($url, ['http', 'https', 'mailto', 'tel']);
    }

    /**
     * Resolve WordPress padding values to an MJML inner-padding value.
     *
     * @param array<string, mixed> $attrs Button attributes.
     * @return string CSS padding shorthand.
     */
    private static function getInnerPadding(array $attrs): string
    {
        $padding = $attrs['style']['spacing']['padding'] ?? null;
        if ($padding === null) {
            return '12px 24px';
        }

        if (is_string($padding) || is_numeric($padding)) {
            $value = self::normalizeSpacingValue($padding);
            return $value . ' ' . $value;
        }
        if (!is_array($padding)) {
            return '12px 24px';
        }

        $vertical = $padding['top'] ?? $padding['bottom'] ?? '12px';
        $horizontal = $padding['right'] ?? $padding['left'] ?? '24px';

        return implode(' ', [
            self::normalizeSpacingValue($padding['top'] ?? $vertical),
            self::normalizeSpacingValue($padding['right'] ?? $horizontal),
            self::normalizeSpacingValue($padding['bottom'] ?? $vertical),
            self::normalizeSpacingValue($padding['left'] ?? $horizontal),
        ]);
    }

    /**
     * Normalize a spacing value or WordPress spacing preset.
     *
     * @param mixed $value Spacing value.
     * @return string Supported CSS length, or zero.
     */
    private static function normalizeSpacingValue(mixed $value): string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return '0';
        }

        $value = trim((string) $value);
        if (str_starts_with($value, 'var:preset|spacing|')) {
            $preset = (int) substr($value, strrpos($value, '|') + 1);
            $pixels = max(0, ($preset - 10) * 2);
            return $pixels > 0 ? $pixels . 'px' : '0';
        }

        return preg_match('/^\d+(?:\.\d+)?(?:px|em|rem|%)$/i', $value)
            ? $value
            : '0';
    }

    /**
     * Resolve the button border radius.
     *
     * @param array<string, mixed> $attrs Button attributes.
     * @return string CSS border-radius value.
     */
    private static function getBorderRadius(array $attrs): string
    {
        $radius = $attrs['style']['border']['radius'] ?? null;
        if (is_string($radius) || is_numeric($radius)) {
            return (string) $radius;
        }
        if (is_array($radius) && $radius !== []) {
            $corners = [
                $radius['topLeft'] ?? '0',
                $radius['topRight'] ?? '0',
                $radius['bottomRight'] ?? '0',
                $radius['bottomLeft'] ?? '0',
            ];

            return implode(' ', array_map(
                static fn(mixed $value): string =>
                    is_string($value) || is_numeric($value)
                        ? (string) $value
                        : '0',
                $corners
            ));
        }

        return str_contains(
            (string) ($attrs['className'] ?? ''),
            'no-border-radius'
        ) ? '0' : '4px';
    }

    /**
     * Build the button border declaration.
     *
     * @param array<string, mixed> $attrs     Button attributes.
     * @param bool                 $isOutline Whether the outline style is active.
     * @param string               $textColor Resolved button text color.
     * @return string|null CSS border shorthand, or null when no border applies.
     */
    private static function getBorder(
        array $attrs,
        bool $isOutline,
        string $textColor
    ): ?string {
        $border = $attrs['style']['border'] ?? [];
        $width = $border['width'] ?? ($isOutline ? '2px' : null);
        $style = $border['style'] ?? ($width !== null ? 'solid' : null);
        $color = $border['color']
            ?? $attrs['border-color']
            ?? ($isOutline ? $textColor : null);

        if ($width === null || $style === null || $color === null) {
            return null;
        }

        return trim((string) $width)
            . ' ' . trim((string) $style)
            . ' ' . trim((string) $color);
    }

    /**
     * Convert the block width percentage to pixels.
     *
     * @param array<string, mixed> $attrs          Button attributes.
     * @param int                  $availableWidth Maximum available width.
     * @return string|null Width in pixels, or null when unspecified.
     */
    private static function getWidth(
        array $attrs,
        int $availableWidth
    ): ?string {
        if (!isset($attrs['width']) || !is_numeric($attrs['width'])) {
            return null;
        }

        $percentage = max(1, min(100, (float) $attrs['width']));
        return max(
            1,
            (int) round($availableWidth * $percentage / 100)
        ) . 'px';
    }
}
