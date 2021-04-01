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
        if (!current_user_can('edit_others_posts')) {
            return;
        }

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
        $sitename       = get_bloginfo('name');
        $custom_logo_id = get_theme_mod('custom_logo');
        $logo           = $custom_logo_id ? wp_get_attachment_image_src($custom_logo_id, 'medium')[0] : null;

        $sitename_block = sprintf(
            '<!-- wp:heading {"align":"center","level":1} --><h1 class="has-text-align-center">%s</h1><!-- /wp:heading -->',
            $sitename
        );

        $logo_block = $logo ? sprintf(
            '<!-- wp:image {"align":"center","id":%s,"sizeSlug":"medium"} --><figure class="wp-block-image aligncenter size-medium"><img src="%s" alt="%s" class="wp-image-%s" /></figure><!-- /wp:image -->',
            $custom_logo_id,
            $logo,
            $sitename,
            $custom_logo_id
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
                $logo ? $logo_block : $sitename_block,
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
