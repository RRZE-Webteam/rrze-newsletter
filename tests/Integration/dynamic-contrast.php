<?php

/**
 * Local WordPress smoke test, never a production health check:
 * wp --skip-plugins --skip-themes eval-file tests/Integration/dynamic-contrast.php
 * Creates/deletes one draft, intercepts wp_mail, and uses only local feed data.
 */
if (!defined('WP_CLI') || !WP_CLI || wp_get_environment_type() === 'production') {
    throw new RuntimeException('Run only through WP-CLI on a non-production WordPress installation.');
}

require_once dirname(__DIR__, 2) . '/rrze-newsletter.php';
\RRZE\Newsletter\plugin()->loaded();
(new \RRZE\Newsletter\Settings())->onLoaded();
new \RRZE\Newsletter\RestApi();

$messages = [];
$requests = 0;
$mail = static function ($return, $args) use (&$messages) {
    $messages[] = $args['message'];
    return true; // PHPMailer/SMTP must never run.
};
$http = static function ($return, $args, $url) use (&$requests) {
    if ($url !== 'https://example.com/rrze-contrast-test.xml') {
        throw new RuntimeException('Unexpected network request: ' . $url);
    }
    $requests++;
    return ['headers' => ['content-type' => 'application/rss+xml'], 'response' => ['code' => 200, 'message' => 'OK'],
        'cookies' => [], 'body' => '<?xml version="1.0"?><rss version="2.0"><channel><title>Fixture</title><link>https://example.com/</link><description>Fixture</description><item><title>RSS contrast fixture</title><link>https://example.com/article</link><description><![CDATA[<p style="color:#fff">Nested RSS text</p>]]></description></item></channel></rss>'];
};
add_filter('pre_wp_mail', $mail, PHP_INT_MAX, 2);
add_filter('pre_http_request', $http, PHP_INT_MAX, 3);

$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$postId = 0;
$previousUser = get_current_user_id();
$calendar = tempnam(sys_get_temp_dir(), 'rrze-contrast-');
try {
    $date = gmdate('Ymd', strtotime('+2 days'));
    file_put_contents($calendar, "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//RRZE//Contrast fixture//EN\r\nBEGIN:VEVENT\r\nUID:contrast@example.test\r\nDTSTAMP:{$date}T080000Z\r\nDTSTART:{$date}T100000Z\r\nDTEND:{$date}T110000Z\r\nSUMMARY:ICS contrast fixture\r\nDESCRIPTION:Calendar description\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n");
    $postId = wp_insert_post(['post_type' => 'newsletter', 'post_status' => 'draft', 'post_title' => 'Temporary contrast regression fixture'], true);
    $check(!is_wp_error($postId) && $postId > 0, 'Could not create fixture.');
    $saved = '<!doctype html><html><head><style>a{color:inherit}</style></head><body style="background:#fff;color:#000"><div>RSS_BLOCK_rss</div><div style="background:#101010">ICS_BLOCK_ics</div><!--[if mso]><p>Outlook fixture</p><![endif]--></body></html>';
    update_post_meta($postId, 'rrze_newsletter_email_html', $saved);
    update_post_meta($postId, 'rrze_newsletter_from_email', 'fixture@example.test');
    update_post_meta($postId, 'rrze_newsletter_rss_attrs', ['rss' => ['postId' => $postId, 'feedURL' => 'https://example.com/rrze-contrast-test.xml',
        'headingColor' => '#fff', 'textColor' => '#fff', 'displayContent' => true]]);
    update_post_meta($postId, 'rrze_newsletter_ics_attrs', ['ics' => ['postId' => $postId, 'feedURL' => 'file://' . $calendar,
        'headingColor' => '#000', 'textColor' => '#000', 'displayDescription' => true]]);

    $data = \RRZE\Newsletter\CPT\Newsletter::getData($postId);
    $check(is_array($data), 'Queue content could not be created.');
    $protected = $data['content'];
    $check(str_contains($protected, 'RSS contrast fixture'), 'RSS did not render.');
    $check(str_contains($protected, 'ICS contrast fixture'), 'ICS did not render.');
    $check(str_contains($protected, 'color:#000000 !important;'), 'RSS on white was not corrected.');
    $check(str_contains($protected, 'color:#ffffff !important;'), 'ICS on dark was not corrected.');
    $check(!str_contains($protected, '_BLOCK_'), 'A feed placeholder survived.');
    $check($requests === 1, 'RSS was fetched more than once.');
    $check((bool) wp_cache_get('rrze_newsletter_rss_block_not_empty', $postId), 'RSS delivery condition was lost.');
    $check((bool) wp_cache_get('rrze_newsletter_ics_block_not_empty', $postId), 'ICS delivery condition was lost.');

    // Preview uses the real REST route, including permissions and ID validation.
    \RRZE\Newsletter\CPT\Newsletter::registerPostType();
    wp_set_current_user(0);
    $route = '/rrze-newsletter/v1/email/' . $postId . '/preview';
    $check(rest_do_request(new WP_REST_Request('GET', $route))->get_status() === 403, 'Anonymous preview was allowed.');
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    $check((bool) $admins, 'This smoke test needs an existing local administrator.');
    wp_set_current_user((int) $admins[0]);
    $metaBefore = get_post_meta($postId);
    $postBefore = get_post($postId)->to_array();
    foreach ([false, 'unchanged'] as $availability) {
        foreach (['rss', 'ics'] as $type) {
            $key = 'rrze_newsletter_' . $type . '_block_not_empty';
            wp_cache_delete($key, $postId);
            if ($availability !== false) {
                wp_cache_set($key, $availability, $postId);
            }
        }
        $preview = rest_do_request(new WP_REST_Request('GET', $route));
        $check($preview->get_status() === 200, 'Preview route failed.');
        $check($preview->get_data()['html'] === $protected, 'Preview differs from final mail HTML.');
        foreach (['rss', 'ics'] as $type) {
            $check(wp_cache_get('rrze_newsletter_' . $type . '_block_not_empty', $postId) === $availability,
                'Preview changed feed delivery conditions.');
        }
    }
    $check(get_post_meta($postId) === $metaBefore && get_post($postId)->to_array() === $postBefore,
        'Preview changed the newsletter or its metadata.');
    $check(!$messages, 'Preview attempted to send mail.');
    $check(rest_do_request(new WP_REST_Request('GET', '/rrze-newsletter/v1/email/0/preview'))->get_status() === 400,
        'Invalid newsletter was accepted.');

    $response = (new \RRZE\Newsletter\RestApi())->test($postId, ['fixture@example.test']);
    $check(!is_wp_error($response), 'Intercepted test mail failed.');
    $check(count($messages) === 1 && $messages[0] === $protected, 'Test mail and queue snapshot differ.');
    $check(get_post_meta($postId, 'rrze_newsletter_email_html', true) === $saved, 'Saved editor HTML was changed.');

    foreach ([false, true] as $enabled) {
        update_post_meta($postId, 'rrze_newsletter_contrast_protection', $enabled);
        $body = \RRZE\Newsletter\CPT\Newsletter::getData($postId)['content'];
        $check(str_contains($body, 'color:#ffffff !important;') === $enabled, 'Explicit contrast setting was ignored.');
        $check(rest_do_request(new WP_REST_Request('GET', $route))->get_data()['html'] === $body, 'Preview ignored contrast setting.');
        (new \RRZE\Newsletter\RestApi())->test($postId, ['fixture@example.test']);
        $check(end($messages) === $body, 'Test mail ignored explicit contrast setting.');
    }
    WP_CLI::success("$checks checks passed; " . count($messages) . ' test mails intercepted, none sent.');
} finally {
    wp_set_current_user($previousUser);
    if (is_int($postId) && $postId > 0) {
        wp_delete_post($postId, true);
        wp_cache_delete('rrze_newsletter_rss_block_not_empty', $postId);
        wp_cache_delete('rrze_newsletter_ics_block_not_empty', $postId);
    }
    unlink($calendar);
    remove_filter('pre_wp_mail', $mail, PHP_INT_MAX);
    remove_filter('pre_http_request', $http, PHP_INT_MAX);
}
