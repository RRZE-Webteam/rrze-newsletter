<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Tags
{
    const SUPPORTED_TAGS = [
        // The subscriber's first name if it's available in the mailing list.
        'FNAME'         => '',
        // The subscriber's last name if it's available in the mailing list.
        'LNAME'         => '',
        // The subscriber's full name (generated from FNAME and LNAME).
        'NAME'          => '',
        // The subscriber's email address.
        'EMAIL'         => '',
        // The URL to unsubscribe from the newsletters.
        'UNSUB'         => '',
        // The permalink of the newsletter.
        'PERMALINK'     => '',
        // The date the newsletter was sent.
        'DATE'          => '',
        // Displays the current year.
        'CURRENT_YEAR'  => '',
        // Finds out whether a newsletter is public.
        'IS_PUBLIC'  => '',
        // Show in email only.
        'EMAIL_ONLY'  => 'show'
    ];

    public static function sanitizeTags($postId, array $tags = []): array
    {
        $tags = wp_parse_args($tags, self::SUPPORTED_TAGS);
        $tags = array_intersect_key($tags, self::SUPPORTED_TAGS);
        return self::setDefaultValues($postId, $tags);
    }

    protected static function setDefaultValues(int $postId, array $tags): array
    {
        $options = (object) Settings::getOptions();

        $name = !empty($tags['FNAME'] . $tags['LNAME']) ? trim(sprintf('%1$s %2$s', $tags['FNAME'], $tags['LNAME'])) : '';

        $email = Utils::sanitizeEmail($tags['EMAIL']);
        $encryptedEmail = Utils::encrypt($email);

        $subscPageSlug = $options->mailing_list_subsc_page_slug;
        $unsubUrl = site_url($subscPageSlug . '/?update=' . $encryptedEmail);

        $isPublic = (bool) get_post_meta($postId, 'rrze_newsletter_is_public', true);

        $tags['NAME'] = $name;
        $tags['UNSUB'] = $unsubUrl;
        $tags['PERMALINK'] = (string) get_permalink($postId);
        $tags['DATE'] = (string) get_the_time(get_option('date_format'), $postId);
        $tags['CURRENT_YEAR'] = date('Y');
        $tags['IS_PUBLIC'] = (string) $isPublic;

        return $tags;
    }
}
