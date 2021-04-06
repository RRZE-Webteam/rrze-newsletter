<?php

/* ---------------------------------------------------------------------------
 * Custom Post Type 'newsletter_mail_queue'
 * ------------------------------------------------------------------------- */

namespace RRZE\Newsletter\CPT;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Capabilities;

class NewsletterQueue
{
    const POST_TYPE = 'newsletter_queue';

    public function __construct()
    {
        // Register CPT.
        add_action('init', [__CLASS__, 'registerPostType']);
        // Custom Post Status
        add_action('init', [__CLASS__, 'registerPostStatus']);
    }

    public function onLoaded()
    {
        // CPT Menu
        add_action('admin_menu', [__CLASS__, 'adminMenu']);
        // CPT Custom Columns.
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [__CLASS__, 'columns']);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [__CLASS__, 'customColumn'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE . '_sortable_columns', [__CLASS__, 'sortableColumns']);
        // CPT List Filters.
        add_filter('months_dropdown_results', [__CLASS__, 'removeMonthsDropdown'], 10, 2);
        add_action('restrict_manage_posts', [__CLASS__, 'applyFilters']);
        add_filter('parse_query', [__CLASS__, 'filterQuery']);
        // List Actions
        add_filter('post_row_actions', [__CLASS__, 'rowActions'], 10, 2);
        add_filter('bulk_actions-edit-' . self::POST_TYPE, [__CLASS__, 'bulkActions']);
        // List Views
        add_filter('views_edit-' . self::POST_TYPE, [__CLASS__, 'views']);
    }

    public static function registerPostType()
    {
        $labels = [
            'name'                  => _x('Mail Queue', 'Post type general name', 'rrze-newsletter'),
            'singular_name'         => _x('Mail Queue', 'Post type singular name', 'rrze-newsletter'),
            'menu_name'             => _x('Mail Queue', 'Admin Menu text', 'rrze-newsletter'),
            'name_admin_bar'        => _x('Mail Queue', 'Add New on Toolbar', 'rrze-newsletter'),
            'add_new'               => __('Add New', 'rrze-newsletter'),
            'add_new_item'          => __('Add New Mail Queue', 'rrze-newsletter'),
            'new_item'              => __('New Mail Queue', 'rrze-newsletter'),
            'edit_item'             => __('Edit Mail Queue', 'rrze-newsletter'),
            'view_item'             => __('View Mail Queue', 'rrze-newsletter'),
            'all_items'             => __('All Mail Queue', 'rrze-newsletter'),
            'search_items'          => __('Search Mail Queue', 'rrze-newsletter'),
            'not_found'             => __('No Mail Queue found.', 'rrze-newsletter'),
            'not_found_in_trash'    => __('No Mail Queue found in Trash.', 'rrze-newsletter'),
            'filter_items_list'     => _x('Filter Mail Queue list', 'Screen reader text for the filter links heading on the post type listing screen.', 'rrze-newsletter'),
            'items_list_navigation' => _x('Mail Queue list navigation', 'Screen reader text for the pagination heading on the post type listing screen.', 'rrze-newsletter'),
            'items_list'            => _x('Mail Queue list', 'Screen reader text for the items list heading on the post type listing screen.', 'rrze-newsletter'),
        ];

        $args = [
            'labels'              => $labels,
            'supports'            => false,
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_rest'        => true,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'show_in_admin_bar'   => false,
            'can_export'          => false,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => Capabilities::getCptCapabilityType(self::POST_TYPE),
            'capabilities'        => (array) Capabilities::getCptCaps(self::POST_TYPE),
            'map_meta_cap'        => Capabilities::getCptMapMetaCap(self::POST_TYPE),
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    public static function adminMenu()
    {
        add_submenu_page(
            'edit.php?post_type=' . Newsletter::POST_TYPE,
            __('Mail Queue', 'rrze-newsletter'),
            __('Mail Queue', 'rrze-newsletter'),
            'manage_options',
            '/edit.php?post_type=' . self::POST_TYPE,
            null,
            2
        );
    }

    public static function registerPostStatus()
    {
        register_post_status('mail-queued', [
            'label'                     => _x('Queued', 'Mail Queue Status', 'rrze-newsletter'),
            'public'                    => true,
            'private'                   => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: Number of newsletter queued mails. */
            'label_count'               => _n_noop(
                'Queued <span class="count">(%s)</span>',
                'Queued <span class="count">(%s)</span>',
                'rrze-newsletter'
            )
        ]);

        register_post_status('mail-sent', [
            'label'                     => _x('Sent', 'Mail Queue Status', 'rrze-newsletter'),
            'public'                    => true,
            'private'                   => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: Number of sent newsletter mails. */
            'label_count'               => _n_noop(
                'Sent <span class="count">(%s)</span>',
                'Sent <span class="count">(%s)</span>',
                'rrze-newsletter'
            )
        ]);

        register_post_status('mail-error', [
            'label'                     => _x('Error', 'Mail Queue Status', 'rrze-newsletter'),
            'public'                    => true,
            'private'                   => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            /* translators: %s: Number of newsletter error mails. */
            'label_count'               => _n_noop(
                'Error <span class="count">(%s)</span>',
                'Error <span class="count">(%s)</span>',
                'rrze-newsletter'
            )
        ]);
    }

    public static function getData(int $postId): array
    {
        $data = [];

        $post = get_post($postId);
        if (!$post) {
            return $data;
        }

        $data['id'] = $post->ID;

        $data['send_date_gmt'] = $post->post_date_gmt;
        $data['send_date'] = $post->post_date;
        $data['post_date_format'] = sprintf(
            __('%1$s at %2$s'),
            get_the_time(__('Y/m/d'), $post),
            get_the_time(__('g:i a'), $post)
        );

        $data['status'] = $post->post_status;
        $data['subject'] = $post->post_title;

        $data['newsletter_id'] = absint(get_post_meta($post->ID, 'rrze_newsletter_queue_newsletter_id', true));
        $data['newsletter_url'] = get_post_meta($post->ID, 'rrze_newsletter_queue_newsletter_url', true);
        $data['from'] = get_post_meta($post->ID, 'rrze_newsletter_queue_from', true);
        $data['to'] = get_post_meta($post->ID, 'rrze_newsletter_queue_to', true);
        $data['sent_date_gmt'] = get_post_meta($post->ID, 'rrze_newsletter_queue_sent_date_gmt', true);
        $data['sent_date'] = $data['sent_date_gmt'] ? get_date_from_gmt($data['sent_date_gmt']) : '&mdash;';
        $data['retries'] = absint(get_post_meta($post->ID, 'rrze_newsletter_queue_retries', true));
        $data['error'] = get_post_meta($post->ID, 'rrze_newsletter_queue_error', true);

        return $data;
    }

    public static function columns($columns)
    {
        $queryVar = get_query_var('post_status');
        $columns = [
            'cb' => $columns['cb'],
            'subject' => __('Subject', 'rrze-newsletter'),
            'send_date' => __('Scheduled Date', 'rrze-newsletter'),
            'sent_date' => __('Send Date', 'rrze-newsletter'),
            'from' => __('From', 'rrze-newsletter'),
            'to' => __('To', 'rrze-newsletter'),
            'retries' => __('Retries', 'rrze-newsletter'),
            'status' => __('Status', 'rrze-newsletter'),
            'error' => __('Error', 'rrze-newsletter')
        ];

        if ($queryVar != 'mail-error') {
            unset($columns['error']);
        }
        return $columns;
    }

    public static function customColumn($column, $postId)
    {
        $data = self::getData($postId);
        $status = $data['status'];
        $stati = get_post_stati(['show_in_admin_status_list' => true], 'objects');
        $statusLabel = $stati[$status]->label;

        switch ($column) {
            case 'subject':
                echo esc_attr($data['subject']);
                break;
            case 'send_date':
                echo $data['send_date'];
                break;
            case 'sent_date':
                echo $data['sent_date'];
                break;
            case 'from':
                echo esc_attr($data['from']);
                break;
            case 'to':
                echo esc_attr($data['to']);
                break;
            case 'retries':
                echo $data['retries'];
                break;
            case 'status':
                echo $statusLabel;
                break;
            case 'error':
                echo $data['error'];
                break;
            default:
                echo '&mdash;';
        }
    }

    public static function sortableColumns($columns)
    {
        return $columns;
    }

    /**
     * Filters the array of row action links on the Newsletter list table.
     * The filter is evaluated only for non-hierarchical post types.
     * @param array $actions An array of row action links.
     * @param object $post \WP_Post The post object.
     * @return array $actions
     */
    public static function rowActions(array $actions, \WP_Post $post): array
    {
        if (
            $post->post_type != self::POST_TYPE
            || !in_array($post->post_status, ['mail-queued', 'mail-sent', 'mail-error'])
        ) {
            return $actions;
        }
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }
        if (isset($actions['inline hide-if-no-js'])) {
            unset($actions['inline hide-if-no-js']);
        }
        return $actions;
    }

    public static function bulkActions($actions)
    {
        if (isset($actions['edit'])) {
            unset($actions['edit']);
        }
        return $actions;
    }

    public static function views($views)
    {
        if (isset($views['mine'])) {
            unset($views['mine']);
        }
        return $views;
    }

    public static function removeMonthsDropdown($months, $postType)
    {
        if ($postType == self::POST_TYPE) {
            $months = [];
        }
        return $months;
    }

    public static function applyFilters($postType)
    {
        // @todo
    }

    public static function filterQuery($query)
    {
        // @todo
    }
}
