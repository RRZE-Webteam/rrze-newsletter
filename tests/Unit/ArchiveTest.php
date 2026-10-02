<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use RRZE\Newsletter\Archive;
use RRZE\Newsletter\Utils;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\SettingsEnvironment as Settings;

final class ArchiveTest extends ApplicationTestCase
{
    public function testArchivedBodyDecodesBase64AndRemovesOnlyArchiveLinks(): void
    {
        $body = '<p>Hello Ada</p><a href="https://example.test/newsletter/archive/token">Browser view</a><a href="https://example.test/article">Article</a>';
        $archive = new ArchiveHarness();
        foreach ([$body, base64_encode($body)] as $content) {
            self::assertSame('<p>Hello Ada</p><a href="https://example.test/article">Article</a>', $archive->stored((object) ['post_content' => $content]));
        }
        self::assertContains(['action', 'template_redirect', [$archive, 'redirectTemplate'], 10, 1], App::$hooks);
        self::assertSame('newsletter/archive', Archive::archiveSlug());
        self::assertSame('newsletter/test', Archive::testSlug());
    }

    public function testPreviewPersonalizesDefaultsAndRemovesViewInBrowserLink(): void
    {
        $post = $this->post();
        Settings::$fields['subscription'] = [['name' => 'disabled', 'default' => 'on']];
        App::$meta[42]['rrze_newsletter_email_html'] = '<p>{{=CURRENT_YEAR}} {{=DATE}}</p><a href="{{=ARCHIVE}}">Browser view</a><a href="https://example.test/article">Article</a>';
        self::assertSame('<p>2026 2026-09-15</p><a href="https://example.test/article">Article</a>', (new ArchiveHarness())->preview($post));
        unset(App::$meta[42]['rrze_newsletter_email_html']);
        $archive = new ArchiveHarness();
        self::assertSame('', $archive->preview($post));
    }

    public function testUnrelatedMalformedAndMissingPostRoutesDoNotOutputContent(): void
    {
        $server = $_SERVER;
        try {
            $archive = new Archive();
            foreach (['', '/', '/newsletter/archive', '/other/page/' . Utils::encryptQueryVar('42'),
                '/newsletter/archive/' . Utils::encryptQueryVar('999'), '/newsletter/test/' . Utils::encryptQueryVar('999')] as $path) {
                $_SERVER['REQUEST_URI'] = $path;
                self::assertSame('', $this->captureOutput(fn () => $archive->redirectTemplate()), $path);
            }
            self::assertSame([], App::$writes);
        } finally { $_SERVER = $server; }
    }
}

final class ArchiveHarness extends Archive
{
    public function stored(object $post): string { $this->archiveContent($post); return $this->content; }
    public function preview(object $post): string { $this->testContent($post); return $this->content; }
}
