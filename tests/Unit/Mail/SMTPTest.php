<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RRZE\Newsletter\Mail\SMTP;
use RRZE\Newsletter\Tests\Support\MailEnvironment;
use RRZE\Newsletter\Tests\Support\MailerSpy;

final class SMTPTest extends TestCase
{
    // Existing wire format with the synthetic keys in MailEnvironment.php.
    private const PASSWORD = 'WndKT1RzTFlPL2swVVN4dXlKSmRsTW9qaDd3ejRJa0l0em1aOFhoSG9lST0=';

    protected function setUp(): void
    {
        MailEnvironment::reset();
    }

    protected function tearDown(): void
    {
        MailEnvironment::reset();
    }

    public function testLoadingRegistersErrorHandler(): void
    {
        $smtp = $this->smtp();
        $smtp->onLoaded();

        self::assertSame(['action' => ['wp_mail_failed' => [[$smtp, 'onMailError']]]], MailEnvironment::$hooks);
        self::assertSame([], MailEnvironment::$messages);
    }

    public function testMessageIsForwardedUnchangedToWordPress(): void
    {
        self::assertTrue($this->send($this->smtp()));
        self::assertSame([[
            'to' => 'recipient@example.test',
            'subject' => 'Grüße from the newsletter',
            'body' => '<p>Hello &amp; welcome</p>',
            'headers' => ['Reply-To: reply@example.test', 'X-Test: unchanged'],
        ]], MailEnvironment::$messages);
    }

    public function testMailFailureIsReturnedWithoutRetrying(): void
    {
        MailEnvironment::$result = false;

        self::assertFalse($this->send($this->smtp()));
        self::assertCount(1, MailEnvironment::$messages);
    }

    public function testPerMessageHooksAreActiveDuringSendAndRemovedAfterwards(): void
    {
        foreach ([true, false] as $result) {
            MailEnvironment::reset();
            MailEnvironment::$result = $result;
            $smtp = $this->smtp();
            $smtp->onLoaded();
            MailEnvironment::$duringSend = static function () use ($smtp): void {
                self::assertSame([[$smtp, 'phpMailerInit']], MailEnvironment::$hooks['action']['phpmailer_init']);
                self::assertSame([[$smtp, 'setContentType']], MailEnvironment::$hooks['filter']['wp_mail_content_type']);
                self::assertSame([[$smtp, 'filterFrom']], MailEnvironment::$hooks['filter']['wp_mail_from']);
                self::assertSame([[$smtp, 'filterName']], MailEnvironment::$hooks['filter']['wp_mail_from_name']);
            };

            self::assertSame($result, $this->send($smtp));

            self::assertSame([
                ['action', 'phpmailer_init', [$smtp, 'phpMailerInit']],
                ['filter', 'wp_mail_content_type', [$smtp, 'setContentType']],
                ['filter', 'wp_mail_from', [$smtp, 'filterFrom']],
                ['filter', 'wp_mail_from_name', [$smtp, 'filterName']],
            ], MailEnvironment::$removedHooks);
            self::assertSame([], MailEnvironment::$hooks['action']['phpmailer_init']);
            self::assertSame([[$smtp, 'onMailError']], MailEnvironment::$hooks['action']['wp_mail_failed']);
            self::assertSame([
                'wp_mail_content_type' => [],
                'wp_mail_from' => [],
                'wp_mail_from_name' => [],
            ], MailEnvironment::$hooks['filter']);
        }
    }

    public function testCleanupPreservesOtherPluginsCallbacks(): void
    {
        $otherCallback = static function (): void {};
        MailEnvironment::$hooks = [
            'action' => ['phpmailer_init' => [$otherCallback]],
            'filter' => ['wp_mail_from' => [$otherCallback]],
        ];

        $this->send($this->smtp());

        self::assertSame([$otherCallback], MailEnvironment::$hooks['action']['phpmailer_init']);
        self::assertSame([$otherCallback], MailEnvironment::$hooks['filter']['wp_mail_from']);
    }

    public function testPhpMailerReceivesConfigurationAndDecryptedPassword(): void
    {
        $smtp = $this->smtp();
        $mailer = new MailerSpy();
        MailEnvironment::$duringSend = static function () use ($smtp, $mailer): void {
            $smtp->phpMailerInit($mailer);
        };

        $this->send($smtp);

        self::assertSame(1, $mailer->smtpCalls);
        self::assertTrue($mailer->SMTPKeepAlive);
        self::assertSame('smtp.example.test', $mailer->Host);
        self::assertSame(587, $mailer->Port);
        self::assertSame('tls', $mailer->SMTPSecure);
        self::assertTrue($mailer->SMTPAuth);
        self::assertSame('newsletter-user', $mailer->Username);
        self::assertSame('smtp-test-password', $mailer->Password);
        self::assertSame('bounces@example.test', $mailer->Sender);
        self::assertSame('Hello & welcome', $mailer->AltBody);
        self::assertSame([], MailEnvironment::$optionReads);
    }

    public function testEncryptionNoneDisablesSecureTransportAndSslIsPreserved(): void
    {
        foreach (['none' => false, 'ssl' => 'ssl'] as $option => $expected) {
            $mailer = new MailerSpy();
            $this->smtp(['mail_server_encryption' => $option])->phpMailerInit($mailer);

            self::assertSame($expected, $mailer->SMTPSecure);
        }
    }

    public function testAuthenticationIsEnabledOnlyByOnSetting(): void
    {
        foreach (['on' => true, 'off' => false, '' => false, 'ON' => false] as $option => $expected) {
            $mailer = new MailerSpy();
            $this->smtp(['mail_server_auth' => $option])->phpMailerInit($mailer);

            self::assertSame($expected, $mailer->SMTPAuth);
        }
    }

    public function testEmptyEnvelopeSenderFallsBackToAdminAddress(): void
    {
        $mailer = new MailerSpy();
        $this->smtp(['mail_server_sender' => ''])->phpMailerInit($mailer);

        self::assertSame('admin@example.test', $mailer->Sender);
        self::assertSame(['admin_email'], MailEnvironment::$optionReads);
    }

    public function testEmptyStoredPasswordRemainsEmpty(): void
    {
        $mailer = new MailerSpy();
        $this->smtp(['mail_server_password' => ''])->phpMailerInit($mailer);

        self::assertSame('', $mailer->Password);
    }

    public function testOnlyExistingEmbeddedImagesAreAddedInOrder(): void
    {
        $smtp = $this->smtp();
        $mailer = new MailerSpy();
        // Resolve the repository fixture, not any attachment from live wp-content.
        $fixture = dirname(__DIR__, 3) . '/assets/images/wordpress-blue.png';
        self::assertFileExists($fixture);
        self::assertFileDoesNotExist($fixture . '.missing');
        MailEnvironment::$duringSend = static function () use ($smtp, $mailer): void {
            $smtp->phpMailerInit($mailer);
        };

        $this->send($smtp, ['attachments' => [
            ['path' => $fixture, 'cid' => 'header-logo'],
            ['path' => $fixture . '.missing', 'cid' => 'missing-logo'],
            ['path' => $fixture, 'cid' => 'footer-logo'],
        ]]);

        self::assertSame([[$fixture, 'header-logo'], [$fixture, 'footer-logo']], $mailer->embeddedImages);
    }

    public function testAttachmentsAreClearedAfterSuccessAndFailure(): void
    {
        foreach ([true, false] as $result) {
            MailEnvironment::reset();
            MailEnvironment::$result = $result;
            $smtp = $this->smtp();
            $fixture = dirname(__DIR__, 3) . '/assets/images/wordpress-blue.png';
            $during = new MailerSpy();
            MailEnvironment::$duringSend = static function () use ($smtp, $during): void {
                $smtp->phpMailerInit($during);
            };

            $this->send($smtp, ['attachments' => [['path' => $fixture, 'cid' => 'logo']]]);
            $after = new MailerSpy();
            $smtp->phpMailerInit($after);

            self::assertSame([[$fixture, 'logo']], $during->embeddedImages);
            self::assertSame([], $after->embeddedImages);
        }
    }

    public function testSenderEmailFallsBackForInvalidOrMissingAddresses(): void
    {
        foreach (['sender@example.test' => 'sender@example.test', 'invalid' => 'default@example.test', '' => 'default@example.test'] as $input => $expected) {
            $smtp = $this->smtp();
            MailEnvironment::$duringSend = static function () use ($smtp, $expected): void {
                self::assertSame($expected, $smtp->filterFrom('default@example.test'));
            };
            $this->send($smtp, ['from' => $input]);
        }
    }

    public function testSenderNameFallsBackOnlyWhenEmpty(): void
    {
        foreach (['Newsletter Team' => 'Newsletter Team', '' => 'Site Name', '0' => '0'] as $input => $expected) {
            $smtp = $this->smtp();
            MailEnvironment::$duringSend = static function () use ($smtp, $expected): void {
                self::assertSame($expected, $smtp->filterName('Site Name'));
            };
            $this->send($smtp, ['fromName' => (string) $input]);
        }
    }

    public function testConsecutiveMessagesReplaceSenderAndPlainTextState(): void
    {
        $smtp = $this->smtp();
        $this->send($smtp);
        $mailer = new MailerSpy();
        MailEnvironment::$duringSend = static function () use ($smtp, $mailer): void {
            self::assertSame('second@example.test', $smtp->filterFrom('default@example.test'));
            self::assertSame('Second Team', $smtp->filterName('Site Name'));
            $smtp->phpMailerInit($mailer);
        };

        $this->send($smtp, ['from' => 'second@example.test', 'fromName' => 'Second Team', 'altBody' => 'Second plain text']);

        self::assertSame('Second plain text', $mailer->AltBody);
        self::assertCount(2, MailEnvironment::$messages);
        self::assertSame([], MailEnvironment::$hooks['action']['phpmailer_init']);
    }

    public function testErrorCallbackRetainsTheLatestErrorObject(): void
    {
        $smtp = $this->smtp();
        self::assertNull($smtp->getError());
        $first = (object) ['message' => 'Connection refused'];
        $second = (object) ['message' => 'Authentication failed'];

        $smtp->onMailError($first);
        self::assertSame($first, $smtp->getError());
        $smtp->onMailError($second);
        self::assertSame($second, $smtp->getError());
    }

    public function testContentTypeIsHtml(): void
    {
        self::assertSame('text/html', $this->smtp()->setContentType());
    }

    private function smtp(array $options = []): SMTP
    {
        $reflection = new ReflectionClass(SMTP::class);
        $smtp = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('options')->setValue($smtp, (object) array_replace([
            'mail_server_host' => 'smtp.example.test',
            'mail_server_port' => 587,
            'mail_server_encryption' => 'tls',
            'mail_server_auth' => 'on',
            'mail_server_username' => 'newsletter-user',
            'mail_server_password' => self::PASSWORD,
            'mail_server_sender' => 'bounces@example.test',
        ], $options));
        return $smtp;
    }

    private function send(SMTP $smtp, array $overrides = []): bool
    {
        return $smtp->send(...array_values(array_replace([
            'from' => 'sender@example.test',
            'fromName' => 'Newsletter Team',
            'to' => 'recipient@example.test',
            'subject' => 'Grüße from the newsletter',
            'body' => '<p>Hello &amp; welcome</p>',
            'altBody' => 'Hello & welcome',
            'headers' => ['Reply-To: reply@example.test', 'X-Test: unchanged'],
            'attachments' => [],
        ], $overrides)));
    }
}
