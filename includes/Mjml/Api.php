<?php

namespace RRZE\Newsletter\Mjml;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

class Api
{
    const API_URL = 'https://api.mjml.io/v1/render';

    /**
     * Return MJML API credentials.
     *
     * @return string|\WP_Error API key and API secret as a key:secret string 
     *                          or \WP_Error otherwise.
     */
    public static function credentials()
    {
        $key = get_option('rrze_newsletter_mjml_api_key', false);
        $secret = get_option('rrze_newsletter_mjml_api_secret', false);
        if (isset($key, $secret)) {
            $credentials = "$key:$secret";
        } else {
            $credentials = new \WP_Error(
                'rrze_newsletter_mjml_api_credentials',
                __('Mjml Api credentials not available.', 'rrze-newsletter')
            );
        }
        return $credentials;
    }

    /** 
     * Return Mjml Api request.
     * 
     * @param string Mjml-compliant Markup.
     * @return array|\WP_Error Api respond.
     */
    public static function request(string $markup)
    {
        $credentials = self::credentials();
        $response = wp_remote_post(
            self::API_URL,
            [
                'body' => wp_json_encode(
                    [
                        'mjml' => $markup,
                    ]
                ),
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($credentials),
                ],
                'timeout' => 45,
            ]
        );
        return $response;
    }

    public static function activationNotice()
    {
        if (
            is_admin()
            && !self::credentials()
            && !get_option('rrze_newsletter_activation_notice_dismissed', false)
        ) {
            add_action('admin_notices', [__CLASS__, 'addNotice']);
            add_action('admin_enqueue_scripts', [__CLASS__, 'activationNoticeDismissScripts']);
            add_action('wp_ajax_rrze_newsletter_activation_notice_dismiss', [__CLASS__, 'activationNoticeDismissAjax']);
        }
    }

    public static function addNotice()
    {
        $screen = get_current_screen();
        if (Newsletter::POST_TYPE !== $screen->post_type) {
            return;
        }
        $url = admin_url('edit.php?post_type=' . Newsletter::POST_TYPE . '&page=rrze-newsletter-settings-admin');

        echo '<div class="notice notice-info is-dismissible rrze-newsletter-notification-notice"><p>';
        echo wp_kses_post(
            sprintf(
                // translators: Notify users to set API credentials on the settings page.
                __('Please <a href="%s">set up the API credentials</a>.', 'rrze-newsletter'),
                $url
            )
        );
        echo '</p></div>';
    }

    public static function activationNoticeDismissScripts()
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

    public static function activationNoticeDismissAjax()
    {
        update_option('rrze_newsletter_activation_notice_dismissed', true);
    }
}
