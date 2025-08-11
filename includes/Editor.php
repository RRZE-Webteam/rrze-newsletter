<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;

/**
 * Class Editor
 * 
 * This class handles the editor enhancements for the RRZE Newsletter plugin.
 * It disables featured image support, modifies allowed block types,
 * and sets up custom styles and scripts for the newsletter editor.
 * 
 * @package RRZE\Newsletter
 */
final class Editor
{
    /**
     * @var Editor|null $instance The singleton instance of the Editor class.
     */
    protected static $instance = null;

    /**
     * @var string|null $newsletterExcerptLengthFilter The filter for the newsletter excerpt length.
     */
    public static $newsletterExcerptLengthFilter = null;

    /**
     * Returns the singleton instance of the Editor class.
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor method.
     * 
     * This method sets up the editor by disabling featured image support,
     * modifying allowed block types, and enqueuing necessary scripts and styles.
     * It also registers block patterns for the newsletter editor.
     * 
     * @return void
     */
    public function __construct()
    {
        // Disable featured image support for the newsletter post type.
        add_action('init', [$this, 'disableFeaturedImageSupport']);

        // After theme setup, filter the theme JSON data for the newsletter editor.
        add_action('after_setup_theme', [$this, 'afterSetupTheme'], 99);

        // Remove editor modifications when viewing a newsletter post.
        add_action('the_post', [__CLASS__, 'removeEditorModifications']);

        // Enqueue block editor assets for the newsletter editor.
        add_action('enqueue_block_editor_assets', [__CLASS__, 'enqueueBlockEditorAssets']);

        // Modify allowed block types for the newsletter editor.
        add_filter('allowed_block_types_all', [__CLASS__, 'newsletterAllowedBlockTypes']);

        // Filter the excerpt length for the newsletter editor.
        add_action('rest_post_query', [__CLASS__, 'maybeFilterExcerptLength'], 10, 2);

        // Reset the excerpt length after the posts are retrieved.
        add_filter('the_posts', [__CLASS__, 'maybeResetExcerptLength']);

        // Register block patterns for the newsletter editor.
        add_action('init', ['\RRZE\Newsletter\Patterns\Patterns', 'registerBlockPatterns']);       
    }

    /**
     * Disables featured image support for the newsletter post type.
     * 
     * This method removes the featured image support from the newsletter post type,
     * ensuring that featured images are not used in newsletters.
     * 
     * @return void
     */
    public function disableFeaturedImageSupport()
    {
        remove_post_type_support(Newsletter::POST_TYPE, 'thumbnail');
    }

    /**
     * After theme setup, filter the theme JSON data for the newsletter editor.
     * This method modifies the theme JSON data to customize the editor styles,
     * colors, and typography settings specifically for the newsletter editor.
     * 
     * @return void
     */
    public function afterSetupTheme()
    {
        add_filter('wp_theme_json_data_theme', [$this, 'filterThemeJsonTheme']);
    }

    /**
     * Removes editor modifications when viewing a newsletter post.
     * 
     * This method checks if the current post type is a newsletter post type,
     * and if so, it removes unnecessary editor modifications such as custom colors,
     * gradients, and editor styles.
     * 
     * @return void
     */
    public static function removeEditorModifications()
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
        add_theme_support('disable-custom-colors');
        add_theme_support('editor-color-palette', []);
        add_theme_support('disable-custom-gradients');
        add_theme_support('editor-gradient-presets', []);
    }

    /**
     * Filters the allowed block types for the newsletter editor.
     * 
     * This method modifies the allowed block types when editing a newsletter post type.
     * It restricts the available blocks to those relevant for newsletters, such as paragraphs,
     * headings, images, and custom blocks like RSS and ICS.
     * 
     * @param array $allowedBlockTypes The existing allowed block types.
     * @return array The modified allowed block types for the newsletter editor.
     */
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

    /**
     * Filters the excerpt length for the newsletter editor.
     * 
     * This method checks if the request has an 'excerpt_length' parameter and applies
     * a filter to change the excerpt length accordingly. It is used to customize the
     * excerpt length for newsletter posts in the REST API.
     * 
     * @param array $args The existing query arguments.
     * @param \WP_REST_Request $request The REST API request object.
     * @return array The modified query arguments with the excerpt length filter applied.
     */
    public static function maybeFilterExcerptLength($args, $request)
    {
        $params = $request->get_params();

        if (isset($params['excerpt_length'])) {
            self::filterExcerptLength(intval($params['excerpt_length']));
        }

        return $args;
    }

    /**
     * Resets the excerpt length after the posts are retrieved.
     * 
     * This method checks if a newsletter excerpt length filter is set and removes it
     * after the posts are retrieved. This ensures that the excerpt length is reset
     * for subsequent queries.
     * 
     * @param array $posts The retrieved posts.
     * @return array The posts with the excerpt length filter removed.
     */
    public static function maybeResetExcerptLength($posts)
    {
        if (self::$newsletterExcerptLengthFilter) {
            self::removeExcerptLengthFilter();
        }

        return $posts;
    }

    /**
     * Filters the excerpt length for the newsletter editor.
     * 
     * This method applies a filter to change the excerpt length for newsletter posts.
     * It is used to customize the excerpt length when displaying newsletters in the editor.
     * 
     * @param int $excerptLength The desired excerpt length.
     * @return void
     */
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

    /**
     * Removes the excerpt length filter for the newsletter editor.
     * 
     * This method removes the previously set excerpt length filter, ensuring that
     * the excerpt length is reset to its default value after processing newsletter posts.
     * 
     * @return void
     */
    public static function removeExcerptLengthFilter()
    {
        remove_filter(
            'excerpt_length',
            self::$newsletterExcerptLengthFilter,
            999
        );
    }

    /**
     * Enqueues block editor assets for the newsletter editor.
     * 
     * This method registers and enqueues the necessary styles and scripts for the
     * newsletter editor in the block editor. It also localizes script data for use
     * in the editor, such as service provider configuration and email HTML meta.
     * 
     * @return void
     */
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
     * Check if the current post type is a newsletter post type.
     *
     * @return bool True if editing a newsletter post type, false otherwise.
     */
    public static function isEditingNewsletter()
    {
        return Newsletter::POST_TYPE === get_post_type();
    }

    /**
     * Returns the color palette for the newsletter editor.
     *
     * @return array The color palette.
     */
    public static function colorPallete()
    {
        return [
            [
                'slug' => 'base',
                'color' => '#000000',
                'name' => __('Base')
            ],
            [
                'slug' => 'contrast',
                'color' => '#ffffff',
                'name' => __('Contrast')
            ],
            [
                'slug' => '#04316a',
                'color' => '#04316a',
                'name' => 'Friedrich-Alexander-Universität Erlangen-Nürnberg (FAU)'
            ],
            [
                'slug' => '#fdb735',
                'color' => '#fdb735',
                'name' => 'Philosophische Fakultät'
            ],
            [
                'slug' => '#c50f3c',
                'color' => '#c50f3c',
                'name' => 'Rechts- und Wirtschaftswissenschaftliche Fakultät'
            ],
            [
                'slug' => '#18b4f1',
                'color' => '#18b4f1',
                'name' => 'Medizinische Fakultät'
            ],
            [
                'slug' => '#7bb725',
                'color' => '#7bb725',
                'name' => 'Naturwissenschaftliche Fakultät'
            ],
            [
                'slug' => '#8C9FB1',
                'color' => '#8C9FB1',
                'name' => 'Technische Fakultät'
            ]
        ];
    }

    /**
     * Filters the theme JSON data for the newsletter editor.
     *
     * @param WP_Theme_JSON_Resolver $themeJson The theme JSON resolver instance.
     * @return WP_Theme_JSON_Resolver The modified theme JSON resolver.
     */
    public function filterThemeJsonTheme($themeJson)
    {
        if (!self::isEditingNewsletter()) {
            return $themeJson;
        }

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
                    'link' => true,
                    'button' => false,
                    'palette' => self::colorPallete()
                ],
                'spacing' => [
                    'blockGap' => false,
                    'customSpacingSize' => true,
                    'padding' => [
                        'individual' => true
                    ],
                    'units' => [
                        'px',
                        'em',
                        'rem',
                        '%'
                    ],
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
                ],
                'blocks' => [
                    'core/group' => [
                        'shadow' => [
                            'defaultPresets' => false
                        ],
                        'color' => [
                            'palette' => self::colorPallete()
                        ],
                        'spacing' => [
                            'padding' => [
                                'individual' => true
                            ]
                        ]
                    ],
                    'core/paragraph' => [
                        'color' => [
                            'palette' => self::colorPallete()
                        ]
                    ],
                    'core/heading' => [
                        'color' => [
                            'palette' => self::colorPallete()
                        ]
                    ],
                    'core/column' => [
                        'color' => [
                            'palette' => self::colorPallete()
                        ]
                    ]
                ]
            ],
            'styles' => [
                'color' => [
                    'text' => '#000000',
                    'background' => 'transparent',
                ],
                'elements' => [
                    'link' => [
                        'color' => [
                            'text'       => '#000000',
                            ':hover'     => '#000000',
                        ]
                    ]
                ]
            ]
        ];
        return $themeJson->update_with($data);
    }
}
