<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\Settings;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment;
use RRZE\Newsletter\Tests\Support\SettingsTestCase;

final class SettingsTest extends SettingsTestCase
{
    public function testDefaultsAreFlattenedWithSectionPrefixes(): void
    {
        self::assertSame('rrze_newsletter_unit', Settings::getOptionName());
        self::assertSame([
            'mail_server_host' => 'localhost', 'mail_server_sender' => 'admin@example.test', 'mail_server_auth' => '',
            'mail_queue_send_limit' => '15', 'mail_queue_max_retries' => '1',
        ], Settings::getOptions());
    }

    public function testSavedValuesOverrideDefaultsButUnknownKeysAreDropped(): void
    {
        SettingsEnvironment::$stored = ['mail_server_host' => 'smtp.example.test', 'mail_server_auth' => 'off', 'unknown' => 'discard'];
        $options = Settings::getOptions();
        self::assertSame('smtp.example.test', $options['mail_server_host']);
        self::assertSame('off', $options['mail_server_auth']);
        self::assertSame('15', $options['mail_queue_send_limit']);
        self::assertArrayNotHasKey('unknown', $options);
    }

    public function testQueueFiltersOverrideSavedLimitsIncludingZero(): void
    {
        SettingsEnvironment::$stored = ['mail_queue_send_limit' => '10', 'mail_queue_max_retries' => '5'];
        SettingsEnvironment::$filters = ['rrze_newsletter_mail_queue_send_limit' => 3, 'rrze_newsletter_mail_queue_max_retries' => 0];
        $options = Settings::getOptions();
        self::assertSame(3, $options['mail_queue_send_limit']);
        self::assertSame(0, $options['mail_queue_max_retries']);
    }

    public function testOptionGetterUsesFallbackOnlyForMissingValues(): void
    {
        $this->settings->useOptions(['mail_server_host' => '', 'mail_queue_send_limit' => '0']);
        self::assertSame('', $this->settings->getOption('mail_server', 'host', 'fallback'));
        self::assertSame('0', $this->settings->getOption('mail_queue', 'send_limit', 'fallback'));
        self::assertSame('fallback', $this->settings->getOption('mail_server', 'missing', 'fallback'));
    }

    public function testEmptySubmissionPreservesCurrentOptions(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'old']);
        self::assertSame(['mail_server_host' => 'old'], $this->settings->sanitizeOptions([]));
        self::assertSame([], SettingsEnvironment::$errors);
    }

    public function testSanitizersTransformOnlySubmittedFields(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'old', 'mail_server_auth' => 'on']);
        self::assertSame(['mail_server_host' => 'new', 'mail_server_auth' => 'on', 'mail_queue_send_limit' => 60], $this->settings->sanitizeOptions([
            'mail_server_host' => ' new ', 'mail_queue_send_limit' => '60',
        ]));
        self::assertSame([], SettingsEnvironment::$errors);
    }

    public function testRequiredFieldErrorPreservesOldValueAndAllowsOtherUpdates(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'old']);
        self::assertSame(['mail_server_host' => 'old', 'mail_server_auth' => 'off'], $this->settings->sanitizeOptions(['mail_server_host' => '', 'mail_server_auth' => 'off']));
        self::assertSame([['settings' => 'newsletter-mail_server', 'code' => 'mail_server_host', 'message' => 'The field Host is required.', 'type' => 'error']], SettingsEnvironment::$errors);
    }

    public function testInvalidSanitizedValuePreservesOldValueWithValidationError(): void
    {
        $this->settings->useOptions(['mail_server_sender' => 'old@example.test']);
        self::assertSame(['mail_server_sender' => 'old@example.test'], $this->settings->sanitizeOptions(['mail_server_sender' => 'not-an-email']));
        self::assertSame([['settings' => 'newsletter-mail_server', 'code' => 'mail_server_sender', 'message' => 'The value of the field Sender is not valid.', 'type' => 'error']], SettingsEnvironment::$errors);
    }

    public function testOutOfRangeLimitUsesSanitizerDefault(): void
    {
        self::assertSame(['mail_queue_send_limit' => 15], $this->settings->sanitizeOptions(['mail_queue_send_limit' => '61']));
        self::assertSame([], SettingsEnvironment::$errors);
    }

    public function testSanitizerLookupRejectsMissingAndNonCallableDefinitions(): void
    {
        SettingsEnvironment::$fields['mail_server'][] = ['name' => 'broken', 'sanitize_callback' => 'missing_unit_test_callback'];
        foreach (['', 'unknown', 'mail_server_auth', 'mail_server_broken'] as $key) {
            self::assertFalse($this->settings->sanitizer($key));
        }
        self::assertIsCallable($this->settings->sanitizer('mail_server_host'));
    }

    public function testDescriptionsAreOptional(): void
    {
        self::assertSame('', $this->settings->getFieldDescription([]));
        self::assertSame('', $this->settings->getFieldDescription(['desc' => '']));
        self::assertSame('<p class="description">Help text</p>', $this->settings->getFieldDescription(['desc' => 'Help text']));
    }

    public function testRequestedTabIsSelectedAndUnknownTabFallsBack(): void
    {
        $original = $_GET;
        try {
            foreach (['newsletter-mail_queue' => 'newsletter-mail_queue-tab', 'unknown' => 'newsletter-mail_server-tab'] as $requested => $expected) {
                $_GET = ['current-tab' => $requested];
                $this->settings->tabs();
                $xpath = $this->html($this->captureOutput(fn () => $this->settings->showTabs()));
                self::assertSame([$expected], $this->values($xpath, '//a[contains(@class,"nav-tab-active")]/@id'));
                self::assertSame(['Server', 'Queue'], $this->values($xpath, '//a'));
            }
        } finally {
            $_GET = $original;
        }
    }

    public function testOnlyOneVisibleSectionOmitsTabNavigation(): void
    {
        $this->settings->tabs(['mail_queue']);
        $xpath = $this->html($this->captureOutput(fn () => $this->settings->showTabs()));
        self::assertSame(['Newsletter settings'], $this->values($xpath, '//h1'));
        self::assertSame([], $this->values($xpath, '//a'));
    }
}
