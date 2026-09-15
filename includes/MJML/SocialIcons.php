<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

/**
 * Class SocialIcons
 * 
 * Provides methods to handle social media icons for MJML rendering.
 * 
 * @package RRZE\Newsletter\MJML
 */
class SocialIcons
{
    /** @return array<string, array{name: string, color: string, defaultIcon: string}> */
    public static function getServices(): array
    {
        static $services;
        if ($services === null) {
            $services = json_decode(file_get_contents(__DIR__ . '/../../assets/social-links/services.json'), true, 512, JSON_THROW_ON_ERROR);
        }
        return $services;
    }

    /**
     * Returns the icon attributes for a given service name and block attributes.
     *
     * @param string $serviceName The name of the social media service.
     * @param array $blockAttrs The block attributes containing class names.
     * @return array<string, string> An associative array with 'icon' and 'color'.
     */
    public static function getIconAttributes(string $serviceName, array $blockAttrs): array
    {
        $service = self::getServices()[$serviceName] ?? null;
        if ($service === null) {
            return [];
        }

        $color = $service['color'];
        $icon = $service['defaultIcon'];

        if (isset($blockAttrs['className'])) {
            $icon = self::determineIconVariant($blockAttrs['className'], $icon);
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
     * @param string $defaultIcon The default variant for the service background.
     * @return string The icon variant ('black' or 'white').
     */
    private static function determineIconVariant(string $className, string $defaultIcon): string
    {
        if (
            strpos($className, 'is-style-filled-black') !== false ||
            strpos($className, 'is-style-circle-white') !== false
        ) {
            return 'black';
        }
        if (strpos($className, 'is-style-circle-black') !== false ||
            strpos($className, 'is-style-filled-white') !== false ||
            strpos($className, 'is-style-filled-primary-text') !== false) {
            return 'white';
        }
        return $defaultIcon;
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
