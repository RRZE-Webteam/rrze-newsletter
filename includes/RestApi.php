<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\Mail\Send;

class RestApi
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'restApiInit']);
    }

    public function restApiInit()
    {
        register_rest_route(
            'rrze-newsletter/v1',
            'email/(?P<id>[\a-z]+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'api_retrieve'],
                'permission_callback' => [$this, 'api_authoring_permissions_check'],
                'args'                => [
                    'id' => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validate_newsletter_id'],
                    ],
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'email/(?P<id>[\a-z]+)/test',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'api_test'],
                'permission_callback' => [$this, 'api_authoring_permissions_check'],
                'args'                => [
                    'id'         => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validate_newsletter_id'],
                    ],
                    'test_email' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'email/(?P<id>[\a-z]+)/sender',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'api_sender'],
                'permission_callback' => [$this, 'api_authoring_permissions_check'],
                'args'                => [
                    'id'        => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validate_newsletter_id'],
                    ],
                    'from_name' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'replyto'  => [
                        'sanitize_callback' => 'sanitize_email',
                    ],
                ],
            ]
        );

        register_rest_route(
            'rrze-newsletter/v1',
            'layouts',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'api_get_layouts'],
                'permission_callback' => [$this, 'api_authoring_permissions_check'],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'post-meta/(?P<id>[\a-z]+)',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'api_set_post_meta'],
                'permission_callback' => [$this, 'api_administration_permissions_check'],
                'args'                => [
                    'id'    => [
                        'validate_callback' => [$this, 'validate_newsletter_id'],
                        'sanitize_callback' => 'absint',
                    ],
                    'key'   => [
                        'validate_callback' => [$this, 'validate_newsletter_post_meta_key'],
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'value' => [
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'color-palette',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'api_set_color_palette'],
                'permission_callback' => [$this, 'api_administration_permissions_check'],
            ]
        );
    }

    public function validate_newsletter_id($id)
    {
        return Newsletter::POST_TYPE === get_post_type($id);
    }

    public function api_get_layouts()
    {
        $layouts_query = new \WP_Query(
            [
                'post_type'      => NewsletterLayout::POST_TYPE,
                'posts_per_page' => -1,
            ]
        );
        $user_layouts  = array_map(
            function ($post) {
                $post->meta = [
                    'background_color' => get_post_meta($post->ID, 'background_color', true),
                    'font_body'        => get_post_meta($post->ID, 'font_body', true),
                    'font_header'      => get_post_meta($post->ID, 'font_header', true),
                ];
                return $post;
            },
            $layouts_query->get_posts()
        );
        $layouts = array_merge(
            $user_layouts,
            NewsletterLayout::get_default_layouts(),
            apply_filters('rrze_newsletter_templates', [])
        );

        return rest_ensure_response($layouts);
    }

    public function api_retrieve($request)
    {
        $response = json_encode($request['id']);
        return rest_ensure_response($response);
    }

    public function api_authoring_permissions_check($request)
    {
        if (!current_user_can('edit_others_posts')) {
            return new \WP_Error(
                'rrze_newsletter_rest_forbidden',
                esc_html__('You cannot use this resource.', 'rrze-newsletter'),
                [
                    'status' => 403,
                ]
            );
        }
        return true;
    }

    public function api_administration_permissions_check($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error(
                'rrze_newsletter_rest_forbidden',
                esc_html__('You cannot use this resource.', 'rrze-newsletter'),
                [
                    'status' => 403,
                ]
            );
        }
        return true;
    }

    public function api_sender($request)
    {
        $response = $this->sender(
            $request['id'],
            $request['from_name'],
            $request['replyto']
        );
        return rest_ensure_response($response);
    }

    public function sender($post_id, $from_name, $reply_to)
    {
        $data           = [];
        $data['result'] = [];

        return rest_ensure_response($data);
    }

    public function api_test($request)
    {
        $emails = explode(',', $request['test_email']);
        foreach ($emails as &$email) {
            $email = sanitize_email(trim($email));
        }
        $response = $this->test(
            $request['id'],
            $emails
        );
        return rest_ensure_response($response);
    }

    public function test($post_id, $emails)
    {
        $args = [
            'from' => 'newsletter@localhost',
            'fromName' => 'Newsletter',
            'replyTo' => 'newsletter@localhost',
            'to' => implode(', ', $emails)
        ];
        $post = get_post($post_id);
        $send = new Send;
        $message = $send->set($post, $args);

        $data = [];
        $data['result']  = [];
        $data['message'] = $message;
        return $data;
    }

    public function api_set_post_meta($request)
    {
        $id = $request['id'];
        $key = $request['key'];
        $value = $request['value'];
        update_post_meta($id, $key, $value);
        return [];
    }

    public function validate_newsletter_post_meta_key($key)
    {
        return in_array(
            $key,
            [
                'rrze_newsletter_from_name',
                'rrze_newsletter_from_email',
                'rrze_newsletter_replyto',
                'rrze_newsletter_font_header',
                'rrze_newsletter_font_body',
                'rrze_newsletter_background_color',
                'rrze_newsletter_preview_text'
            ]
        );
    }

    public function api_set_color_palette($request)
    {
        update_option('rrze_newsletter_color_palette', $request->get_body());
        return rest_ensure_response([]);
    }
}
