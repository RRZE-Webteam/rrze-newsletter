<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;

use function RRZE\Newsletter\plugin;

class Send
{
    /**
     * Options
     * @var object
     */
    protected $options;

    /**
     * SMTP
     * @var object RRZE\Newsletter\Mail\SMTP
     */
    protected $smtp;

    protected $render;

    public function __construct()
    {
        $this->options = (object) Settings::getOptions();
        $this->smtp = new SMTP;
        $this->smtp->onLoaded();
    }

    /**
     * Default options
     * @return array
     */
    protected function defaultArgs(): array
    {
        return [
            'from' => '',
            'fromName' => '',
            'replyTo' => '',
            'to' => '',
            'subject' => '',
            'body' => '',
            'altBody' => ''
        ];
    }

    public function email(array $args)
    {
        $default = $this->defaultArgs();
        $args = wp_parse_args($args, $default);
        $args = array_intersect_key($args, $default);

        extract($args);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailtool: RRZE Newsletter ' . plugin()->getVersion() . ' (https://github.com/RRZE-Webteam/rrze-newsletter)',
            'Reply-To: ' . $replyTo
        ];

        $isSent = $this->smtp->send(
            $from,
            $fromName,
            $to,
            $subject,
            $body,
            $altBody,
            $headers
        );

        if ($isSent) {
            $result = sprintf(
                // translators: Message after the email was sent successfully.
                __('Email sent successfully to %s.', 'rrze-newsletter'),
                $to
            );
        } else {
            $result = new \WP_Error(
                'rrze_newsletter_email_error',
                sprintf(
                    // translators: Error message when sending email.
                    __('There was an error sending the email to %s.', 'rrze-newsletter'),
                    $to
                )
            );
        }

        return $result;
    }
}
