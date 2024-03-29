<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;

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
        // The URL to change the newsletter subscription.
        'UPDATE'        => '',
        // The link of the newsletter archive.
        'ARCHIVE'       => '',
        // The date the newsletter was sent.
        'DATE'          => '',
        // Displays the current year.
        'CURRENT_YEAR'  => '',
        // Show in email only.
        'EMAIL_ONLY'    => 'show'
    ];

    public static function sanitizeTags($postId, array $tags = []): array
    {
        $tags = wp_parse_args($tags, self::SUPPORTED_TAGS);
        $tags = array_intersect_key($tags, self::SUPPORTED_TAGS);
        return self::setDefaultValues($postId, $tags);
    }

    protected static function setDefaultValues($postId, array $tags): array
    {
        $options = (object) Settings::getOptions();

        $name = !empty($tags['FNAME'] . $tags['LNAME']) ? trim(sprintf('%1$s %2$s', $tags['FNAME'], $tags['LNAME'])) : '';

        $email = Utils::sanitizeEmail($tags['EMAIL']);

        $unsubUrl = '';
        $updateUrl = '';
        $subscriptionDisabled = $options->subscription_disabled == 'on' ? true : false;
        $isSubscriptionDisabled = apply_filters('rrze_newsletter_disable_subscription', $subscriptionDisabled);
        if (!$isSubscriptionDisabled) {
            $subscriptionPageSlug = get_post_field('post_name', absint($options->subscription_page_id));
            $subscriptionPage = get_page_by_path($subscriptionPageSlug);
            if (!is_null($subscriptionPage)) {
                $subscriptionPageId = $subscriptionPage->ID;

                $subscriptionPageUrl = get_option('permalink_structure')
                    ? get_page_link($subscriptionPageId)
                    : add_query_arg('page_id', $subscriptionPageId, site_url());

                $unsubUrl = add_query_arg('a', Utils::encryptQueryVar('unsub|' . $email), $subscriptionPageUrl);
                $updateUrl = add_query_arg('a', Utils::encryptQueryVar('update|' . $email), $subscriptionPageUrl);
            }
        }

        $tags['NAME'] = $name;
        $tags['UNSUB'] = $unsubUrl;
        $tags['UPDATE'] = $updateUrl;
        $tags['ARCHIVE'] = untrailingslashit($tags['ARCHIVE']);
        $tags['DATE'] = (string) get_the_time(get_option('date_format'), $postId);
        $tags['CURRENT_YEAR'] = get_the_time('Y', $postId);

        return $tags;
    }
}
