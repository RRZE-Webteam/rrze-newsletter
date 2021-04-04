<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Utils;

class SMTP
{
    /**
     * The email address to send from.
     * @var string
     */
    protected $emailFrom = '';

    /**
     * The name to associate with the $emailFrom email address.
     * @var string
     */
    protected $emailFromName = '';

    /**
     * The plain-text message body.
     * @var string
     */
    protected $altBody = '';

    /**
     * File attachments
     * @var array
     */
    protected $attachments = [];

    /**
     * Error
     * @var object \WP_ERROR
     */
    protected $error;

    /**
     * Options
     * @var object
     */
    protected $options;

    /**
     * __construct
     */
    public function __construct()
    {
        $this->options = (object) Settings::getOptions();
    }

    public function onLoaded()
    {
        // Fires after a PHPMailer\PHPMailer\Exception is caught.
        add_action('wp_mail_failed', [$this, 'onMailError']);
    }

    /**
     * send
     * Send an email.
     * @param string $from
     * @param string $fromName
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @param array $headers
     * @param array $attachment
     * @return boolean
     */
    public function send(
        string $from,
        string $fromName,
        string $to,
        string $subject,
        string $body,
        string $altBody,
        array $headers,
        array $attachments = []
    ): bool {
        $this->emailFrom = $from;
        $this->emailFromName = $fromName;
        $this->altBody = $altBody;
        $this->attachments = $attachments;

        // Setup before send email.
        add_action('phpmailer_init', [$this, 'phpMailerInit']);
        add_filter('wp_mail_content_type', [$this, 'setContentType']);
        add_filter('wp_mail_from', [$this, 'filterFrom']);
        add_filter('wp_mail_from_name', [$this, 'filterName']);

        // Send Email.
        $isSent = wp_mail($to, $subject, $body, $headers);

        // Cleanup after send email.
        $this->attachments = [];
        remove_action('phpmailer_init', [$this, 'phpMailerInit']);
        remove_filter('wp_mail_content_type', [$this, 'setContentType']);
        remove_filter('wp_mail_from', [$this, 'filterFrom']);
        remove_filter('wp_mail_from_name', [$this, 'filterName']);

        return $isSent;
    }

    /**
     * phpmailerInit
     * @param object $phpmailer Ref. to the current instance of \PHPMailer
     */
    public function phpMailerInit($phpmailer)
    {
        $phpmailer->SMTPKeepAlive = true;
        $phpmailer->IsSMTP();

        $phpmailer->Host = $this->options->mail_server_host;
        $phpmailer->Port = $this->options->mail_server_port;
        $phpmailer->SMTPSecure = ($this->options->mail_server_encryption == 'none')
            ? false
            : $this->options->mail_server_encryption;
        $phpmailer->SMTPAuth = ($this->options->mail_server_auth == 'on');
        $phpmailer->Username = $this->options->mail_server_username;
        $phpmailer->Password = Utils::getPassoword($this->options->mail_server_password);

        $phpmailer->Sender = $this->options->mail_server_sender ?: get_option('admin_email');

        $phpmailer->AltBody = $this->altBody;

        // Add attachments to email (if any).
        foreach ($this->attachments as $attachment) {
            if (file_exists($attachment['path'])) {
                $phpmailer->AddEmbeddedImage($attachment['path'], $attachment['cid']);
            }
        }
    }

    public function setContentType()
    {
        return "text/html";
    }

    /**
     * filterFrom
     * Callable function of the hook 'wp_mail_from'. 
     * Filters the email address to send from.
     * @param string $from Sender's email address
     * @return string
     */
    public function filterFrom($from): string
    {
        return (filter_var($this->emailFrom, FILTER_VALIDATE_EMAIL)) ? $this->emailFrom : $from;
    }

    /**
     * filterName
     * Callable function of the hook 'wp_mail_from_name'.
     * Filters the name to associate with the 'from' email address.
     * @param string $name Sender's name
     * @return string
     */
    public function filterName($name): string
    {
        return ($this->emailFromName != '') ? $this->emailFromName : $name;
    }

    /**
     * PHPMailer Exception message
     * @param object $error \WP_Error object with the PHPMailer\PHPMailer\Exception message, 
     *                      and an array containing the mail recipient, subject, message, headers, and attachments.
     * @return object       \WP_Error object
     */
    public function onMailError($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}
