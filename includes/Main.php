<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;
use RRZE\Newsletter\Mjml\Api as MjmlApi;
use RRZE\Newsletter\Mail\Queue;
use RRZE\Newsletter\Mail\QueueTask;

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

        // Editor
        Editor::instance();

        // MJML API
        MjmlApi::activationNotice();

        // RestApi
        new RestApi;

        // Schedule
        Cron::init();

        // Run Async Tasks
        $this->asyncTasks();
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

    public function asyncTasks()
    {
        new QueueTask();
        add_action(
            'wp_async_rrze_newsletter_queue_task',
            function () {
                $queue = new Queue;
                $queue->set();
            }
        );
        if (!get_option('rrze_newsletter_queue_task_lock')) {
            add_option('rrze_newsletter_queue_task_lock', 1);
            do_action('rrze_newsletter_queue_task');
        }
    }      
}
