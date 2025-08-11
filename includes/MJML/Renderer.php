<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;
use RRZE\Newsletter\MJML\StyleProcessor;
use RRZE\Newsletter\MJML\LinkProcessor;
use RRZE\Newsletter\MJML\TemplateRenderer;
use RRZE\Newsletter\MJML\BlockProcessor;

use WP_Post;
use WP_Error;

/**
 * Class Renderer
 *
 * Handles rendering and conversion of post content to MJML for email newsletters.
 * 
 * @package RRZE\Newsletter\MJML
 */
final class Renderer
{
    /** @var array|null Color palette configuration */
    private static $colorPalette = null;

    /** @var string|null Header font */
    private static $fontHeader = null;

    /** @var string|null Body font */
    private static $fontBody = null;

    /** @var string|null Link color */
    private static $linkColor = null;

    /** @var string|null Link text decoration */
    private static $linkTextDecoration = null;

    /** @var array Supported font families */
    private static $supportedFonts = [
        'Arial, Helvetica, sans-serif',
        'Calibri, sans-serif',
        'Tahoma, sans-serif',
        'Trebuchet MS, sans-serif',
        'Verdana, sans-serif',
        'Cambria, serif',
        'Georgia, serif',
        'Palatino, serif',
        'Times New Roman, serif',
        'Courier, monospace',
    ];

    /**
     * Parses a post's content and returns all non-empty blocks.
     *
     * @param WP_Post $post The post object.
     * @return array Array of valid blocks.
     */
    private static function getValidPostBlocks(WP_Post $post): array
    {
        return array_filter(
            parse_blocks($post->post_content),
            fn($block) => !empty($block['blockName'])
        );
    }

    /**
     * Converts post content (string or WP_Post) to MJML components.
     *
     * @param object $post A WP_Post object or dummy object.
     * @param string $content The post content.
     * @param bool $processLinks Whether to process links for tracking, etc.
     * @return string MJML markup.
     */
    private static function postToMjmlComponents(object $post, string $content, bool $processLinks): string
    {
        $postId = ($post instanceof WP_Post) ? $post->ID : 0;
        $mjmlBody = '';

        $validBlocks = array_filter(
            parse_blocks($content),
            fn($block) => !empty($block['blockName'])
        );

        foreach ($validBlocks as $block) {
            $blockContent = '';

            // Convert reusable blocks to group blocks
            if (
                $block['blockName'] === 'core/block' &&
                !empty($block['attrs']['ref'])
            ) {
                $reusableBlockPost = get_post($block['attrs']['ref']);
                if ($reusableBlockPost instanceof WP_Post) {
                    $block['blockName'] = 'core/group';
                    $block['innerBlocks'] = self::getValidPostBlocks($reusableBlockPost);
                    $block['innerHTML'] = $reusableBlockPost->post_content;
                    $block['innerContent'] = $reusableBlockPost->post_content;
                }
            }

            $blockContent = BlockProcessor::renderMjmlComponent($postId, $block);

            $mjmlBody .= $blockContent;
        }

        return $processLinks ? LinkProcessor::processLinks($post, $mjmlBody) : $mjmlBody;
    }

    /**
     * Converts a WP_Post object to final MJML markup.
     *
     * @param WP_Post $post The post.
     * @return string MJML markup.
     */
    public static function fromPost(WP_Post $post): string
    {
        // Load color palette and fonts from post meta.
        self::$colorPalette = json_decode(get_option('rrze_newsletter_color_palette', false), true);

        self::$fontHeader = get_post_meta($post->ID, 'rrze_newsletter_font_header', true);
        if (!in_array(self::$fontHeader, self::$supportedFonts, true)) {
            self::$fontHeader = 'Arial';
        }

        self::$fontBody = get_post_meta($post->ID, 'rrze_newsletter_font_body', true);
        if (!in_array(self::$fontBody, self::$supportedFonts, true)) {
            self::$fontBody = 'Arial';
        }

        self::$linkColor = 'inherit';
        self::$linkTextDecoration = 'underline';

        $previewText = get_post_meta($post->ID, 'rrze_newsletter_preview_text', true) ?: '';
        $backgroundColor = get_post_meta($post->ID, 'rrze_newsletter_background_color', true) ?: '#f0f0f0';

        $data = [
            'title' => $post->post_title,
            'preview_text' => $previewText,
            'background_color' => $backgroundColor,
            'link_color' => self::$linkColor,
            'link_text_decoration' => self::$linkTextDecoration,
            'body' => self::postToMjmlComponents($post, $post->post_content, true)
        ];

        return TemplateRenderer::renderTemplate($data);
    }

    /**
     * Converts an associative array of newsletter data to MJML markup.
     *
     * @param array $args The arguments.
     * @return string MJML markup.
     */
    public static function fromAry(array $args): string
    {
        $defaults = [
            'title' => '',
            'preview_text' => '',
            'background_color' => '#ffffff',
            'content' => ''
        ];
        $args = wp_parse_args($args, $defaults);
        $args = array_intersect_key($args, $defaults);

        self::$fontHeader = 'Arial';
        self::$fontBody = 'Arial';

        $data = [
            'title' => $args['title'],
            'preview_text' => $args['preview_text'],
            'background_color' => $args['background_color'],
            'body' => self::postToMjmlComponents(new \stdClass, $args['content'], false)
        ];

        return TemplateRenderer::renderTemplate($data);
    }

    /**
     * Retrieves already rendered email-compliant HTML for a newsletter post.
     *
     * @param WP_Post $post The post.
     * @return WP_Error|string Email HTML or error.
     */
    public static function retrieveEmailHtml(WP_Post $post)
    {
        $emailHtml = get_post_meta($post->ID, 'rrze_newsletter_email_html', true);
        if (empty($emailHtml)) {
            return new WP_Error(
                'rrze_newsletter_mjml_render_error',
                __('MJML rendering error.', 'rrze-newsletter')
            );
        }
        return $emailHtml;
    }

    /**
     * Get color from the color palette.
     *
     * @param string $colorSlug Color slug from the block attributes.
     * @return string Hex color code or empty string if not found.
     */
    public static function getColorFromPalette(string $colorSlug): string
    {
        return self::$colorPalette[$colorSlug] ?? '';
    }

    /**
     * Get font header from the settings.
     * 
     * @return string Font family for the header.
     */
    public static function getFontHeader(): string
    {
        return self::$fontHeader ?: 'Arial';
    }

    /**
     * Get font body from the settings.
     * 
     * @return string Font family for the body text.
     */
    public static function getFontBody(): string
    {
        return self::$fontBody ?: 'Arial';
    }
}
