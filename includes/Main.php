<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Cron;
use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Subscription;
use RRZE\Newsletter\Archive;
use RRZE\Newsletter\Notices;
use RRZE\Newsletter\RestApi;
use RRZE\Newsletter\Editor;
use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Blocks\ICS\ICS;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;

/**
 * Main class for the RRZE Newsletter plugin.
 * 
 * This class initializes the plugin, registers custom post types,
 * sets up roles and capabilities, and handles plugin activation
 * and deactivation. It also registers various components such as
 * settings, custom blocks, editor enhancements, and REST API endpoints.
 * 
 * @package RRZE\Newsletter
 */
class Main
{
    /**
     * Constructor method.
     * 
     * This method sets up the plugin by adding action and filter hooks,
     * initializing settings, custom post types, blocks, editor enhancements,
     * notices, REST API endpoints, and scheduling tasks.
     * 
     * @return void
     */
    public function __construct()
    {
        // Adds a settings link to the plugin action links.
        add_filter('plugin_action_links_' . plugin()->getBaseName(), [$this, 'settingsLink']);

        // Sets the queue for the newsletter when a post is published.
        add_action('transition_post_status', [$this, 'maybeSetQueue'], 10, 3);

        // Settings
        $settings = new Settings;
        $settings->onLoaded();

        // Custom Post Types
        $newsletter = new Newsletter;
        $newsletter->onLoaded();
        $newslQueue = new NewsletterQueue;
        $newslQueue->onLoaded();
        new NewsletterLayout;

        // Newsletter Subscription
        new Subscription;

        // Newsletter Archive
        new Archive;

        // Blocks
        RSS::register();
        ICS::register();

        // Editor
        Editor::instance();

        // Notices
        new Notices;

        // RestApi
        new RestApi;

        // Schedule
        Cron::init();
    }

    /**
     * Adds a settings link to the plugin action links.
     * 
     * @param array $links The existing plugin action links.
     * @return array The modified plugin action links with the settings link added.
     */
    public function settingsLink($links)
    {
        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=rrze-newsletter'),
            __('Settings', 'rrze-newsletter')
        );
        array_unshift($links, $settingsLink);
        return $links;
    }

    /**
     * Sets the queue for the newsletter when a post is published.
     * 
     * This method checks if the post status is changing to 'publish' and
     * if the post type is 'newsletter'. If so, it updates the post meta
     * to indicate that the newsletter is ready to be sent and sets it in the queue.
     * 
     * @param string $newStatus The new post status.
     * @param string $oldStatus The old post status.
     * @param WP_Post $post The post object.
     * @return void
     */
    public function maybeSetQueue($newStatus, $oldStatus, $post)
    {
        if (
            'publish' !== $newStatus || 'publish' === $oldStatus
            || Newsletter::POST_TYPE !== get_post_type($post->ID)
        ) {
            return;
        }
        update_post_meta($post->ID, 'rrze_newsletter_status', 'send');
        $queue = new Queue;
        $queue->set($post->ID);
    }
}
