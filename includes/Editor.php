<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;

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
        add_action('init', [$this, 'disableFeaturedImageSupport']);
        add_action('after_setup_theme', [$this, 'afterSetupTheme']);
        add_action('the_post', [__CLASS__, 'stripEditorModifications']);
        add_action('enqueue_block_editor_assets', [__CLASS__, 'enqueueBlockEditorAssets']);
        add_filter('allowed_block_types_all', [__CLASS__, 'newsletterAllowedBlockTypes']);
        add_action('rest_post_query', [__CLASS__, 'maybeFilterExcerptLength'], 10, 2);
        add_filter('the_posts', [__CLASS__, 'maybeResetExcerptLength']);
        add_action('init', ['\RRZE\Newsletter\Patterns\Patterns', 'registerBlockPatterns']);
    }

    public function disableFeaturedImageSupport()
    {
        remove_post_type_support(Newsletter::POST_TYPE, 'thumbnail');
    }

    public function afterSetupTheme()
    {
        add_filter('wp_theme_json_data_theme', [$this, 'filterThemeJsonTheme']);
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
            'core/image',
            'core/separator',
            'core/list',
            'core/list-item',
            'core/social-links',
            'core/social-link',
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
        if (!self::isEditingNewsletter()) {
            return;
        }

        $assetFile = include plugin()->getPath('build') . 'editor.asset.php';

        wp_register_style(
            'rrze-newsletter',
            plugins_url('build/editor.style.css', plugin()->getBasename()),
            [],
            $assetFile['version'] ?? plugin()->getVersion(),
        );
        wp_style_add_data('rrze-newsletter', 'rtl', 'replace');
        wp_enqueue_style('rrze-newsletter');

        wp_enqueue_script(
            'rrze-newsletter',
            plugins_url('build/editor.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            $assetFile['version'] ?? plugin()->getVersion(),
            true
        );
        wp_localize_script(
            'rrze-newsletter',
            'rrze_newsletter_data',
            [
                'is_service_provider_configured' => true,
                'service_provider' => 'provider',
                'email_html_meta' => 'rrze_newsletter_email_html',
                'mjml_handling_post_types' => [Newsletter::POST_TYPE],
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

    public function filterThemeJsonTheme($themeJson)
    {
        $data = [
            'version'  => 2,
            'settings' => [
                'color' => [
                    'custom' => true,
                    'customDuotone' => false,
                    'defaultDuotone' => false,
                    'customGradient' => false,
                    'defaultGradients' => false,
                    'defaultPalette' => false,
                    'background' => true,
                    'text' => true,
                    'link' => true
                ],
                'spacing' => [
                    'blockGap' => false,
                    'customSpacingSize' => true,
                    'padding' => true,
                    'spacingScale' => [
                        'operator' => '+',
                        'increment' => 20,
                        'steps' => 7,
                        'mediumStep' => 80,
                        'unit' => 'px'
                    ]
                ],
                'layout' => [
                    'contentSize' => '680px',
                ],
                'typography' => [
                    'fontSizes' => [
                        [
                            'name' => 'Small',
                            'slug' => 'small',
                            'size' => '14px'
                        ],
                        [
                            'name' => 'Normal',
                            'slug' => 'normal',
                            'size' => '16px'
                        ],
                        [
                            'name' => 'Medium',
                            'slug' => 'medium',
                            'size' => '22px'
                        ],
                        [
                            'name' => 'Large',
                            'slug' => 'large',
                            'size' => '26px'
                        ],
                        [
                            'name' => 'Extra Large',
                            'slug' => 'x-large',
                            'size' => '30px'
                        ],
                    ],
                    'fontStyle' => false,
                    'fontWeight' => false,
                    'letterSpacing' => false,
                    'lineHeight' => false,
                    'textDecoration' => true,
                    'dropCap' => false,
                ]
            ]
        ];
        return $themeJson->update_with($data);
    }
}
