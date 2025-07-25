<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Blocks\ICS\ICS;
use RRZE\Newsletter\Mail\Send;
use RRZE\Newsletter\MJML\Renderer;
use Html2Text\Html2Text;

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
                    'from_email'  => [
                        'sanitize_callback' => 'sanitize_email',
                    ],
                    'replyto'  => [
                        'sanitize_callback' => 'sanitize_email',
                    ],
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'email/(?P<id>[\a-z]+)/recipient',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'apiRecipient'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'id'        => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validateNewsletterId'],
                    ],
                    'to_email' => [
                        'sanitize_callback' => 'sanitize_email',
                    ]
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
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
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
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'post-mjml',
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'apiGetMjml'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'post_id' => [
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                    ],
                    'content' => [
                        'required' => true,
                    ],
                ],
            ]
        );
        register_rest_field(
            'post',
            'newsletter_author_info',
            [
                'get_callback' => [$this, 'getAuthorInfo'],
                'schema'       => [
                    'context' => [
                        'edit',
                    ],
                    'type'    => 'array',
                ],
            ]
        );
        register_rest_route(
            'rrze-newsletter/v1',
            'repeat/weekly/(?P<id>[\a-z]+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'apiRetrieveWeeklyRrules'],
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
            'repeat/monthly/(?P<id>[\a-z]+)',
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [$this, 'apiRetrieveMonthlyRrules'],
                'permission_callback' => [$this, 'apiAuthoringPermissionsCheck'],
                'args'                => [
                    'id' => [
                        'sanitize_callback' => 'absint',
                        'validate_callback' => [$this, 'validateNewsletterId'],
                    ],
                ],
            ]
        );
    }

    public function validateNewsletterId($id)
    {
        return Newsletter::POST_TYPE === get_post_type($id);
    }

    public function apiGetLayouts()
    {
        $layoutsQuery = new \WP_Query(
            [
                'post_type'      => NewsletterLayout::POST_TYPE,
                'posts_per_page' => -1,
            ]
        );
        $userLayouts = array_map(
            function ($post) {
                $post->meta = [
                    'rrze_newsletter_background_color' => get_post_meta($post->ID, 'rrze_newsletter_background_color', true),
                    'rrze_newsletter_font_body' => get_post_meta($post->ID, 'rrze_newsletter_font_body', true),
                    'rrze_newsletter_font_header' => get_post_meta($post->ID, 'rrze_newsletter_font_header', true),
                    'rrze_newsletter_link_color' => get_post_meta($post->ID, 'rrze_newsletter_link_color', true),
                    'rrze_newsletter_link_text_decoration' => get_post_meta($post->ID, 'rrze_newsletter_link_text_decoration', true),
                ];
                return $post;
            },
            $layoutsQuery->get_posts()
        );
        $response = array_merge(
            $userLayouts,
            NewsletterLayout::getDefaultLayouts(),
            apply_filters('rrze_newsletter_templates', [])
        );
        return rest_ensure_response($response);
    }

    public function apiRetrieveWeeklyRrules($request)
    {
        $postId = $request['id'];
        $postDate = get_post_time('Y-m-d', false, $postId);
        $data = Utils::getWeeklyRecurrence($postDate, 1, true);
        $response = isset($data[$postDate]) ? json_encode($data[$postDate]) : json_encode([]);
        return rest_ensure_response($response);
    }

    public function apiRetrieveMonthlyRrules($request)
    {
        $postId = $request['id'];
        $postDate = get_post_time('Y-m-d', false, $postId);
        $monthlyRecurrence = Utils::getMonthlyRecurrence($postDate, 1, true);
        $data = [];
        foreach ($monthlyRecurrence[$postDate] as $recurrence) {
            foreach ($recurrence as $key => $label) {
                $data[] = ['label' => $label, 'value' => $key];
            }
        }
        $response = json_encode($data);
        return rest_ensure_response($response);
    }

    public function apiRetrieve($request)
    {
        $response = json_encode($request['id']);
        return rest_ensure_response($response);
    }

    public function getAuthorInfo($post)
    {
        $author_data[] = [
            'display_name' => get_the_author_meta('display_name', $post['author']),
            'id'           => $post['author'],
            'author_link'  => get_author_posts_url($post['author']),
        ];
        return $author_data;
    }

    public function apiAuthoringPermissionsCheck($request)
    {
        if (!current_user_can('edit_others_newsletters')) {
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
            $request['from_email'],
            $request['replyto']
        );
        return rest_ensure_response($response);
    }

    public function sender($postId, $fromName, $fromEmail, $replyTo)
    {
        $fromEmail = sanitize_email(trim($fromEmail));

        $message = '';
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $message = sprintf(
                // translators: Message if the email address is not valid.
                __('The sender email address is not valid.', 'rrze-newsletter'),
                $fromEmail
            );
        }

        $response = is_wp_error($message) ? $message : ['message' => $message];

        return rest_ensure_response($response);
    }

    public function apiRecipient($request)
    {
        $response = $this->recipient(
            $request['id'],
            $request['to_email']
        );
        return rest_ensure_response($response);
    }

    public function recipient($postId, $email)
    {
        $message = '';
        if (!$email = Utils::sanitizeEmail($email)) {
            $message = sprintf(
                // translators: Message if the email address is not valid.
                __('The recipient email address is not valid.', 'rrze-newsletter'),
                $email
            );
        } elseif (!$email = Utils::sanitizeRecipientEmail($email)) {
            $message = sprintf(
                // translators: Message if the email domain is not allowed.
                __('The recipient email domain is not allowed.', 'rrze-newsletter'),
                $email
            );
        }

        $response = is_wp_error($message) ? $message : ['message' => $message];

        return rest_ensure_response($response);
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

        $body = Renderer::retrieveEmailHtml($post);
        if (is_wp_error($body)) {
            return $body;
        }

        if ($rssAttrs = get_post_meta($postId, 'rrze_newsletter_rss_attrs', true)) {
            foreach ($rssAttrs as $key => $attrs) {
                if (strpos($body, 'RSS_BLOCK_' . $key) !== false) {
                    $body = str_replace('RSS_BLOCK_' . $key, RSS::renderMJML($attrs), $body);
                }
            }
        }

        if ($icsAttrs = get_post_meta($postId, 'rrze_newsletter_ics_attrs', true)) {
            foreach ($icsAttrs as $key => $attrs) {
                if (strpos($body, 'ICS_BLOCK_' . $key) !== false) {
                    $body = str_replace('ICS_BLOCK_' . $key, ICS::renderMJML($attrs), $body);
                }
            }
        }

        $from = get_post_meta($postId, 'rrze_newsletter_from_email', true);
        $fromName = get_post_meta($postId, 'rrze_newsletter_from_name', true);
        $replyTo = get_post_meta($postId, 'rrze_newsletter_replyto', true);

        $emailsList = [];
        foreach ($emails as $email) {
            if (!Utils::sanitizeEmail(trim($email))) {
                continue;
            }
            $emailsList[$email] = $email;
        }

        $sentEmails = [];
        foreach ($emailsList as $to) {
            $archiveSlug = Archive::testSlug();
            $archiveQuery = Utils::encryptQueryVar($postId);
            $archiveUrl = site_url($archiveSlug . '/' . $archiveQuery);

            // Parse tags.
            $data = [
                'EMAIL' => $to,
                'ARCHIVE' => $archiveUrl
            ];
            $data = Tags::sanitizeTags($postId, $data);
            $parser = new Parser();
            $tBody = $parser->parse($body, $data);
            $html2text = new Html2Text($tBody);
            $tAltBody = $html2text->getText();
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
                'rrze_newsletter_link_color',
                'rrze_newsletter_link_text_decoration',
                'rrze_newsletter_preview_text'
            ]
        );
    }

    public function apiSetColorPalette($request)
    {
        update_option(
            'rrze_newsletter_color_palette',
            wp_json_encode(
                array_merge(
                    json_decode((string) get_option('rrze_newsletter_color_palette', '{}'), true) ?? [],
                    json_decode($request->get_body(), true)
                )
            )
        );
        return rest_ensure_response([]);
    }

    /**
     * Get MJML markup for a post.
     * Content is sent straight from the editor, because all this happens
     * before post is saved in the database.
     *
     * @param WP_REST_Request $request API request object.
     */
    public function apiGetMjml($request)
    {
        $post = get_post($request['post_id']);
        if (!empty($request['title'])) {
            $post->post_title = $request['title'];
        }
        $post->post_content = $request['content'];
        return rest_ensure_response(['mjml' => Renderer::fromPost($post)]);
    }
}
