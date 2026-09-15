<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\Blocks;

use RRZE\Newsletter\Blocks\RSS\RSS;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\FeedEnvironment as State;

final class RssTest extends ApplicationTestCase
{
    protected function setUp(): void { parent::setUp(); State::reset(); $this->post(); }
    protected function tearDown(): void { State::reset(); parent::tearDown(); }

    public function testRegistrationAndDefaultsUseTheBundledBlockMetadata(): void
    {
        RSS::register();
        self::assertSame([RSS::class, 'renderHTML'], State::$calls[0][2]['render_callback']);
        self::assertFileExists(State::$calls[0][1]);
        $attrs = RssHarness::attributes(['itemsToShow' => 2, 'feedURL' => 'https://example.test/feed']);
        self::assertSame(2, $attrs['itemsToShow']);
        self::assertSame('https://example.test/feed', $attrs['feedURL']);
        self::assertFalse($attrs['sinceLastSend']);
        self::assertFalse($attrs['displayContent']);
        self::assertSame(25, $attrs['excerptLength']);
        self::assertSame('24px', $attrs['headingFontSize']);
    }

    public function testSinceLastSendIncludesExactBoundaryAndFiltersOlderItems(): void
    {
        App::$meta[42]['rrze_newsletter_send_date_gmt'] = '2026-09-15 12:00:00';
        $feed = new FeedFixture([
            new FeedItemFixture('Old', '', '', '2026-09-15 11:59:59'),
            new FeedItemFixture('Exact', '', '', '2026-09-15 12:00:00'),
            new FeedItemFixture('New', '', '', '2026-09-15 12:00:01'),
        ]);
        $html = RssHarness::items(['postId' => 42, 'sinceLastSend' => true, 'itemsToShow' => 3], $feed);
        self::assertStringNotContainsString('Old', $html);
        self::assertStringContainsString('Exact', $html);
        self::assertStringContainsString('New', $html);
        self::assertSame([[0, 3]], $feed->requests);
        self::assertSame('', RssHarness::items(['postId' => 42, 'sinceLastSend' => true], new FeedFixture([new FeedItemFixture('Old', '', '', '2026-09-14')])));
    }

    public function testTitleDateContentAndStylesHaveIndependentDisplayOptions(): void
    {
        $feed = new FeedFixture([new FeedItemFixture(' <b>News & events</b> ', 'https://example.test/news', '<p>Body &amp; details</p>', '2026-09-15 12:00:00')]);
        $html = RssHarness::items(['postId' => 42, 'displayDate' => true, 'displayContent' => true,
            'headingColor' => '#123456', 'textColor' => '#654321'], $feed);
        self::assertStringContainsString("<a href='https://example.test/news'>News &amp; events</a>", $html);
        self::assertStringContainsString('2026-09-15', $html);
        self::assertStringContainsString('<p>Body & details</p>', $html);
        self::assertStringContainsString('font-size:24px;color:#123456;', $html);
        self::assertStringContainsString('font-size:16px;color:#654321;', $html);
        self::assertSame([['texturize', '<p>Body & details</p>'], ['convert_chars', '<p>Body & details</p>'], ['autop', '<p>Body & details</p>']], State::$calls);
        $minimal = RssHarness::items(['postId' => 42], $feed);
        self::assertStringNotContainsString('2026-09-15', $minimal);
        self::assertStringNotContainsString('Body', $minimal);
    }

    public function testReadMoreAndExcerptUseConfiguredLengthAndNormalizeEllipsis(): void
    {
        $feed = new FeedFixture([new FeedItemFixture('Article', 'https://example.test/read', 'Long text', '2026-09-15')]);
        $html = RssHarness::items(['postId' => 42, 'displayReadMore' => true, 'displayContent' => true, 'excerptLimit' => true, 'excerptLength' => -4], $feed);
        self::assertStringContainsString('Excerpt [&hellip;]', $html);
        self::assertStringContainsString('Continue reading "Article"&hellip;', $html);
        self::assertStringNotContainsString("<a href='", $html);
        self::assertContains(['trim', 'Long text', 4, ' [&hellip;]'], State::$calls);
        State::$trimmed = 'Short excerpt';
        self::assertStringContainsString('Short excerpt', RssHarness::items(['postId' => 42, 'displayContent' => true, 'excerptLimit' => true], $feed));
    }

    public function testEmptyTitlesMissingDatesAndLinksHaveSafeFallbackMarkup(): void
    {
        $html = RssHarness::items(['postId' => 42, 'displayDate' => true, 'displayContent' => true,
            'displayReadMore' => true, 'headingFontSize' => '', 'headingColor' => '', 'textFontSize' => '', 'textColor' => ''],
            new FeedFixture([new FeedItemFixture(' <b></b> ', '', '', '')]));
        self::assertStringContainsString('(no title)', $html);
        self::assertStringNotContainsString('<a', $html);
        self::assertStringNotContainsString('<p', $html);
        self::assertStringNotContainsString('style=', $html);
        self::assertSame('', RssHarness::items(['postId' => 42], new FeedFixture([])));
    }

    public function testHtmlRenderingOutsideNewsletterReturnsBeforeFetching(): void
    {
        $hadPost = array_key_exists('post', $GLOBALS);
        $post = $GLOBALS['post'] ?? null;
        try {
            $GLOBALS['post'] = $this->post(['post_type' => 'post']);
            self::assertSame('', RSS::renderHTML(['feedURL' => 'https://must-not-fetch.invalid']));
            $GLOBALS['post'] = null;
            self::assertSame('', RSS::renderHTML([]));
        } finally {
            if ($hadPost) { $GLOBALS['post'] = $post; } else { unset($GLOBALS['post']); }
        }
    }
}

final class RssHarness extends RSS
{
    public static function attributes(array $attrs): array { return parent::parseAtts($attrs); }
    public static function items(array $attrs, FeedFixture $feed): string { return parent::render(parent::parseAtts($attrs), $feed); }
}

/** Scripted parsed feed, not a SimplePie parser or HTTP client. */
final class FeedFixture
{
    public array $requests = [];
    public function __construct(private array $items) {}
    public function get_items(int $start, int $limit): array { $this->requests[] = [$start, $limit]; return $this->items; }
}

final class FeedItemFixture
{
    public function __construct(private string $title, private string $link, private string $content, private string $date) {}
    public function get_title(): string { return $this->title; }
    public function get_link(): string { return $this->link; }
    public function get_content(): string { return $this->content; }
    public function get_date(string $format): string|false { return $this->date === '' ? false : gmdate($format, strtotime($this->date)); }
}
