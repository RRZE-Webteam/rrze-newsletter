<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\Mail\Send;
use RRZE\Newsletter\MJML\Render;

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
                'callback'            => [$this, 'apiRetrieve'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'id' => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validateNewsletterId'],
                    ],
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'email/(?P<id>[\a-z]+)/test',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'apiTest'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'id'         => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validateNewsletterId'],
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
                'callback'            => [$this, 'apiSender'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'id'        => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validateNewsletterId'],
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
                'callback'            => [$this, 'apiGetLayouts'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'post-meta/(?P<id>[\a-z]+)',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'apiSetPostMeta'],
                'permission_callback' => [$this, 'apiAdministrationPermissionsCheck'],
                'args'                => [
                    'id'    => [
                        'validate_callback' => [$this, 'validateNewsletterId'],
                        'sanitize_callback' => 'absint',
                    ],
                    'key'   => [
                        'validate_callback' => [$this, 'validateNewsletterPostMetaKey'],
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
                'callback'            => [$this, 'apiSetColorPalette'],
                'permission_callback' => [$this, 'apiAdministrationPermissionsCheck'],
            ]
        );
    }

    public function validateNewsletterId($id)
    {
        return Newsletter::POST_TYPE === get_post_type($id);
    }

    public function apiGetLayouts()
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

    public function apiRetrieve($request)
    {
        $response = json_encode($request['id']);
        return rest_ensure_response($response);
    }

    public function apiAuthoringPermissionsCheck($request)
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

    public function apiAdministrationPermissionsCheck($request)
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

    public function apiSender($request)
    {
        $response = $this->sender(
            $request['id'],
            $request['from_name'],
            $request['replyto']
        );
        return rest_ensure_response($response);
    }

    public function sender($postId, $fromName, $replyTo)
    {
        $data = [];
        $data['result'] = [];
        // @todo
        return rest_ensure_response($data);
    }

    public function apiTest($request)
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

    public function test($postId, $emails)
    {
        $post = get_post($postId);
        $subject = $post->post_title;

        $mjmlRender = new Render;
        $body = $mjmlRender->toHtml($post);
        if (is_wp_error($body)) {
            return $body;
        }

        $from = get_post_meta($postId, 'rrze_newsletter_from_email', true);
        $fromName = get_post_meta($postId, 'rrze_newsletter_from_name', true);
        $replyTo = get_post_meta($postId, 'rrze_newsletter_replyto', true);

        $html2text = new Html2Text($body);
        $altBody = $html2text->getText();

        $emailsList = [];
        foreach ($emails as $email) {
            if (!Utils::sanitizeEmail(trim($email))) {
                continue;
            }
            $emailsList[$email] = $email;
        }

        $sentEmails = [];
        foreach ($emailsList as $to) {
            // Parse tags.
            $data = [
                'EMAIL' => $to
            ];
            $data = Tags::sanitizeTags($postId, $data);
            $parser = new Parser();
            $tBody = $parser->parse($body, $data);
            $tAltBody = $parser->parse($altBody, $data);
            // End Parse tags.

            $args = [
                'from' => $from,
                'fromName' => $fromName,
                'replyTo' => $replyTo,
                'to' => $to,
                'subject' => $subject,
                'body' => $tBody,
                'altBody' => $tAltBody
            ];

            $send = new Send;
            $isSent = $send->email($args);
            if (!is_wp_error($isSent)) {
                $sentEmails[] = $to;
            }
        }

        if (!empty($sentEmails)) {
            $message = sprintf(
                // translators: Message after the email was sent successfully.
                __('Email sent successfully to %s.', 'rrze-newsletter'),
                implode(', ', $sentEmails)
            );
        } else {
            $message = new \WP_Error(
                'rrze_newsletter_email_error',
                __('There was an error sending the email.', 'rrze-newsletter')
            );
        }

        return is_wp_error($message) ? $message : ['message' => $message];
    }

    public function apiSetPostMeta($request)
    {
        $id = $request['id'];
        $key = $request['key'];
        $value = $request['value'];
        update_post_meta($id, $key, $value);
        return [];
    }

    public function validateNewsletterPostMetaKey($key)
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

    public function apiSetColorPalette($request)
    {
        update_option('rrze_newsletter_color_palette', $request->get_body());
        return rest_ensure_response([]);
    }
}
