<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'newsletter_layout'
 * ------------------------------------------------------------------------- */

namespace RRZE\Newsletter\CPT;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Capabilities;
use function RRZE\Newsletter\plugin;

class NewsletterLayout
{
    const POST_TYPE = 'newsletter_layout';

    public function __construct()
    {
        add_action('init', [__CLASS__, 'registerPostType']);
        add_action('init', [__CLASS__, 'registerMeta']);
    }

    public static function registerPostType()
    {
        $args = [
            'public'              => false,
            'show_in_rest'        => true,
            'supports'            => ['editor', 'title', 'custom-fields'],
            'capability_type'     => Capabilities::getCptCapabilityType(self::POST_TYPE),
            'capabilities'        => (array) Capabilities::getCptCaps(self::POST_TYPE),
            'map_meta_cap'        => Capabilities::getCptMapMetaCap(self::POST_TYPE),
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register custom fields.
     */
    public static function registerMeta()
    {
        register_meta(
            'post',
            'rrze_newsletter_font_header',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_font_body',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_background_color',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_link_color',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_link_text_decoration',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
    }

    public static function layoutTokenReplacement($content, $extra = [])
    {
        $sitename = get_bloginfo('name');
        $customLogoId = get_theme_mod('custom_logo');
        $logo = $customLogoId ? wp_get_attachment_image_src($customLogoId, 'medium')[0] : null;

        $sitenameBlock = sprintf(
            '<!-- wp:heading {"align":"center","level":1} --><h1 class="has-text-align-center">%s</h1><!-- /wp:heading -->',
            $sitename
        );

        $logoBlock = $logo ? sprintf(
            '<!-- wp:image {"align":"center","id":%s,"sizeSlug":"medium"} --><figure class="wp-block-image aligncenter size-medium"><img src="%s" alt="%s" class="wp-image-%s" /></figure><!-- /wp:image -->',
            $customLogoId,
            $logo,
            $sitename,
            $customLogoId
        ) : null;

        $search = array_merge(
            [
                '__SITENAME__',
                '__LOGO__',
                '__LOGO_OR_SITENAME__',
            ],
            array_keys($extra)
        );
        $replace = array_merge(
            [
                $sitename,
                $logo,
                $logo ? $logoBlock : $sitenameBlock,
            ],
            array_values($extra)
        );
        return str_replace($search, $replace, $content);
    }

    public static function getDefaultLayouts()
    {
        $layoutsBasePath = plugin()->getPath('includes/layouts/');
        $layouts = [];
        $layoutId = 1;
        $siteUrl = get_site_url();

        foreach (scandir($layoutsBasePath) as $layout) {
            if (strpos($layout, '.json') !== false) {
                $decodedLayout = json_decode(file_get_contents($layoutsBasePath . $layout, true));
                $postContent = self::layoutTokenReplacement($decodedLayout->content);

                // Replace relative URLs with absolute URLs
                $postContent = preg_replace_callback(
                    '/(href|src)="(\/[^"]*)"/i',
                    function ($matches) use ($siteUrl) {
                        return $matches[1] . '="' . $siteUrl . $matches[2] . '"';
                    },
                    $postContent
                );

                $layouts[] = [
                    'ID'           => $layoutId,
                    'post_title'   => '',
                    'post_content' => $postContent,
                ];
                $layoutId++;
            }
        }
        return $layouts;
    }
}
