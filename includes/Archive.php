<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;

/**
 * Generates a virtual page showing the output of the newsletter 
 * that does not depend on the style of the theme.
 */
class Archive
{
    /**
     * Option name
     *
     * @var string
     */
    protected $content = '';

    public function __construct()
    {
        add_action('template_redirect', [$this, 'redirectTemplate']);
    }

    public static function getPageBase()
    {
        return Newsletter::POST_TYPE . '/archive';
    }

    public function redirectTemplate()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            return;
        }
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path)));
        if (
            !isset($segments[0])
            || !isset($segments[1])
            || !isset($segments[2])
            || $segments[0] . '/' . $segments[1] != self::getPageBase()
        ) {
            return;
        }

        $archive = $this->getArchive($segments[2]);
        $post = get_post(absint($archive['postid']));
        $timestamp = absint($archive['timestamp']);
        $email = $archive['email'];
        if (
            is_a($post, '\WP_Post')
            && $email = Utils::sanitizeEmail($email)
        ) {
            $this->getContent($post, $timestamp, $email);
        }

        if ($this->content) {
            echo $this->content;
            exit;
        }
    }

    protected function getArchive(string $string)
    {
        $archive = Utils::decryptQueryVar(trim($string));
        $archive = explode('|', $archive);
        $data = [
            'postid' => $archive[0] ?? '',
            'timestamp' => $archive[1] ?? '',
            'email' => $archive[2] ?? ''
        ];
        return $data;
    }

    protected function getContent(\WP_Post $post, int $timestamp, string $email = '')
    {
        $postId = $post->ID;

        $data = Newsletter::getData($postId);
        if (empty($data)) {
            return '';
        }

        if ($archiveContent = get_post_meta($postId, 'rrze_newsletter_archive_' . $timestamp, true)) {
            $data['content'] = $archiveContent;
        }

        // Set recipient.
        $recipient = [];

        $isMailingListDisabled = apply_filters('rrze_newsletter_disable_mailing_list', false);
        if (!$isMailingListDisabled && !empty($data['mailing_list_terms'])) {
            foreach ($data['mailing_list_terms'] as $term) {
                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    continue;
                }

                $aryList = explode(PHP_EOL, sanitize_textarea_field($list));
                foreach ($aryList as $row) {
                    $aryRow = explode(',', $row);
                    $toEmail = isset($aryRow[0]) ? trim($aryRow[0]) : ''; // Email Address
                    $fname = isset($aryRow[1]) ? trim($aryRow[1]) : ''; // First Name
                    $lname = isset($aryRow[2]) ? trim($aryRow[2]) : ''; // Last Name

                    if (Utils::sanitizeEmail($toEmail)) {
                        continue;
                    }

                    $recipient[$toEmail] = [
                        'to_fname' => $fname,
                        'to_lname' => $lname,
                        'to_email' => $toEmail
                    ];
                }
            }
        } elseif ($isMailingListDisabled) {
            $email = (string) get_post_meta($postId, 'rrze_newsletter_to_email', true);
            if ($email = Utils::sanitizeRecipientEmail($email)) {
                $recipient[$email] = [
                    'to_fname' => '',
                    'to_lname' => '',
                    'to_email' => $email
                ];
            }
        }

        $mail = $recipient[$email] ?? ['to_email' => $email];

        $toFname  = $mail['to_fname'] ?? '';
        $toLname  = $mail['to_lname'] ?? '';
        $toEmail  = $mail['to_email'] ?? '';

        $content = $data['content'];

        $archivePageBase = Archive::getPageBase();
        $archiveQuery = Utils::encryptQueryVar($postId . '|' . $timestamp . '|' . $email);
        $archiveUrl = site_url($archivePageBase . '/' . $archiveQuery);

        // Parse tags.
        $data = [
            'FNAME' => $toFname,
            'LNAME' => $toLname,
            'EMAIL' => $toEmail,
            'EMAIL_ONLY' => '',
            'ARCHIVE' => $archiveUrl
        ];
        $data = Tags::sanitizeTags($postId, $data);
        $parser = new Parser();
        $this->content = $parser->parse($content, $data);
    }
}
