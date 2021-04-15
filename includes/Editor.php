<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

final class Editor
{
    protected static $instance = null;

    public static $newsletter_excerpt_length_filter = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('the_post', [__CLASS__, 'stripEditorModifications']);
        add_action('enqueueBlockEditorAssets', [__CLASS__, 'enqueueBlockEditorAssets']);
        add_filter('allowed_block_types', [__CLASS__, 'newsletterAllowedBlockTypes'], 10, 2);
        add_action('rest_post_query', [__CLASS__, 'maybeFilterExcerptLength'], 10, 2);
        add_filter('the_posts', [__CLASS__, 'maybeResetExcerptLength']);
    }

    public static function stripEditorModifications()
    {
        if (!self::is_editing_newsletter()) {
            return;
        }

        $enqueueBlockEditorAssets_filters = $GLOBALS['wp_filter']['enqueueBlockEditorAssets']->callbacks;
        foreach ($enqueueBlockEditorAssets_filters as $index => $filter) {
            $action_handlers = array_keys($filter);
            foreach ($action_handlers as $handler) {
                if (__CLASS__ . '::enqueueBlockEditorAssets' != $handler) {
                    remove_action('enqueueBlockEditorAssets', $handler, $index);
                }
            }
        }

        remove_editor_styles();
        add_theme_support('editor-gradient-presets', array());
        add_theme_support('disable-custom-gradients');
    }

    public static function newsletterAllowedBlockTypes($allowed_block_types, $post)
    {
        if (!self::is_editing_newsletter()) {
            return $allowed_block_types;
        }
        return array(
            'core/rss',
            'core/spacer',
            'core/block',
            'core/group',
            'core/paragraph',
            'core/heading',
            'core/column',
            'core/columns',
            'core/buttons',
            'core/button',
            'core/image',
            'core/separator',
            'core/list',
            'core/quote',
            'core/social-links'
        );
    }

    public static function maybeFilterExcerptLength($args, $request)
    {
        $params = $request->get_params();

        if (isset($params['excerpt_length'])) {
            self::filter_excerpt_length(intval($params['excerpt_length']));
        }

        return $args;
    }

    public static function maybeResetExcerptLength($posts)
    {
        if (self::$newsletter_excerpt_length_filter) {
            self::remove_excerpt_length_filter();
        }

        return $posts;
    }

    public static function filter_excerpt_length($excerpt_length)
    {
        if (is_int($excerpt_length)) {
            self::$newsletter_excerpt_length_filter = add_filter(
                'excerpt_length',
                function () use ($excerpt_length) {
                    return $excerpt_length;
                },
                999
            );
        }
    }

    public static function remove_excerpt_length_filter()
    {
        remove_filter(
            'excerpt_length',
            self::$newsletter_excerpt_length_filter,
            999
        );
    }

    public static function enqueueBlockEditorAssets()
    {
        if (self::is_editing_newsletter()) {
            wp_register_style(
                'rrze-newsletter',
                plugins_url('dist/editor.css', plugin()->getBasename()),
                [],
                filemtime(plugin()->getPath('dist') . 'editor.css')
            );
            wp_style_add_data('rrze-newsletter', 'rtl', 'replace');
            wp_enqueue_style('rrze-newsletter');
        }

        if (!self::is_editing_newsletter()) {
            return;
        }
        wp_enqueue_script(
            'rrze-newsletter',
            plugins_url('dist/editor.js', plugin()->getBasename()),
            [],
            filemtime(plugin()->getPath('dist') . 'editor.js'),
            true
        );
        wp_localize_script(
            'rrze-newsletter',
            'rrze_newsletter_data',
            [
                'is_service_provider_configured' => true,
                'service_provider'               => 'provider',
            ]
        );

        wp_set_script_translations(
            'rrze-newsletter',
            'rrze-newsletter',
            plugin()->getPath('languages')
        );
    }

    /**
     * Is editing a newsletter?
     */
    public static function is_editing_newsletter()
    {
        return Newsletter::POST_TYPE === get_post_type();
    }
}
