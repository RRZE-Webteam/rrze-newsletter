<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'newsletter'
 * ------------------------------------------------------------------------- */

namespace RRZE\Newsletter\CPT;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Html2Text;
use RRZE\Newsletter\Mjml\Render;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Capabilities;
use RRZE\Newsletter\Events;

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
        add_action('init', [__CLASS__, 'register_meta']);
        // Register Taxonomies.
        add_action('init', [__CLASS__, 'registerTaxonomies']);
    }

    public function onLoaded()
    {
        // Custom Columns.
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'customColumn'], 10, 2);
        // Taxonomy Terms Fields.
        add_action('newsletter_mailing_list_add_form_fields', [__CLASS__, 'addFormFields']);
        add_action('newsletter_mailing_list_edit_form_fields', [__CLASS__, 'editFormFields'], 10, 2);
        add_action('created_newsletter_mailing_list', [__CLASS__, 'saveFormFields']);
        add_action('edited_newsletter_mailing_list', [__CLASS__, 'saveFormFields']);
        // Taxonomy Custom Columns.
        add_filter('manage_edit-newsletter_mailing_list_columns', [__CLASS__, 'mailListColumns']);
        add_filter('manage_newsletter_mailing_list_custom_column', [__CLASS__, 'mailListCustomColumns'], 10, 3);
        // Post Stuff.  
        add_action('default_title', [__CLASS__, 'defaultTitle'], 10, 2);
        add_action('wp_head', [__CLASS__, 'publicCustomStyle'], 10, 2);
        add_filter('display_post_states', [__CLASS__, 'displayPostStates'], 10, 2);
        add_action('pre_get_posts', [__CLASS__, 'maybeDisplayPublicArchivePosts']);
        add_action('template_redirect', [__CLASS__, 'maybeDisplayPublicPost']);
        add_filter('post_row_actions', [__CLASS__, 'displayViewOrPreviewLink']);
        add_filter('post_row_actions', [__CLASS__, 'removeQuickEdit'], 10, 2);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save'], 10, 3);
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
            'capability_type'     => Capabilities::getCptCapabilityType(self::POST_TYPE),
            'capabilities'        => (array) Capabilities::getCptCaps(self::POST_TYPE),
            'map_meta_cap'        => Capabilities::getCptMapMetaCap(self::POST_TYPE),
        ];

        $args = [
            'labels'           => $labels,
            'public'           => true,
            'public_queryable' => true,
            'query_var'        => true,
            'show_ui'          => true,
            'show_in_rest'     => true,
            'supports'         => ['author', 'editor', 'title', 'custom-fields', 'revisions', 'thumbnail'],
            'menu_icon'        => 'dashicons-email-alt',
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    public static function register_meta()
    {
        // Used only for the block editor.
        register_meta(
            'post',
            'newsletterData',
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
            'newsletterValidationErrors',
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

    public static function registerTaxonomies()
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
            'labels' => $labels,
            'hierarchical' => true,
            'rewrite' => self::CATEGORY,
            'show_in_rest' => true
        ];
        register_taxonomy(self::CATEGORY, self::POST_TYPE, $args);

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
            'hierarchical' => true,
            'rewrite' => self::MAILING_LIST,
            'show_in_rest' => true,
        ];
        register_taxonomy(self::MAILING_LIST, self::POST_TYPE, $args);
    }

    public static function columns($columns)
    {
        if (!isset($columns['mailing_list'])) {
            $columns = array_merge(
                array_slice($columns, 0, 3),
                ['mailing_list' => __('Mailing List', 'rrze-newsletter')],
                array_slice($columns, 3)
            );
        }

        return $columns;
    }

    public function customColumn($column, $postId)
    {
        if ($column !== 'mailing_list') {
            return;
        }
        $mailingList = self::getTermsList($postId, self::MAILING_LIST);
        echo $mailingList['links'] ? $mailingList['links'] : '&mdash;';
    }

    public static function addFormFields($taxonomy)
    {
        echo '<div class="form-field">
        <label for="newsletter_mailing_list">' . __('E-mail Addresses', 'rrze-newsletter') . '</label>
        <textarea id="newsletter_mailing_list" rows="5" cols="40" name="rrze_newsletter_mailing_list"></textarea>
        <p>' . __('Enter one email address per line.', 'rrze-newsletter') . '</p>
        </div>';
    }

    public static function editFormFields($term, $taxonomy)
    {
        $value = get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true);

        echo '<tr class="form-field">
        <th>
            <label for="newsletter_mailing_list">' . __('E-mail Addresses', 'rrze-newsletter') . '</label>
        </th>
        <td>
            <textarea id="newsletter_mailing_list" rows="5" cols="50" name="rrze_newsletter_mailing_list">' . $value . '</textarea>
            <p class="description">' . __('Enter one email address per line.', 'rrze-newsletter') . '</p>
        </td>
        </tr>';
    }

    public static function saveFormFields(int $termId)
    {
        if (isset($_POST['rrze_newsletter_mailing_list'])) {
            $mailingList = Utils::sanitizeMailingList((string) $_POST['rrze_newsletter_mailing_list']);
            update_term_meta(
                $termId,
                'rrze_newsletter_mailing_list',
                $mailingList
            );
        }
    }

    public static function mailListColumns($columns)
    {
        $columns['posts'] = __('Newsletter', 'rrze-newsletter');
        $columns['emails'] = __('Emails', 'rrze-newsletter');
        return $columns;
    }

    public static function mailListCustomColumns($content, $columnName, $termId)
    {
        $term = get_term($termId, 'newsletter_mailing_list');
        switch ($columnName) {
            case 'emails':
                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    $content = 0;
                }
                $mailList = explode(PHP_EOL, $list);
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
        $data['content'] = self::getBody($postId);
        $data['excerpt'] = self::getAltBody($postId);

        $data['mail_lists'] = self::getTermsList($postId, self::MAILING_LIST);

        $fromEmail = get_post_meta($postId, 'rrze_newsletter_from_email', true);
        $fromName = get_post_meta($postId, 'rrze_newsletter_from_name', true);
        $replyTo = get_post_meta($postId, 'rrze_newsletter_replyto', true);
        $data['from_email'] = $fromEmail;
        $data['from_name'] = $fromName;
        $data['from'] = $fromName != '' ? sprintf('%1$s <%2$s>', $fromName, $fromEmail) : $fromEmail;
        $data['replyto'] = $replyTo;

        $data['status'] = get_post_meta($postId, 'rrze_newsletter_status', true);
        $data['post_status'] = $post->post_status;

        return $data;
    }

    public static function getPostsToQueue(): array
    {
        $args = [
            'fields'            => 'ids',
            'post_type'         => self::POST_TYPE,
            'post_status'       => 'publish',
            'nopaging'          => true,
            'meta_query'        => [
                [
                    'key'       => 'rrze_newsletter_status',
                    'value'     => ['send'],
                    'compare'   => 'IN'
                ]
            ]
        ];
        return get_posts($args);
    }

    protected static function getTermsList($postId, $taxonomy)
    {
        $postTerms = [
            'taxonomy' => $taxonomy,
            'terms' => null,
            'links' => null
        ];
        $postType = get_post_type($postId);
        $terms = get_the_terms($postId, $taxonomy);
        $termslinks = [];
        if ($terms !== false && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $termslinks[] = "<a href='edit.php?post_type={$postType}&{$taxonomy}={$term->slug}'> " . esc_html(sanitize_term_field('name', $term->name, $term->term_id, $taxonomy, 'edit')) . "</a>";
            }
            $postTerms['terms'] = $terms;
            $postTerms['links'] = implode(', ', $termslinks);
        }
        return $postTerms;
    }

    public static function defaultTitle($post_title, $post)
    {
        if (self::POST_TYPE === get_post_type($post)) {
            $post_title = sprintf(__('Newsletter of %s', 'rrze-notices'), date_i18n(get_option('date_format'), false, true));
        }
        return $post_title;
    }

    public static function publicCustomStyle()
    {
        if (!is_single()) {
            return;
        }
        $post = get_post();
        if ($post && self::POST_TYPE === $post->post_type) {
            $fontHeader = get_post_meta($post->ID, 'font_header', true);
            $fontBody = get_post_meta($post->ID, 'font_body', true);
            $backgroundColor = get_post_meta($post->ID, 'background_color', true);
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
                    ;
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

        $post_status = get_post_status_object($post->post_status);
        $isSent = 'publish' === $post_status->name;
        $isPublic = get_post_meta($post->ID, 'rrze_newsletter_is_public', true);

        if ($isSent) {
            $timestamp = current_time('U');
            $sendDate = get_the_time('U', $post);
            $timeDiff = $timestamp - $sendDate;
            $sendDate = human_time_diff($sendDate, $timestamp);

            if ($timeDiff < 24 * HOUR_IN_SECONDS) {
                $postStates[$post_status->name] = sprintf(
                    /* translators: Relative time stamp of sent/published date */
                    __('Sent %1$s ago', 'rrze-newsletter'),
                    $sendDate
                );
            } else {
                $postStates[$post_status->name] = sprintf(
                    /* translators:  Absolute time stamp of sent/published date */
                    __('Sent %1$s', 'rrze-newsletter'),
                    get_the_time(get_option('date_format'), $post)
                );
            }

            $publicHtml = $isPublic
                ? ' <span class="dashicons dashicons-visibility"></span>'
                : ' <span class="dashicons dashicons-hidden"></span>';

            $postStates[$post_status->name] .= $publicHtml;
        }

        return $postStates;
    }

    public static function maybeDisplayPublicArchivePosts($query)
    {
        if (
            is_admin() ||
            !$query->is_main_query() ||
            !is_post_type_archive(self::POST_TYPE)
        ) {
            return;
        }

        if (
            is_post_type_archive(self::POST_TYPE)
            || self::POST_TYPE === get_post_type()
        ) {
            $metaQuery = $query->get('meta_query');
            if (empty($metaQuery) || !is_array($metaQuery)) {
                $metaQuery = [];
            }
            $metaQuery[] = [
                'key'          => 'rrze_newsletter_is_public',
                'value'        => '1',
                'meta_compare' => '=',
            ];
            $query->set('meta_query', $metaQuery);
        }
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
            global $wp_query;

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

    public static function save($postId, $post, $update)
    {
        if (!$update) {
            update_post_meta($postId, 'rrze_newsletter_template_id', -1);
        }

        $render = new Render;
        if (is_wp_error($render)) {
            return;
        }
        $body = $render->renderHtmlEmail($post);
        self::setBody($postId, $body);

        $html2text = new Html2Text($body);
        $altBody = $html2text->getText();
        self::setAltBody($postId, $altBody);     
    }

    protected static function setBody(int $postId, string $body)
    {
        return update_post_meta($postId, 'rrze_newsletter_body', $body);
    }

    public static function getBody(int $postId)
    {
        return get_post_meta($postId, 'rrze_newsletter_body', true);
    }

    protected static function setAltBody(int $postId, string $altBody)
    {
        return update_post_meta($postId, 'rrze_newsletter_altbody', $altBody);
    }

    public static function getAltBody(int $postId)
    {
        return get_post_meta($postId, 'rrze_newsletter_altbody', true);
    }

    public static function setStatus(int $postId, string $status)
    {
        return update_post_meta($postId, 'rrze_newsletter_status', $status);
    }

    public static function getStatus(int $postId)
    {
        return get_post_meta($postId, 'rrze_newsletter_status', true);
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

    public static function support_featured_image_options($post_types)
    {
        return array_merge(
            $post_types,
            [self::POST_TYPE]
        );
    }
}
