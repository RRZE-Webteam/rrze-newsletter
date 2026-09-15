<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\SocialIcons;

use function RRZE\Newsletter\plugin;

final class SocialLinksProcessor
{
    /**
     * Render a core/social-links block and its supported children.
     *
     * @param array<string, mixed>             $attrs       Container attributes.
     * @param array<int, array<string, mixed>> $innerBlocks Social link blocks.
     * @return string Rendered MJML social markup.
     */
    public static function render(
        array $attrs,
        array $innerBlocks
    ): string {
        $markup = '<mj-social '
            . AttributeHandler::arrayToAttributes(
                self::getContainerAttributes($attrs)
            )
            . '>';

        foreach ($innerBlocks as $linkBlock) {
            $element = self::renderElement($linkBlock, $attrs);
            if ($element !== '') {
                $markup .= $element;
            }
        }

        return $markup . '</mj-social>';
    }

    /**
     * Render a single social link element.
     *
     * @param array<string, mixed> $block       Social link block.
     * @param array<string, mixed> $parentAttrs Container attributes.
     * @return string Rendered MJML element, or an empty string.
     */
    private static function renderElement(
        array $block,
        array $parentAttrs
    ): string {
        if (($block['blockName'] ?? '') !== 'core/social-link') {
            return '';
        }
        $attrs = $block['attrs'] ?? [];
        $url = $attrs['url'] ?? '';
        $serviceName = is_string($attrs['service'] ?? null) ? $attrs['service'] : '';
        if (!is_string($url) || trim($url) === '') {
            return '';
        }
        // Never turn executable URLs into clickable fallback links. Keep normal
        // web links, relative links and the mail/telephone services supported.
        $scheme = parse_url(preg_replace('/[\x00-\x20\x7f]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8')), PHP_URL_SCHEME);
        if ($scheme !== null && (!is_string($scheme) || !in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true))) {
            return '';
        }

        $icon = SocialIcons::getIconAttributes($serviceName, $parentAttrs);
        $fallback = empty($icon);
        // A future WordPress or third-party service must not silently lose its URL.
        $icon = $fallback ? SocialIcons::getIconAttributes('chain', $parentAttrs) : $icon;
        $label = is_string($attrs['label'] ?? null) ? trim($attrs['label']) : '';
        $label = $label !== '' ? $label : (SocialIcons::getServices()[$serviceName]['name'] ?? ($serviceName !== '' ? $serviceName : 'Link'));

        $elementAttrs = [
            'href' => $url,
            'src' => plugins_url(
                'assets/social-links/' . $icon['icon'],
                plugin()->getBasename()
            ),
            'background-color' => $icon['color'],
            'css-class' => 'social-element',
            'padding' => '2px',
            'alt' => $label,
            'title' => $label,
        ];

        return '<mj-social-element '
            . AttributeHandler::arrayToAttributes($elementAttrs)
            . '>'
            . (($fallback || !empty($parentAttrs['showLabels'])) ? htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '')
            . '</mj-social-element>';
    }

    /**
     * Build MJML attributes for the social links container.
     *
     * @param array<string, mixed> $attrs Container attributes.
     * @return array<string, mixed> MJML social attributes.
     */
    private static function getContainerAttributes(array $attrs): array
    {
        return [
            'icon-size' => '24px',
            'mode' => 'horizontal',
            'padding' => '0',
            'border-radius' => '999px',
            'icon-padding' => '7px',
            'align' => $attrs['align'] ?? 'left',
        ];
    }
}
