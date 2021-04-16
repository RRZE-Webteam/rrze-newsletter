<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

class Tags
{
    const SUPPORTED_TAGS = [
        'FNAME'     => '', // First Name
        'LNAME'     => '', // Last Name
        'NAME'      => '', // Full Name
        'EMAIL'     => '', // Email
        'SUBSCURL'  => '', // Subscription Url
        'USUBSCURL' => '', // Update Subscription Url
        'SURL'      => '', // Website Url
    ];

    public static function sanitizeTags(array $tags): array
    {
        $tags = wp_parse_args($tags, self::SUPPORTED_TAGS);
        $tags = array_intersect_key($tags, self::SUPPORTED_TAGS);
        return self::setDefaultValues($tags);
    }

    protected static function setDefaultValues(array $tags): array
    {
        $options = (object) Settings::getOptions();
        $subscPageSlug = $options->mailing_list_subsc_page_slug;
        $email = Utils::sanitizeEmail($tags['EMAIL']);
        $encryptedEmail = Utils::encrypt($email);
        $subscUrl = site_url($subscPageSlug);
        $updateSubscUrl = site_url($subscPageSlug . '/?update=' . $encryptedEmail);

        $tags['SUBSCURL'] = $subscUrl;
        $tags['USUBSCURL'] = $email ? $updateSubscUrl : $subscUrl;
        $tags['SLINK'] = site_url();

        return $tags;
    }
}
