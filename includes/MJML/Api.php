<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;

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
        if (is_wp_error($endPoint)) {
            return $endPoint;
        }
        $credentials = self::credentials();
        $response = wp_remote_post(
            $endPoint,
            [
                'body' => json_encode(
                    [
                        'mjml' => $markup,
                    ],
                    JSON_UNESCAPED_UNICODE
                ),
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Basic ' . base64_encode($credentials),
                ],
                'timeout' => 45,
            ]
        );
        return $response;
    }
}
