<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'newsletter_layout'
 * ------------------------------------------------------------------------- */

namespace RRZE\Newsletter\CPT;

defined('ABSPATH') || exit;

use function RRZE\Newsletter\plugin;

class NewsletterLayout
{
    const POST_TYPE = 'newsletter_layout';

    public function __construct()
    {
        add_action('init', [__CLASS__, 'registerPostType']);
        add_action('init', [__CLASS__, 'register_meta']);
    }

    public static function registerPostType()
    {
        $cpt_args = [
            'public'       => false,
            'show_in_rest' => true,
            'supports'     => ['editor', 'title', 'custom-fields'],
            'taxonomies'   => [],
        ];
        register_post_type(self::POST_TYPE, $cpt_args);
    }

    /**
     * Register custom fields.
     */
    public static function register_meta()
    {
        register_meta(
            'post',
            'font_header',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => true,
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'font_body',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => true,
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'background_color',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => true,
                'type'           => 'string',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
    }

    public static function layout_token_replacement($content, $extra = [])
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

    public static function get_default_layouts()
    {
        $layouts_base_path = plugin()->getPath('includes/layouts/');
        $layouts = [];
        $layout_id = 1;
        foreach (scandir($layouts_base_path) as $layout) {
            if (strpos($layout, '.json') !== false) {
                $decoded_layout  = json_decode(file_get_contents($layouts_base_path . $layout, true));
                $layouts[]      = array(
                    'ID'           => $layout_id,
                    'post_title'   => $decoded_layout->title,
                    'post_content' => self::layout_token_replacement($decoded_layout->content),
                );
                $layout_id++;
            }
        }
        return $layouts;
    }
}
