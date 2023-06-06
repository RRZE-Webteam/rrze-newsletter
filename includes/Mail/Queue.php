<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\{Archive, Parser, Settings, Utils, Tags};
use RRZE\Newsletter\CPT\{Newsletter, NewsletterQueue};
use Html2Text\Html2Text;

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
        if (Newsletter::POST_TYPE === get_post_type($postId)) {
            $this->add($postId);
        }
    }

    protected function add(int $postId)
    {
        $post = get_post($postId);

        if (
            $post->post_status != 'publish'
            || Newsletter::getStatus($postId) != 'send'
        ) {
            return;
        }

        $data = Newsletter::getData($postId);
        if (empty($data) || is_wp_error($data)) {
            Newsletter::setStatus($postId, 'error');
            do_action(
                'rrze.log.error',
                [
                    'plugin' => plugin()->getBaseName(),
                    'method' => __METHOD__,
                    'message' => sprintf('Error: Newsletter %d. The newsletter data is empty or wrong.', absint($postId))
                ]
            );
            return;
        }

        // Maybe the newsletter is recurring.
        $this->maybeSetRecurrence($postId);

        // Check if it should be skipped.
        if ($this->maybeSkipped($postId)) {
            // Set the newsletter status to 'skipped'.
            Newsletter::setStatus($postId, 'skipped');
            return;
        }

        // Set recipient.
        $recipient = [];

        $isMailingListDisabled = apply_filters('rrze_newsletter_disable_mailing_list', false);

        if (!$isMailingListDisabled && !empty($data['mailing_list_terms'])) {
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

                    $to = !empty($name) ? sprintf('%1$s <%2$s>', $name, $email) : $email;

                    $recipient[$email] = [
                        'to_fname' => $fname,
                        'to_lname' => $lname,
                        'to_email' => $email,
                        'to' => $to
                    ];
                }
            }
        } elseif ($isMailingListDisabled && ($email = get_post_meta($postId, 'rrze_newsletter_to_email', true))) {
            if ($email = Utils::sanitizeRecipientEmail($email)) {
                $recipient[$email] = [
                    'to_fname' => '',
                    'to_lname' => '',
                    'to_email' => $email,
                    'to' => $email
                ];
            }
        }

        if (empty($recipient)) {
            Newsletter::setStatus($postId, 'error');
            do_action(
                'rrze.log.error',
                [
                    'plugin' => plugin()->getBaseName(),
                    'method' => __METHOD__,
                    'message' => sprintf('Error: Newsletter %d. The recipient\'s email address array is empty.', absint($postId))
                ]
            );
            return;
        }

        // Update the custom taxonomies' term counts.
        foreach ((array) get_object_taxonomies(Newsletter::POST_TYPE) as $taxonomy) {
            $ttIds = wp_get_object_terms($postId, $taxonomy, ['fields' => 'tt_ids']);
            wp_update_term_count($ttIds, $taxonomy);
        }

        foreach ($recipient as $mail) {
            // Insert post in the mail queue.
            $args = [
                'post_date' => $data['send_date'],
                'post_date_gmt' => $data['send_date_gmt'],
                'post_title' => $data['title'],
                'post_content' => '',
                'post_excerpt' => '',
                'post_type' => NewsletterQueue::POST_TYPE,
                'post_status' => 'mail-queued',
                'post_author' => 1
            ];

            $queueId = wp_insert_post($args);
            if ($queueId == 0 || is_wp_error($queueId)) {
                continue;
            }

            $archiveSlug = Archive::archiveSlug();
            $archiveQuery = Utils::encryptQueryVar($queueId);
            $archiveUrl = site_url($archiveSlug . '/' . $archiveQuery);

            // Parse tags.
            $tags = [
                'FNAME' => $mail['to_fname'],
                'LNAME' => $mail['to_lname'],
                'EMAIL' => $mail['to_email'],
                'ARCHIVE' => $archiveUrl
            ];
            $tags = Tags::sanitizeTags($postId, $tags);
            $parser = new Parser();
            $body = $parser->parse($data['content'], $tags);
            $html2text = new Html2Text($body);
            $altBody = $html2text->getText();
            // End Parse tags. 

            $args = [
                'ID' => $queueId,
                'post_content' => base64_encode($body),
                'post_excerpt' => $altBody
            ];

            $queueId = wp_update_post($args);
            if ($queueId == 0 || is_wp_error($queueId)) {
                continue;
            }

            add_post_meta($queueId, 'rrze_newsletter_queue_newsletter_id', $postId, true);
            add_post_meta($queueId, 'rrze_newsletter_queue_from_email', $data['from_email'], true);
            add_post_meta($queueId, 'rrze_newsletter_queue_from_name', $data['from_name'], true);
            add_post_meta($queueId, 'rrze_newsletter_queue_from', $data['from'], true);
            add_post_meta($queueId, 'rrze_newsletter_queue_replyto', $data['from_email'], true);
            add_post_meta($queueId, 'rrze_newsletter_queue_to', $mail['to'], true);
            add_post_meta($queueId, 'rrze_newsletter_queue_retries', 0, true);
        }

        update_post_meta($postId, 'rrze_newsletter_send_date_gmt', $data['send_date_gmt']);

        // Set the status of the newsletter to "sent".
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

            $to  = get_post_meta($post->ID, 'rrze_newsletter_queue_to', true);

            $subject = $post->post_title;
            $body = base64_decode($post->post_content, true);
            $body = $body !== false ? $body : $post->post_content;
            $altBody = $post->post_excerpt;

            $blogName = get_bloginfo('name');
            $website = $blogName ? $blogName : parse_url(site_url(), PHP_URL_HOST);

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
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

    /**
     * Set the next occurrence date if the bulletin has recurrence rules.
     *
     * @param integer $postId
     * @return void
     */
    protected function maybeSetRecurrence(int $postId)
    {
        $hasConditionals = (bool) get_post_meta($postId, 'rrze_newsletter_has_conditionals', true);
        $isRecurring = (bool) get_post_meta($postId, 'rrze_newsletter_is_recurring', true);
        if (!$hasConditionals || !$isRecurring) {
            return false;
        }

        $repeat = get_post_meta($postId, 'rrze_newsletter_recurrence_repeat', true);
        switch ($repeat) {
            case 'HOURLY':
                $currentTime = current_time('mysql');
                $rrule = 'FREQ=HOURLY;INTERVAL=1';
                break;
            case 'DAILY':
                $currentTime = current_time('mysql');
                $rrule = 'FREQ=DAILY;INTERVAL=1';
                break;
            case 'WEEKLY':
                $currentTime = current_time('Y-m-d');
                $data = Utils::getWeeklyRecurrence($currentTime);
                $rrule = $data[$currentTime] ?? '';
                break;
            case 'MONTHLY':
                $currentTime = current_time('Y-m-d');
                $recurrenceMonthly = get_post_meta($postId, 'rrze_newsletter_recurrence_monthly', true);
                $data = Utils::getMonthlyRecurrence($currentTime);
                $rrule = $data[$currentTime][$recurrenceMonthly] ?? '';
                break;
            default:
                $currentTime = current_time('mysql');
                $rrule = 'ASAP';
                break;
        }

        if ($rrule == 'ASAP') {
            $interval = 'PT5M';
            $dt = new \DateTime($currentTime, Utils::currentTimeZone());
            $dt->add(new \DateInterval($interval));
        } else {
            $nextOcurrence = Utils::nextOcurrences($currentTime, $rrule);
            $dt = $nextOcurrence[0] ?? '';
            if (!$dt) {
                return false;
            }
        }

        $newDate = $dt->format('Y-m-d H:i:s');
        $newGmtDate = get_gmt_from_date($newDate);

        return wp_update_post([
            'ID'            => $postId,
            'post_status'   => 'future',
            'post_date'     => $newDate,
            'post_date_gmt' => $newGmtDate,
        ]);
    }

    /**
     * Check if sending the newsletter should be skipped.
     *
     * @param integer $postId Id of the post.
     * @return boolean True if sending should be skipped.
     */
    protected function maybeSkipped($postId)
    {
        $skipped = false;

        // Check if there are any conditionals.
        $hasConditionals = (bool) get_post_meta($postId, 'rrze_newsletter_has_conditionals', true);
        $rssBlock = (bool) get_post_meta($postId, 'rrze_newsletter_conditionals_rss_block', true);
        $icsBlock = (bool) get_post_meta($postId, 'rrze_newsletter_conditionals_ics_block', true);
        if ($hasConditionals && ($rssBlock || $icsBlock)) {
            $isRssBlockNotEmpty = (bool) wp_cache_get('rrze_newsletter_rss_block_not_empty', $postId);
            $isIcsBlockNotEmpty = (bool) wp_cache_get('rrze_newsletter_ics_block_not_empty', $postId);
            if ($rssBlock && !$isRssBlockNotEmpty) {
                $skipped = true;
            }
            if ($icsBlock && !$isIcsBlockNotEmpty) {
                $skipped = true;
            }
        }

        return $skipped;
    }
}
