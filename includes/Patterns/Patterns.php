<?php

namespace RRZE\Newsletter\Patterns;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

class Patterns
{
    public static function registerBlockPatterns()
    {
        $contentPatterns = self::getContentPatterns();
        if (empty($contentPatterns)) {
            return;
        }

        $availableCategories = self::availableCategories();
        foreach ($availableCategories as $name => $category) {
            register_block_pattern_category(
                $name,
                $category
            );
        }

        $availablePatterns = self::availablePatterns();
        foreach ($availablePatterns as $name => $pattern) {
            if (empty($contentPatterns[$name])) {
                continue;
            }
            $siteUrl = get_site_url();
            // Replace relative URLs with absolute URLs
            $pattern['content'] = preg_replace_callback(
                '/(href|src)="(\/[^"]*)"/i',
                function ($matches) use ($siteUrl) {
                    return $matches[1] . '="' . $siteUrl . $matches[2] . '"';
                },
                $contentPatterns[$name]
            );
            if (!isset($pattern['content'])) {
                continue;
            }

            register_block_pattern(
                $name,
                $pattern
            );
        }
    }

    public static function availableCategories()
    {
        return [
            'rrze-newsletter' => [
                'label' => _x('Newsletter', 'Pattern category label', 'rrze-newsletter'),
            ],
        ];
    }

    public static function availablePatterns()
    {
        return [
            'header-with-logo' => [
                'title' => __('Header with logo', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('Header with a logo image.', 'rrze-newsletter'),
            ],
            'header-with-view-in-browser-link' => [
                'title' => __('Header with view in browser link', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('Header with link tag generator to view in browser.', 'rrze-newsletter'),
            ],
            'section' => [
                'title' => __('Section', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('A section with an image and paragraphs.', 'rrze-newsletter'),
            ],
            'section-with-list' => [
                'title' => __('Section with list', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('A section with a list block.', 'rrze-newsletter'),
            ],
            'two-columns' => [
                'title' => __('Two columns', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('Two columns with images and text.', 'rrze-newsletter'),
            ],
            'footer-with-social-icons' => [
                'title' => __('Footer with social icons', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => __('Footer with social icons and paragraphs.', 'rrze-newsletter'),
            ],
        ];
    }

    public static function getContentPatterns()
    {
        $jsonFilePath = plugin()->getPath('includes/Patterns') . 'patterns.json';

        $jsonContent = file_get_contents($jsonFilePath);

        $patternsArray = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $patternsArray;
        } else {
            return [];
        }
    }
}
