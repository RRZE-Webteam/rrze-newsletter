<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

final class Editor
{
    protected static $instance = null;

    public static $newsletterExcerptLengthFilter = null;

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
        add_action('enqueue_block_editor_assets', [__CLASS__, 'enqueueBlockEditorAssets']);
        add_filter('allowed_block_types_all', [__CLASS__, 'newsletterAllowedBlockTypes']);
        add_action('rest_post_query', [__CLASS__, 'maybeFilterExcerptLength'], 10, 2);
        add_filter('the_posts', [__CLASS__, 'maybeResetExcerptLength']);
    }

    public static function stripEditorModifications()
    {
        if (!self::isEditingNewsletter()) {
            return;
        }

        $allowedActions = [
            __CLASS__ . '::enqueueBlockEditorAssets',
            'rrze_newsletter_enqueue_scripts',
            'wp_enqueue_editor_format_library_assets'
        ];
        $enqueueBlockEditorAssetsFilters = $GLOBALS['wp_filter']['enqueue_block_editor_assets']->callbacks;
        foreach ($enqueueBlockEditorAssetsFilters as $index => $filter) {
            $actionHandlers = array_keys($filter);
            foreach ($actionHandlers as $handler) {
                if (!in_array($handler, $allowedActions, true)) {
                    remove_action('enqueue_block_editor_assets', $handler, $index);
                }
            }
        }

        remove_editor_styles();
        add_theme_support('editor-gradient-presets', []);
        add_theme_support('disable-custom-gradients');
    }

    public static function newsletterAllowedBlockTypes($allowedBlockTypes)
    {
        if (!self::isEditingNewsletter()) {
            return $allowedBlockTypes;
        }
        return [
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
            'core/social-link',
            'core/social-links',
            'rrze-newsletter/post-inserter',
            'rrze-newsletter/rss',
            'rrze-newsletter/ics'
        ];
    }

    public static function maybeFilterExcerptLength($args, $request)
    {
        $params = $request->get_params();

        if (isset($params['excerpt_length'])) {
            self::filterExcerptLength(intval($params['excerpt_length']));
        }

        return $args;
    }

    public static function maybeResetExcerptLength($posts)
    {
        if (self::$newsletterExcerptLengthFilter) {
            self::removeExcerptLengthFilter();
        }

        return $posts;
    }

    public static function filterExcerptLength($excerptLength)
    {
        if (is_int($excerptLength)) {
            self::$newsletterExcerptLengthFilter = add_filter(
                'excerpt_length',
                function () use ($excerptLength) {
                    return $excerptLength;
                },
                999
            );
        }
    }

    public static function removeExcerptLengthFilter()
    {
        remove_filter(
            'excerpt_length',
            self::$newsletterExcerptLengthFilter,
            999
        );
    }

    public static function enqueueBlockEditorAssets()
    {
        if (self::isEditingNewsletter()) {
            wp_register_style(
                'rrze-newsletter',
                plugins_url('dist/editor.css', plugin()->getBasename()),
                [],
                filemtime(plugin()->getPath('dist') . 'editor.css')
            );
            wp_style_add_data('rrze-newsletter', 'rtl', 'replace');
            wp_enqueue_style('rrze-newsletter');
        }

        if (!self::isEditingNewsletter()) {
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
    public static function isEditingNewsletter()
    {
        return Newsletter::POST_TYPE === get_post_type();
    }
}
