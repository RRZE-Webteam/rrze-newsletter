<?php
declare(strict_types=1);
namespace RRZE\Newsletter\Tests\Unit\MJML;

use RRZE\Newsletter\MJML\Renderer;
use RRZE\Newsletter\MJML\LinkProcessor;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;
use RRZE\Newsletter\Tests\Support\ApplicationTestCase;

final class RendererTest extends ApplicationTestCase
{
    public function testArrayRenderingUsesDefaultsAndIgnoresUnknownArguments(): void
    {
        $markup = Renderer::fromAry(['title' => 'Preview', 'unknown' => 'not rendered']);
        self::assertStringContainsString('<mj-title>Preview</mj-title>', $markup);
        self::assertStringContainsString('background-color="#ffffff" width="680px"', $markup);
        self::assertStringNotContainsString('not rendered', $markup);
        self::assertSame('Arial', Renderer::getFontHeader());
        self::assertSame('Arial', Renderer::getFontBody());
    }

    public function testParsedBlocksAreRenderedInOrderAndFreeformOrUnknownBlocksSkipped(): void
    {
        App::$blocks['content'] = [$this->list('First'), ['blockName' => null, 'innerHTML' => 'freeform'], ['blockName' => 'unknown', 'innerHTML' => 'unsupported'], $this->list('Second')];
        $markup = Renderer::fromAry(['content' => 'content']);
        self::assertStringContainsString('<li>First</li>', $markup);
        self::assertStringContainsString('<li>Second</li>', $markup);
        self::assertLessThan(strpos($markup, '<li>Second</li>'), strpos($markup, '<li>First</li>'));
        self::assertStringNotContainsString('freeform', $markup);
        self::assertStringNotContainsString('unsupported', $markup);
    }

    public function testPostRenderingLoadsSupportedFontsPaletteAndPreview(): void
    {
        $post = $this->post();
        App::$options['rrze_newsletter_color_palette'] = '{"primary":"#abcdef"}';
        App::$meta[42] = ['rrze_newsletter_font_header' => 'Georgia, serif', 'rrze_newsletter_font_body' => 'Verdana, sans-serif', 'rrze_newsletter_preview_text' => 'Preview text', 'rrze_newsletter_background_color' => '#112233'];
        $markup = Renderer::fromPost($post);
        self::assertSame('Georgia, serif', Renderer::getFontHeader());
        self::assertSame('Verdana, sans-serif', Renderer::getFontBody());
        self::assertSame('#abcdef', Renderer::getColorFromPalette('primary'));
        self::assertSame('', Renderer::getColorFromPalette('unknown'));
        self::assertStringContainsString('<mj-preview>Preview text</mj-preview>', $markup);
        self::assertStringContainsString('background-color="#112233"', $markup);
    }

    public function testInvalidFontsAndMissingPostOptionsUseFallbacks(): void
    {
        App::$meta[42] = ['rrze_newsletter_font_header' => 'Unlisted Font', 'rrze_newsletter_font_body' => ''];
        $markup = Renderer::fromPost($this->post());
        self::assertSame('Arial', Renderer::getFontHeader());
        self::assertSame('Arial', Renderer::getFontBody());
        self::assertStringContainsString('background-color="#fff"', $markup);
        self::assertStringNotContainsString('<mj-preview>', $markup);
    }

    public function testReusableBlocksExpandAndMissingReferencesDoNotRender(): void
    {
        $this->post(['ID' => 99, 'post_type' => 'wp_block', 'post_content' => 'reusable']);
        App::$blocks['reusable'] = [$this->list('Reusable content'), ['blockName' => null, 'innerHTML' => 'ignore']];
        App::$blocks['main'] = [
            ['blockName' => 'core/block', 'attrs' => ['ref' => 99], 'innerHTML' => ''],
            ['blockName' => 'core/block', 'attrs' => ['ref' => 999], 'innerHTML' => ''],
        ];
        $markup = Renderer::fromAry(['content' => 'main']);
        self::assertStringContainsString('Reusable content', $markup);
        self::assertStringNotContainsString('ignore', $markup);
        self::assertSame(1, substr_count($markup, '<li>Reusable content</li>'));
    }

    public function testStoredHtmlIsReturnedUnchangedAndMissingHtmlIsAnError(): void
    {
        $post = $this->post();
        self::assertInstanceOf(\WP_Error::class, Renderer::retrieveEmailHtml($post));
        App::$meta[42]['rrze_newsletter_email_html'] = '<html><body>Saved</body></html>';
        self::assertSame('<html><body>Saved</body></html>', Renderer::retrieveEmailHtml($post));
    }

    public function testLinksAddTrackingAndPreserveExistingQueryAndFragment(): void
    {
        $post = $this->post(['post_title' => 'September News']);
        $markup = LinkProcessor::processLinks($post, '<a href="https://example.test/news?existing=yes#part">Read</a>');
        self::assertSame([[['utm_campaign' => '2026-09-15', 'utm_source' => 'september-news', 'utm_medium' => 'email'], 'https://example.test/news?existing=yes#part']], App::$urlCalls);
        self::assertStringContainsString('existing=yes&utm_campaign=2026-09-15', $markup);
        self::assertStringContainsString('#part', $markup);
    }

    public function testSpecialTokensAreRecoveredAndInvalidUrlsAreNotTracked(): void
    {
        $post = $this->post();
        foreach (['UNSUB', 'UPDATE', 'ARCHIVE'] as $token) {
            self::assertSame('<a href="{{=' . $token . '}}">Link</a>', LinkProcessor::processLinks($post, '<a href="https://example.test/{{=' . $token . '}}/extra">Link</a>'));
        }
        foreach (['', '<p>No links</p>', '<a href="#section">Anchor</a>', '<a href="relative/path">Relative</a>'] as $markup) {
            self::assertSame($markup, LinkProcessor::processLinks($post, $markup));
        }
        self::assertSame([], App::$urlCalls);
    }

    public function testDuplicateLinksAreEachProcessed(): void
    {
        $link = '<a href="https://example.test">Read</a>';
        $markup = LinkProcessor::processLinks($this->post(), $link . $link);
        self::assertSame(2, substr_count($markup, 'utm_medium=email'));
        self::assertCount(2, App::$urlCalls);
    }

    private function list(string $text): array
    {
        $html = '<ul><li>' . $text . '</li></ul>';
        return ['blockName' => 'core/list', 'attrs' => [], 'innerHTML' => $html, 'innerContent' => [$html], 'innerBlocks' => []];
    }
}
