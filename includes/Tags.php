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
        // The date the newsletter was sent.
        'DATE'          => '',
        // Displays the current year.
        'CURRENT_YEAR'  => '',
    ];

    public static function sanitizeTags(\WP_POST $post, array $tags = []): array
    {
        $tags = wp_parse_args($tags, self::SUPPORTED_TAGS);
        $tags = array_intersect_key($tags, self::SUPPORTED_TAGS);
        return self::setDefaultValues($post, $tags);
    }

    protected static function setDefaultValues(\WP_POST $post, array $tags): array
    {
        $options = (object) Settings::getOptions();

        $name = !empty($tags['FNAME'] . $tags['LNAME']) ? trim(sprintf('%1$s %2$s', $tags['FNAME'], $tags['LNAME'])) : '';

        $email = Utils::sanitizeEmail($tags['EMAIL']);
        $encryptedEmail = Utils::encrypt($email);

        $subscPageSlug = $options->mailing_list_subsc_page_slug;
        $unsubUrl = site_url($subscPageSlug . '/?update=' . $encryptedEmail);

        $tags['NAME'] = $name;
        $tags['UNSUB'] = $unsubUrl;
        $tags['DATE'] = get_the_time(get_option('date_format'), $post);
        $tags['CURRENT_YEAR'] = date('Y');

        return $tags;
    }
}
