<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterLayout;
use RRZE\Newsletter\CPT\NewsletterQueue;
use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Blocks\ICS\ICS;
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

        // Newsletter Archive
        new Archive;

        // Blocks
        RSS::register();
        ICS::register();

        // Editor
        Editor::instance();
        add_filter('wp_theme_json_data_theme', [$this, 'filterThemeJsonTheme']);

        // Notices
        new Notices;

        // RestApi
        new RestApi;

        // Schedule
        Cron::init();

        // Queue task
        add_action('transition_post_status', [$this, 'maybeSetQueue'], 10, 3);
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

    public function filterThemeJsonTheme($theme_json)
    {
        $data = [
            'version'  => 2,
            'settings' => [
                'spacing' => [
                    'customSpacingSize' => false,
                ],
                'typography' => [
                    'fontSizes' => [
                        [
                            'name' => 'Small',
                            'slug' => 'small',
                            'size' => '13px'
                        ],
                        [
                            'name' => 'Medium',
                            'slug' => 'medium',
                            'size' => '20px'
                        ],
                        [
                            'name' => 'Large',
                            'slug' => 'large',
                            'size' => '36px'
                        ],
                        [
                            'name' => 'Extra Large',
                            'slug' => 'x-large',
                            'size' => '42px'
                        ],
                    ],
                    'fontStyle' => false,
                    'fontWeight' => false,
                    'letterSpacing' => false,
                    'lineHeight' => false,
                    'textDecoration' => true,
                    'dropCap' => false,
                ]
            ],
        ];
        return $theme_json->update_with($data);
    }
}
