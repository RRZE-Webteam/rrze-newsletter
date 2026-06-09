<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class ImageProcessor
{
    public static function beginRender(): void
    {
        ImageSizeResolver::reset();
    }

    /**
     * Render image block as mj-image and figcaption as mj-text.
     *
     * @param array $attrs The block attributes.
     * @param string $innerHtml The inner HTML of the block.
     * @param string $fontFamily The font family to use for the text.
     * @return string Rendered MJML markup for the image block.
     */
    public static function render(
        array $attrs,
        string $innerHtml,
        string $fontFamily,
        int $availableWidth
    ): string {
        $imageContent = self::parseImageContent($innerHtml);
        if ($imageContent === null) {
            return '';
        }

        $image = $imageContent['image'];
        $imageUrl = self::normalizeImageUrl($image->getAttribute('src'));
        $imgAttrs = self::buildImageAttributes($image, $attrs, $imageUrl);
        $imgAttrs = self::resolveImageDimensions(
            $imgAttrs,
            $attrs,
            $imageContent['size'],
            $imageUrl,
            $availableWidth
        );
        $imgAttrs = self::applyImagePresentationAttributes(
            $imgAttrs,
            $image,
            $attrs
        );
        $markup = '<mj-image '
            . AttributeHandler::arrayToAttributes($imgAttrs)
            . ' />';

        return $markup . self::renderImageCaption(
            $imageContent['caption'],
            $attrs,
            $fontFamily
        );
    }

    /**
     * @return array{image: \DOMElement, caption: \DOMText|null, size: array{0: int, 1: int}|null}|null
     */
    private static function parseImageContent(string $innerHtml): ?array
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . $innerHtml,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXpath($dom);
        $image = $xpath->query('//img')[0];
        if (!$image instanceof \DOMElement) {
            return null;
        }

        $width = LayoutHelper::resolvePixelValue(
            $image->getAttribute('width')
        );
        $height = LayoutHelper::resolvePixelValue(
            $image->getAttribute('height')
        );

        return [
            'image' => $image,
            'caption' => $xpath->query('//figcaption/text()')[0] ?: null,
            'size' => $width && $height ? [$width, $height] : null,
        ];
    }

    private static function normalizeImageUrl(string $imageUrl): string
    {
        $imageUrl = trim($imageUrl);
        if (str_starts_with($imageUrl, '//')) {
            $scheme = wp_parse_url(home_url('/'), PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $imageUrl;
        }
        if ($imageUrl !== '' && !preg_match('#^https?://#i', $imageUrl)) {
            return home_url($imageUrl);
        }

        return $imageUrl;
    }

    private static function buildImageAttributes(
        \DOMElement $image,
        array $attrs,
        string $imageUrl
    ): array {
        $imgAttrs = [
            'padding'         => '0',
            'align'           => $attrs['align'] ?? 'left',
            'fluid-on-mobile' => 'true',
            'src'             => $imageUrl,
        ];

        $alt = trim($image->getAttribute('alt'));
        if ($alt !== '') {
            $imgAttrs['alt'] = $alt;
        }

        $presetWidth = self::getImagePresetWidth($attrs);
        if ($presetWidth !== null) {
            $imgAttrs['width'] = $presetWidth;
        }
        if (isset($attrs['width'])) {
            $imgAttrs['width'] = $attrs['width'];
        }

        $requestedHeight = LayoutHelper::resolvePixelValue(
            $attrs['height'] ?? null
        );
        if ($requestedHeight !== null) {
            $imgAttrs['height'] = $requestedHeight . 'px';
        }

        return $imgAttrs;
    }

    private static function getImagePresetWidth(array $attrs): ?string
    {
        $sizeSlug = $attrs['sizeSlug'] ?? null;
        $className = $attrs['className'] ?? '';

        if ($sizeSlug === 'medium' || str_contains($className, 'size-medium')) {
            return '300px';
        }
        if ($sizeSlug === 'thumbnail' || str_contains($className, 'size-thumbnail')) {
            return '150px';
        }

        return null;
    }

    /**
     * @param array{0: int, 1: int}|null $htmlImageSize
     */
    private static function resolveImageDimensions(
        array $imgAttrs,
        array $attrs,
        ?array $htmlImageSize,
        string $imageUrl,
        int $availableWidth
    ): array {
        $requestedHeight = LayoutHelper::resolvePixelValue(
            $attrs['height'] ?? null
        );
        $requestedWidth = LayoutHelper::resolveWidth(
            $imgAttrs['width'] ?? null,
            $availableWidth
        );

        $imgAttrs = self::normalizeRequestedImageWidth(
            $imgAttrs,
            $requestedWidth,
            $requestedHeight,
            $availableWidth
        );
        $requestedWidth = LayoutHelper::resolveWidth(
            $imgAttrs['width'] ?? null,
            $availableWidth
        );

        $needsImageSize = !isset($imgAttrs['height']) || $requestedWidth === null;
        $imageSize = $needsImageSize
            ? ($htmlImageSize ?? ImageSizeResolver::resolve($imageUrl, $attrs))
            : null;

        if ($requestedHeight !== null && $requestedWidth === null) {
            return self::resolveHeightOnlyImage(
                $imgAttrs,
                $imageSize,
                $requestedHeight,
                $availableWidth
            );
        }
        if ($imageSize && !isset($imgAttrs['height'])) {
            return self::applyImageAspectRatio(
                $imgAttrs,
                $imageSize,
                $requestedWidth,
                $availableWidth
            );
        }

        return $imgAttrs;
    }

    private static function normalizeRequestedImageWidth(
        array $imgAttrs,
        ?int $requestedWidth,
        ?int $requestedHeight,
        int $availableWidth
    ): array {
        if (!isset($imgAttrs['width'])) {
            return $imgAttrs;
        }
        if ($requestedWidth === null) {
            unset($imgAttrs['width']);
            return $imgAttrs;
        }

        $renderedWidth = min($requestedWidth, $availableWidth);
        $imgAttrs['width'] = $renderedWidth . 'px';
        if ($requestedHeight !== null && $renderedWidth < $requestedWidth) {
            $scaledHeight = max(
                1,
                (int) round($requestedHeight * ($renderedWidth / $requestedWidth))
            );
            $imgAttrs['height'] = $scaledHeight . 'px';
        }

        return $imgAttrs;
    }

    /**
     * @param array{0: int, 1: int}|null $imageSize
     */
    private static function resolveHeightOnlyImage(
        array $imgAttrs,
        ?array $imageSize,
        int $requestedHeight,
        int $availableWidth
    ): array {
        if ($imageSize === null) {
            unset($imgAttrs['height']);
            return $imgAttrs;
        }

        [$intrinsicWidth, $intrinsicHeight] = $imageSize;
        $renderedHeight = $requestedHeight;
        $renderedWidth = (int) round(
            $intrinsicWidth * ($renderedHeight / $intrinsicHeight)
        );
        if ($renderedWidth > $availableWidth) {
            $renderedHeight = max(
                1,
                (int) round($renderedHeight * ($availableWidth / $renderedWidth))
            );
            $renderedWidth = $availableWidth;
        }

        $imgAttrs['width'] = $renderedWidth . 'px';
        $imgAttrs['height'] = $renderedHeight . 'px';

        return $imgAttrs;
    }

    /**
     * @param array{0: int, 1: int} $imageSize
     */
    private static function applyImageAspectRatio(
        array $imgAttrs,
        array $imageSize,
        ?int $requestedWidth,
        int $availableWidth
    ): array {
        [$intrinsicWidth, $intrinsicHeight] = $imageSize;
        $renderedWidth = min($requestedWidth ?? $intrinsicWidth, $availableWidth);
        $renderedHeight = (int) round(
            $intrinsicHeight * ($renderedWidth / $intrinsicWidth)
        );

        $imgAttrs['width'] = $renderedWidth . 'px';
        $imgAttrs['height'] = $renderedHeight . 'px';

        return $imgAttrs;
    }

    private static function applyImagePresentationAttributes(
        array $imgAttrs,
        \DOMElement $image,
        array $attrs
    ): array {
        if (isset($attrs['href'])) {
            $imgAttrs['href'] = $attrs['href'];
        } else {
            $parent = $image->parentNode;
            if ($parent instanceof \DOMElement && $parent->nodeName === 'a') {
                $href = trim($parent->getAttribute('href'));
                if ($href !== '') {
                    $imgAttrs['href'] = $href;
                }
            }
        }

        if (
            isset($attrs['className'])
            && str_contains($attrs['className'], 'is-style-rounded')
        ) {
            $imgAttrs['border-radius'] = '999px';
        }

        return $imgAttrs;
    }

    private static function renderImageCaption(
        ?\DOMText $caption,
        array $attrs,
        string $fontFamily
    ): string {
        if ($caption === null) {
            return '';
        }

        $captionAttrs = [
            'align' => 'left',
            'font-size' => '14px',
            'line-height' => '1.4',
            'padding' => '16px 0',
            'font-family' => $fontFamily,
            'color' => $attrs['color'] ?? '#000000',
        ];

        return '<mj-text ' . AttributeHandler::arrayToAttributes($captionAttrs) . '>'
            . $caption->wholeText
            . '</mj-text>';
    }

}
