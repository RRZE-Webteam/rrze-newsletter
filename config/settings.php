<?php

namespace RRZE\Newsletter\Config;

defined('ABSPATH') || exit;

/**
 * Returns the name of the option.
 * @return string Option name
 */
function getOptionName(): string
{
    return 'rrze_newsletter';
}

/**
 * Returns the settings of the menu.
 * @return array Menu settings
 */
function getMenuSettings(): array
{
    return [
        'page_title'    => __('Newsletters', 'rrze-newsletter'),
        'menu_title'    => __('Newsletters', 'rrze-newsletter'),
        'capability'    => 'manage_options',
        'menu_slug'     => 'rrze-newsletter',
        'title'         => __('Newsletter Settings', 'rrze-newsletter'),
    ];
}

/**
 * Returns the sections settings.
 * @return array Sections settings
 */
function getSections(): array
{
    return [
        [
            'id'    => 'mail_server',
            'title' => __('Mail Server', 'rrze-newsletter'),
            'desc' => ''
        ],
        [
            'id'    => 'mail_queue',
            'title' => __('Mail Queue', 'rrze-newsletter'),
            'desc' => ''
        ],
        [
            'id'    => 'mailing_list',
            'title' => __('Mailing List', 'rrze-newsletter'),
            'desc' => ''
        ],
        [
            'id'    => 'mjml_api',
            'title' => __('MJML API', 'rrze-newsletter'),
            'desc' => sprintf(
                '<p>%1$s</p><a href="https://mjml.io/api">%2$s <span class="dashicons dashicons-external"></span></a>',
                __('Enter your MJML API keys.', 'rrze-newsletter'),
                __('Request MJML API keys', 'rrze-newsletter')
            )
        ]
    ];
}

/**
 * Returns the settings fields.
 * @return array Settings fields
 */
function getFields(): array
{
    return [
        'mail_server' => [
            [
                'name'    => 'encryption',
                'label'   => __('Encryption', 'rrze-newsletter'),
                'desc'    => '',
                'type'    => 'radio',
                'options' => [
                    'none' => __('None', 'rrze-newsletter'),
                    'tls'  => __('TLS', 'rrze-newsletter'),
                    'ssl'  => __('SSL', 'rrze-newsletter')
                ]
            ],
            [
                'name'              => 'host',
                'label'             => __('Host', 'rrze-newsletter'),
                'desc'              => __('Host ip address.', 'rrze-newsletter'),
                'placeholder'       => '127.0.0.1',
                'type'              => 'text',
                'default'           => '127.0.0.1',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'port',
                'label'             => __('Port', 'rrze-newsletter'),
                'desc'              => __('Host port.', 'rrze-newsletter'),
                'placeholder'       => '587',
                'type'              => 'text',
                'default'           => '587',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'sender',
                'label'             => __('Sender Addresse', 'rrze-newsletter'),
                'desc'              => '',
                'placeholder'       => get_option('admin_email'),
                'type'              => 'text',
                'default'           => get_option('admin_email'),
                'sanitize_callback' => ['\RRZE\Newsletter\Utils', 'sanitizeEmail']
            ],
            [
                'name'  => 'auth',
                'label' => __('Authentication', 'rrze-newsletter'),
                'desc'  => __('Authentication is required to access the SMTP server', 'rrze-newsletter'),
                'type'  => 'checkbox'
            ],
            [
                'name'              => 'username',
                'label'             => __('Username', 'rrze-newsletter'),
                'desc'              => '',
                'placeholder'       => '',
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'    => 'password',
                'label'   => __('Password', 'rrze-newsletter'),
                'desc'    => '',
                'type'    => 'password',
                'default' => '',
                'sanitize_callback' => ['\RRZE\Newsletter\Utils', 'sanitizePassword']
            ]
        ],
        'mail_queue' => [
            [
                'name'              => 'send_limit',
                'label'             => __('Send Limit', 'rrze-newsletter'),
                'desc'              => __('Maximum number of emails that can be sent per minute.', 'rrze-newsletter'),
                'placeholder'       => '15',
                'min'               => '1',
                'max'               => '60',
                'step'              => '1',
                'type'              => 'number',
                'default'           => '15',
                'sanitize_callback' => [
                    function ($input) {
                        return \RRZE\Newsletter\Utils::validateIntRange($input, 15, 1, 60);
                    }
                ]
            ],
            [
                'name'              => 'max_retries',
                'label'             => __('Max. Retries', 'rrze-newsletter'),
                'desc'              => __('Maximum number of retries until an email is sent successfully.', 'rrze-newsletter'),
                'placeholder'       => '1',
                'min'               => '0',
                'max'               => '10',
                'step'              => '1',
                'type'              => 'number',
                'default'           => '1',
                'sanitize_callback' => [
                    function ($input) {
                        return \RRZE\Newsletter\Utils::validateIntRange($input, 1, 0, 10);
                    }
                ]
            ]
        ],
        'mailing_list' => [
            [
                'name'              => 'unsubscribed',
                'label'             => __('Unsubscribed E-mail Addresses', 'rrze-newsletter'),
                'desc'              => __('List of cancelled email addresses through the unsubscription link.', 'rrze-newsletter'),
                'placeholder'       => '',
                'type'              => 'textarea',
                'default'           => '',
                'sanitize_callback' => ['\RRZE\Newsletter\Utils', 'sanitizeMailingList']
            ],
        ],
        'mjml_api' => [
            [
                'name'              => 'key',
                'label'             => __('Application ID', 'rrze-newsletter'),
                'desc'              => '',
                'placeholder'       => '',
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field'
            ],
            [
                'name'              => 'secret',
                'label'             => __('Secret Key', 'rrze-newsletter'),
                'desc'              => '',
                'placeholder'       => '',
                'type'              => 'text',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field'
            ]
        ]
    ];
}
