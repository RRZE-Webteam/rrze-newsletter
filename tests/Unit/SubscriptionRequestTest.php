<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\Subscription;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Mail\Send;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\MailEnvironment as Mail;
use RRZE\Newsletter\Tests\Support\RequestEnvironment as Request;
use RRZE\Newsletter\Tests\Support\RedirectRecorded;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment as Settings;
use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment as State;

final class SubscriptionRequestTest extends ApplicationTestCase
{
    private array $globals;

    protected function setUp(): void
    {
        parent::setUp();
        Request::reset();
        Mail::reset();
        $this->globals = [$_GET, $_POST, isset($GLOBALS['post']), $GLOBALS['post'] ?? null];
        $_GET = $_POST = [];
        $GLOBALS['post'] = $this->post(['post_name' => 'subscribe', 'post_type' => 'page']);
        App::$options['permalink_structure'] = '/%postname%/';
        Settings::$fields['subscription'] = [];
        foreach (['disabled' => '', 'page_id' => 42, 'confirmation_subject' => 'Confirm subscription',
            'confirmation_message' => "Confirm: CONFIRMATION_LINK\nSITE_LINK", 'change_cancel_subject' => 'Manage subscription',
            'change_cancel_message' => 'Manage: CONFIRMATION_LINK SITE_LINK'] as $name => $default) {
            Settings::$fields['subscription'][] = compact('name', 'default');
        }
        Settings::$fields['mailing_list'] = [['name' => 'unsubscribed', 'default' => '']];
    }

    protected function tearDown(): void
    {
        [$_GET, $_POST, $hadPost, $post] = $this->globals;
        if ($hadPost) { $GLOBALS['post'] = $post; } else { unset($GLOBALS['post']); }
        Request::reset();
        Mail::reset();
        parent::tearDown();
    }

    private function member(string $email = ''): void
    {
        State::$terms = [(object) ['term_id' => 12, 'name' => 'Research news', 'description' => 'Updates']];
        State::$termMeta[12] = ['rrze_newsletter_mailing_list' => $email, 'rrze_newsletter_mailing_list_public' => 1];
    }

    private function runRequest(string $action = ''): string
    {
        $_GET = $action === '' ? [] : ['a' => Utils::encryptQueryVar($action)];
        $subscription = new Subscription();
        $this->captureOutput(fn () => $subscription->init());
        return $subscription->theContent('original');
    }

    private function redirect(string $action = ''): array
    {
        try { $this->runRequest($action); self::fail('Expected a redirect'); }
        catch (RedirectRecorded $redirect) {
            parse_str((string) parse_url($redirect->getMessage(), PHP_URL_QUERY), $query);
            return explode('|', Utils::decryptQueryVar($query['a'] ?? ''));
        }
    }

    public function testInitIgnoresDisabledAdminNonPageAndOtherSlugContexts(): void
    {
        foreach (['disabled', 'admin', 'not-page', 'missing-page-id', 'other-slug'] as $case) {
            App::$filters['rrze_newsletter_disable_subscription'] = $case === 'disabled';
            Request::$admin = $case === 'admin';
            Request::$page = $case !== 'not-page';
            Settings::$stored['subscription_page_id'] = $case === 'missing-page-id' ? 0 : 42;
            App::$posts[99] = new \WP_Post((object) ['ID' => 99, 'post_name' => 'elsewhere']);
            App::$currentId = $case === 'other-slug' ? 99 : 42;
            $subscription = new Subscription();
            $subscription->init();
            self::assertSame([], Request::$calls, $case);
            self::assertSame([], State::$termWrites, $case);
        }
    }

    public function testGetRendersFormOnlyWhenListsExistAndAddsScopedHooks(): void
    {
        self::assertStringContainsString('No newsletters available', $this->runRequest());
        $this->member();
        $html = $this->runRequest();
        self::assertStringContainsString('Research news', $html);
        self::assertStringContainsString('rrze_newsletter_subscription_field', $html);
        self::assertContains(['nocache'], Request::$calls);
        self::assertContains('the_content', array_column(App::$hooks, 1));
        self::assertContains('wp_enqueue_scripts', array_column(App::$hooks, 1));
    }

    public function testInvalidNonceDoesNotCreateTransientOrSendMail(): void
    {
        $this->member();
        $_POST = ['rrze_newsletter_subscription_field' => 'bad', 'email' => 'ada@example.test', 'mailing_lists' => [12 => 1]];
        self::assertStringContainsString('Subscribe to Newsletter', $this->runRequest());
        self::assertSame([], Request::$transientWrites);
        self::assertSame([], Mail::$messages);
        self::assertContains(['nonce', 'bad', 'rrze_newsletter_subscription'], Request::$calls);
    }

    public function testNewSubmissionStagesDataWithoutAddingMembershipOrSendingMail(): void
    {
        $this->member();
        Request::$nonceValid = true;
        $_POST = ['rrze_newsletter_subscription_field' => 'valid', 'email' => 'ada@example.test', 'mailing_lists' => [12 => 'on', 0 => 'on']];
        [$action, $token] = $this->redirect();
        self::assertSame('added', $action);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        self::assertSame([$token, ['email' => 'ada@example.test', 'mailing_lists' => [12 => 1]], 60], Request::$transientWrites[0]);
        self::assertSame([], State::$termWrites);
        self::assertSame([], Mail::$messages);
    }

    public function testInvalidAndDuplicateSubmissionsCarryFieldErrors(): void
    {
        $this->member('ada@example.test');
        Request::$nonceValid = true;
        foreach (['' => 'Please fill in this field.', 'invalid' => 'The email address does not meet the requirements.',
            'ada@example.test' => 'The email address for the subscription is already registered.'] as $email => $error) {
            $_POST = ['rrze_newsletter_subscription_field' => 'valid', 'email' => $email];
            [$action, $token] = $this->redirect();
            self::assertSame('add_error', $action);
            self::assertSame($error, State::$transients[$token]['email_error']);
            self::assertSame('Please select at least one subscription.', State::$transients[$token]['ml_error']);
        }
        self::assertSame([], Mail::$messages);
    }

    public function testAddedConsumesStagingTokenAndSendsOneConfirmation(): void
    {
        State::$transients['staged'] = ['email' => 'ada@example.test', 'mailing_lists' => [12 => 1]];
        self::assertStringContainsString('You will now receive an email with a link.', $this->runRequest('added|staged'));
        self::assertSame(['staged'], State::$deletedTransients);
        self::assertCount(1, Mail::$messages);
        self::assertSame('ada@example.test', Mail::$messages[0]['to']);
        self::assertSame('Confirm subscription', Mail::$messages[0]['subject']);
        [$token, $data, $ttl] = Request::$transientWrites[0];
        self::assertSame(86400, $ttl);
        self::assertSame([12 => 1], $data['mailing_lists']);
        self::assertStringContainsString(Utils::encryptQueryVar('confirm|' . $token), Mail::$messages[0]['body']);
        self::assertStringNotContainsString('CONFIRMATION_LINK', Mail::$messages[0]['body']);
        self::assertSame([], State::$termWrites);
    }

    public function testConfirmationAddsMembershipOnceAndStagesSuccessNotice(): void
    {
        $this->member();
        State::$transients['confirm-token'] = ['email' => 'ada@example.test', 'mailing_lists' => [12 => 1]];
        [$action, $token] = $this->redirect('confirm|confirm-token');
        self::assertSame('confirmed', $action);
        self::assertSame('ada@example.test', State::$termMeta[12]['rrze_newsletter_mailing_list']);
        self::assertArrayNotHasKey('confirm-token', State::$transients);
        self::assertSame('ada@example.test', State::$transients[$token]);
        self::assertStringContainsString('successfully signed up', $this->runRequest('confirmed|' . $token));
        self::assertArrayNotHasKey($token, State::$transients);
        $writes = State::$termWrites;
        try { $this->runRequest('confirm|confirm-token'); self::fail('Consumed token should redirect'); }
        catch (RedirectRecorded $redirect) { self::assertSame('https://example.test', $redirect->getMessage()); }
        self::assertSame($writes, State::$termWrites);
    }

    public function testUpdateFormAndSubmissionPreserveMemberName(): void
    {
        $this->member('ada@example.test,Ada,Lovelace');
        self::assertStringContainsString('Manage newsletter subscription for ada@example.test', $this->runRequest('update|ada@example.test'));
        Request::$nonceValid = true;
        $_POST = ['rrze_newsletter_subscription_field' => 'valid', 'email' => 'ada@example.test', 'mailing_lists' => [12 => 'on']];
        [$action, $token] = $this->redirect('update|ada@example.test');
        self::assertSame('updated', $action);
        self::assertSame('ada@example.test,Ada,Lovelace', State::$termMeta[12]['rrze_newsletter_mailing_list']);
        $_POST = [];
        self::assertStringContainsString('have been updated', $this->runRequest('updated|' . $token));
    }

    public function testUnsubscribeRecordsGlobalOptOutAndConsumesSuccessNotice(): void
    {
        $this->member('ada@example.test');
        [$action, $token] = $this->redirect('unsub|ada@example.test');
        self::assertSame('canceled', $action);
        self::assertSame('ada@example.test', State::$optionWrites[0][1]->mailing_list_unsubscribed);
        self::assertStringContainsString('successfully unsubscribed', $this->runRequest('canceled|' . $token));
        self::assertSame([], State::$termWrites);
    }

    public function testUnknownMemberGetsNoticeWithoutMutation(): void
    {
        foreach (['update', 'unsub'] as $action) {
            self::assertStringContainsString('Email address does not exist', $this->runRequest($action . '|missing@example.test'));
        }
        self::assertSame([], State::$optionWrites);
        self::assertSame([], Request::$transientWrites);
    }

    public function testCancelAndChangeFormsValidateBeforeStaging(): void
    {
        foreach (['cancel', 'change'] as $action) {
            $_POST = [];
            self::assertStringContainsString('Change subscription', $this->runRequest($action . '|' . $action));
            Request::$nonceValid = true;
            $_POST = ['rrze_newsletter_subscription_field' => 'valid', 'email' => ''];
            [$next, $token] = $this->redirect($action . '|' . $action);
            self::assertSame('cancel_change_error', $next);
            self::assertSame('Please fill in this field.', State::$transients[$token]['email_error']);
            $_POST['email'] = 'invalid';
            [$next, $token] = $this->redirect($action . '|' . $action);
            self::assertSame('The email address does not meet the requirements.', State::$transients[$token]['email_error']);
            $_POST['email'] = 'ada@example.test';
            [$next, $token] = $this->redirect($action . '|' . $action);
            self::assertSame('cancel_change', $next);
            self::assertSame($action, State::$transients[$token]['action']);
        }
    }

    public function testChangeConfirmationSendsManagementMessageAndRedirectsToUpdate(): void
    {
        $this->member('ada@example.test');
        State::$transients['staged'] = ['action' => 'change', 'email' => 'ada@example.test'];
        self::assertStringContainsString('confirm your cancellation / change', $this->runRequest('cancel_change|staged'));
        self::assertSame('Manage subscription', Mail::$messages[0]['subject']);
        $token = Request::$transientWrites[0][0];
        self::assertSame(['update', 'ada@example.test'], $this->redirect('confirm|' . $token));
        self::assertSame([], State::$termWrites);
    }

    public function testCancelConfirmationRecordsOptOut(): void
    {
        State::$transients['token'] = ['action' => 'cancel', 'email' => 'ada@example.test'];
        [$action] = $this->redirect('confirm|token');
        self::assertSame('canceled', $action);
        self::assertSame('ada@example.test', State::$optionWrites[0][1]->mailing_list_unsubscribed);
    }

    public function testUnknownCancelChangeEmailNeverSendsConfirmation(): void
    {
        State::$transients['staged'] = ['action' => 'cancel', 'email' => 'missing@example.test'];
        self::assertStringContainsString('Email address does not exist', $this->runRequest('cancel_change|staged'));
        self::assertSame([], Mail::$messages);
    }

    public function testErrorActionsRedisplayDataAndPlainPermalinks(): void
    {
        $this->member();
        App::$options['permalink_structure'] = '';
        foreach (['add_error', 'cancel_change_error'] as $action) {
            State::$transients['form'] = ['email' => 'invalid', 'email_error' => 'Check email', 'ml_error' => 'Choose list'];
            $html = $this->runRequest($action . '|form');
            self::assertStringContainsString('Check email', $html);
            self::assertStringContainsString('invalid', $html);
            self::assertStringContainsString('page_id=42', $html);
            self::assertArrayNotHasKey('form', State::$transients);
        }
    }

    public function testContentReplacementIsScopedAndAssetsUseBuiltDependencies(): void
    {
        $subscription = new Subscription();
        $this->captureOutput(fn () => $subscription->init());
        $GLOBALS['post'] = new \WP_Post((object) ['ID' => 99]);
        self::assertSame('Other page', $subscription->theContent('Other page'));
        $subscription->enqueueScripts();
        $asset = include dirname(__DIR__, 2) . '/build/subscription.asset.php';
        self::assertSame(['dashicons'], Request::$assets[0][1][2]);
        self::assertSame($asset['dependencies'], Request::$assets[1][1][2]);
        self::assertSame($asset['version'], Request::$assets[1][1][3]);
    }

    public function testConfirmationBuildersReplaceTokensWithoutKeepingHtmlTags(): void
    {
        $harness = new ConfirmationHarness();
        $html = $harness->emailHtml('Title & more', "<b>Hello</b>\nCONFIRMATION_LINK SITE_LINK", 'https://example.test/confirm?a=1&b=2');
        self::assertStringContainsString('<title>Title &amp; more</title>', $html);
        self::assertStringNotContainsString('<b>', $html);
        self::assertStringContainsString('Hello<br', $html);
        self::assertStringContainsString('href="https://example.test/confirm?a=1&amp;b=2"', $html);
        self::assertSame('Hello https://example.test/confirm https://example.test', $harness->emailText('<b>Hello</b> CONFIRMATION_LINK SITE_LINK'));
        self::assertSame('Newsletter subscription confirmation', Subscription::confirmationSubject());
        self::assertSame('Newsletter subscription', Subscription::changeOrCancelSubject());
        foreach ([Subscription::confirmationMessage(), Subscription::changeOrCancelMessage()] as $message) {
            self::assertSame(1, substr_count($message, 'CONFIRMATION_LINK'));
            self::assertSame(1, substr_count($message, 'SITE_LINK'));
        }
    }

    public function testConfirmationIgnoresInvalidEmailAndRemovesTokenOnException(): void
    {
        $harness = new ConfirmationHarness();
        $harness->send(['email' => 'invalid']);
        self::assertSame([], Request::$transientWrites);
        Mail::$duringSend = static function (): void { throw new \RuntimeException('Transport failure'); };
        $harness->send(['email' => 'ada@example.test']);
        self::assertCount(1, Request::$transientWrites);
        self::assertSame([Request::$transientWrites[0][0]], State::$deletedTransients);
        self::assertSame([], State::$transients);
    }

    public function testSendReturnsSuccessOrErrorAndDiscardsUnknownHeaderInput(): void
    {
        $send = new Send();
        self::assertSame('Email sent successfully to ada@example.test.', $send->email(['to' => 'ada@example.test', 'replyTo' => 'reply@example.test', 'headers' => ['Injected: value']]));
        self::assertSame(['Content-Type: text/html; charset=UTF-8', 'X-Mailtool: RRZE Newsletter test (https://github.com/RRZE-Webteam/rrze-newsletter)', 'Reply-To: reply@example.test'], Mail::$messages[0]['headers']);
        self::assertSame('', Mail::$messages[0]['body']);
        Mail::$result = false;
        $error = $send->email(['to' => 'ada@example.test']);
        self::assertSame('rrze_newsletter_email_error', $error->get_error_code());
        self::assertStringContainsString('ada@example.test', $error->get_error_message());
    }
}

final class ConfirmationHarness extends Subscription
{
    public function __construct() { parent::__construct(); $this->pageLink = 'https://example.test/subscribe/'; }
    public function send(array $data): void { $this->sendConfirmation($data); }
    public function emailHtml(string $title, string $message, string $url): string { return $this->buildConfirmationEmailHtml($title, $message, $url, 'https://example.test', 'Test site'); }
    public function emailText(string $message): string { return $this->buildConfirmationEmailText($message, 'https://example.test/confirm', 'https://example.test'); }
}
