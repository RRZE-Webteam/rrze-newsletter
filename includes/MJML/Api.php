<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\CPT\Newsletter;
use function RRZE\Newsletter\plugin;

class Api
{
    /**
     * Return MJML API Endpoint.
     *
     * @return string|\WP_Error API Endpoint or \WP_Error otherwise.
     */
    public static function apiEndpoint()
    {
        $options = (object) Settings::getOptions();
        $apiEndpoint = $options->mjml_api_endpoint;
        if (!$apiEndpoint) {
            $apiEndpoint = new \WP_Error(
                'rrze_newsletter_mjml_api_endpoint',
                __('MJML API Endpoint not available.', 'rrze-newsletter')
            );
        }
        return $apiEndpoint;
    }

    /**
     * Return MJML API credentials.
     *
     * @return string API key and API secret as a key:secret string
     */
    public static function credentials()
    {
        $options = (object) Settings::getOptions();
        $key = $options->mjml_api_key;
        $secret = $options->mjml_api_secret;
        $credentials = "$key:$secret";
        return $credentials;
    }

    /** 
     * Return MJML Api request.
     * 
     * @param string MJML-compliant Markup.
     * @return array|\WP_Error Api respond.
     */
    public static function request(string $markup)
    {
        $endPoint = self::apiEndpoint();
        $credentials = self::credentials();
        $response = wp_remote_post(
            $endPoint,
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
        $credentials = self::apiEndpoint();
        if (
            is_admin()
            && (!$credentials || is_wp_error($credentials))
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

        echo '<div class="notice notice-info is-dismissible rrze-newsletter-notification-notice"><p>';
        echo wp_kses_post(
            sprintf(
                '<a href="%1$s">%2$s</a>',
                admin_url('options-general.php?page=rrze-newsletter&current-tab=rrze-newsletter-mjml_api'),
                // translators: Notify users to set the API Endpoint on the settings page.
                __('Please set up the MJML API Endpoint.', 'rrze-newsletter')
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
