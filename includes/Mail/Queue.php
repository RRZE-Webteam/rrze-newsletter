<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use RRZE\Newsletter\{Archive, Parser, Settings, Utils, Tags};
use RRZE\Newsletter\CPT\{Newsletter, NewsletterQueue};
use RRZE\Newsletter\Scheduling\NewsletterLock;
use Html2Text\Html2Text;

use function RRZE\Newsletter\plugin;

class Queue
{
    private ?NewsletterLock $recurringLock = null;

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
            if ($this->isRecurring($postId)) {
                $lock = NewsletterLock::acquire($postId);
                if (!$lock) {
                    return;
                }
                $this->recurringLock = $lock;
            }
            try {
                $this->add($postId);
            } finally {
                $this->recurringLock?->release();
                $this->recurringLock = null;
            }
        }
    }

    protected function add(int $postId)
    {
        $post = get_post($postId);

        if (
            !$post || $post->post_status != 'publish'
            || Newsletter::getStatus($postId) != 'send'
        ) {
            return;
        }

        // Keep the previous send date available while rendering conditional blocks.
        $sendAttemptDateGmt = current_time('mysql', true);

        // Persist the next occurrence before RSS/ICS requests or queue writes can
        // interrupt this run. Keep this occurrence's dates for its mail snapshots.
        $post = clone $post;
        try {
            $recurrence = $this->maybeSetRecurrence($postId);
            if (is_wp_error($recurrence)) {
                $this->recurrenceError($postId, $recurrence->get_error_message());
                return;
            }
            $data = $this->getNewsletterData($post);
        } catch (\Throwable $error) {
            $this->recurrenceError($postId, 'Queue creation interrupted (' . get_class($error) . ').');
            return;
        }
        if ($this->recurringLock && !$this->recurringLock->owns()) {
            return;
        }
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
            if ($this->recurringLock && !$this->recurringLock->owns()) {
                return;
            }
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
            $tags = Tags::sanitizeTags($post, $tags);
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

        if ($this->recurringLock && !$this->recurringLock->owns()) {
            return;
        }
        update_post_meta($postId, 'rrze_newsletter_send_date_gmt', $sendAttemptDateGmt);

        // Set the status of the newsletter to "sent".
        Newsletter::setStatus($postId, 'sent');
    }

    protected function getNewsletterData(\WP_Post $post): \WP_Error|array|string
    {
        return Newsletter::getData($post->ID, $post);
    }

    /**
     * Reconcile schedules only. Never replay a possibly partial queue build.
     * Runs before mail transport so a failing transport cannot prevent recovery.
     */
    public function recoverRecurringNewsletters(): void
    {
        $offset = 0;
        do {
            $posts = get_posts([
                'post_type' => Newsletter::POST_TYPE,
                'post_status' => ['future', 'publish'],
                'numberposts' => 100,
                'offset' => $offset,
                'orderby' => 'ID',
                'order' => 'ASC',
                'meta_query' => [
                    ['key' => 'rrze_newsletter_has_conditionals', 'value' => '1'],
                    ['key' => 'rrze_newsletter_is_recurring', 'value' => '1'],
                ],
            ]);
            foreach ($posts as $candidate) {
                $lock = NewsletterLock::acquire($candidate->ID);
                if (!$lock) {
                    continue;
                }
                try {
                    // Re-read in case an editor or another cron request changed it.
                    $post = get_post($candidate->ID);
                    if (!$post || $post->post_type !== Newsletter::POST_TYPE
                        || !in_array($post->post_status, ['future', 'publish'], true) || !$this->isRecurring($post->ID)) {
                        continue;
                    }
                    if ($post->post_status === 'future') {
                        if (false !== wp_next_scheduled('publish_future_post', [$post->ID])) {
                            continue;
                        }
                        // Legacy partial builds may already have snapshots for this
                        // exact date. Older issues must not suppress a new issue.
                        $existing = get_posts([
                            'post_type' => NewsletterQueue::POST_TYPE,
                            'post_status' => ['mail-queued', 'mail-sent', 'mail-error'],
                            'numberposts' => 1,
                            'fields' => 'ids',
                            'meta_key' => 'rrze_newsletter_queue_newsletter_id',
                            'meta_value' => $post->ID,
                            'date_query' => [[
                                'column' => 'post_date_gmt',
                                'after' => $post->post_date_gmt,
                                'before' => $post->post_date_gmt,
                                'inclusive' => true,
                            ]],
                        ]);
                        if (!$existing) {
                            $timestamp = strtotime($post->post_date_gmt . ' UTC');
                            $result = $timestamp === false || $timestamp <= 0
                                ? new \WP_Error('newsletter_invalid_date', 'The scheduled newsletter date is invalid.')
                                : $this->ensurePublicationEvent($post->ID, max(time() + MINUTE_IN_SECONDS, $timestamp));
                        } else {
                            $result = $this->maybeSetRecurrence($post->ID);
                        }
                    } else {
                        // A published recurring source has already entered the
                        // send path. Advance it without creating another queue.
                        $result = $this->maybeSetRecurrence($post->ID);
                    }
                    if (is_wp_error($result)) {
                        $this->recurrenceError($post->ID, $result->get_error_message());
                    } elseif ($result !== false) {
                        do_action('rrze.log.info', [
                            'plugin' => plugin()->getBaseName(),
                            'method' => __METHOD__,
                            'message' => sprintf('Newsletter %d: recurring publication schedule restored.', $post->ID),
                        ]);
                    }
                } catch (\Throwable $error) {
                    $this->recurrenceError($candidate->ID, 'Schedule recovery interrupted (' . get_class($error) . ').');
                } finally {
                    $lock->release();
                }
            }
            $offset += count($posts);
        } while (count($posts) === 100);
    }

    protected function isRecurring(int $postId): bool
    {
        return (bool) get_post_meta($postId, 'rrze_newsletter_has_conditionals', true)
            && (bool) get_post_meta($postId, 'rrze_newsletter_is_recurring', true);
    }

    protected function ensurePublicationEvent(int $postId, int $timestamp): true|\WP_Error
    {
        if (false !== wp_next_scheduled('publish_future_post', [$postId])) {
            return true;
        }
        $result = wp_schedule_single_event($timestamp, 'publish_future_post', [$postId], true);
        if (is_wp_error($result)) {
            // A concurrent repair may have won the race to insert the event.
            if (false !== wp_next_scheduled('publish_future_post', [$postId])) {
                return true;
            }
            return $result;
        }
        return $result && false !== wp_next_scheduled('publish_future_post', [$postId])
            ? true
            : new \WP_Error('newsletter_schedule_failed', 'Could not schedule the next newsletter publication.');
    }

    protected function recurrenceError(int $postId, string $message): void
    {
        if (!$this->recurringLock || $this->recurringLock->owns()) {
            Newsletter::setStatus($postId, 'error');
        }
        do_action('rrze.log.error', [
            'plugin' => plugin()->getBaseName(),
            'method' => __METHOD__,
            'message' => sprintf('Error: Newsletter %d. %s', $postId, $message),
        ]);
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
     * @return int|false|\WP_Error Post ID, false when not recurring, or a scheduling error.
     */
    protected function maybeSetRecurrence(int $postId)
    {
        if (!$this->isRecurring($postId)) {
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
                return new \WP_Error('newsletter_recurrence_failed', 'Could not determine the next newsletter date.');
            }
        }

        $newDate = $dt->format('Y-m-d H:i:s');
        $newGmtDate = get_gmt_from_date($newDate);

        $result = wp_update_post([
            'ID'            => $postId,
            'post_status'   => 'future',
            'post_date'     => $newDate,
            'post_date_gmt' => $newGmtDate,
        ], true);
        if (is_wp_error($result)) {
            return $result;
        }
        if (!$result) {
            return new \WP_Error('newsletter_update_failed', 'Could not save the next newsletter date.');
        }
        // WordPress does not propagate errors from its future-post cron hook.
        $scheduled = $this->ensurePublicationEvent($postId, strtotime($newGmtDate . ' UTC'));
        return is_wp_error($scheduled) ? $scheduled : $result;
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
        if ((bool) get_post_meta($postId, 'rrze_newsletter_has_conditionals', true)) {
            $rssBlock = (bool) get_post_meta($postId, 'rrze_newsletter_conditionals_rss_block', true);
            $icsBlock = (bool) get_post_meta($postId, 'rrze_newsletter_conditionals_ics_block', true);
            $isRssBlockNotEmpty = (bool) wp_cache_get('rrze_newsletter_rss_block_not_empty', $postId);
            $isIcsBlockNotEmpty = (bool) wp_cache_get('rrze_newsletter_ics_block_not_empty', $postId);

            wp_cache_delete('rrze_newsletter_rss_block_not_empty', $postId);
            wp_cache_delete('rrze_newsletter_ics_block_not_empty', $postId);

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
