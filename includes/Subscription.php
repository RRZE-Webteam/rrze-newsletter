<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\Mail\Send;
use RRZE\Newsletter\MJML\Render;

class Subscription
{
    /**
     * Option name
     *
     * @var string
     */
    protected $optionName;

    /**
     * Options
     * @var object
     */
    protected $options;

    protected $pageBase;

    protected $pageTitle;

    protected $content = '';

    public function __construct()
    {
        $this->optionName = Settings::getOptionName();
        $this->options = (object) Settings::getOptions();

        $this->pageBase = self::getPageBase();
        $this->pageTitle = $this->options->subscription_page_title;

        add_action('init', [$this, 'init']);
    }

    public static function getPageBase()
    {
        return Newsletter::POST_TYPE . 's/subscription';
    }

    public static function getPageUrl()
    {
        return site_url(\RRZE\Newsletter\Subscription::getPageBase());
    }

    public function init()
    {
        if (!get_option('permalink_structure')) {
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = array_values(array_filter(explode('/', $path)));
        if (
            is_admin()
            || !is_main_query()
            || !isset($segments[0])
            || !isset($segments[1])
            || $segments[0] . '/' . $segments[1] != self::getPageBase()
        ) {
            return;
        }

        $urlQuery = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        $submitted = false;
        if (
            isset($_POST['rrze_newsletter_subscription_field'])
            && wp_verify_nonce($_POST['rrze_newsletter_subscription_field'], 'rrze_newsletter_subscription')
        ) {
            $submitted = true;
        }

        if (strpos($urlQuery, 'update') !== false && $email = $this->getUpdate()) {
            if ($submitted) {
                $email = $_POST['email'] ?? null;
                $mailingLists = $_POST['mailing_lists'] ?? [];
                $unsubscribeAll = isset($_POST['unsubscribe_all']) ? true : false;
                $this->updateMailingLists($email, $mailingLists, $unsubscribeAll);

                $transient = Utils::encryptUrlQuery(bin2hex(random_bytes(8)));
                set_transient($transient, $email, 30);
                wp_redirect(site_url($this->pageBase . '/?updated=' . $transient));
                exit();
            }
            $this->updateSubscription($email);
        } elseif (strpos($urlQuery, 'updated') !== false && $email = $this->getUpdated()) {
            $this->updatedNotice($email);
        } elseif ($urlQuery == '') {
            $error = '';
            $postEmail = $_POST['email'] ?? '';
            $email = Utils::sanitizeEmail($postEmail);
            $mailingLists = $_POST['mailing_lists'] ?? [];
            if ($submitted && $email) {
                $this->sendConfirmation($email, $mailingLists);

                $transient = Utils::encryptUrlQuery(bin2hex(random_bytes(8)));
                set_transient($transient, $email, 30);
                wp_redirect(site_url($this->pageBase . '/?added=' . $transient));
                exit();
            } elseif ($submitted && !$postEmail) {
                $error = __('Please fill in this field.', 'rrze-newsletter');
            } elseif ($submitted && !$email) {
                $error = __('The email address is not valid.', 'rrze-newsletter');
            }
            if ($error) {
                $errorData = [
                    'email' => sanitize_text_field($postEmail),
                    'mailing_lists' => $mailingLists,
                    'error' => $error
                ];
                $transient = Utils::encryptUrlQuery(bin2hex(random_bytes(8)));
                set_transient($transient, $errorData, 30);
                wp_redirect(site_url($this->pageBase . '/?error=' . $transient));
                exit();
            }
            $this->addSubscription();
        } elseif (strpos($urlQuery, 'error') !== false && $error = $this->getError()) {
            $this->addSubscription($error);
        } elseif (strpos($urlQuery, 'added') !== false && $email = $this->getAdded()) {
            $this->addedNotice($email);
        } elseif (strpos($urlQuery, 'confirmation') !== false && $data = $this->getConfirmation()) {
            $email = $data['email'] ?? null;
            $mailingLists = $data['mailing_lists'] ?? [];
            $this->updateMailingLists($email, $mailingLists, false, false);

            $transient = Utils::encryptUrlQuery(bin2hex(random_bytes(8)));
            set_transient($transient, $email, 30);
            wp_redirect(site_url($this->pageBase . '/?confirmed=' . $transient));
            exit();
        } elseif (strpos($urlQuery, 'confirmed') !== false && $email = $this->getConfirmed()) {
            $this->confirmedNotice($email);
        } else {
            return;
        }

        // Enqueue scripts.
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        // Build the virtual page.
        add_filter('the_posts', [$this, 'generatePage']);
    }

    protected function updateMailingLists(string $email, array $mailingLists, bool $unsubscribeAll, $unsubscribeEmpty = true)
    {
        $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $this->options->mailing_list_unsubscribed));
        if ($unsubscribeAll) {
            if (in_array($email, $unsubscribed)) {
                return;
            }
            $unsubscribed[] = $email;
            $this->updateUnsubscribed($unsubscribed);
            return;
        }
        if (($key = array_search($email, $unsubscribed)) !== false) {
            unset($unsubscribed[$key]);
            $this->updateUnsubscribed($unsubscribed);
        }

        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);
        foreach ($mailingListTerms as $term) {
            $mailingList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true);
            $mailingList = Utils::sanitizeMailingList($mailingList, \ARRAY_N);
            $unsubscribedFromList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);
            $unsubscribedFromList = Utils::sanitizeUnsubscribedList($unsubscribedFromList, \ARRAY_N);
            $isPublic = (bool) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_public', true);
            if (isset($mailingLists[$term->term_id])) {
                if (!isset($mailingList[$email])) {
                    $mailingList[$email] = $email;
                }
                $mailingList = Utils::sanitizeMailingList(implode(PHP_EOL, $mailingList));
                update_term_meta(
                    $term->term_id,
                    'rrze_newsletter_mailing_list',
                    $mailingList
                );
                if (isset($unsubscribedFromList[$email])) {
                    unset($unsubscribedFromList[$email]);
                }
            } else {
                if ($isPublic || $unsubscribeEmpty) {
                    $unsubscribedFromList[$email] = $email;
                }
            }
            $unsubscribedFromList = Utils::sanitizeUnsubscribedList(implode(PHP_EOL, $unsubscribedFromList));
            update_term_meta(
                $term->term_id,
                'rrze_newsletter_mailing_list_unsubscribed',
                $unsubscribedFromList
            );
        }
    }

    protected function updateUnsubscribed(array $unsubscribed)
    {
        $mailingListUsubscribed = Utils::sanitizeUnsubscribedList(implode(PHP_EOL, $unsubscribed));
        $this->options->mailing_list_unsubscribed = $mailingListUsubscribed;
        return update_option($this->optionName, $this->options);
    }

    public function addSubscription(array $data = [])
    {
        $email = $data['email'] ?? '';
        $lists = $data['mailing_lists'] ?? '';
        $error = $data['error'] ?? '';

        $mailingLists = $this->publicMailingLists();

        foreach ($mailingLists as $key => $list) {
            $checked = isset($lists[$list['id']]) ? 'checked="checked"' : '';
            $mailingLists[$key]['checked'] = $checked;
        }

        $data = [
            'title' => __('Add newsletter subscription', 'rrze-newsletter'),
            'description' => __('Please check the newsletters below that you would like to receive from us and then enter your email address to add your subscription.', 'rrze-newsletter'),
            'no_newsletters_available' => __('At the moment there are no newsletters available to subscribe.', 'rrze-newsletter'),
            'button_label' => __('Add subscription', 'rrze-newsletter'),
            'mailing_lists' => $mailingLists,
            'email_placeholder' => __('Enter your email address.', 'rrze-newsletter'),
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field'),
            'email' => $email,
            'error' => $error,
            'action' => site_url($this->pageBase),
            'add' => 'true',
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/index.html', $data));
    }

    public function updateSubscription($email)
    {
        $options = (object) Settings::getOptions();
        $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));
        $canceled = in_array($email, $unsubscribed) ? 'checked="checked"' : '';

        $mailingLists = $this->getMailingLists($email);

        $data = [
            'title' => sprintf(
                /* translators: Email address to subscribe to the newsletter */
                __('Newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'description' => __('Please check the newsletters below that you would like to receive from us to update your subscription.', 'rrze-newsletter'),
            'no_newsletters_available' => __('At the moment there are no newsletters available to subscribe.', 'rrze-newsletter'),
            'unsubscribe_all_label' => __('Cancel my subscription to all future newsletters.', 'rrze-newsletter'),
            'button_label' => __('Update subscription', 'rrze-newsletter'),
            'email' => $email,
            'mailing_lists' => $mailingLists,
            'canceled' => $canceled,
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field'),
            'action' => '',
            'update' => 'true',
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/index.html', $data));
    }

    public function updatedNotice($email)
    {
        $encryptedEmail = Utils::encryptUrlQuery($email);
        $data = [
            'title' => sprintf(
                /* translators: Email address to subscribe to the newsletter */
                __('Newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('Your newsletter settings have been updated.', 'rrze-newsletter'),
            'link_text' => __('Back to manage newsletter subscription page', 'rrze-newsletter'),
            'link_url' => site_url($this->pageBase . '/?update=' . $encryptedEmail)
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function addedNotice(string $email)
    {
        $data = [
            'title' => sprintf(
                __('Newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('We have sent a confirmation link to your email address.', 'rrze-newsletter'),
            'link_text' => __('Back to home page', 'rrze-newsletter'),
            'link_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function confirmedNotice(string $email)
    {
        $encryptedEmail = Utils::encryptUrlQuery($email);
        $data = [
            'title' => sprintf(
                __('Newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('Your newsletter subscription has been confirmed.', 'rrze-newsletter'),
            'link_text' => __('Manage newsletter subscription page', 'rrze-newsletter'),
            'link_url' => site_url($this->pageBase . '/?update=' . $encryptedEmail)
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function getMailingLists(string $email)
    {
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);

        $mailingLists = [];
        if (!empty($mailingListTerms)) {
            $options = (object) Settings::getOptions();
            $unsubscribed = Utils::sanitizeUnsubscribedList((string) $options->mailing_list_unsubscribed, \ARRAY_N);

            foreach ($mailingListTerms as $term) {
                $mailingLists = array_merge($mailingLists, [
                    [
                        'id' => $term->term_id,
                        'title' => $term->name,
                        'description' => $term->description,
                        'checked' => false
                    ]
                ]);

                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    continue;
                }

                $isPublic = (bool) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_public', true);

                $unsubscribedFromList = Utils::sanitizeUnsubscribedList(
                    (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true),
                    \ARRAY_N
                );

                $unsubscribed = array_unique(
                    array_merge($unsubscribed, $unsubscribedFromList)
                );

                $subscriptions = [];

                $aryList = Utils::sanitizeMailingList($list, \ARRAY_N);
                if (
                    isset($aryList[$email])
                    && !isset($unsubscribed[$email])
                ) {
                    $subscriptions[$email] = $email;
                }
                if (
                    !isset($aryList[$email])
                    && !$isPublic
                ) {
                    unset($mailingLists[array_key_last($mailingLists)]);
                } elseif (isset($subscriptions[$email])) {
                    $checked = in_array($email, $subscriptions) ? 'checked="checked"' : '';
                    $mailingLists[array_key_last($mailingLists)]['checked'] = $checked;
                }
            }
        }
        return $mailingLists;
    }

    public function publicMailingLists()
    {
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key'     => 'rrze_newsletter_mailing_list_public',
                    'value'   => '1',
                    'compare' => '='
                ]
            ]
        ]);

        $mailingLists = [];
        if (!empty($mailingListTerms)) {
            foreach ($mailingListTerms as $term) {
                $mailingLists = array_merge($mailingLists, [
                    [
                        'id' => $term->term_id,
                        'title' => $term->name,
                        'description' => $term->description,
                        'checked' => false
                    ]
                ]);
            }
        }
        return $mailingLists;
    }

    public function getUpdate()
    {
        $email = $_GET['update'] ?? '';
        return Utils::sanitizeEmail(Utils::decryptUrlQuery($email));
    }

    public function getUpdated()
    {
        $transient = $_GET['updated'] ?? '';
        $email = get_transient($transient);
        delete_transient($transient);
        return Utils::sanitizeEmail($email);
    }

    public function getError()
    {
        $transient = $_GET['error'] ?? '';
        $error = get_transient($transient);
        delete_transient($transient);
        if (isset($error['email'], $error['mailing_lists'], $error['error'])) {
            return $error;
        }
        return '';
    }

    public function getAdded()
    {
        $transient = $_GET['added'] ?? '';
        $email = get_transient($transient);
        delete_transient($transient);
        return Utils::sanitizeEmail($email);
    }

    public function getConfirmation()
    {
        $confirmation = $_GET['confirmation'] ?? '';
        $data = get_transient($confirmation);
        delete_transient($confirmation);
        $email = $data['email'] ?? '';
        if (Utils::sanitizeEmail($email)) {
            return $data;
        }
        return false;
    }

    public function getConfirmed()
    {
        $transient = $_GET['confirmed'] ?? '';
        $email = get_transient($transient);
        delete_transient($transient);
        return Utils::sanitizeEmail($email);
    }

    protected function sendConfirmation(string $email, array $mailingLists)
    {
        $data = [
            'email' => $email,
            'mailing_lists' => $mailingLists
        ];
        $transient = Utils::encryptUrlQuery(bin2hex(random_bytes(8)));
        set_transient($transient, $data, DAY_IN_SECONDS);

        $options = (object) Settings::getOptions();

        $confirmationLink = sprintf(
            '<a href="%1$s">%2$s',
            site_url($this->pageBase . '/?confirmation=' . $transient),
            __('Confirm subscription to the newsletter.', 'rrze-newsletter')
        );

        $hostname = parse_url(site_url(), PHP_URL_HOST);
        $siteLink = sprintf(
            '<a href="%1$s">%2$s',
            site_url(),
            $hostname
        );

        $blogName = get_bloginfo('name');

        $from = 'no-reply@' . $hostname;
        $fromName = $blogName ? $blogName : $hostname;
        $replyTo = $from;

        $title = $options->subscription_confirmation_subject;
        $message = $options->subscription_confirmation_message;

        $content = $message;
        $content = strip_tags($content);
        $content = wpautop($content);
        $content = str_replace(['<p>', '</p>'], ['<!-- wp:paragraph --><p>', '</p><!-- /wp:paragraph -->'], $content);
        $content = str_replace('CONFIRMATION_LINK', $confirmationLink, $content);
        $content = str_replace('SITE_LINK', $siteLink, $content);
        $content = sprintf(
            '<!-- wp:heading {"textAlign":"center","level":1} --><h1 class="has-text-align-center">%s</h1><!-- /wp:heading -->',
            $title
        ) . $content;

        $mjmlData = [
            'title' => $title,
            'preview_text' => '',
            'background_color' => '#ffffff',
            'content' => $content
        ];

        $mjmlRender = new Render;
        $body = $mjmlRender->toHtml($mjmlData);
        if (is_wp_error($body)) {
            return $body;
        }

        $html2text = new Html2Text($body);
        $altBody = $html2text->getText();

        $args = [
            'from' => $from,
            'fromName' => $fromName,
            'replyTo' => $replyTo,
            'to' => $email,
            'subject' => $title,
            'body' => $body,
            'altBody' => $altBody
        ];

        $send = new Send;
        return $send->email($args);
    }

    public static function confirmationTitle()
    {
        return __('Newsletter Subscription', 'rrze-newsletter');
    }

    public static function confirmationMsg()
    {
        $text = __('Click on the link below to confirm your subscription to the newsletter.', 'rrze-newsletter') . "\n\n";
        $text .= 'CONFIRMATION_LINK' . "\n\n";
        $text .= __('If you have not subscribed to the newsletter, please ignore this email.', 'rrze-newsletter') . "\n\n";
        $text .= __('Sincerely', 'rrze-newsletter') . "\n";
        $text .= 'SITE_LINK';
        return $text;
    }

    public function enqueueScripts($hook)
    {
        wp_enqueue_style(
            'rrze-newsletter-subscription',
            plugins_url('dist/subscription.css', plugin()->getBasename()),
            [],
            plugin()->getVersion()
        );

        wp_enqueue_script(
            'rrze-newsletter-subscription',
            plugins_url('dist/subscription.js', plugin()->getBasename()),
            ['jquery'],
            plugin()->getVersion(),
            true
        );
    }

    public function generatePage(array $posts): array
    {
        global $wp, $wp_query;

        if (strcasecmp($wp->request, $this->pageBase) !== 0) {
            return $posts;
        }

        $post = $this->postObject();

        $posts = [$post];

        $wp_query->is_page = true;
        $wp_query->is_singular = true;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_category = false;
        unset($wp_query->query['error']);
        $wp_query->query_vars['error'] = '';
        $wp_query->is_404 = false;

        nocache_headers();

        return ($posts);
    }

    protected function postObject(): object
    {
        $post                        = new \stdClass;
        $post->ID                    = -1;
        $post->post_author           = 1;
        $post->post_date             = current_time('mysql');
        $post->post_date_gmt         = current_time('mysql', true);
        $post->post_content          = $this->content;
        $post->post_title            = $this->pageTitle;
        $post->post_excerpt          = '';
        $post->post_status           = 'publish';
        $post->comment_status        = 'closed';
        $post->ping_status           = 'closed';
        $post->post_password         = '';
        $post->post_name             = sanitize_title($this->pageBase);
        $post->to_ping               = '';
        $post->pinged                = '';
        $post->modified              = $post->post_date;
        $post->modified_gmt          = $post->post_date_gmt;
        $post->post_content_filtered = '';
        $post->post_parent           = 0;
        $post->guid                  = get_home_url(1, '/' . $this->pageBase);
        $post->menu_order            = 0;
        $post->post_type             = 'page';
        $post->post_mime_type        = '';
        $post->comment_count         = 0;

        return $post;
    }
}
