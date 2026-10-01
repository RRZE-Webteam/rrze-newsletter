<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\ManagedSpacing;

/** Converts the core grid layout to email columns, or a linear nested layout. */
final class MediaTextProcessor
{
    public static function render(array $block, array $attrs, string $fontFamily, RenderContext $context): string
    {
        $mediaWidth = is_numeric($attrs['mediaWidth'] ?? null) ? (float) $attrs['mediaWidth'] : 50;
        $mediaWidth = max(15, min(85, $mediaWidth)); // The core block's resize limits.
        $nested = $context->inColumn || $context->inList;
        $mediaPadding = $context->managedSpacing && !$nested ? '0 8px' : '0';
        $mediaPixels = $nested ? $context->availableWidth : (int) round($context->availableWidth * $mediaWidth / 100);
        $media = self::renderMedia($block, $attrs, $fontFamily, $context->postId,
            LayoutHelper::subtractHorizontalPadding($mediaPixels, $mediaPadding));
        if ($context->managedSpacing) {
            $media = ManagedSpacing::spaceComponents($media);
        }

        $textWidth = $media === '' ? 100 : 100 - $mediaWidth;
        $textPixels = $nested ? $context->availableWidth : (int) round($context->availableWidth * $textWidth / 100);
        $textPadding = $nested || $media === '' ? '0' : ($context->managedSpacing ? '0 8px' : '0 ' . (int) round($textPixels * .08) . 'px');
        $textContext = $context->insideColumn()->withAvailableWidth(LayoutHelper::subtractHorizontalPadding($textPixels, $textPadding));
        $defaults = AttributeInheritance::forChild([], $attrs, $context->defaultAttrs);
        if (isset($attrs['font-size'])) {
            $defaults['font-size'] = $attrs['font-size'];
        }
        $defaults['padding'] = '0 0 16px 0';
        $text = '';
        foreach ($block['innerBlocks'] ?? [] as $child) {
            $text .= BlockProcessor::render($child, $textContext->withDefaultAttrs(
                AttributeInheritance::withoutParentLinkColor($defaults, $child)
            ));
        }
        // MJML forbids columns/sections inside a column (including grid cells).
        if ($nested) {
            return $media . $text;
        }
        if ($media === '' && $text === '') {
            return '';
        }

        $alignment = match ($attrs['verticalAlignment'] ?? 'center') {
            'top' => 'top',
            'bottom' => 'bottom',
            default => 'middle',
        };
        $columnAttrs = [
            'vertical-align' => $alignment,
            'background-color' => $attrs['background-color'] ?? $attrs['container-background-color'] ?? null,
        ];
        $markup = '';
        if ($media !== '') {
            $markup .= self::column($media, $columnAttrs + ['width' => $mediaWidth . '%', 'padding' => $mediaPadding, 'css-class' => 'rrze-media-text-media']);
        }
        $markup .= self::column($text, $columnAttrs + ['width' => $textWidth . '%', 'padding' => $textPadding, 'css-class' => 'rrze-media-text-content']);
        if (($attrs['isStackedOnMobile'] ?? true) === false) {
            $direction = ($attrs['mediaPosition'] ?? 'left') === 'right' ? 'rtl' : 'ltr';
            return '<mj-group direction="' . $direction . '">' . $markup . '</mj-group>';
        }
        return $markup;
    }

    private static function column(string $content, array $attrs): string
    {
        return '<mj-column ' . AttributeHandler::arrayToAttributes($attrs) . '>' . $content . '</mj-column>';
    }

    private static function renderMedia(array $block, array $attrs, string $fontFamily, int $postId, int $width): string
    {
        $html = (string) ($block['innerHTML'] ?? '');
        $imageAttrs = [
            'id' => $attrs['mediaId'] ?? 0,
            'width' => '100%',
            'align' => 'left',
            'container-background-color' => $attrs['background-color'] ?? $attrs['container-background-color'] ?? null,
        ];
        if (!empty($attrs['useFeaturedImage'])) {
            $imageAttrs['id'] = get_post_thumbnail_id($postId);
            $image = wp_get_attachment_image_src($imageAttrs['id'], $attrs['mediaSizeSlug'] ?? 'full');
            if (!$image) {
                return '';
            }
            $html = '<img ' . AttributeHandler::arrayToAttributes([
                'src' => $image[0], 'width' => $image[1], 'height' => $image[2],
                'alt' => get_post_meta($imageAttrs['id'], '_wp_attachment_image_alt', true),
            ]) . ' />';
        }
        if (trim($html) === '') {
            return '';
        }
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $video = $dom->getElementsByTagName('video')->item(0);
        if ($video instanceof \DOMElement) {
            // Embedded video is not portable across mail clients. Keep it reachable.
            $url = $video->getAttribute('src');
            if ($url === '') {
                $url = $video->getElementsByTagName('source')->item(0)?->getAttribute('src') ?? '';
            }
            $url = esc_url($url, ['http', 'https']);
            if ($url === '') {
                return '';
            }
            $poster = esc_url($video->getAttribute('poster'), ['http', 'https']);
            $markup = '';
            if ($poster !== '') {
                $imageAttrs['href'] = $url;
                $markup = ImageProcessor::render($imageAttrs, '<img ' . AttributeHandler::arrayToAttributes([
                    'src' => $poster, 'alt' => __('Video'),
                    'width' => $video->getAttribute('width'), 'height' => $video->getAttribute('height'),
                ]) . ' />', $fontFamily, $width);
            }
            return $markup . '<mj-text ' . AttributeHandler::arrayToAttributes([
                'font-family' => $fontFamily, 'font-size' => '16px', 'padding' => '16px 0',
                'container-background-color' => $imageAttrs['container-background-color'],
            ]) . '><a ' . AttributeHandler::arrayToAttributes(['href' => $url,
                'style' => 'color:' . ($attrs['link'] ?? '#000000') . ';']) . '>' . esc_html(__('Video')) . '</a></mj-text>';
        }
        // Images always retain their aspect ratio. CSS cover-cropping cannot be
        // reproduced reliably in Outlook; the editor flags the imageFill option.
        return ImageProcessor::render($imageAttrs, $html, $fontFamily, $width);
    }
}
