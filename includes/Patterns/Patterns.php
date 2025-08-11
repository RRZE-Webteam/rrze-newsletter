<?php

namespace RRZE\Newsletter\Patterns;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

/**
 * Class Patterns
 * 
 * Handles the registration of block patterns for the RRZE Newsletter plugin.
 *
 * @package RRZE\Newsletter\Patterns
 */
class Patterns
{
    /**
     * Registers block patterns for the RRZE Newsletter plugin.
     *
     * This method retrieves content patterns from a JSON file, registers block pattern categories,
     * and registers individual block patterns with their respective content.
     * It ensures that relative URLs in the patterns are replaced with absolute URLs.
     *
     * @return void
     */
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

    /**
     * Returns an array of available block pattern categories.
     *
     * Each category is represented by an associative array with a 'label' key.
     *
     * @return array An array of available block pattern categories.
     */
    public static function availableCategories()
    {
        return [
            'rrze-newsletter' => [
                'label' => _x('Newsletter', 'Pattern category label', 'rrze-newsletter'),
            ],
        ];
    }

    /**
     * Returns an array of available block patterns.
     *
     * Each pattern is represented by an associative array with keys such as 'title', 'categories',
     * 'postTypes', and 'description'.
     *
     * @return array An array of available block patterns.
     */
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

    /**
     * Retrieves content patterns from a JSON file.
     *
     * The JSON file is expected to be located in the 'includes/Patterns' directory of the plugin.
     * If the file cannot be read or if there is a JSON decoding error, an empty array is returned.
     *
     * @return array An associative array of content patterns.
     */
    public static function getContentPatterns()
    {
        $jsonFilePath = plugin()->getPath('includes/Patterns') . 'patterns.json';

        $jsonContent = file_get_contents($jsonFilePath);
        if ($jsonContent === false) {
            return [];
        }

        $patternsArray = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $patternsArray;
        } else {
            return [];
        }
    }
}
