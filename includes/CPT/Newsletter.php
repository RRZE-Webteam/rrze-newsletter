<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'newsletter'
 * ------------------------------------------------------------------------- */

namespace RRZE\Newsletter\CPT;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Render;
use RRZE\Newsletter\Tags;
use RRZE\Newsletter\Parser;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Capabilities;

class Newsletter
{
    const POST_TYPE = 'newsletter';

    const CATEGORY = 'newsletter_category';

    const MAILING_LIST = 'newsletter_mailing_list';

    public function __construct()
    {
        // Register Post Type.
        add_action('init', [__CLASS__, 'registerPostType']);
        // Register Metadata.
        add_action('init', [__CLASS__, 'registerMeta']);
        // Register Taxonomies.
        add_action('init', [__CLASS__, 'registerCategory']);
        add_action('init', [__CLASS__, 'registerMailingList']);
    }

    public function onLoaded()
    {
        if (!apply_filters('rrze_newsletter_disable_mailing_list', false)) {
            // Taxonomy Terms Fields.
            add_action('newsletter_mailing_list_add_form_fields', [__CLASS__, 'addFormFields']);
            add_action('newsletter_mailing_list_edit_form_fields', [__CLASS__, 'editFormFields'], 10, 2);
            add_action('created_newsletter_mailing_list', [__CLASS__, 'saveFormFields']);
            add_action('edited_newsletter_mailing_list', [__CLASS__, 'saveFormFields']);
            // Taxonomy Custom Columns.
            add_filter('manage_edit-newsletter_mailing_list_columns', [__CLASS__, 'mailListColumns']);
            add_filter('manage_newsletter_mailing_list_custom_column', [__CLASS__, 'mailListCustomColumns'], 10, 3);
        }

        // Post Stuff.  
        add_action('default_title', [__CLASS__, 'defaultTitle'], 10, 2);
        add_filter('the_content', [__CLASS__, 'theContent']);
        add_action('wp_head', [__CLASS__, 'publicCustomStyle'], 10, 2);
        add_filter('display_post_states', [__CLASS__, 'displayPostStates'], 10, 2);
        add_action('pre_get_posts', [__CLASS__, 'maybeDisplayPublicArchivePosts']);
        add_action('template_redirect', [__CLASS__, 'maybeDisplayPublicPost']);
        add_filter('post_row_actions', [__CLASS__, 'displayViewOrPreviewLink']);
        add_filter('post_row_actions', [__CLASS__, 'removeQuickEdit'], 10, 2);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'savePost'], 10, 3);
        add_action('wp_trash_post', [__CLASS__, 'trash'], 10, 1);
    }

    public static function registerPostType()
    {
        $labels = [
            'name'               => _x('Newsletters', 'post type general name', 'rrze-newsletter'),
            'singular_name'      => _x('Newsletter', 'post type singular name', 'rrze-newsletter'),
            'menu_name'          => _x('Newsletters', 'admin menu', 'rrze-newsletter'),
            'name_admin_bar'     => _x('Newsletter', 'add new on admin bar', 'rrze-newsletter'),
            'add_new'            => _x('Add New', 'popup', 'rrze-newsletter'),
            'add_new_item'       => __('Add New Newsletter', 'rrze-newsletter'),
            'new_item'           => __('New Newsletter', 'rrze-newsletter'),
            'edit_item'          => __('Edit Newsletter', 'rrze-newsletter'),
            'view_item'          => __('View Newsletter', 'rrze-newsletter'),
            'all_items'          => __('All Newsletters', 'rrze-newsletter'),
            'search_items'       => __('Search Newsletters', 'rrze-newsletter'),
            'parent_item_colon'  => __('Parent Newsletters:', 'rrze-newsletter'),
            'not_found'          => __('No newsletters found.', 'rrze-newsletter'),
            'not_found_in_trash' => __('No newsletters found in Trash.', 'rrze-newsletter'),
            'capability_type'    => Capabilities::getCptCapabilityType(self::POST_TYPE),
            'capabilities'       => (array) Capabilities::getCptCaps(self::POST_TYPE),
            'map_meta_cap'       => Capabilities::getCptMapMetaCap(self::POST_TYPE),
        ];

        $args = [
            'labels'            => $labels,
            'public'            => true,
            'has_archive'       => true,
            'public_queryable'  => true,
            'query_var'         => true,
            'show_ui'           => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'supports'          => ['author', 'editor', 'title', 'custom-fields', 'revisions', 'thumbnail'],
            'menu_icon'         => 'dashicons-email-alt',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public static function registerMeta()
    {
        // Used only for the block editor.
        register_meta(
            'post',
            'rrze_newsletter_data',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'type'                 => 'object',
                        'context'              => ['edit'],
                        'additionalProperties' => true,
                        'properties'           => [],
                    ],
                ],
                'type'           => 'object',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );

        // Used only for the block editor.
        register_meta(
            'post',
            'rrze_newsletter_validation_errors',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'type'    => 'array',
                        'context' => ['edit'],
                        'items'   => [
                            'type' => 'string',
                        ],
                    ],
                ],
                'type'           => 'array',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_email_html',
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
            'rrze_newsletter_from_name',
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
            'rrze_newsletter_from_email',
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
        if (apply_filters('rrze_newsletter_disable_mailing_list', false)) {
            register_meta(
                'post',
                'rrze_newsletter_to_email',
                [
                    'object_subtype' => self::POST_TYPE,
                    'show_in_rest'   => [
                        'schema' => [
                            'context' => ['edit'],
                        ],
                    ],
                    'type'           => 'string',
                    'single'         => true,
                    'auth_callback'  => '__return_true'
                ]
            );
        }
        register_meta(
            'post',
            'rrze_newsletter_replyto',
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
            'rrze_newsletter_preview_text',
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
            'rrze_newsletter_is_public',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'boolean',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_has_conditionals',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'boolean',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_conditionals_operator',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'default'        => 'or',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_conditionals_rss_block',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'boolean',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_conditionals_ics_block',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'boolean',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_is_recurring',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'boolean',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_recurrence_repeat',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'default'        => 'DAILY',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_recurrence_monthly',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'string',
                'default'        => 'BYSETPOS',
                'single'         => true,
                'auth_callback'  => '__return_true',
            ]
        );
        register_meta(
            'post',
            'rrze_newsletter_template_id',
            [
                'object_subtype' => self::POST_TYPE,
                'show_in_rest'   => [
                    'schema' => [
                        'context' => ['edit'],
                    ],
                ],
                'type'           => 'integer',
                'single'         => true,
                'auth_callback'  => '__return_true',
                'default'        => -1,
            ]
        );
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
    }

    public static function registerCategory()
    {
        $labels = [
            'name' => _x('Categories', 'Taxonomy general name', 'rrze-newsletter'),
            'singular_name' => _x('Category', 'Taxonomy singular name', 'rrze-newsletter'),
            'all_items' => __('All Categories', 'rrze-newsletter'),
            'edit_item' => __('Edit Category', 'rrze-newsletter'),
            'view_item' => __('View Category', 'rrze-newsletter'),
            'update_item' => __('Update Category', 'rrze-newsletter'),
            'add_new_item' => __('Add New Category', 'rrze-newsletter'),
            'new_item_name' => __('New Category Name', 'rrze-newsletter'),
            'parent_item' => __('Main Category', 'rrze-newsletter'),
            'parent_item_colon' => __('Main Category:', 'rrze-newsletter'),
            'search_items' => __('Search Categories', 'rrze-newsletter'),
            'not_found' => __('No categories found', 'rrze-newsletter'),
            'back_to_items' => __('Back to categories', 'rrze-newsletter'),
        ];
        $args = [
            'labels'            => $labels,
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'newsletters', 'with_front' => false]
        ];
        register_taxonomy(self::CATEGORY, self::POST_TYPE, $args);
    }

    public static function registerMailingList()
    {
        $labels = [
            'name' => _x('Mailing Lists', 'Taxonomy general name', 'rrze-newsletter'),
            'singular_name' => _x('Mailing List', 'Taxonomy singular name', 'rrze-newsletter'),
            'all_items' => __('All Lists', 'rrze-newsletter'),
            'edit_item' => __('Edit List', 'rrze-newsletter'),
            'view_item' => __('View List', 'rrze-newsletter'),
            'update_item' => __('Update List', 'rrze-newsletter'),
            'add_new_item' => __('Add New List', 'rrze-newsletter'),
            'new_item_name' => __('New List Name', 'rrze-newsletter'),
            'parent_item' => __('Main List', 'rrze-newsletter'),
            'parent_item_colon' => __('Main List:', 'rrze-newsletter'),
            'search_items' => __('Search Lists', 'rrze-newsletter'),
            'not_found' => __('No lists found', 'rrze-newsletter'),
            'back_to_items' => __('Back to lists', 'rrze-newsletter'),
        ];
        $args = [
            'labels' => $labels,
            'public' => false,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true
        ];
        if (!apply_filters('rrze_newsletter_disable_mailing_list', false)) {
            register_taxonomy(self::MAILING_LIST, self::POST_TYPE, $args);
        }
    }

    public static function addFormFields($taxonomy)
    {
        echo '<div class="form-field">',
        '<input type="checkbox" name="rrze_newsletter_mailing_list_public" value="true">',
        '<span>', __('Public Mailing List', 'rrze-newsletter'), '</span>',
        '<p>', __('A public mailing list can be listed on the subscription page and viewed by everyone.', 'rrze-newsletter'), '</p>',
        '</div>';

        echo '<div class="form-field">',
        '<label for="rrze_newsletter_mailing_list">', __('Subscribed email addresses', 'rrze-newsletter'), '</label>',
        '<textarea id="newsletter_mailing_list" rows="5" cols="40" name="rrze_newsletter_mailing_list"></textarea>',
        '<p>', __("Enter one email address per line. Note: The recipient's first and last name separated by a comma are optional and can be added using a comma after the email address using the following format: email-address,first name,last name", 'rrze-newsletter'), '</p>',
        '</div>';
    }

    public static function editFormFields($term, $taxonomy)
    {
        $value = (bool) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_public', true);

        echo '<tr class="form-field">',
        '<th>', '<label for="rrze_newsletter_mailing_list_public">' . __('Public Mailing List', 'rrze-newsletter') . '</label>', '</th>',
        '<td>',
        '<input type="checkbox" name="rrze_newsletter_mailing_list_public" value="true" ', checked($value, true, false), '>',
        '<span>', __('It can be listed on the subscription page and seen by everyone.', 'rrze-newsletter'), '</span>',
        '</td>',
        '</tr>';

        $value = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true);

        echo '<tr class="form-field">',
        '<th><label for="rrze_newsletter_mailing_list">' . __('Subscribed email addresses', 'rrze-newsletter') . '</label></th>',
        '<td>',
        '<textarea id="rrze_newsletter_mailing_list" rows="5" cols="50" name="rrze_newsletter_mailing_list">', $value, '</textarea>',
        '<p class="description">', __("List of email addresses that have been subscribed to this mailing list. Enter one email address per line. Note: The recipient's first and last name separated by a comma are optional and can be added using a comma after the email address using the following format: email-address,first name,last name", 'rrze-newsletter'), '</p>',
        '</td>',
        '</tr>';

        $value = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);

        echo '<tr class="form-field">',
        '<th><label for="rrze_newsletter_mailing_list_unsubscribed">' . __('Unsubscribed E-mail Addresses', 'rrze-newsletter') . '</label></th>',
        '<td>',
        '<textarea id="rrze_newsletter_mailing_list_unsubscribed" rows="5" cols="50" name="rrze_newsletter_mailing_list_unsubscribed">', $value, '</textarea>',
        '<p class="description">', __('List of email addresses that have been unsubscribed from this mailing list. Enter one email address per line.', 'rrze-newsletter'), '</p>',
        '</td>',
        '</tr>';
    }

    public static function saveFormFields(int $termId)
    {
        $isPublic = isset($_POST['rrze_newsletter_mailing_list_public']);
        update_term_meta(
            $termId,
            'rrze_newsletter_mailing_list_public',
            $isPublic
        );

        if (isset($_POST['rrze_newsletter_mailing_list'])) {
            $mailingList = Utils::sanitizeMailingList((string) $_POST['rrze_newsletter_mailing_list']);
            update_term_meta(
                $termId,
                'rrze_newsletter_mailing_list',
                $mailingList
            );
        }

        if (isset($_POST['rrze_newsletter_mailing_list_unsubscribed'])) {
            $unsubscribed = Utils::sanitizeUnsubscribedList((string) $_POST['rrze_newsletter_mailing_list_unsubscribed']);
            update_term_meta(
                $termId,
                'rrze_newsletter_mailing_list_unsubscribed',
                $unsubscribed
            );
        }
    }

    public static function mailListColumns($columns)
    {
        $columns['posts'] = __('Newsletter', 'rrze-newsletter');
        $columns['public'] = __('Public', 'rrze-newsletter');
        $columns['emails-in'] = __('In', 'rrze-newsletter');
        $columns['emails-out'] = __('Out', 'rrze-newsletter');
        return $columns;
    }

    public static function mailListCustomColumns($content, $columnName, $termId)
    {
        $term = get_term($termId, 'newsletter_mailing_list');
        switch ($columnName) {
            case 'public':
                $isPublic = (bool) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_public', true);
                $content = $isPublic ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>';
                break;
            case 'emails-in':
                $list = get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true);
                $mailList = $list ? explode(PHP_EOL, $list) : [];
                $content = count($mailList);
                break;
            case 'emails-out':
                $list = get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);
                $mailList = $list ? explode(PHP_EOL, $list) : [];
                $content = count($mailList);
                break;
            default:
                break;
        }
        return $content;
    }

    public static function getData(int $postId): array
    {
        $data = [];

        $post = get_post($postId);
        if (!$post) {
            return $data;
        }

        $body = Render::retrieveEmailHtml($post);
        if (is_wp_error($body)) {
            return $body;
        }

        $data['id'] = $postId;

        $data['post_date_gmt'] = $post->post_date_gmt;
        $data['post_date'] = $post->post_date;
        $data['post_date_format'] = sprintf(
            __('%1$s at %2$s'),
            get_the_time(__('Y/m/d'), $post),
            get_the_time(__('g:i a'), $post)
        );

        $data['send_date_gmt'] = $data['post_date_gmt'];
        $data['send_date'] = $data['post_date'];
        $data['send_date_format'] = $data['post_date_format'];

        $data['title'] = $post->post_title;
        $data['content'] = $body;
        $data['excerpt'] = '';

        $data['mailing_list_terms'] = self::getTermsList($postId, self::MAILING_LIST);

        $fromEmail = get_post_meta($postId, 'rrze_newsletter_from_email', true);
        $fromName = get_post_meta($postId, 'rrze_newsletter_from_name', true);
        $replyTo = get_post_meta($postId, 'rrze_newsletter_replyto', true);
        $data['from_email'] = $fromEmail;
        $data['from_name'] = $fromName;
        $data['from'] = $fromName != '' ? sprintf('%1$s <%2$s>', $fromName, $fromEmail) : $fromEmail;
        $data['replyto'] = $replyTo;

        $data['status'] = self::getStatus($postId);
        $data['post_status'] = $post->post_status;

        return $data;
    }

    protected static function getTermsList($postId, $taxonomy)
    {
        $terms = get_the_terms($postId, $taxonomy);
        if ($terms !== false && !is_wp_error($terms)) {
            return $terms;
        }
        return false;
    }

    public static function defaultTitle($post_title, $post)
    {
        if (self::POST_TYPE === get_post_type($post)) {
            $post_title = sprintf(
                /* translators: Default title of the newsletter including the date. */
                __('Newsletter of %s', 'rrze-newsletter'),
                date_i18n(get_option('date_format'), false, true)
            );
        }
        return $post_title;
    }

    public static function theContent($content)
    {
        if (self::POST_TYPE === get_post_type()) {
            $post = get_post();
            // Parse tags.
            $data = [
                'EMAIL_ONLY' => ''
            ];
            $data = Tags::sanitizeTags($post->ID, $data);
            $parser = new Parser();
            $content = $parser->parse($content, $data);
            // End Parse tags.
        }
        return $content;
    }

    public static function publicCustomStyle()
    {
        if (!is_single()) {
            return;
        }
        $post = get_post();
        if ($post && self::POST_TYPE === $post->post_type) {
            $fontHeader = get_post_meta($post->ID, 'rrze_newsletter_font_header', true);
            $fontBody = get_post_meta($post->ID, 'rrze_newsletter_font_body', true);
            $backgroundColor = get_post_meta($post->ID, 'rrze_newsletter_background_color', true);
?>
            <style>
                .main-content {
                    background-color: <?php echo esc_attr($backgroundColor); ?>;
                    font-family: <?php echo esc_attr($fontBody); ?>;
                }

                .main-content h1,
                .main-content h2,
                .main-content h3,
                .main-content h4,
                .main-content h5,
                .main-content h6 {
                    font-family: <?php echo esc_attr($fontHeader); ?>;
                }

                <?php if ($backgroundColor) : ?>.entry-content {
                    padding: 0 32px;
                }

                <?php endif; ?>
            </style>
<?php
        }
    }

    public static function displayPostStates($postStates, $post)
    {
        if (self::POST_TYPE !== $post->post_type) {
            return $postStates;
        }

        $postStatus = get_post_status_object($post->post_status);
        $isPublish = 'publish' === $postStatus->name;

        $isPublic = (bool) get_post_meta($post->ID, 'rrze_newsletter_is_public', true);

        $sendStatus = self::getStatus($post->ID);

        $isRecurring = (bool) get_post_meta($post->ID, 'rrze_newsletter_is_recurring', true);

        $output = '';

        if ($sendStatus == 'error') {
            $flags = ' <span class="dashicons dashicons-warning"></span>';
        } else {
            $flags = $sendStatus == 'skipped'
                ? ' <span class="dashicons dashicons-controls-skipforward"></span>'
                : '';

            $flags .= $isPublic
                ? ' <span class="dashicons dashicons-visibility"></span>'
                : ' <span class="dashicons dashicons-hidden"></span>';

            $flags .= $isRecurring
                ? ' <span class="dashicons dashicons-image-rotate"></span>'
                : '';
        }

        if ($isPublish && $sendStatus == 'sent') {
            $timestamp = current_time('U');
            $sendDate = get_the_time('U', $post);
            $timeDiff = $timestamp - $sendDate;
            $sendDate = human_time_diff($sendDate, $timestamp);
            if ($timeDiff < 24 * HOUR_IN_SECONDS) {
                $output = sprintf(
                    /* translators: Relative time stamp of sent/published date */
                    __('Sent %1$s ago', 'rrze-newsletter'),
                    $sendDate
                );
            } else {
                $output = sprintf(
                    /* translators:  Absolute time stamp of sent/published date */
                    __('Sent %1$s', 'rrze-newsletter'),
                    get_the_time(get_option('date_format'), $post)
                );
            }
        }

        $postStates[$postStatus->name] = $output . $flags;

        return $postStates;
    }

    public static function maybeDisplayPublicArchivePosts($query)
    {
        if (
            is_admin() ||
            !$query->is_main_query() ||
            (!is_tax(self::CATEGORY) &&
                !is_post_type_archive(self::POST_TYPE))
        ) {
            return;
        }

        if (is_tax(self::CATEGORY) || empty($query->get('post_type'))) {
            $query->set('post_type', ['post', self::POST_TYPE]);
        }

        $metaQuery = $query->get('meta_query', []);
        $metaQueryParams = [
            [
                'key'     => 'rrze_newsletter_is_public',
                'value'   => true,
                'compare' => '=',
            ],
        ];

        if (is_tax(self::CATEGORY)) {
            $metaQueryParams['relation'] = 'OR';
            $metaQueryParams[] = [
                'key'     => 'rrze_newsletter_is_public',
                'compare' => 'NOT EXISTS',
            ];
        }

        $metaQuery[] = $metaQueryParams;
        $query->set('meta_query', $metaQuery);
    }

    public static function maybeDisplayPublicPost()
    {
        if (
            current_user_can('edit_others_posts') ||
            !is_singular(self::POST_TYPE)
        ) {
            return;
        }

        $isPublic = get_post_meta(get_the_ID(), 'rrze_newsletter_is_public', true);
        if (empty($isPublic)) {
            add_filter(
                'wpseo_title',
                function ($title) {
                    return str_replace(get_the_title(), __('Page not found', 'rrze-newsletter'), $title);
                }
            );

            status_header(404);
            nocache_headers();
            include get_query_template('404');
            die();
        }
    }

    public static function displayViewOrPreviewLink($actions)
    {
        if ('publish' !== get_post_status() || self::POST_TYPE !== get_post_type()) {
            return $actions;
        }

        $isPublic = get_post_meta(get_the_ID(), 'rrze_newsletter_is_public', true);

        if (empty($isPublic) && isset($actions['view'])) {
            $actions['view'] = sprintf(
                '<a href="%1$s" rel="bookmark" aria-label="%2$s">%3$s</a>',
                esc_url(get_the_permalink()),
                esc_attr(get_the_title()),
                __('Preview', 'rrze-newsletter')
            );
        }

        return $actions;
    }

    public static function removeQuickEdit($actions)
    {
        if (self::POST_TYPE === get_post_type()) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    public static function savePost($postId, $post, $update)
    {
        if (!$update) {
            update_post_meta($postId, 'rrze_newsletter_template_id', -1);
            self::setStatus($postId, '');
        }
    }

    public static function setStatus(int $postId, string $status)
    {
        if (Newsletter::POST_TYPE === get_post_type($postId)) {
            return update_post_meta($postId, 'rrze_newsletter_status', $status);
        }
    }

    public static function getStatus(int $postId)
    {
        return get_post_meta($postId, 'rrze_newsletter_status', true);
    }

    public static function getLastSendDateGmt(int $postId)
    {
        $sendDate = date('Y-m-d H:i:s', HOUR_IN_SECONDS); // UNIX Epoch time + 1 hour
        if (in_array(get_post_status($postId), ['publish', 'future'])) {
            $sendDateGmt = (string) get_post_meta($postId, 'rrze_newsletter_send_date_gmt', true);
            if (Utils::validateDate($sendDateGmt)) {
                $sendDate = $sendDateGmt;
            }
        }
        return $sendDate;
    }

    public static function validateNewsletterId($postId)
    {
        return Newsletter::POST_TYPE === get_post_type($postId);
    }

    public static function trash($postId)
    {
        if (Newsletter::POST_TYPE !== get_post_type($postId)) {
            return;
        }
        //@todo
    }
}
