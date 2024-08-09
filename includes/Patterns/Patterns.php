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
                'title' => _x('Header with logo image', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('Header with a logo image.', 'Pattern description', 'rrze-newsletter'),
            ],
            'header-with-view-in-browser-link' => [
                'title' => _x('Header with view in browser link', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('Header with link tag generator for display in the browser.', 'Pattern description', 'rrze-newsletter'),
            ],
            'section-with-image' => [
                'title' => _x('Section with image and paragraphs', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('A section with an image and paragraphs blocks.', 'Pattern description', 'rrze-newsletter'),
            ],
            'section-with-heading' => [
                'title' => _x('Section with heading, image and paragraphs', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('A section with heading, image and paragraphs blocks.', 'Pattern description', 'rrze-newsletter'),
            ],
            'section-with-heading-list' => [
                'title' => _x('Section with heading and list', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('A section with heading and list blocks.', 'Pattern description', 'rrze-newsletter'),
            ],
            'two-columns-section-with-heading' => [
                'title' => _x('Two columns section with heading', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('Two columns section with heading, images and paragraphs blocks.', 'Pattern description', 'rrze-newsletter'),
            ],
            'footer-with-social-icons' => [
                'title' => _x('Footer with social icons', 'Pattern title', 'rrze-newsletter'),
                'categories' => ['rrze-newsletter'],
                'postTypes' => [Newsletter::POST_TYPE],
                'description' => _x('Footer with social icons and paragraph blocks.', 'Pattern description', 'rrze-newsletter'),
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
