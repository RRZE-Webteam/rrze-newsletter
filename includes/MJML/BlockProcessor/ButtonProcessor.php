<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;

final class ButtonProcessor
{
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
     * @return array{text: string, url: string, target: string, rel: string, title: string}
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
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $innerHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

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
     * @param array{text: string, url: string, target: string, rel: string, title: string} $content
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

    private static function getBorderRadius(array $attrs): string
    {
        $radius = $attrs['style']['border']['radius'] ?? null;
        if (is_string($radius) || is_numeric($radius)) {
            return (string) $radius;
        }

        return str_contains(
            (string) ($attrs['className'] ?? ''),
            'no-border-radius'
        ) ? '0' : '4px';
    }

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
