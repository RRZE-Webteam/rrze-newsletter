<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\Tests\Support\SettingsEnvironment;
use RRZE\Newsletter\Tests\Support\SettingsTestCase;

final class SettingsFieldsTest extends SettingsTestCase
{
    public function testTextFieldUsesStoredValueAndConsistentFormNames(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'smtp.example.test']);
        $xpath = $this->field('callbackText', ['desc' => 'Server name']);
        foreach (['type' => 'text', 'class' => 'regular-text', 'id' => 'mail_server-host', 'name' => 'rrze_newsletter_unit[mail_server_host]', 'value' => 'smtp.example.test'] as $key => $value) {
            self::assertSame([$value], $this->values($xpath, '//input/@' . $key));
        }
        self::assertSame(['Server name'], $this->values($xpath, '//p[@class="description"]'));
        self::assertSame([], $this->values($xpath, '//input/@placeholder'));
    }

    public function testTextFieldSupportsCustomTypeSizePlaceholderAndDefault(): void
    {
        $xpath = $this->field('callbackText', ['type' => 'email', 'size' => 'large', 'placeholder' => 'Email']);
        self::assertSame(['email'], $this->values($xpath, '//input/@type'));
        self::assertSame(['large-text'], $this->values($xpath, '//input/@class'));
        self::assertSame(['Email'], $this->values($xpath, '//input/@placeholder'));
        self::assertSame(['fallback'], $this->values($xpath, '//input/@value'));
    }

    public function testNumberFieldIncludesBoundsStepAndPlaceholder(): void
    {
        $xpath = $this->field('callbackNumber', ['min' => '0', 'max' => '60', 'step' => '1', 'size' => 'small', 'placeholder' => '15']);
        foreach (['type' => 'number', 'min' => '0', 'max' => '60', 'step' => '1', 'class' => 'small-number', 'placeholder' => '15'] as $key => $value) {
            self::assertSame([$value], $this->values($xpath, '//input/@' . $key));
        }
    }

    public function testNumberFieldOmitsUnspecifiedBounds(): void
    {
        $xpath = $this->field('callbackNumber');
        self::assertSame(['regular-number'], $this->values($xpath, '//input/@class'));
        self::assertSame([], $this->values($xpath, '//input/@min | //input/@max | //input/@step | //input/@placeholder'));
    }

    public function testCheckboxHasHiddenOffValueAndReflectsSavedState(): void
    {
        foreach (['on', 'off'] as $value) {
            $this->settings->useOptions(['mail_server_host' => $value]);
            $xpath = $this->field('callbackCheckbox', ['desc' => 'Enable sending']);
            self::assertSame(['off'], $this->values($xpath, '//input[@type="hidden"]/@value'));
            self::assertSame(['on'], $this->values($xpath, '//input[@type="checkbox"]/@value'));
            self::assertSame($value === 'on' ? ['checked'] : [], $this->values($xpath, '//input/@checked'));
            self::assertSame(['mail_server-host'], $this->values($xpath, '//label/@for'));
        }
    }

    public function testRadioFieldChecksOnlyCurrentOption(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'tls']);
        $xpath = $this->field('callbackRadio', ['options' => ['none' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL']]);
        self::assertSame(['none', 'tls', 'ssl'], $this->values($xpath, '//input/@value'));
        self::assertSame(['tls'], $this->values($xpath, '//input[@checked]/@value'));
        self::assertSame(['mail_server-host-none', 'mail_server-host-tls', 'mail_server-host-ssl'], $this->values($xpath, '//label/@for'));
    }

    public function testSelectFieldKeepsOrderAndSelectsCurrentValue(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'ssl']);
        $xpath = $this->field('callbackSelect', ['class' => 'widefat', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL']]);
        self::assertSame(['widefat'], $this->values($xpath, '//select/@class'));
        self::assertSame(['TLS', 'SSL'], $this->values($xpath, '//option'));
        self::assertSame(['ssl'], $this->values($xpath, '//option[@selected]/@value'));
    }

    public function testSelectWithoutOptionsHasNoSelection(): void
    {
        $xpath = $this->field('callbackSelect', ['options' => []]);
        self::assertSame([''], $this->values($xpath, '//select/@class'));
        self::assertSame([], $this->values($xpath, '//option'));
    }

    public function testPageSelectorDelegatesSelectedIdAndFallback(): void
    {
        $this->settings->useOptions(['mail_server_host' => '42']);
        $xpath = $this->field('callbackSelectPage', ['default' => '0', 'desc' => 'Choose page']);
        self::assertSame([[
            'name' => 'rrze_newsletter_unit[mail_server_host]', 'echo' => 0,
            'show_option_none' => '&mdash; Select &mdash;', 'option_none_value' => '0',
            'selected' => '42', 'class' => 'none',
        ]], SettingsEnvironment::$dropdowns);
        self::assertSame(['Choose page'], $this->values($xpath, '//p'));
    }

    public function testTextareaPreservesMultilineTextAndDimensions(): void
    {
        $this->settings->useOptions(['mail_server_host' => "line one\nline two"]);
        $xpath = $this->field('callbackTextarea', ['size' => 'large', 'placeholder' => 'Addresses']);
        self::assertSame(["line one\nline two"], $this->values($xpath, '//textarea'));
        foreach (['rows' => '10', 'cols' => '55', 'class' => 'large-text', 'placeholder' => 'Addresses'] as $key => $value) {
            self::assertSame([$value], $this->values($xpath, '//textarea/@' . $key));
        }
    }

    public function testTextareaDefaultHasNoPlaceholder(): void
    {
        $xpath = $this->field('callbackTextarea');
        self::assertSame(['fallback'], $this->values($xpath, '//textarea'));
        self::assertSame(['regular-text'], $this->values($xpath, '//textarea/@class'));
        self::assertSame([], $this->values($xpath, '//textarea/@placeholder'));
    }

    public function testPasswordFieldDecodesSyntheticStoredPassword(): void
    {
        $this->settings->useOptions(['mail_server_host' => 'WndKT1RzTFlPL2swVVN4dXlKSmRsTW9qaDd3ejRJa0l0em1aOFhoSG9lST0=']);
        $xpath = $this->field('callbackPassword', ['size' => 'large']);
        self::assertSame(['password'], $this->values($xpath, '//input/@type'));
        self::assertSame(['smtp-test-password'], $this->values($xpath, '//input/@value'));
        self::assertSame(['large-text'], $this->values($xpath, '//input/@class'));
    }

    public function testMissingPasswordDoesNotUseFieldDefault(): void
    {
        $xpath = $this->field('callbackPassword');
        self::assertSame([''], $this->values($xpath, '//input/@value'));
        self::assertSame(['regular-text'], $this->values($xpath, '//input/@class'));
    }
}
