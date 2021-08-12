<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\Api;
use function RRZE\Newsletter\plugin;

class Notices
{
    protected $notices;

    public function __construct()
    {
        add_action('admin_init', [$this, 'set']);
        add_action('admin_init', [$this, 'show']);
    }

    public function set()
    {
        $options = (object) Settings::getOptions();

        $apiEndpoint = Api::apiEndpoint();
        if (!$apiEndpoint || is_wp_error($apiEndpoint)) {
            $this->notices[] = wp_kses_post(
                sprintf(
                    '<span class="dashicons dashicons-info"></span> <a href="%1$s">%2$s &mdash; %3$s</a>',
                    admin_url('options-general.php?page=rrze-newsletter&current-tab=rrze-newsletter-mjml_api'),
                    // translators: Notify users to set up the newsletter settings.
                    __('Newsletter Settings', 'rrze-newsletter'),
                    // translators: Notify users to set up the API Endpoint on the settings page.
                    __('Please set up the MJML API Endpoint.', 'rrze-newsletter')
                )
            );
        }

        $subscriptionDisabled = $options->subscription_disabled == 'on' ? true : false;
        $isSubscriptionDisabled = apply_filters('rrze_newsletter_disable_subscription', $subscriptionDisabled);
        if (!$isSubscriptionDisabled) {
            $subscriptionPageId = absint($options->subscription_page_id);
            if (!$subscriptionPageId) {
                $this->notices[] = wp_kses_post(
                    sprintf(
                        '<span class="dashicons dashicons-info"></span> <a href="%1$s">%2$s &mdash; %3$s</a>',
                        admin_url('options-general.php?page=rrze-newsletter&current-tab=rrze-newsletter-subscription'),
                        // translators: Notify users to set up the newsletter settings.
                        __('Newsletter Settings', 'rrze-newsletter'),
                        // translators: Notify users to set up the subscription page on the settings page.
                        __('Please set up the subscripction page.', 'rrze-newsletter')
                    )
                );
            }
        }
    }

    public function show()
    {
        if (
            $this->notices
            && !get_option('rrze_newsletter_activation_notice_dismissed', false)
        ) {
            add_action('admin_notices', [$this, 'addNotice']);
            add_action('admin_enqueue_scripts', [$this, 'activationNoticeDismissScripts']);
            add_action('wp_ajax_rrze_newsletter_activation_notice_dismiss', [$this, 'activationNoticeDismissAjax']);
        }
    }

    public function addNotice()
    {
        echo '<div class="notice notice-warning is-dismissible rrze-newsletter-notification-notice"><p>';
        foreach ($this->notices as $notice) {
            echo $notice, '<br>';
        }
        echo '</p></div>';
    }

    public function activationNoticeDismissScripts()
    {
        wp_enqueue_script(
            'rrze_newsletter_activation_notice_dismiss',
            plugins_url('dist/admin.js', plugin()->getBasename()),
            ['jquery'],
            plugin()->getVersion(),
            false
        );

        wp_localize_script(
            'rrze_newsletter_activation_notice_dismiss',
            'rrze_newsletter_activation_notice_dismiss_params',
            [
                'ajaxurl' => get_admin_url() . 'admin-ajax.php',
            ]
        );
        wp_enqueue_script('rrze_newsletter_activation_notice_dismiss');
    }

    public function activationNoticeDismissAjax()
    {
        update_option('rrze_newsletter_activation_notice_dismissed', true);
    }
}
