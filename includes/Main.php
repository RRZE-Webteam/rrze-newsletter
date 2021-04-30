<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;
use RRZE\Newsletter\MJML\Api as MjmlApi;
use RRZE\Newsletter\Mail\Queue;

class Main
{
    /**
     * __construct
     */
    public function __construct()
    {
        add_filter('plugin_action_links_' . plugin()->getBaseName(), [$this, 'settingsLink']);

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

        // Editor
        Editor::instance();

        // MJML API
        MjmlApi::activationNotice();

        // RestApi
        new RestApi;

        // Schedule
        Cron::init();

        // Queue task
        add_action('rrze_newsletter_queue_task', [$this, 'setQueue']);
    }

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

    public function setQueue($postId)
    {
        wp_clear_scheduled_hook('rrze_newsletter_queue_task', [$postId]);
        $queue = new Queue;
        $queue->set($postId);
    }
}
