<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\SocialIcons;

use function RRZE\Newsletter\plugin;

final class SocialLinksProcessor
{
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

    private static function renderElement(
        array $block,
        array $parentAttrs
    ): string {
        $url = $block['attrs']['url'] ?? '';
        $serviceName = $block['attrs']['service'] ?? '';
        if ($url === '' || $serviceName === '') {
            return '';
        }

        $icon = SocialIcons::getIconAttributes($serviceName, $parentAttrs);
        if (empty($icon)) {
            return '';
        }

        $elementAttrs = [
            'href' => $url,
            'src' => plugins_url(
                'assets/social-links/' . $icon['icon'],
                plugin()->getBasename()
            ),
            'background-color' => $icon['color'],
            'css-class' => 'social-element',
            'padding' => '2px',
        ];

        return '<mj-social-element '
            . AttributeHandler::arrayToAttributes($elementAttrs)
            . ' />';
    }

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
