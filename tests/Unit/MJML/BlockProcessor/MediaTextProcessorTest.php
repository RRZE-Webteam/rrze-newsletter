<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;
use RRZE\Newsletter\Tests\Support\ImageLookupEnvironment as Images;
use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;

final class MediaTextProcessorTest extends MjmlTestCase
{
    protected function setUp(): void
    {
        Images::reset(); App::reset(); BlockProcessor::beginRender();
    }

    protected function tearDown(): void
    {
        Images::reset(); App::reset(); BlockProcessor::beginRender();
    }

    private function block(array $attrs = [], ?string $media = null): array
    {
        $block = $this->container('core/media-text', [$this->listBlock('News &amp; events')], $attrs);
        $block['innerHTML'] = '<div class="wp-block-media-text"><figure class="wp-block-media-text__media">'
            . ($media ?? '<a href="https://example.test/story?a=1&amp;b=2"><img src="https://example.test/image.jpg" alt="Grüße &amp; News" width="1200" height="600" /></a>')
            . '</figure><div class="wp-block-media-text__content"></div></div>';
        return $block;
    }

    public function testDefaultLayoutPreservesImageLinkAltTextAndChildContent(): void
    {
        $block = $this->block();
        $before = $block;
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));
        self::assertSame(['50%', '50%'], $this->values($xpath, '//mj-column/@width'));
        self::assertSame(['middle', 'middle'], $this->values($xpath, '//mj-column/@vertical-align'));
        self::assertSame(['340px'], $this->values($xpath, '//mj-image/@width'));
        self::assertSame(['auto'], $this->values($xpath, '//mj-image/@height'));
        self::assertSame(['https://example.test/story?a=1&b=2'], $this->values($xpath, '//mj-image/@href'));
        self::assertSame(['Grüße & News'], $this->values($xpath, '//mj-image/@alt'));
        self::assertSame(['News & events'], $this->values($xpath, '//li'));
        $this->assertNodes($xpath, '//mj-column//mj-column | //mj-column//mj-section', 0);
        self::assertSame($before, $block);
    }

    public function testRightPositionReversesDesktopColumnsButKeepsImageFirstForMobile(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->block(['mediaPosition' => 'right', 'mediaWidth' => 30]), RenderContext::root(42)));
        self::assertSame(['rtl'], $this->values($xpath, '//mj-section/@direction'));
        self::assertSame(['30%', '70%'], $this->values($xpath, '//mj-column/@width'));
        $this->assertNodes($xpath, '/test-root/mj-section/mj-column[1]/mj-image', 1);
        self::assertSame(['204px'], $this->values($xpath, '//mj-image/@width'));
    }

    public function testNonStackedMobileLayoutAndVerticalAlignmentArePreserved(): void
    {
        foreach (['top' => 'top', 'center' => 'middle', 'bottom' => 'bottom'] as $input => $expected) {
            $xpath = $this->parseMjml(BlockProcessor::render($this->block([
                'mediaPosition' => 'right', 'isStackedOnMobile' => false, 'verticalAlignment' => $input,
            ]), RenderContext::root(42)));
            self::assertSame(['rtl'], $this->values($xpath, '//mj-group/@direction'));
            self::assertSame([$expected, $expected], $this->values($xpath, '//mj-group/mj-column/@vertical-align'));
        }
    }

    public function testInvalidWidthsStayWithinCoreResizeLimits(): void
    {
        foreach (['nonsense' => '50%', '-20' => '15%', '300' => '85%'] as $value => $expected) {
            $xpath = $this->parseMjml(BlockProcessor::render($this->block(['mediaWidth' => $value]), RenderContext::root(42)));
            self::assertSame([$expected], $this->values($xpath, '//mj-column[1]/@width'));
        }
    }

    public function testManagedSpacingUsesNewsletterGuttersWithoutChangingSavedSpacing(): void
    {
        $block = $this->block(['style' => ['spacing' => ['padding' => ['left' => '200px', 'right' => '200px']]]]);
        $original = $block;
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42, managedSpacing: true)));
        self::assertSame(['0 24px'], $this->values($xpath, '//mj-section/@padding'));
        self::assertSame(['0 8px', '0 8px'], $this->values($xpath, '//mj-column/@padding'));
        self::assertSame(['300px'], $this->values($xpath, '//mj-image/@width'));
        self::assertSame(['0 0 16px 0', '0 0 16px 0'], $this->values($xpath, '//mj-image/@padding | //mj-text/@padding'));
        self::assertSame($original, $block);
    }

    public function testExpertPaddingIsAppliedOnceAroundBothColumns(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->block([
            'style' => ['spacing' => ['padding' => ['left' => 'var:preset|spacing|30', 'right' => 'var:preset|spacing|30']]],
        ]), RenderContext::root(42)));
        self::assertSame(['0 40px 0 40px'], $this->values($xpath, '//mj-section/@padding'));
        self::assertSame(['300px'], $this->values($xpath, '//mj-image/@width'));
    }

    public function testGroupBackgroundAndTextStylesReachChildrenWithoutLeakingLayout(): void
    {
        $media = $this->block(['customBackgroundColor' => '#123456', 'customTextColor' => '#ffffff', 'fontSize' => 'large']);
        $media['innerBlocks'][] = $this->listBlock('Small child', ['fontSize' => 'small']);
        $block = $this->container('core/group', [$media], ['customBackgroundColor' => '#eeeeee']);
        $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));
        self::assertSame(['#123456', '#123456'], $this->values($xpath, '//mj-column/@background-color'));
        self::assertSame(['#ffffff', '#ffffff'], $this->values($xpath, '//mj-text/@color'));
        self::assertSame(['26px', '14px'], $this->values($xpath, '//mj-text/@font-size'));
        $this->assertNodes($xpath, '//mj-text/@mediaWidth | //mj-text/@mediaPosition', 0);
    }

    public function testNestedMediaTextFlattensInColumnsAndGridCells(): void
    {
        foreach (['columns', 'grid'] as $layout) {
            $media = $this->block(['mediaPosition' => 'right']);
            $block = $layout === 'columns'
                ? $this->container('core/columns', [$this->container('core/column', [$media], ['width' => '40%'])])
                : $this->container('core/group', [$media], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
            $xpath = $this->parseMjml(BlockProcessor::render($block, RenderContext::root(42)));
            $this->assertNodes($xpath, '//mj-column//mj-column | //mj-column//mj-section | //mj-column//mj-group', 0);
            $this->assertNodes($xpath, '//mj-image', 1);
            self::assertSame(['News & events'], $this->values($xpath, '//li'));
        }
    }

    public function testMissingMediaKeepsTextWithoutAnEmptyHalfColumn(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->block([], ''), RenderContext::root(42)));
        self::assertSame(['100%'], $this->values($xpath, '//mj-column/@width'));
        $this->assertNodes($xpath, '//mj-image', 0);
        self::assertSame(['News & events'], $this->values($xpath, '//li'));
    }

    public function testFeaturedImageUsesThisNewsletterAndPreservesItsAlternativeText(): void
    {
        App::$meta[42]['_thumbnail_id'] = 123;
        App::$meta[123]['_wp_attachment_image_alt'] = 'Featured & current';
        Images::$attachments[123]['full'] = ['https://example.test/featured.jpg', 1200, 600];
        $xpath = $this->parseMjml(BlockProcessor::render($this->block(['useFeaturedImage' => true]), RenderContext::root(42)));
        self::assertSame(['https://example.test/featured.jpg'], $this->values($xpath, '//mj-image/@src'));
        self::assertSame(['Featured & current'], $this->values($xpath, '//mj-image/@alt'));
        self::assertSame([[123, 'full']], Images::$attachmentSizeLookups);
    }

    public function testImageFillKeepsTheFullProportionalImageInsteadOfHidingIt(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->block(['imageFill' => true, 'focalPoint' => ['x' => .1, 'y' => .9]]), RenderContext::root(42)));
        self::assertSame(['auto'], $this->values($xpath, '//mj-image/@height'));
        self::assertSame(['340px'], $this->values($xpath, '//mj-image/@width'));
    }

    public function testVideoUsesALinkedPosterAndTextLinkWithoutEmbeddingAPlayer(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->block(['mediaType' => 'video'],
            '<video controls="controls" poster="https://example.test/poster.jpg" width="1200" height="600"><source src="https://example.test/video.mp4" /></video>'
        ), RenderContext::root(42)));
        self::assertSame(['https://example.test/poster.jpg'], $this->values($xpath, '//mj-image/@src'));
        self::assertSame(['https://example.test/video.mp4'], $this->values($xpath, '//mj-image/@href'));
        self::assertSame(['https://example.test/video.mp4'], $this->values($xpath, '//mj-text/a/@href'));
        $this->assertNodes($xpath, '//video | //source', 0);
    }

    public function testVideoWithoutPosterIsStillReachableAndUnsafeUrlsAreRejected(): void
    {
        foreach (['https://example.test/video.mp4' => 1, 'javascript:alert(1)' => 0] as $url => $count) {
            $xpath = $this->parseMjml(BlockProcessor::render($this->block(['mediaType' => 'video'], '<video src="' . $url . '"></video>'), RenderContext::root(42)));
            $this->assertNodes($xpath, '//mj-text/a', $count);
            $this->assertNodes($xpath, '//video | //mj-image', 0);
            self::assertSame(['News & events'], $this->values($xpath, '//li'));
        }
    }
}
