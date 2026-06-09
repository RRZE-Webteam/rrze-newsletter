<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\MJML\SocialIcons;

use function RRZE\Newsletter\plugin;

final class BasicBlockProcessor
{
    public static function renderSeparator(array $attrs, array &$sectionAttrs): string
    {
        $isWide = ($attrs['className'] ?? '') === 'is-style-wide';
        $dividerAttrs = [
            'padding' => '0',
            'border-width' => '1px',
            'width' => $isWide ? '100%' : '128px',
        ];
        unset($sectionAttrs['background-color']);

        if (
            isset($attrs['backgroundColor'])
            && Renderer::getColorFromPalette($attrs['backgroundColor'])
        ) {
            $dividerAttrs['border-color'] = Renderer::getColorFromPalette(
                $attrs['backgroundColor']
            );
        } else {
            $dividerAttrs['border-color'] = $attrs['background-color']
                ?? $attrs['border-color']
                ?? $attrs['color']
                ?? '#000000';
        }

        return '<mj-divider '
            . AttributeHandler::arrayToAttributes($dividerAttrs)
            . ' />';
    }

    public static function renderSpacer(array $attrs): string
    {
        $heightParts = explode('|', (string) ($attrs['height'] ?? '0'));
        $spacerAttrs = [
            'height' => absint(end($heightParts)) . 'px',
        ];

        return '<mj-spacer '
            . AttributeHandler::arrayToAttributes($spacerAttrs)
            . ' />';
    }

    public static function renderSocialLinks(
        array $attrs,
        array $innerBlocks
    ): string {
        $socialAttrs = [
            'icon-size' => '24px',
            'mode' => 'horizontal',
            'padding' => '0',
            'border-radius' => '999px',
            'icon-padding' => '7px',
            'align' => $attrs['align'] ?? 'left',
        ];
        $markup = '<mj-social '
            . AttributeHandler::arrayToAttributes($socialAttrs)
            . '>';

        foreach ($innerBlocks as $linkBlock) {
            $url = $linkBlock['attrs']['url'] ?? '';
            $serviceName = $linkBlock['attrs']['service'] ?? '';
            if ($url === '' || $serviceName === '') {
                continue;
            }

            $socialIcon = SocialIcons::getIconAttributes($serviceName, $attrs);
            if (empty($socialIcon)) {
                continue;
            }

            $elementAttrs = [
                'href' => $url,
                'src' => plugins_url(
                    'assets/social-links/' . $socialIcon['icon'],
                    plugin()->getBasename()
                ),
                'background-color' => $socialIcon['color'],
                'css-class' => 'social-element',
                'padding' => '2px',
            ];
            $markup .= '<mj-social-element '
                . AttributeHandler::arrayToAttributes($elementAttrs)
                . ' />';
        }

        return $markup . '</mj-social>';
    }
}
