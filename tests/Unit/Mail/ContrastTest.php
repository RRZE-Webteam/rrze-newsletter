<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Mail;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Mail\Contrast;

final class ContrastTest extends TestCase
{
    private function email(string $body, string $css = ''): string
    {
        return '<!doctype html><html><head><style>' . $css . '</style></head><body style="background:#fff;color:#000">' . $body . '</body></html>';
    }

    public function testWhiteRssHeadingIsCorrectedOnlyAfterInsertion(): void
    {
        $html = $this->email('<div>RSS_BLOCK_a</div><!--[if mso]><table><tr><td>Outlook</td></tr></table><![endif]--><p style="color:white">Static content</p>');
        $fragment = '<div class="rrze-newsletter-rss"><h3 style="color:#fff">Neuigkeiten &amp; Grüße</h3></div>';
        $actual = Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]);
        self::assertStringContainsString('color:#000000 !important;', $actual);
        self::assertStringContainsString('Neuigkeiten &amp; Grüße', $actual);
        self::assertStringNotContainsString('rrze-feed', $actual);
        self::assertSame($html, preg_replace('~<div class="rrze-newsletter-rss">.*?</div>~s', 'RSS_BLOCK_a', $actual));
    }

    public function testEachOccurrenceUsesItsOwnBackground(): void
    {
        $html = $this->email('<div bgcolor="#fff">ICS_BLOCK_a</div><div style="background:#000">ICS_BLOCK_a</div>');
        $actual = Contrast::replace($html, ['ICS_BLOCK_a' => '<p style="color:#777">Calendar</p>']);
        self::assertSame(1, substr_count($actual, 'color:#000000 !important;'));
        // #777 passes 4.5 on black and must stay byte-for-byte unchanged there.
        self::assertStringContainsString('<div style="background:#000"><p style="color:#777">Calendar</p></div>', $actual);
    }

    public function testDarkNestedColumnAndInheritedLinkAreProtected(): void
    {
        $html = $this->email('<table bgcolor="#fff"><tr><td style="background-color:#102030"><div>ICS_BLOCK_a</div></td></tr></table>', 'a {color:inherit}');
        $actual = Contrast::replace($html, ['ICS_BLOCK_a' => '<div class="rrze-newsletter-ics"><h3 style="color:rgb(0, 0, 0)"><a href="https://example.test/event?a=1&amp;b=2">Event</a></h3><p>Details</p></div>']);
        self::assertSame(2, substr_count($actual, 'color:#ffffff !important;'));
        self::assertStringContainsString('href="https://example.test/event?a=1&amp;b=2"', $actual);
    }

    public function testStylesheetLinkColorAndImportantCascadeAreUsed(): void
    {
        $html = $this->email('RSS_BLOCK_a', 'a{color:#000} .rrze-newsletter-rss a{color:#fff!important}');
        $actual = Contrast::replace($html, ['RSS_BLOCK_a' => '<div class="rrze-newsletter-rss"><a style="color:black" href="#one">Title</a><p style="color:black!important;color:white">Readable</p></div>']);
        self::assertStringContainsString('style="color:black;color:#000000 !important;"', $actual);
        self::assertStringContainsString('<p style="color:black!important;color:white">Readable</p>', $actual);
    }

    public function testCorrectingParentPreservesReadableAndUncertainDescendants(): void
    {
        $fragment = '<div style="background:#000">Dark <span style="background:#fff"><b>Light</b></span><span style="background-image:url(test.png)">Uncertain</span></div>';
        $actual = Contrast::replace($this->email('RSS_BLOCK_a'), ['RSS_BLOCK_a' => $fragment]);
        self::assertStringContainsString('background:#000;color:#ffffff !important;', $actual);
        self::assertStringContainsString('background:#fff;color:#000 !important;', $actual);
        self::assertStringContainsString('background-image:url(test.png);color:#000 !important;', $actual);
        self::assertStringContainsString('<b>Light</b>', $actual);
    }

    public function testReadableFragmentsRemainByteForByteIntact(): void
    {
        $html = $this->email('RSS_BLOCK_a');
        $fragment = "<div class='rrze-newsletter-rss'><p style='color: #123456;'>Grüße &#38; News</p></div>";
        self::assertSame(str_replace('RSS_BLOCK_a', $fragment, $html), Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]));
    }

    public function testUncertainBackgroundsAndHiddenTextAreNotGuessed(): void
    {
        foreach (['background-image:url(https://example.test/bg.png)', 'background:linear-gradient(white,black)',
            'background-color:rgba(255,255,255,0.5)', 'opacity:.5', 'filter:brightness(.5)', 'mix-blend-mode:multiply',
            'display:none', 'visibility:hidden', 'background-color:var(--surface)'] as $style) {
            $html = $this->email('<div style="' . $style . '">RSS_BLOCK_a</div>');
            $fragment = '<p style="color:white">Unknown</p>';
            self::assertSame(str_replace('RSS_BLOCK_a', $fragment, $html), Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]), $style);
        }
    }

    public function testOpaqueInnerBackgroundCanCoverAnOuterImage(): void
    {
        $html = $this->email('<div style="background-image:url(test.png)"><div style="background:white">RSS_BLOCK_a</div></div>');
        self::assertStringContainsString('color:#000000 !important;', Contrast::replace($html, ['RSS_BLOCK_a' => '<p style="color:white">Text</p>']));
    }

    public function testAlphaAndUnknownTextColorsAreNotGuessed(): void
    {
        foreach (['rgba(255,255,255,.5)', '#fff8', '#ffffff80', 'var(--foreground)', 'color(display-p3 1 1 1)'] as $color) {
            $html = $this->email('RSS_BLOCK_a');
            $fragment = '<p style="color:' . $color . '">Text</p>';
            self::assertSame(str_replace('RSS_BLOCK_a', $fragment, $html), Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]), $color);
        }
    }

    public function testFullyOpaqueHexAlphaColorsAreSupported(): void
    {
        foreach (['#ffff', '#ffffffff'] as $color) {
            $html = $this->email('<div style="background:' . $color . '">RSS_BLOCK_a</div>');
            self::assertStringContainsString('color:#000000 !important;', Contrast::replace($html, ['RSS_BLOCK_a' => '<p style="color:' . $color . '">Text</p>']));
        }
    }

    public function testUnsupportedPaintRulesDoNotTriggerSpeculativeChanges(): void
    {
        foreach (['@media(max-width:600px){p{background:black}}', 'p:nth-child(2){background:black}',
            '@supports(display:grid){p{color:black}}', '@import url(https://example.test/theme.css);'] as $css) {
            $html = $this->email('RSS_BLOCK_a', $css);
            $fragment = '<p style="color:white">Text</p>';
            self::assertSame(str_replace('RSS_BLOCK_a', $fragment, $html), Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]), $css);
        }
    }

    public function testLayoutOnlyMediaRulesDoNotDisableProtection(): void
    {
        $html = $this->email('RSS_BLOCK_a', '@media(max-width:600px){.column>table{width:100%;padding:16px}} a{color:inherit}');
        self::assertStringContainsString('color:#000000 !important;', Contrast::replace($html, ['RSS_BLOCK_a' => '<a href="#" style="color:#fff">Text</a>']));
    }

    public function testExternalAndConditionalStylesheetsAreNotGuessed(): void
    {
        foreach (['<link rel="stylesheet" href="https://example.test/theme.css">',
            '<style media="(max-width:600px)">p{background:black}</style>'] as $sheet) {
            $html = str_replace('</head>', $sheet . '</head>', $this->email('RSS_BLOCK_a'));
            $fragment = '<p style="color:white">Text</p>';
            self::assertSame(str_replace('RSS_BLOCK_a', $fragment, $html), Contrast::replace($html, ['RSS_BLOCK_a' => $fragment]));
        }
    }

    public function testProtectionIsIdempotent(): void
    {
        $fragment = '<p style="color:white">Text</p>';
        $once = Contrast::replace($this->email('RSS_BLOCK_a'), ['RSS_BLOCK_a' => $fragment]);
        preg_match('~<body[^>]*>(.*)</body>~s', $once, $match);
        self::assertSame($once, Contrast::replace($this->email('RSS_BLOCK_a'), ['RSS_BLOCK_a' => $match[1]]));
    }
}
