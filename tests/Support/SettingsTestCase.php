<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Settings;

abstract class SettingsTestCase extends TestCase
{
    protected SettingsHarness $settings;

    protected function setUp(): void
    {
        SettingsEnvironment::reset();
        RecipientEnvironment::reset();
        $this->settings = new SettingsHarness();
        $this->settings->useOptions([]);
    }

    protected function tearDown(): void
    {
        $this->settings->useOptions([]);
        SettingsEnvironment::reset();
        RecipientEnvironment::reset();
    }

    protected function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }

    protected function field(string $method, array $args = []): DOMXPath
    {
        $html = $this->captureOutput(fn () => $this->settings->$method(array_replace([
            'section' => 'mail_server', 'id' => 'host', 'default' => 'fallback', 'desc' => '',
            'min' => '', 'max' => '', 'step' => '',
        ], $args)));
        return $this->html($html);
    }

    protected function html(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            self::assertTrue($document->loadHTML('<html><body>' . $html . '</body></html>', LIBXML_NONET));
            // libxml's HTML4 parser warns about ordinary query-string ampersands.
            $unexpected = array_filter(libxml_get_errors(), static fn ($error) => $error->code !== 23);
            self::assertSame([], array_values(array_map(static fn ($error) => $error->message, $unexpected)));
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return new DOMXPath($document);
    }

    protected function values(DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes);
        return array_map(static fn ($node) => $node->nodeValue, iterator_to_array($nodes));
    }
}

final class SettingsHarness extends Settings
{
    public function __construct()
    {
        $this->settingsPrefix = 'newsletter-';
        $this->settingsMenu = ['title' => 'Newsletter settings', 'menu_slug' => 'newsletter'];
        $this->settingsSections = [['id' => 'mail_server', 'title' => 'Server'], ['id' => 'mail_queue', 'title' => 'Queue']];
    }

    public function useOptions(array $options): void
    {
        self::$optionName = 'rrze_newsletter_unit';
        self::$options = $options;
    }

    public function sanitizer(string $key): callable|bool
    {
        return $this->getSanitizeCallback($key);
    }

    public function tabs(array $hidden = []): void
    {
        $this->hiddenSections = $hidden;
        $this->setTabs();
    }
}
