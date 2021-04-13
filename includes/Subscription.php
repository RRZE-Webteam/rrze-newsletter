<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use stdClass;

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

    protected $pageSlug;

    protected $pageTitle;

    protected $content = '';

    public function __construct()
    {
        $this->optionName = Settings::getOptionName();
        $this->options = (object) Settings::getOptions();

        $this->pageSlug = $this->options->mailing_list_subsc_page_slug;
        $this->pageTitle = $this->options->mailing_list_subsc_page_title;

        add_action('init', [$this, 'init']);
    }

    public function init()
    {
        $pageSlug = '';
        if (get_option('permalink_structure')) {
            $pageSlug = trim(basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
        }
        if (
            $pageSlug !== $this->pageSlug
            || is_admin()
            || !is_main_query()
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
                $mailingList = $_POST['mailing_lists'] ?? [];
                $unsubscribeAll = isset($_POST['unsubscribe_all']) ? true : false;

                $this->updateMailingLists($email, $mailingList, $unsubscribeAll);

                $transient = Utils::encrypt(bin2hex(random_bytes(8)));
                set_transient($transient, $email, 30);
                wp_redirect(site_url($this->pageSlug . '/?updated=' . $transient));
                exit();
            }
            $this->updateSubscription($email);
        } elseif (strpos($urlQuery, 'updated') !== false && $email = $this->getUpdated()) {
            $this->updatedSubscription($email);
        } elseif (strpos($urlQuery, 'confirmation') !== false && $data = $this->getConfirmation()) {
            if ($submitted) {
                $transient = Utils::encrypt(bin2hex(random_bytes(8)));
                set_transient($transient, $data, DAY_IN_SECONDS);
                wp_redirect(site_url($this->pageSlug . '/?confirmed=' . $transient));
                exit();
            } else {
                $this->updateSubscription($email);
            }
        } elseif ($urlQuery == '') {
            $this->newSubscription();
        } else {
            return;
        }

        // Enqueue scripts.
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);

        // Build the virtual page.
        add_filter('the_posts', [$this, 'generatePage']);
    }

    protected function updateMailingLists(string $email, array $mailingLists, bool $unsubscribeAll)
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
            $mailingList = explode(PHP_EOL, sanitize_textarea_field($mailingList));
            $unsubscribedFromList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);
            $unsubscribedFromList = explode(PHP_EOL, sanitize_textarea_field($unsubscribedFromList));
            if (isset($mailingLists[$term->term_id])) {
                $mailingList[] = $email;
                $mailingList = Utils::sanitizeMailingList(implode(PHP_EOL, $mailingList));
                update_term_meta(
                    $term->term_id,
                    'rrze_newsletter_mailing_list',
                    $mailingList
                );
                if (($key = array_search($email, $unsubscribedFromList)) !== false) {
                    unset($unsubscribedFromList[$key]);
                }
            } else {
                $unsubscribedFromList[] = $email;
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

    public function newSubscription()
    {
        $this->content = 'New Subscription!';
    }

    public function updateSubscription($email)
    {
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);

        $mailingLists = [];
        if (!empty($mailingListTerms)) {
            $options = (object) Settings::getOptions();
            $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));
            $canceled = in_array($email, $unsubscribed) ? 'checked="checked"' : '';

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

                $unsubscribedFromList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true);
                $unsubscribedFromList = explode(
                    PHP_EOL,
                    sanitize_textarea_field($unsubscribedFromList)
                );
                $unsubscribedFromList = array_unique(
                    array_merge($unsubscribed, $unsubscribedFromList)
                );

                $subscriptions = [];
                $aryList = explode(PHP_EOL, sanitize_textarea_field($list));
                foreach ($aryList as $row) {
                    $aryRow = explode(',', $row);
                    $emailAddress = isset($aryRow[0]) ? trim($aryRow[0]) : ''; // Email Address

                    if (
                        !Utils::sanitizeEmail($emailAddress)
                        || in_array($emailAddress, $unsubscribedFromList)
                    ) {
                        continue;
                    }

                    $subscriptions[] = $emailAddress;
                }

                $checked = in_array($email, $subscriptions) ? 'checked="checked"' : '';
                $mailingLists[array_key_last($mailingLists)]['checked'] = $checked;
            }
        }

        $data = [
            'title' => __('Newsletter Subscription', 'rrze-newsletter'),
            'subtitle' => sprintf(
                __('Manage newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'description' => __('Please mark all newsletters below that you would like to receive from us:', 'rrze-newsletter'),
            'unsubscribe_all_label' => __('Cancel my subscription to all future newsletters.', 'rrze-newsletter'),
            'button_label' => __('Update subscription', 'rrze-newsletter'),
            'email' => $email,
            'mailing_lists' => $mailingLists,
            'canceled' => $canceled,
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field')
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/update.html', $data));
    }

    public function updatedSubscription($email)
    {
        $data = [
            'title' => __('Newsletter Subscription', 'rrze-newsletter'),
            'subtitle' => sprintf(
                __('Manage newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'subnotice' => __('Your newsletter settings have been updated.', 'rrze-newsletter'),
            'back_to_home_page' => __('Back to home page', 'rrze-newsletter'),
            'site_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/updated.html', $data));
    }

    public function publicMailingLists()
    {
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);

        $mailingLists = [];
        if (!empty($mailingListTerms)) {
            foreach ($mailingListTerms as $term) {
                $mailingLists = array_merge($mailingLists, [
                    [
                        'id' => $term->term_id,
                        'title' => $term->name,
                        'description' => $term->description
                    ]
                ]);
            }
        }
        return $mailingLists;
    }

    public function getUpdate()
    {
        $email = $_GET['update'] ?? '';
        return Utils::sanitizeEmail(Utils::decrypt($email));
    }

    public function getUpdated()
    {
        $transient = $_GET['updated'] ?? '';
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
        $mailingLists = $data['mailing_lists'] ?? [];
        if (Utils::sanitizeEmail($email) && $mailingLists) {
            return $data;
        }
        return false;
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

        if (strcasecmp($wp->request, $this->pageSlug) !== 0) {
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

        return ($posts);
    }

    protected function postObject(): object
    {
        $post                        = new stdClass;
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
        $post->post_name             = $this->pageSlug;
        $post->to_ping               = '';
        $post->pinged                = '';
        $post->modified              = $post->post_date;
        $post->modified_gmt          = $post->post_date_gmt;
        $post->post_content_filtered = '';
        $post->post_parent           = 0;
        $post->guid                  = get_home_url(1, '/' . $this->pageSlug);
        $post->menu_order            = 0;
        $post->post_type             = 'page';
        $post->post_mime_type        = '';
        $post->comment_count         = 0;

        return $post;
    }
}
