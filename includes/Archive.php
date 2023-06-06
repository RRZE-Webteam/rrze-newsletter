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

    public static function archiveSlug()
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
        ) {
            return;
        }

        $archive = Utils::decryptQueryVar(trim($segments[2]));
        $archive = explode('|', $archive);
        $slug = $segments[0] . '/' . $segments[1];

        if ($slug == self::archiveSlug()) {
            $data = [
                'postid' => $archive[0] ?? '',
                'timestamp' => $archive[1] ?? '',
                'email' => $archive[2] ?? ''
            ];
        } else {
            return;
        }

        $post = get_post(absint($data['postid']));
        if (!is_a($post, '\WP_Post')) {
            return;
        }

        if ($data['timestamp'] && $data['email']) {
            // Deprecated.
            $this->deprecatedArchiveContent($post, $data);
        } else {
            $this->archiveContent($post);
        }

        if ($this->content) {
            echo $this->content;
            exit;
        }
    }

    protected function archiveContent(object $post)
    {
        $archiveUrlPath = '/newsletter/archive/';
        $content = base64_decode($post->post_content, true);
        $content = $content !== false ? $content : $post->post_content;
        $this->content = preg_replace_callback('~<a[^>]+href="([^"]*' . $archiveUrlPath . '[^"]*)"[^>]*>(.*?)<\/a>~i', function ($matches) {
            return '';
        }, $content);
    }

    protected function deprecatedArchiveContent(object $post, array $data)
    {
        $postId = $post->ID;
        $timestamp = absint($data['timestamp']);
        $email = Utils::sanitizeEmail($data['email']);
        if (!$timestamp || !$email) {
            return;
        }

        $data = Newsletter::getData($postId);
        if ($content = get_post_meta($postId, 'rrze_newsletter_archive_' . $timestamp, true)) {
            $data['content'] = $content;
        }

        $this->setContent($postId, $data);
    }

    protected function setContent(int $postId, array $data)
    {
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

        // Parse tags.
        $data = [
            'FNAME' => $toFname,
            'LNAME' => $toLname,
            'EMAIL' => $toEmail,
            'EMAIL_ONLY' => '',
            'ARCHIVE' => ''
        ];
        $data = Tags::sanitizeTags($postId, $data);
        $parser = new Parser();
        $this->content = $parser->parse($content, $data);
    }
}
