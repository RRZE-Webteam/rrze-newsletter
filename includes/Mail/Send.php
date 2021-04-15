<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Html2Text;
use RRZE\Newsletter\Parser;

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
            'body' => ''
        ];
    }

    public function email(array $args)
    {
        $default = $this->defaultArgs();
        $args = wp_parse_args($args, $default);
        $args = array_intersect_key($args, $default);

        extract($args);

        $emailsList = [];
        $toAry = explode(',', sanitize_textarea_field($to));
        foreach ($toAry as $email) {
            if (!Utils::sanitizeEmail(trim($email))) {
                continue;
            }
            $emailsList[$email] = $email;
        }

        $html2text = new Html2Text($body);
        $altBody = $html2text->getText();

        $blogName = get_bloginfo('name');
        $website = $blogName ? $blogName : parse_url(site_url(), PHP_URL_HOST);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'X-Mailtool: RRZE-Newsletter Plugin V' . plugin()->getVersion() . ' on ' . $website,
            'Reply-To: ' . $replyTo
        ];

        $sentEmails = [];
        foreach ($emailsList as $to) {
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
                $sentEmails[] = $to;
            }
        }

        if (!empty($sentEmails)) {
            $result = sprintf(
                // translators: Message after successful test email.
                __('Email test sent successfully to %s.', 'rrze-newsletter'),
                implode(', ', $sentEmails)
            );
        } else {
            $error = $this->smtp->getError();
            //$result = is_wp_error($error) ? $error->get_error_message() : '';
            $result = new \WP_Error(
                'rrze_newsletter_email_error',
                __('There was an error in the email test.', 'rrze-newsletter')
            );
        }

        return $result;
    }
}
