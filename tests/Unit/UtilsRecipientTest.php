<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Tests\Support\RecipientEnvironment;
use RRZE\Newsletter\Utils;

final class UtilsRecipientTest extends TestCase
{
    private const SENDER_FILTER = 'rrze_newsletter_sender_allowed_domains';
    private const RECIPIENT_FILTER = 'rrze_newsletter_recipient_allowed_domains';

    protected function setUp(): void
    {
        RecipientEnvironment::reset();
    }

    protected function tearDown(): void
    {
        RecipientEnvironment::reset();
    }

    public function testValidEmailsPreserveAddressAndPlusTag(): void
    {
        foreach (['ada@example.test', 'ada+weekly@example.test', 'Ada.Lovelace@research.example.test'] as $email) {
            self::assertSame($email, Utils::sanitizeEmail($email));
        }
        self::assertSame([], RecipientEnvironment::$actions);
    }

    public function testInvalidEmailShapesReturnEmptyWithoutLoggingAtBaseLevel(): void
    {
        foreach (['', 'missing-at.example.test', '@example.test', 'ada@', 'ada@@example.test', 'Ada <ada@example.test>'] as $email) {
            self::assertSame('', Utils::sanitizeEmail($email), $email);
        }
        self::assertSame([], RecipientEnvironment::$actions);
    }

    public function testEmailValidationUsesTheWordPressSanitizerResult(): void
    {
        RecipientEnvironment::$textResults['raw valid fixture'] = 'ada@example.test';
        RecipientEnvironment::$textResults['raw invalid fixture'] = 'not-an-email';

        self::assertSame('ada@example.test', Utils::sanitizeEmail('raw valid fixture'));
        self::assertSame('', Utils::sanitizeEmail('raw invalid fixture'));
        self::assertSame(['raw valid fixture', 'raw invalid fixture'], RecipientEnvironment::$textCalls);
    }

    public function testEmptyAllowListsDoNotRestrictValidAddresses(): void
    {
        foreach (['', []] as $allowList) {
            RecipientEnvironment::$filters = [self::SENDER_FILTER => $allowList, self::RECIPIENT_FILTER => $allowList];

            self::assertSame('ada@external.test', Utils::sanitizeSenderEmail('ada@external.test'));
            self::assertSame('ada@external.test', Utils::sanitizeRecipientEmail('ada@external.test'));
        }
        self::assertSame([], RecipientEnvironment::$actions);
    }

    public function testSenderAllowListAcceptsListedDomainsAndRejectsOthers(): void
    {
        RecipientEnvironment::$filters[self::SENDER_FILTER] = ['example.test', 'other.test'];

        self::assertSame('ada@example.test', Utils::sanitizeSenderEmail('ada@example.test'));
        self::assertSame('ada@other.test', Utils::sanitizeSenderEmail('ada@other.test'));
        self::assertSame('', Utils::sanitizeSenderEmail('ada@blocked.test'));
        self::assertSame('', Utils::sanitizeSenderEmail('invalid'));
        self::assertSame([], RecipientEnvironment::$actions);
    }

    public function testRecipientAllowListAcceptsAnyListedDomain(): void
    {
        RecipientEnvironment::$filters[self::RECIPIENT_FILTER] = ['example.test', 'other.test'];

        self::assertSame('ada@example.test', Utils::sanitizeRecipientEmail('ada@example.test'));
        self::assertSame('ada@other.test', Utils::sanitizeRecipientEmail('ada@other.test'));
        self::assertSame([], RecipientEnvironment::$actions);
        self::assertSame([[self::RECIPIENT_FILTER, ''], [self::RECIPIENT_FILTER, '']], RecipientEnvironment::$filterCalls);
    }

    public function testSenderAndRecipientPoliciesAreIndependent(): void
    {
        RecipientEnvironment::$filters = [self::SENDER_FILTER => ['sender.test'], self::RECIPIENT_FILTER => ['recipient.test']];

        self::assertSame('a@sender.test', Utils::sanitizeSenderEmail('a@sender.test'));
        self::assertSame('', Utils::sanitizeSenderEmail('a@recipient.test'));
        self::assertSame('a@recipient.test', Utils::sanitizeRecipientEmail('a@recipient.test'));
        self::assertSame('', Utils::sanitizeRecipientEmail('a@sender.test'));
        self::assertCount(1, RecipientEnvironment::$actions);
    }

    public function testAllowedDomainDoesNotMatchSubdomainsOrSuffixLookalikes(): void
    {
        RecipientEnvironment::$filters = [self::SENDER_FILTER => ['example.test'], self::RECIPIENT_FILTER => ['example.test']];
        foreach (['a@sub.example.test', 'a@notexample.test', 'a@example.test.evil.test'] as $email) {
            self::assertSame('', Utils::sanitizeSenderEmail($email));
            self::assertSame('', Utils::sanitizeRecipientEmail($email));
        }
        self::assertCount(3, RecipientEnvironment::$actions);
    }

    public function testInvalidRecipientLogsValidationFailure(): void
    {
        self::assertSame('', Utils::sanitizeRecipientEmail('invalid'));
        self::assertSame([['rrze.log.error', [[
            'plugin' => 'rrze-newsletter/rrze-newsletter.php',
            'method' => 'RRZE\\Newsletter\\Utils::sanitizeRecipientEmail',
            'message' => 'Error: Invalid Email Address. The recipient email address  is not valid.',
        ]]]], RecipientEnvironment::$actions);
    }

    public function testDisallowedRecipientLogsPolicyFailure(): void
    {
        RecipientEnvironment::$filters[self::RECIPIENT_FILTER] = ['example.test'];

        self::assertSame('', Utils::sanitizeRecipientEmail('ada@blocked.test'));
        self::assertSame([['rrze.log.error', [[
            'plugin' => 'rrze-newsletter/rrze-newsletter.php',
            'method' => 'RRZE\\Newsletter\\Utils::sanitizeRecipientEmail',
            'message' => 'Error: Email Address Not Allowed. The recipient email address ada@blocked.test is not allowed.',
        ]]]], RecipientEnvironment::$actions);
    }

    public function testMailingListSortsAddressesAndTrimsEachField(): void
    {
        $input = implode(PHP_EOL, [' zed@example.test , Zed , Last ', ' ada@example.test , Ada , Lovelace ']);
        $expected = ['ada@example.test' => 'ada@example.test,Ada,Lovelace', 'zed@example.test' => 'zed@example.test,Zed,Last'];

        self::assertSame($expected, Utils::sanitizeMailingList($input, 'array'));
        self::assertSame(implode(PHP_EOL, $expected), Utils::sanitizeMailingList($input));
    }

    public function testMailingListLastDuplicateReplacesEarlierNames(): void
    {
        $input = implode(PHP_EOL, ['ada@example.test,Old,Name', 'zed@example.test,Zed', 'ada@example.test,New,Name']);

        self::assertSame([
            'ada@example.test' => 'ada@example.test,New,Name',
            'zed@example.test' => 'zed@example.test,Zed',
        ], Utils::sanitizeMailingList($input, 'array'));
    }

    public function testMailingListSupportsMissingNamesAndIgnoresExtraColumns(): void
    {
        $input = implode(PHP_EOL, ['a@example.test', 'b@example.test,Bea', 'c@example.test,,Family', 'd@example.test,Dee,Last,ignored']);

        self::assertSame([
            'a@example.test' => 'a@example.test',
            'b@example.test' => 'b@example.test,Bea',
            'c@example.test' => 'c@example.test,,Family',
            'd@example.test' => 'd@example.test,Dee,Last',
        ], Utils::sanitizeMailingList($input, 'array'));
    }

    public function testMailingListDropsInvalidAndDisallowedRowsWithoutDroppingValidOnes(): void
    {
        RecipientEnvironment::$filters[self::RECIPIENT_FILTER] = ['example.test'];
        $input = implode(PHP_EOL, ['bad,Invalid', 'ada@example.test,Ada', 'zed@blocked.test,Zed', 'bea@example.test,Bea']);

        self::assertSame('ada@example.test,Ada' . PHP_EOL . 'bea@example.test,Bea', Utils::sanitizeMailingList($input));
        self::assertCount(2, RecipientEnvironment::$actions);
    }

    public function testListHelpersUseSanitizedTextareaContents(): void
    {
        RecipientEnvironment::$textareaResults['raw mailing-list fixture'] = 'ada@example.test,Ada';
        RecipientEnvironment::$textareaResults['raw unsubscribe fixture'] = 'ada@example.test';

        self::assertSame('ada@example.test,Ada', Utils::sanitizeMailingList('raw mailing-list fixture'));
        self::assertSame('ada@example.test', Utils::sanitizeUnsubscribedList('raw unsubscribe fixture'));
        self::assertSame(['raw mailing-list fixture', 'raw unsubscribe fixture'], RecipientEnvironment::$textareaCalls);
    }

    public function testEmptyAndInvalidListsHaveEmptyTextAndArrayOutputs(): void
    {
        foreach (['sanitizeMailingList', 'sanitizeUnsubscribedList'] as $method) {
            foreach (['', 'invalid', PHP_EOL . 'invalid' . PHP_EOL] as $input) {
                self::assertSame('', Utils::$method($input));
                self::assertSame([], Utils::$method($input, 'array'));
            }
        }
    }

    public function testUnsubscribeListTrimsDeduplicatesAndSorts(): void
    {
        $input = implode(PHP_EOL, [' zed@example.test ', 'ada@example.test', 'zed@example.test', ' ada@example.test ']);
        $expected = ['ada@example.test' => 'ada@example.test', 'zed@example.test' => 'zed@example.test'];

        self::assertSame($expected, Utils::sanitizeUnsubscribedList($input, 'array'));
        self::assertSame(implode(PHP_EOL, $expected), Utils::sanitizeUnsubscribedList($input));
    }

    public function testUnsubscribeListDropsInvalidAndDisallowedAddresses(): void
    {
        RecipientEnvironment::$filters[self::RECIPIENT_FILTER] = ['example.test'];
        $input = implode(PHP_EOL, ['invalid', 'ada@example.test', 'a@blocked.test', 'bea@example.test']);

        self::assertSame('ada@example.test' . PHP_EOL . 'bea@example.test', Utils::sanitizeUnsubscribedList($input));
        self::assertCount(2, RecipientEnvironment::$actions);
    }

    public function testUnsubscribeListDoesNotAcceptMailingListNameColumns(): void
    {
        self::assertSame('', Utils::sanitizeUnsubscribedList('ada@example.test,Ada,Lovelace'));
        self::assertCount(1, RecipientEnvironment::$actions);
    }

    public function testAlreadyNormalizedListsRemainStable(): void
    {
        $mailingList = 'ada@example.test,Ada,Lovelace' . PHP_EOL . 'bea@example.test,Bea';
        $unsubscribed = 'ada@example.test' . PHP_EOL . 'bea@example.test';

        self::assertSame($mailingList, Utils::sanitizeMailingList(Utils::sanitizeMailingList($mailingList)));
        self::assertSame($unsubscribed, Utils::sanitizeUnsubscribedList(Utils::sanitizeUnsubscribedList($unsubscribed)));
        self::assertSame([], RecipientEnvironment::$actions);
    }
}
