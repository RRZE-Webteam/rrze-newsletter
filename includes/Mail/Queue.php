<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\CPT\NewsletterQueue;

use function RRZE\Newsletter\plugin;

class Queue
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

    public function __construct()
    {
        $this->options = (object) Settings::getOptions();
        $this->smtp = new SMTP;
        $this->smtp->onLoaded();
    }

    /**
     * Get the maximum number of emails that can be queued at once.
     * @return int Max. number of emails queued at once.
     */
    public function queueLimit()
    {
        $limit = $this->options->mail_queue_limit;
        return absint($limit);
    }

    /**
     * Get the maximum number of emails that can be sent per minute.
     * @return int Max. number of emails sent per minute.
     */
    public function sendLimit()
    {
        $limit = $this->options->mail_queue_send_limit;
        return absint($limit);
    }

    /**
     * Get max. number of retries until an email is sent successfully.
     * @return int Max. number of retries.
     */
    public function maxRetries()
    {
        $maxRetries = $this->options->mail_queue_max_retries;
        return absint($maxRetries);
    }

    /**
     * Checks whether the mail queue is being created.
     * @param integer $postId
     * @return boolean
     */
    public static function isQueueBeingCreated(int $postId): bool
    {
        return !empty(get_option('rrze_newsletter_queue_' . $postId));
    }

    /**
     * Process items from the mail queue.
     */
    public function processQueue()
    {
        $queue = $this->getQueue();

        foreach ($queue as $post) {
            $newsletterId = absint(get_post_meta($post->ID, 'rrze_newsletter_queue_newsletter_id', true));
            if (!$newsletterId) {
                continue;
            }

            $from = get_post_meta($newsletterId, 'rrze_newsletter_from_email', true);
            $fromName = get_post_meta($newsletterId, 'rrze_newsletter_from_name', true);

            $replyTo = get_post_meta($newsletterId, 'rrze_newsletter_replyto', true);

            $to  = get_post_meta($post->ID, 'rrze_newsletter_queue_to', true);

            $subject = $post->post_title;
            $body = $post->post_content;
            $altBody = $post->post_excerpt;

            $website = get_bloginfo('name') ?? parse_url(site_url(), PHP_URL_HOST);
            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'X-Mailtool: RRZE-Newsletter Plugin V' . plugin()->getVersion() . ' on ' . $website,
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
                $args = [
                    'ID' => $post->ID,
                    'post_status' => 'mail-sent'
                ];
                wp_update_post($args);
                add_post_meta($post->ID, 'rrze_newsletter_queue_sent_date_gmt', date('Y-m-d H:i:s', time()), true);
            } else {
                $error = $this->smtp->getError();
                update_post_meta($post->ID, 'rrze_newsletter_queue_error', $error->get_error_message());
                $retries = absint(get_post_meta($post->ID, 'rrze_newsletter_queue_retries', true));
                if ($retries >= $this->maxRetries()) {
                    $args = [
                        'ID' => $post->ID,
                        'post_status' => 'mail-error'
                    ];
                    wp_update_post($args);
                } else {
                    $retries++;
                    update_post_meta($post->ID, 'rrze_newsletter_queue_retries', $retries);
                }
            }
        }
    }

    /**
     * Get Mail Queue.
     * @return array Array of post objects.
     */
    public function getQueue()
    {
        $before = time();
        $sendLimit = $this->sendLimit();

        $args = [
            'post_type'         => [NewsletterQueue::POST_TYPE],
            'post_status'       => 'mail-queued',
            'numberposts'       => $sendLimit,
            'order'             => 'ASC',
            'orderby'           => 'date',
            'date_query'        => [
                [
                    'column'    => 'post_date_gmt',
                    'before'    => date('Y-m-d H:i:s', $before),
                    'inclusive' => false,
                ],
            ]
        ];

        return get_posts($args);
    }

    public function setQueue(int $postId)
    {
        $data = Newsletter::getData($postId);
        if (empty($data)) {
            return;
        }

        $mailingListQueue = get_option('rrze_newsletter_queue_' . $postId);
        if (
            empty($mailingListQueue)
            && !empty($data['mail_lists']['terms'])
        ) {
            $options = (object) Settings::getOptions();
            $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));

            $mailingList = [];
            foreach ($data['mail_lists']['terms'] as $term) {
                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    continue;
                }
                $aryList = explode(PHP_EOL, sanitize_textarea_field($list));
                foreach ($aryList as $row) {
                    $aryRow = explode(',', $row);
                    $email = isset($aryRow[0]) ? trim($aryRow[0]) : ''; // Email Address
                    $fname = isset($aryRow[1]) ? trim($aryRow[1]) : ''; // First Name
                    $lname = isset($aryRow[2]) ? trim($aryRow[2]) : ''; // Last Name

                    if (
                        !Utils::sanitizeEmail($email)
                        || in_array($email, $unsubscribed)
                    ) {
                        continue;
                    }

                    $name = !empty($fname . $lname) ? trim(sprintf('%1$s %2$s', $fname, $lname)) : '';
                    $mailingList[$email] = !empty($name) ? sprintf('%1$s <%2$s>', $name, $email) : $email;
                }
            }

            update_option('rrze_newsletter_queue_' . $postId, $mailingList);
            Newsletter::setStatus($postId, 'queued');
            $mailingListQueue = $mailingList;
        }

        $postId = $data['id'];

        $args = [
            'post_date' => $data['send_date'],
            'post_date_gmt' => $data['send_date_gmt'],
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_excerpt' => $data['excerpt'],
            'post_type' => NewsletterQueue::POST_TYPE,
            'post_status' => 'mail-queued',
            'post_author' => 1
        ];

        $count = 1;
        foreach ($mailingListQueue as $key => $email) {
            if ($count > $this->queueLimit()) {
                break;
            }

            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            $qId = wp_insert_post($args);

            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            if ($qId != 0 && !is_wp_error($qId)) {
                add_post_meta($qId, 'rrze_newsletter_queue_newsletter_id', $postId, true);
                add_post_meta($qId, 'rrze_newsletter_queue_newsletter_url', get_permalink($postId));
                add_post_meta($qId, 'rrze_newsletter_queue_from', $data['from'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to', $email, true);
                add_post_meta($qId, 'rrze_newsletter_queue_retries', 0, true);
            }

            unset($mailingListQueue[$key]);
            update_option('rrze_newsletter_queue_' . $postId, $mailingListQueue);
            $count++;
        }

        if (empty($mailingListQueue)) {
            delete_option('rrze_newsletter_queue_' . $postId);
            Newsletter::setStatus($postId, 'sent');
        }
    }
}
