<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Parser;
use RRZE\Newsletter\Tags;
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
     * Set the queue.
     *
     * @return void
     */
    public function set($postId)
    {
        if ('newsletter' !== get_post_type($postId)) {
            return;
        }

        $post = get_post($postId);

        if (
            $post->post_status = 'publish'
            && get_post_meta($postId, 'rrze_newsletter_status', true) == 'send'
        ) {
            $this->add($postId);
        }
    }

    public function add(int $postId)
    {
        $status = Newsletter::getStatus($postId);
        if ($status != 'send') {
            return;
        }

        Newsletter::setStatus($postId, 'queued');

        $data = Newsletter::getData($postId);
        if (empty($data)) {
            Newsletter::setStatus($postId, 'error');
            return;
        }

        // Set the mailing list.
        $mailingList = [];
        if (!empty($data['mailing_list_terms'])) {
            $options = (object) Settings::getOptions();
            $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));

            foreach ($data['mailing_list_terms'] as $term) {
                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    continue;
                }

                $unsubscribedFromList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);
                $unsubscribedFromList = explode(
                    PHP_EOL,
                    sanitize_textarea_field($unsubscribedFromList)
                );
                $unsubscribed = array_unique(
                    array_merge($unsubscribed, $unsubscribedFromList)
                );

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
                    $to = !empty($name) ? sprintf('%1$s <%2$s>', $name, $email) : $email;

                    $mailingList[$email] = [
                        'to_fname' => $fname,
                        'to_lname' => $lname,
                        'to_name' => $name,
                        'to_email' => $email,
                        'to' => $to
                    ];
                }
            }
        }

        if (empty($mailingList)) {
            Newsletter::setStatus($postId, 'error');
            return;
        }

        // Update the custom taxonomies' term counts.
        foreach ((array) get_object_taxonomies(Newsletter::POST_TYPE) as $taxonomy) {
            $ttIds = wp_get_object_terms($postId, $taxonomy, ['fields' => 'tt_ids']);
            wp_update_term_count($ttIds, $taxonomy);
        }

        // Insert post in the mail queue.
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

        foreach ($mailingList as $mail) {
            remove_filter('content_save_pre', 'wp_filter_post_kses');
            remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            $qId = wp_insert_post($args);

            add_filter('content_save_pre', 'wp_filter_post_kses');
            add_filter('content_filtered_save_pre', 'wp_filter_post_kses');

            if ($qId != 0 && !is_wp_error($qId)) {
                add_post_meta($qId, 'rrze_newsletter_queue_newsletter_id', $postId, true);
                add_post_meta($qId, 'rrze_newsletter_queue_newsletter_url', get_permalink($postId));
                add_post_meta($qId, 'rrze_newsletter_queue_from_email', $data['from_email'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_from_name', $data['from_name'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_from', $data['from'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_replyto', $data['from_email'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to_fname', $mail['to_fname'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to_lname', $mail['to_lname'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to_name', $mail['to_name'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to_email', $mail['to_email'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_to', $mail['to'], true);
                add_post_meta($qId, 'rrze_newsletter_queue_retries', 0, true);
            }
        }

        Newsletter::setStatus($postId, 'sent');
    }

    /**
     * Process items from the mail queue.
     */
    public function process()
    {
        $queue = $this->get();
        $start = microtime(true);

        foreach ($queue as $post) {
            $timeElapsed = microtime(true) - $start;
            if ($timeElapsed >= MINUTE_IN_SECONDS) {
                break;
            }

            $newsletterId = get_post_meta($post->ID, 'rrze_newsletter_queue_newsletter_id', true);
            if (get_post_type($newsletterId) !== Newsletter::POST_TYPE) {
                continue;
            }

            $from = get_post_meta($post->ID, 'rrze_newsletter_queue_from_email', true);
            $fromName = get_post_meta($post->ID, 'rrze_newsletter_queue_from_name', true);

            $replyTo = get_post_meta($post->ID, 'rrze_newsletter_queue_replyto', true);

            $toFname  = get_post_meta($post->ID, 'rrze_newsletter_queue_to_fname', true);
            $toLname  = get_post_meta($post->ID, 'rrze_newsletter_queue_to_lname', true);
            $toEmail  = get_post_meta($post->ID, 'rrze_newsletter_queue_to_email', true);
            $toName  = get_post_meta($post->ID, 'rrze_newsletter_queue_to_name', true);
            $to  = get_post_meta($post->ID, 'rrze_newsletter_queue_to', true);

            $subject = $post->post_title;
            $body = $post->post_content;
            $altBody = $post->post_excerpt;

            // Parse tags.
            $data = [
                'FNAME' => $toFname,
                'LNAME' => $toLname,
                'NAME' => $toName,
                'EMAIL' => $toEmail
            ];
            $data = Tags::sanitizeTags($data);
            $parser = new Parser();
            $body = $parser->parse($body, $data);
            $altBody = $parser->parse($altBody, $data);
            // End Parse tags.

            $blogName = get_bloginfo('name');
            $website = $blogName ? $blogName : parse_url(site_url(), PHP_URL_HOST);

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
    public function get()
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
}
