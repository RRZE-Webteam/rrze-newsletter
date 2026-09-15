<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use RRZE\Newsletter\MJML\BlockProcessor\FeedProcessor;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;

final class FeedPlaceholderTest extends ApplicationTestCase
{
    public function testRssPlaceholderPersistsAttributesAndPreservesOtherFeeds(): void
    {
        App::$meta[42]['rrze_newsletter_rss_attrs'] = ['other' => ['feedURL' => 'https://example.test/other']];
        $attrs = ['feedURL' => 'https://example.test/feed', 'postId' => 42, 'itemsToShow' => 3];
        $markup = FeedProcessor::renderRss(42, $attrs, 'Arial');
        $key = md5($attrs['feedURL']);
        self::assertSame($attrs, App::$meta[42]['rrze_newsletter_rss_attrs'][$key]);
        self::assertArrayHasKey('other', App::$meta[42]['rrze_newsletter_rss_attrs']);
        self::assertStringContainsString('RSS_BLOCK_' . $key, $markup);
        self::assertStringContainsString('font-family="Arial"', $markup);
        self::assertStringContainsString('padding="0"', $markup);
        self::assertSame(42, App::$writes[0][0]);
    }

    public function testRepeatedFeedUpdatesSameKeyAndDoesNotMixIcsStorage(): void
    {
        $attrs = ['feedURL' => 'https://example.test/feed', 'itemsToShow' => 1];
        FeedProcessor::renderRss(42, $attrs, 'Arial');
        $attrs['itemsToShow'] = 7;
        FeedProcessor::renderRss(42, $attrs, 'Arial');
        $markup = FeedProcessor::renderIcs(42, $attrs, 'Georgia');
        self::assertCount(1, App::$meta[42]['rrze_newsletter_rss_attrs']);
        self::assertSame(7, App::$meta[42]['rrze_newsletter_rss_attrs'][md5($attrs['feedURL'])]['itemsToShow']);
        self::assertStringContainsString('ICS_BLOCK_' . md5($attrs['feedURL']), $markup);
        self::assertSame($attrs, App::$meta[42]['rrze_newsletter_ics_attrs'][md5($attrs['feedURL'])]);
    }

    public function testLinkColorAndTextOverridesAreSerializedWithoutArrayAttributes(): void
    {
        $markup = FeedProcessor::renderIcs(42, ['style' => ['elements' => ['link' => ['color' => ['text' => '#123456']]]],
            'padding' => '8px', 'font-size' => '18px'], 'Arial');
        self::assertStringContainsString('link="#123456"', $markup);
        self::assertStringContainsString('padding="8px"', $markup);
        self::assertStringContainsString('font-size="18px"', $markup);
        self::assertStringNotContainsString('style=', $markup);
        self::assertStringContainsString('ICS_BLOCK_' . md5(''), $markup);
    }
}
