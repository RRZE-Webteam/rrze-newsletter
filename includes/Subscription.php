<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\Mail\Send;
use RRZE\Newsletter\MJML\Renderer;
use Html2Text\Html2Text;

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

    protected $postId;

    protected $pageLink;

    protected $content = '';

    public function __construct()
    {
        $this->optionName = Settings::getOptionName();
        $this->options = (object) Settings::getOptions();

        add_action('wp', [$this, 'init']);
        add_action('wp_ajax_mjml2html', [$this, 'sendEmailConfirmation']);
        add_action('wp_ajax_nopriv_mjml2html', [$this, 'sendEmailConfirmation']);
    }

    public function init()
    {
        $subscriptionDisabled = $this->options->subscription_disabled == 'on' ? true : false;
        $isSubscriptionDisabled = apply_filters('rrze_newsletter_disable_subscription', $subscriptionDisabled);
        $subscriptionPageId = absint($this->options->subscription_page_id);
        if ($isSubscriptionDisabled || is_admin() || !is_page() || !$subscriptionPageId) {
            return;
        }

        $slug = get_post_field('post_name', $subscriptionPageId);

        $post = get_post();
        if ($slug != $post->post_name) {
            return;
        }
        $this->postId = $post->ID;

        $action = '';
        $hash = '';
        $email = '';
        $queryVar = $_GET['a'] ?? '';

        if ($queryVar) {
            $queryVal = $this->getQueryVal($queryVar);
            $action = $queryVal['action'];
            $hash = $queryVal['hash'];
            $email = Utils::sanitizeEmail($queryVal['email']);
        }

        $this->pageLink = get_option('permalink_structure')
            ? get_page_link($this->postId)
            : add_query_arg('page_id', $this->postId, site_url());

        $submitted = false;
        if (
            isset($_POST['rrze_newsletter_subscription_field'])
            && wp_verify_nonce($_POST['rrze_newsletter_subscription_field'], 'rrze_newsletter_subscription')
        ) {
            $submitted = true;
        }

        switch ($action) {
            case '':
                $mlError = '';
                $emailError = '';
                $postEmail = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
                $email = Utils::sanitizeEmail($postEmail);
                $mailingLists = $_POST['mailing_lists'] ?? [];
                $data = [
                    'email' => $email,
                    'mailing_lists' => $mailingLists,
                ];
                if (
                    $submitted
                    && $email
                    && !empty($mailingLists)
                    && !$this->emailExists($email)
                ) {
                    $transient = bin2hex(random_bytes(4));
                    set_transient($transient, $data, MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar('added|' . $transient), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                }
                if ($submitted && empty($mailingLists)) {
                    $mlError = __('Please select at least one subscription.', 'rrze-newsletter');
                }
                if ($submitted && !$postEmail) {
                    $emailError = __('Please fill in this field.', 'rrze-newsletter');
                } elseif ($submitted && !$email) {
                    $emailError = __('The email address does not meet the requirements.', 'rrze-newsletter');
                } elseif ($submitted && $this->emailExists($email)) {
                    $emailError = __('The email address for the subscription is already registered.', 'rrze-newsletter');
                }
                if ($mlError || $emailError) {
                    $data = [
                        'email' => $postEmail,
                        'ml_error' => $mlError,
                        'email_error' => $emailError
                    ];
                    $transient = bin2hex(random_bytes(4));
                    set_transient($transient, $this->sanitizeData($data), MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar('add_error|' . $transient), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                }
                if ($this->publicMailingLists()) {
                    $this->addSubscription();
                } else {
                    $this->noNewslettersAvailable();
                }
                break;
            case 'added':
                $data = $this->getDataFromTransient($hash);
                $email = Utils::sanitizeEmail($data['email']);
                if ($email) {
                    $this->sendConfirmation($this->sanitizeData($data));
                    $this->addedNotice($email);
                }
                break;
            case 'update':
                if ($submitted) {
                    $postEmail = $_POST['email'] ?? '';
                    $email = Utils::sanitizeEmail($postEmail);
                    if ($email) {
                        $mailingLists = $_POST['mailing_lists'] ?? [];
                        $unsubscribeAll = isset($_POST['unsubscribe_all']) ? true : false;
                        $data = [
                            'action' => $action,
                            'email' => $email,
                            'mailing_lists' => $mailingLists
                        ];
                        $this->updateMailingLists($this->sanitizeData($data), $unsubscribeAll);
                        $transient = bin2hex(random_bytes(4));
                        set_transient($transient, $email, MINUTE_IN_SECONDS);
                        $redirect = add_query_arg('a', Utils::encryptQueryVar('updated|' . $transient), $this->pageLink);
                        wp_redirect($redirect);
                        exit();
                    }
                } elseif ($email && $this->emailExists($email)) {
                    $this->updateSubscription($email);
                } elseif ($email && !$this->emailExists($email)) {
                    $this->emailDoesNotExistNotice();
                }
                break;
            case 'updated':
                if ($email = $this->getEmailFromTransient($hash)) {
                    $this->updatedNotice($email);
                }
                break;
            case 'unsub':
                if ($email && $this->emailExists($email)) {
                    $unsubscribed = $this->options->mailing_list_unsubscribed;
                    $unsubscribed = Utils::sanitizeUnsubscribedList($unsubscribed, \ARRAY_N);
                    if (!in_array($email, $unsubscribed)) {
                        $unsubscribed[] = $email;
                        $this->updateUnsubscribed($unsubscribed);
                    }
                    $transient = bin2hex(random_bytes(4));
                    set_transient($transient, $email, MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar('canceled|' . $transient), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                } else {
                    $this->emailDoesNotExistNotice();
                }
                break;
            case 'cancel':
            case 'change':
                $postEmail = isset($_POST['email']) ? sanitize_text_field($_POST['email']) : '';
                $email = Utils::sanitizeEmail($postEmail);
                $error = '';
                $data = [
                    'action' => $action,
                    'email' => $email
                ];
                if ($submitted && $email) {
                    $transient = bin2hex(random_bytes(4));
                    set_transient($transient, $data, MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar('cancel_change|' . $transient), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                }
                if ($submitted && !$postEmail) {
                    $error = __('Please fill in this field.', 'rrze-newsletter');
                } elseif ($submitted && !$email) {
                    $error = __('The email address does not meet the requirements.', 'rrze-newsletter');
                }
                if ($error) {
                    $data = [
                        'email' => $postEmail,
                        'email_error' => $error
                    ];
                    $transient = bin2hex(random_bytes(4));
                    set_transient($transient, $this->sanitizeData($data), MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar('cancel_change_error|' . $transient), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                }
                $this->cancelChangeSubscription();
                break;
            case 'cancel_change':
                $data = $this->getDataFromTransient($hash);
                $email = Utils::sanitizeEmail($data['email']);
                if ($email) {
                    if ($this->emailExists($email)) {
                        $this->sendConfirmation($this->sanitizeData($data));
                        $this->cancelChangeNotice();
                    } else {
                        $this->emailDoesNotExistNotice();
                    }
                }
                break;
            case 'confirm':
                $data = $this->getDataFromTransient($hash);
                $email = Utils::sanitizeEmail($data['email']);
                if ($email) {
                    if (!empty($data['mailing_lists'])) {
                        $this->updateMailingLists($this->sanitizeData($data), false, false);
                    }
                    $transient = bin2hex(random_bytes(4));
                    if ($data['action'] == 'cancel') {
                        $unsubscribed = $this->options->mailing_list_unsubscribed;
                        $unsubscribed = Utils::sanitizeUnsubscribedList($unsubscribed, \ARRAY_N);
                        if (!in_array($email, $unsubscribed)) {
                            $unsubscribed[] = $email;
                            $this->updateUnsubscribed($unsubscribed);
                        }
                        $redirectKey = 'canceled|' . $transient;
                    } elseif ($data['action'] == 'change') {
                        $redirectKey = 'update|' . $email;
                    } else {
                        $redirectKey = 'confirmed|' . $transient;
                    }
                    set_transient($transient, $email, MINUTE_IN_SECONDS);
                    $redirect = add_query_arg('a', Utils::encryptQueryVar($redirectKey), $this->pageLink);
                    wp_redirect($redirect);
                    exit();
                }
                break;
            case 'confirmed':
                if ($email = $this->getEmailFromTransient($hash)) {
                    $this->confirmedNotice($email);
                }
                break;
            case 'canceled':
                if ($email = $this->getEmailFromTransient($hash)) {
                    $this->canceledNotice();
                }
                break;
            case 'add_error':
                if ($data = $this->getDataFromTransient($hash)) {
                    $this->addSubscription($data);
                }
                break;
            case 'cancel_change_error':
                if ($data = $this->getDataFromTransient($hash)) {
                    $this->cancelChangeSubscription($data);
                }
                break;
            default:
                //
        }

        if ($this->content == '') {
            wp_redirect(site_url());
            exit();
        }

        nocache_headers();

        add_filter('the_content', [$this, 'theContent']);

        // Enqueue scripts.
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function theContent($content)
    {
        global $post;

        if ($post->ID != $this->postId) {
            return $content;
        }
        return $this->content;
    }

    public function addSubscription(array $data = [])
    {
        $email = $data['email'] ?? '';
        $lists = $data['mailing_lists'] ?? '';
        $mlError = $data['ml_error'] ?? '';
        $emailError = $data['email_error'] ?? '';

        $mailingLists = $this->publicMailingLists();

        foreach ($mailingLists as $key => $list) {
            $checked = isset($lists[$list['id']]) ? 'checked="checked"' : '';
            $mailingLists[$key]['checked'] = $checked;
        }

        $data = [
            'title' => __('Subscribe to Newsletter', 'rrze-newsletter'),
            'description' => __('To register for the newsletter subscription, please fill out the following form and click on "SUBSCRIBE TO NEWSLETTER". You will then receive an email confirming your subscription. The newsletter will be sent to you automatically by email in text or HTML format.', 'rrze-newsletter'),
            'no_newsletters_available' => __('At the moment there are no newsletters available to subscribe.', 'rrze-newsletter'),
            'button_label' => __('Subscribe to Newsletter', 'rrze-newsletter'),
            'change_cancel_subscription_href' => add_query_arg('a', Utils::encryptQueryVar('change|change'), $this->pageLink),
            'change_cancel_subscription_text' => __('Cancel or change your subscription', 'rrze-newsletter'),
            'mailing_lists' => $mailingLists,
            'email_address_label' => __('Email address', 'rrze-newsletter'),
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field'),
            'email' => $email,
            'ml_error' => $mlError,
            'email_error' => $emailError,
            'formaction' => $this->pageLink,
            'add' => 'add'
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/index.html', $data));
    }

    public function cancelChangeSubscription(array $data = [])
    {
        $email = $data['email'] ?? '';
        $emailError = $data['email_error'] ?? '';

        $data = [
            'title' => __('Cancel or change your newsletter subscription', 'rrze-newsletter'),
            'description' => __('To unsubscribe from all newsletters or to change your current newsletter subscription, please complete the form below. You will then receive an email confirming your subscription.', 'rrze-newsletter'),
            'button_change_label' => __('Change subscription', 'rrze-newsletter'),
            'button_cancel_label' => __('Unsubscribe from all', 'rrze-newsletter'),
            'email_address_label' => __('Email address', 'rrze-newsletter'),
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field'),
            'email' => $email,
            'email_error' => $emailError,
            'formaction_cancel' => add_query_arg('a', Utils::encryptQueryVar('cancel|cancel'), $this->pageLink),
            'formaction_change' => add_query_arg('a', Utils::encryptQueryVar('change|change'), $this->pageLink)
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/cancel-change.html', $data));
    }

    public function updateSubscription($email)
    {
        $options = (object) Settings::getOptions();
        $unsubscribed = explode(PHP_EOL, sanitize_textarea_field((string) $options->mailing_list_unsubscribed));
        $canceled = in_array($email, $unsubscribed) ? 'checked="checked"' : '';

        $mailingLists = $this->getMailingLists($email);

        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'subtitle' => sprintf(
                /* translators: Email address to subscribe to the newsletter */
                __('Manage newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'description' => __('Please check the newsletters below that you would like to receive from us to update your subscription.', 'rrze-newsletter'),
            'no_newsletters_available' => __('At the moment there are no newsletters available to subscribe.', 'rrze-newsletter'),
            'unsubscribe_all_label' => __('Unsubscribe me from all future newsletters', 'rrze-newsletter'),
            'button_label' => __('Update subscription', 'rrze-newsletter'),
            'email' => $email,
            'mailing_lists' => $mailingLists,
            'canceled' => $canceled,
            'nonce_field' => wp_nonce_field('rrze_newsletter_subscription', 'rrze_newsletter_subscription_field'),
            'formaction' => '',
            'update' => 'update'
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/index.html', $data));
    }

    public function addedNotice(string $email)
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('You will now receive an email with a link. By using the link you confirm your subscription.', 'rrze-newsletter'),
            'link_text' => __('Back to home page', 'rrze-newsletter'),
            'link_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function confirmedNotice(string $email)
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('You have successfully signed up for the newsletter subscription.', 'rrze-newsletter'),
            'link_text' => __('Manage newsletter subscription', 'rrze-newsletter'),
            'link_url' => add_query_arg('a', Utils::encryptQueryVar('update|' . $email), $this->pageLink)
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function updatedNotice(string $email)
    {
        $data = [
            'title' => sprintf(
                /* translators: Email address to subscribe to the newsletter */
                __('Newsletter subscription for %s', 'rrze-newsletter'),
                $email
            ),
            'notice' => __('Thank you', 'rrze-newsletter'),
            'description' => __('Your newsletter subscription have been updated.', 'rrze-newsletter'),
            'link_text' => __('Back to newsletter subscription management', 'rrze-newsletter'),
            'link_url' => add_query_arg('a', Utils::encryptQueryVar('update|' . $email), $this->pageLink)
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function cancelChangeNotice()
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('Cancel or change your newsletter subscription', 'rrze-newsletter'),
            'description' => __('You will now receive an email with a link. By using the link, you confirm your cancellation / change.', 'rrze-newsletter'),
            'link_text' => __('Back to home page', 'rrze-newsletter'),
            'link_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function canceledNotice()
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('Cancellation of the subscription to all newsletters.', 'rrze-newsletter'),
            'description' => __('You have successfully unsubscribed from all newsletters.', 'rrze-newsletter'),
            'link_text' => __('Back to home page', 'rrze-newsletter'),
            'link_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function changedNotice(string $email)
    {
        $this->updatedNotice($email);
    }

    public function emailDoesNotExistNotice()
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('Email address does not exist', 'rrze-newsletter'),
            'description' => __('The email address is not registered for a subscription.', 'rrze-newsletter'),
            'link_text' => __('Newsletter subscription', 'rrze-newsletter'),
            'link_url' => $this->pageLink
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public function noNewslettersAvailable()
    {
        $data = [
            'title' => __('Newsletter subscription', 'rrze-newsletter'),
            'notice' => __('No newsletters available', 'rrze-newsletter'),
            'description' => __('At the moment there are no newsletters available to subscribe.', 'rrze-newsletter'),
            'link_text' => __('Back to home page', 'rrze-newsletter'),
            'link_url' => site_url()
        ];

        $this->content = str_replace(PHP_EOL, '', Templates::getContent('subscription/notice.html', $data));
    }

    public static function confirmationSubject()
    {
        return __('Newsletter subscription confirmation', 'rrze-newsletter');
    }

    public static function confirmationMessage()
    {
        $text = __('Thank you for your interest in subscribing to our newsletter. To confirm your subscription, please click on this link:', 'rrze-newsletter') . "\n\n";
        $text .= 'CONFIRMATION_LINK' . "\n\n";
        $text .= __('If the page does not open, copy the link and paste it into the address bar of your browser.', 'rrze-newsletter') . "\n\n";
        $text .= __('If you have not subscribed to our newsletter, please ignore this email.', 'rrze-newsletter') . "\n\n";
        $text .= __('Sincerely', 'rrze-newsletter') . "\n";
        $text .= 'SITE_LINK';
        return $text;
    }

    public static function changeOrCancelSubject()
    {
        return __('Newsletter subscription', 'rrze-newsletter');
    }

    public static function changeOrCancelMessage()
    {
        $text = __('Thank you for your request!', 'rrze-newsletter') . "\n\n";
        $text = __('You would like to change or cancel your newsletter subscription. To make the change or unsubscribe, please click on the following link:', 'rrze-newsletter') . "\n\n";
        $text .= 'CONFIRMATION_LINK' . "\n\n";
        $text .= __('If the page does not open, copy the link and paste it into the address bar of your browser.', 'rrze-newsletter') . "\n\n";
        $text .= __('Sincerely', 'rrze-newsletter') . "\n";
        $text .= 'SITE_LINK';
        return $text;
    }

    protected function sendConfirmation(array $data)
    {
        $action = $data['action'];
        $email = $data['email'];

        $transient = bin2hex(random_bytes(4));
        set_transient($transient, $data, DAY_IN_SECONDS);

        $options = (object) Settings::getOptions();

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
        if (in_array($action, ['cancel', 'change'])) {
            $title = $options->subscription_change_cancel_subject;
            $message = $options->subscription_change_cancel_message;
        }

        $confirmationLink = add_query_arg(
            'a',
            Utils::encryptQueryVar('confirm|' . $transient),
            $this->pageLink
        );
        $confirmationLink = sprintf(
            '<a href="%1$s">%1$s</a>',
            $confirmationLink
        );

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
        $data = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'from' => $from,
            'fromName' => $fromName,
            'replyTo' => $replyTo,
            'to' => $email,
            'subject' => $title,
            'mjml' => Renderer::fromAry($mjmlData)
        ];

        add_action(
            'wp_enqueue_scripts',
            function ($hook) use ($data) {
                $assetFile = include plugin()->getPath('build') . 'subscriptionemail.asset.php';
                wp_enqueue_script(
                    'rrze-newsletter-subscriptionemail',
                    plugins_url('build/subscriptionemail.js', plugin()->getBasename()),
                    $assetFile['dependencies'] ?? [],
                    $assetFile['version'] ?? plugin()->getVersion(),
                    true
                );
                wp_localize_script(
                    'rrze-newsletter-subscriptionemail',
                    'subscriptionEmail',
                    $data
                );
            }
        );
    }

    public function sendEmailConfirmation()
    {
        $from = $_POST['from'] ?? '';
        $fromName = $_POST['fromName'] ?? '';
        $replyTo = $_POST['replyTo'] ?? '';
        $to = $_POST['to'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body']['html'] ?? '';

        $html2text = new Html2Text($body);
        $altBody = $html2text->getText();

        $data = [
            'from' => $from,
            'fromName' => $fromName,
            'replyTo' => $replyTo,
            'to' => $to,
            'subject' => $subject,
            'body' => stripslashes($body),
            'altBody' => $altBody
        ];

        $send = new Send;
        $send->email($data);
    }

    public function getMailingLists(string $email)
    {
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);

        if (!empty($mailingListTerms)) {
            $options = (object) Settings::getOptions();
            $unsubscribed = Utils::sanitizeUnsubscribedList((string) $options->mailing_list_unsubscribed, \ARRAY_N);

            foreach ($mailingListTerms as $term) {
                $mailingLists[$term->term_id] = [
                    'id' => $term->term_id,
                    'title' => $term->name,
                    'description' => $term->description,
                    'checked' => false
                ];

                if (empty($list = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true))) {
                    continue;
                }

                $isPublic = (bool) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_public', true);

                $unsubscribedFromList = Utils::sanitizeUnsubscribedList(
                    (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list_unsubscribed', true),
                    \ARRAY_N
                );

                $subscriptions = [];

                $aryList = Utils::sanitizeMailingList($list, \ARRAY_N);

                if (
                    isset($aryList[$email])
                    && !isset($unsubscribed[$email])
                    && !isset($unsubscribedFromList[$email])
                ) {
                    $subscriptions[$email] = $email;
                }

                if (
                    !isset($aryList[$email])
                    && !$isPublic
                ) {
                    unset($mailingLists[$term->term_id]);
                } else {
                    $checked = isset($subscriptions[$email]) ? 'checked="checked"' : '';
                    $mailingLists[$term->term_id]['checked'] = $checked;
                }
            }
        }

        return $mailingLists ?? [];
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

    protected function emailExists(string $email)
    {
        $allEmails = $this->getAllEmails();
        return isset($allEmails[$email]);
    }

    protected function getAllEmails()
    {
        $emails = [];
        $mailingListTerms = get_terms([
            'taxonomy' => Newsletter::MAILING_LIST,
            'hide_empty' => false,
        ]);
        foreach ($mailingListTerms as $term) {
            $mailingList = (string) get_term_meta($term->term_id, 'rrze_newsletter_mailing_list', true);
            $mailingList = Utils::sanitizeMailingList($mailingList, \ARRAY_N);
            $emails = array_merge($mailingList, $emails);
        }
        return $emails;
    }

    protected function updateMailingLists(array $data, bool $unsubscribeAll, $unsubscribeEmpty = true)
    {
        $email = $data['email'];
        $mailingLists = $data['mailing_lists'];

        $unsubscribed = $this->options->mailing_list_unsubscribed;
        $unsubscribed = Utils::sanitizeUnsubscribedList($unsubscribed, \ARRAY_N);
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

    protected function getQueryVal(string $string)
    {
        $val = Utils::decryptQueryVar(trim($string));
        $val = explode('|', $val);
        $data = [
            'action' => $val[0] ?? '',
            'hash' => $val[1] ?? '',
            'email' => $val[1] ?? ''
        ];
        return $this->sanitizeQueryData($data);
    }

    protected function sanitizeQueryData($data)
    {
        $default = [
            'action' => '',
            'hash' => '',
            'email' => ''
        ];
        $data = wp_parse_args($data, $default);
        $data = array_intersect_key($data, $default);
        return $data;
    }

    protected function getDataFromTransient($transient)
    {
        $val = get_transient($transient);
        delete_transient($transient);
        $data = [
            'action' => $val['action'] ?? '',
            'email' => $val['email'] ?? '',
            'mailing_lists' => $val['mailing_lists'] ?? [],
            'ml_error' => $val['ml_error'] ?? '',
            'email_error' => $val['email_error'] ?? ''
        ];
        return $this->sanitizeData($data);
    }

    protected function sanitizeData($data)
    {
        $default = [
            'action' => '',
            'email' => '',
            'mailing_lists' => '',
            'ml_error' => '',
            'email_error' => ''
        ];
        $data = wp_parse_args($data, $default);
        $data = array_intersect_key($data, $default);
        return $data;
    }

    protected function getEmailFromTransient($transient)
    {
        $email = get_transient($transient);
        delete_transient($transient);
        return Utils::sanitizeEmail($email);
    }

    public function enqueueScripts()
    {
        $assetFile = include plugin()->getPath('build') . 'subscription.asset.php';

        wp_enqueue_style(
            'rrze-newsletter-subscription',
            plugins_url('build/subscription.style.css', plugin()->getBasename()),
            ['dashicons'],
            $assetFile['version'] ?? plugin()->getVersion(),
        );

        wp_enqueue_script(
            'rrze-newsletter-subscription',
            plugins_url('build/subscription.js', plugin()->getBasename()),
            $assetFile['dependencies'] ?? [],
            $assetFile['version'] ?? plugin()->getVersion(),
            true
        );
    }
}
