<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;
use RRZE\Newsletter\Mjml\Api as MjmlApi;

class Main
{
    /**
     * __construct
     */
    public function __construct()
    {
        add_filter('plugin_action_links_' . plugin()->getBaseName(), [$this, 'settingsLink']);

        // Update
        $update = new Update();
        $update->onLoaded();

        // Settings 
        $settings = new Settings;
        $settings->onLoaded();

        // RestApi
        new RestApi;

        // Post Types 
        Newsletter::instance();
        NewsletterLayout::instance();
        NewsletterQueue::instance();

        // Editor
        Editor::instance();

        MjmlApi::activationNotice();

        new Cron;
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
}
