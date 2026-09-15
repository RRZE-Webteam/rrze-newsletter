<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\CPT\Newsletter;
use RRZE\Newsletter\Subscription;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Tests\Support\RecipientEnvironment;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment;
use RRZE\Newsletter\Tests\Support\SettingsHarness;
use RRZE\Newsletter\Tests\Support\SubscriptionEnvironment as State;

final class SubscriptionTest extends TestCase
{
    private SubscriptionHarness $subscription;

    protected function setUp(): void
    {
        State::reset();
        RecipientEnvironment::reset();
        SettingsEnvironment::reset();
        SettingsEnvironment::$fields['mailing_list'] = [['name' => 'unsubscribed', 'label' => 'Unsubscribed', 'default' => '']];
        (new SettingsHarness())->useOptions([]);
        $this->subscription = new SubscriptionHarness();
    }

    protected function tearDown(): void
    {
        State::reset();
        RecipientEnvironment::reset();
        SettingsEnvironment::reset();
        (new SettingsHarness())->useOptions([]);
    }

    public function testMailingListInputUsesPositiveNormalizedKeysOnly(): void
    {
        self::assertSame([12 => 1, 5 => 1], $this->subscription->listsInput([12 => 'on', -5 => false, 0 => 1, 'invalid' => 1]));
        foreach ([null, '', '12', false] as $input) {
            self::assertSame([], $this->subscription->listsInput($input));
        }
    }

    public function testQueryDataAndFormDataRejectUnknownKeysAndSupplyDefaults(): void
    {
        self::assertSame(['action' => 'unsub', 'hash' => '', 'email' => 'ada@example.test'], $this->subscription->queryData(['action' => 'unsub', 'email' => 'ada@example.test', 'admin' => true]));
        self::assertSame(['action' => '', 'email' => '', 'mailing_lists' => [12 => 1], 'ml_error' => '', 'email_error' => ''], $this->subscription->formData(['mailing_lists' => [12 => 1], 'admin' => true]));
    }

    public function testEncryptedQueryDecodesActionAndPayloadWithoutInventingFields(): void
    {
        foreach (['unsub|ada@example.test', 'confirm|one-time-hash'] as $plain) {
            [$action, $payload] = explode('|', $plain);
            self::assertSame(['action' => $action, 'hash' => $payload, 'email' => $payload], $this->subscription->queryValue(' ' . Utils::encryptQueryVar($plain) . ' '));
        }
        self::assertSame(['action' => 'update', 'hash' => '', 'email' => ''], $this->subscription->queryValue(Utils::encryptQueryVar('update')));
    }

    public function testFormTransientIsConsumedOnceAndUnknownFieldsAreDropped(): void
    {
        State::$transients['form-token'] = ['action' => 'add', 'email' => 'ada@example.test', 'mailing_lists' => [12 => 1], 'ml_error' => 'Choose a list', 'admin' => true];
        self::assertSame(['action' => 'add', 'email' => 'ada@example.test', 'mailing_lists' => [12 => 1], 'ml_error' => 'Choose a list', 'email_error' => ''], $this->subscription->formTransient('form-token'));
        self::assertSame([], State::$transients);
        self::assertSame(['form-token'], State::$deletedTransients);
        self::assertSame(['action' => '', 'email' => '', 'mailing_lists' => '', 'ml_error' => '', 'email_error' => ''], $this->subscription->formTransient('form-token'));
    }

    public function testMalformedFormTransientsAreConsumedAndReturnDefaults(): void
    {
        foreach ([false, 'unexpected', 123] as $value) {
            State::$transients['token'] = $value;
            self::assertSame(['action' => '', 'email' => '', 'mailing_lists' => '', 'ml_error' => '', 'email_error' => ''], $this->subscription->formTransient('token'));
            self::assertArrayNotHasKey('token', State::$transients);
        }
        State::$transients['token'] = [];
        self::assertSame([], $this->subscription->formTransient('token')['mailing_lists']);
    }

    public function testEmailTransientIsConsumedAndValidated(): void
    {
        foreach (['ada@example.test' => 'ada@example.test', 'invalid' => ''] as $value => $expected) {
            State::$transients['email-token'] = $value;
            self::assertSame($expected, $this->subscription->emailTransient('email-token'));
            self::assertArrayNotHasKey('email-token', State::$transients);
        }
    }

    public function testPublicMailingListsReturnDisplayDataAndRequestPublicTerms(): void
    {
        $this->term(12, 'News', '', true);
        $this->term(34, 'Events', '', true);
        self::assertSame([
            ['id' => 12, 'title' => 'News', 'description' => 'About News', 'checked' => false],
            ['id' => 34, 'title' => 'Events', 'description' => 'About Events', 'checked' => false],
        ], $this->subscription->publicMailingLists());
        self::assertSame([[
            'taxonomy' => Newsletter::MAILING_LIST, 'hide_empty' => false,
            'meta_query' => [['key' => 'rrze_newsletter_mailing_list_public', 'value' => '1', 'compare' => '=']],
        ]], State::$queries);
    }

    public function testNoTermsReturnsEmptyListsAndNoKnownEmail(): void
    {
        self::assertSame([], $this->subscription->publicMailingLists());
        self::assertSame([], $this->subscription->getMailingLists('ada@example.test'));
        self::assertFalse($this->subscription->hasEmail('ada@example.test'));
    }

    public function testMembershipDisplaysPublicListsAndOnlyJoinedPrivateLists(): void
    {
        $this->term(12, 'Public', 'other@example.test', true);
        $this->term(34, 'Joined', 'ada@example.test,Ada', false);
        $this->term(56, 'Private', 'other@example.test', false);
        $lists = $this->subscription->getMailingLists('ada@example.test');
        self::assertSame([12, 34], array_keys($lists));
        self::assertSame('', $lists[12]['checked']);
        self::assertSame('checked="checked"', $lists[34]['checked']);
    }

    public function testGlobalAndPerListUnsubscribeDisableMembershipCheckmarks(): void
    {
        $this->term(12, 'News', 'ada@example.test', true, 'ada@example.test');
        $this->term(34, 'Events', 'ada@example.test', true);
        $lists = $this->subscription->getMailingLists('ada@example.test');
        self::assertSame('', $lists[12]['checked']);
        self::assertSame('checked="checked"', $lists[34]['checked']);
        SettingsEnvironment::$stored['mailing_list_unsubscribed'] = 'ada@example.test';
        $lists = $this->subscription->getMailingLists('ada@example.test');
        self::assertSame('', $lists[12]['checked']);
        self::assertSame('', $lists[34]['checked']);
    }

    public function testEmailLookupCombinesAllListsAndIgnoresInvalidRows(): void
    {
        $this->term(12, 'News', 'ada@example.test,Ada' . PHP_EOL . 'invalid', true);
        $this->term(34, 'Events', 'bea@example.test,Bea', false);
        self::assertTrue($this->subscription->hasEmail('ada@example.test'));
        self::assertTrue($this->subscription->hasEmail('bea@example.test'));
        self::assertFalse($this->subscription->hasEmail('missing@example.test'));
        self::assertFalse($this->subscription->hasEmail('invalid'));
    }

    public function testUnsubscribeAllUpdatesOnlyGlobalListAndIsIdempotent(): void
    {
        $this->term(12, 'News', 'ada@example.test', true);
        $this->subscription->updateLists('ada@example.test', [], true);
        self::assertCount(1, State::$optionWrites);
        self::assertSame('rrze_newsletter_unit', State::$optionWrites[0][0]);
        self::assertSame('ada@example.test', State::$optionWrites[0][1]->mailing_list_unsubscribed);
        self::assertSame([], State::$termWrites);
        self::assertSame([], State::$queries);
        $this->subscription->updateLists('ada@example.test', [], true);
        self::assertCount(1, State::$optionWrites);
    }

    public function testResubscribeRemovesGlobalAndLocalUnsubscribeWithoutLosingNames(): void
    {
        $this->subscription = new SubscriptionHarness('ada@example.test' . PHP_EOL . 'other@example.test');
        $this->term(12, 'News', 'ada@example.test,Ada,Lovelace', true, 'ada@example.test' . PHP_EOL . 'other@example.test');
        $this->subscription->updateLists('ada@example.test', [12 => 1], false);
        self::assertSame('other@example.test', State::$optionWrites[0][1]->mailing_list_unsubscribed);
        self::assertSame('ada@example.test,Ada,Lovelace', State::$termMeta[12]['rrze_newsletter_mailing_list']);
        self::assertSame('other@example.test', State::$termMeta[12]['rrze_newsletter_mailing_list_unsubscribed']);
        self::assertSame([
            [12, 'rrze_newsletter_mailing_list', 'ada@example.test,Ada,Lovelace'],
            [12, 'rrze_newsletter_mailing_list_unsubscribed', 'other@example.test'],
        ], State::$termWrites);
    }

    public function testSelectingNewListAddsEmailAndDeselectingPublicListUnsubscribes(): void
    {
        $this->term(12, 'Selected', 'zed@example.test,Zed', true);
        $this->term(34, 'Deselected', 'ada@example.test,Ada', true);
        $this->subscription->updateLists('ada@example.test', [12 => 1], false);
        self::assertSame('ada@example.test' . PHP_EOL . 'zed@example.test,Zed', State::$termMeta[12]['rrze_newsletter_mailing_list']);
        self::assertSame('ada@example.test', State::$termMeta[34]['rrze_newsletter_mailing_list_unsubscribed']);
        self::assertSame('ada@example.test,Ada', State::$termMeta[34]['rrze_newsletter_mailing_list']);
        self::assertSame([], State::$optionWrites);
    }

    public function testUnsubscribeEmptyFlagControlsUnselectedPrivateLists(): void
    {
        $this->term(12, 'Private', 'ada@example.test', false);
        $this->subscription->updateLists('ada@example.test', [], false, false);
        self::assertSame('', State::$termMeta[12]['rrze_newsletter_mailing_list_unsubscribed']);
        $this->subscription->updateLists('ada@example.test', [], false, true);
        self::assertSame('ada@example.test', State::$termMeta[12]['rrze_newsletter_mailing_list_unsubscribed']);
    }

    private function term(int $id, string $name, string $members, bool $public, string $unsubscribed = ''): void
    {
        State::$terms[] = (object) ['term_id' => $id, 'name' => $name, 'description' => 'About ' . $name];
        State::$termMeta[$id] = [
            'rrze_newsletter_mailing_list' => $members,
            'rrze_newsletter_mailing_list_public' => $public ? '1' : '',
            'rrze_newsletter_mailing_list_unsubscribed' => $unsubscribed,
        ];
    }
}

/** Exposes protected policies, without booting WordPress, registering hooks or sending email. */
final class SubscriptionHarness extends Subscription
{
    public function __construct(string $unsubscribed = '')
    {
        $this->optionName = 'rrze_newsletter_unit';
        $this->options = (object) ['mailing_list_unsubscribed' => $unsubscribed];
    }

    public function listsInput(mixed $input): array { return $this->sanitizeMailingListsInput($input); }
    public function queryData(array $data): array { return $this->sanitizeQueryData($data); }
    public function formData(array $data): array { return $this->sanitizeData($data); }
    public function queryValue(string $value): array { return $this->getQueryVal($value); }
    public function formTransient(string $key): array { return $this->getDataFromTransient($key); }
    public function emailTransient(string $key): string { return $this->getEmailFromTransient($key); }
    public function hasEmail(string $email): bool { return $this->emailExists($email); }
    public function updateLists(string $email, array $lists, bool $all, bool $empty = true): void
    {
        $this->updateMailingLists(['email' => $email, 'mailing_lists' => $lists], $all, $empty);
    }
}
