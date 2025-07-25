<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

class SocialIcons
{
    /**
     * Associative array mapping social media service names to their icon colors.
     * The keys are the service names, and the values are the corresponding hex color codes.
     *
     * @var array<string, string>
     */
    private const ICON_COLORS = [
        'bluesky' => '#0a7aff',
        'facebook' => '#1977f2',
        'feed' => '#f0f0f0',
        'github' => '#24292d',
        'instagram' => '#f00075',
        'linkedin' => '#0577b5',
        'mastodon' => '#3288d4',
        'tiktok' => '#000000',
        'tumblr' => '#011835',
        'twitter' => '#21a1f3',
        'wordpress' => '#3499cd',
        'youtube' => '#ff0100',
        'x' => '#000000',
    ];

    /**
     * Returns the icon attributes for a given service name and block attributes.
     *
     * @param string $serviceName The name of the social media service.
     * @param array $blockAttrs The block attributes containing class names.
     * @return array<string, string> An associative array with 'icon' and 'color'.
     */
    public static function getIconAttributes(string $serviceName, array $blockAttrs): array
    {
        if (!isset(self::ICON_COLORS[$serviceName])) {
            return [];
        }

        $color = self::ICON_COLORS[$serviceName];
        $icon = 'white';

        if (isset($blockAttrs['className'])) {
            $icon = self::determineIconVariant($blockAttrs['className'], $serviceName);
            $color = self::determineIconColor($blockAttrs['className'], $color);
        }

        return [
            'icon' => sprintf('%s-%s.png', $icon, $serviceName),
            'color' => $color,
        ];
    }

    /**
     * Determines the icon variant based on the class name and service name.
     *
     * @param string $className The class name from the block attributes.
     * @param string $serviceName The name of the social media service.
     * @return string The icon variant ('black' or 'white').
     */
    private static function determineIconVariant(string $className, string $serviceName): string
    {
        if (
            strpos($className, 'is-style-filled-black') !== false ||
            strpos($className, 'is-style-circle-white') !== false ||
            (strpos($className, 'is-style-default') !== false && $serviceName === 'feed')
        ) {
            return 'black';
        }
        return 'white';
    }

    /**
     * Determines the icon color based on the class name and a default color.
     *
     * @param string $className The class name from the block attributes.
     * @param string $defaultColor The default color to use if no specific style is matched.
     * @return string The determined icon color.
     */
    private static function determineIconColor(string $className, string $defaultColor): string
    {
        if (
            strpos($className, 'is-style-filled-black') !== false ||
            strpos($className, 'is-style-filled-white') !== false ||
            strpos($className, 'is-style-filled-primary-text') !== false
        ) {
            return 'transparent';
        }

        if (strpos($className, 'is-style-circle-black') !== false) {
            return '#000';
        }

        if (strpos($className, 'is-style-circle-white') !== false) {
            return '#fff';
        }

        return $defaultColor;
    }
}
