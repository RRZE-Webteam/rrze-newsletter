<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use DOMXPath;
use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ImageProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ImageSizeResolver;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class ImageProcessorTest extends MjmlTestCase
{
    private bool $originalLibxmlErrorMode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLibxmlErrorMode = libxml_use_internal_errors();
        ImageSizeResolver::reset();
    }

    protected function tearDown(): void
    {
        // Restore the caller's state even if a regression assertion fails.
        libxml_clear_errors();
        libxml_use_internal_errors($this->originalLibxmlErrorMode);
        ImageSizeResolver::reset();
        parent::tearDown();
    }

    private function imageHtml(int $width = 1200, int $height = 600): string
    {
        return '<figure><img src="https://example.test/news.png" alt="Newsletter" width="'
            . $width . '" height="' . $height . '" /></figure>';
    }

    private function image(array $attrs = [], ?string $html = null, int $availableWidth = 600): DOMXPath
    {
        return $this->parseMjml(ImageProcessor::render(
            $attrs, $html ?? $this->imageHtml(), 'Georgia', $availableWidth
        ));
    }

    private function imageBlock(): array
    {
        $html = $this->imageHtml();
        return [
            'blockName' => 'core/image', 'attrs' => [],
            'innerHTML' => $html, 'innerContent' => [$html], 'innerBlocks' => [],
        ];
    }

    private function assertDimensions(DOMXPath $xpath, string $width, string $height): void
    {
        self::assertSame([$width], $this->values($xpath, '//mj-image/@width'));
        self::assertSame([$height], $this->values($xpath, '//mj-image/@height'));
    }

    public function testImageRenderingRestoresThePreviousLibxmlErrorMode(): void
    {
        foreach ([false, true] as $mode) {
            libxml_use_internal_errors($mode);

            $markup = ImageProcessor::render([], $this->imageHtml(), 'Arial', 600);

            self::assertStringContainsString('<mj-image ', $markup);
            self::assertSame($mode, libxml_use_internal_errors());
            self::assertSame([], libxml_get_errors());
        }
    }

    public function testMissingImageRestoresThePreviousLibxmlErrorMode(): void
    {
        foreach ([false, true] as $mode) {
            foreach (['', '<p>No image here</p>'] as $html) {
                libxml_use_internal_errors($mode);

                self::assertSame('', ImageProcessor::render([], $html, 'Arial', 600));

                self::assertSame($mode, libxml_use_internal_errors());
                self::assertSame([], libxml_get_errors());
            }
        }
    }

    public function testMalformedImageMarkupClearsParserErrorsAndRestoresLibxmlMode(): void
    {
        $html = '</unexpected>' . $this->imageHtml();
        // Verify that this fixture actually produces libxml diagnostics.
        libxml_use_internal_errors(true);
        (new \DOMDocument())->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        self::assertNotEmpty(libxml_get_errors());
        libxml_clear_errors();

        foreach ([false, true] as $mode) {
            libxml_use_internal_errors($mode);

            $markup = ImageProcessor::render([], $html, 'Arial', 600);

            self::assertStringContainsString('<mj-image ', $markup);
            self::assertSame($mode, libxml_use_internal_errors());
            self::assertSame([], libxml_get_errors());
        }
    }

    public function testLargeImageFitsContainerWithoutChangingAspectRatio(): void
    {
        $xpath = $this->image();

        $this->assertDimensions($xpath, '600px', '300px');
        self::assertSame(['https://example.test/news.png'], $this->values($xpath, '//mj-image/@src'));
        self::assertSame(['Newsletter'], $this->values($xpath, '//mj-image/@alt'));
        self::assertSame(['true'], $this->values($xpath, '//mj-image/@fluid-on-mobile'));
        self::assertSame(['0'], $this->values($xpath, '//mj-image/@padding'));
        self::assertSame(['left'], $this->values($xpath, '//mj-image/@align'));
        $this->assertNodes($xpath, '/test-root/mj-image', 1);
        $this->assertNodes($xpath, '//mj-text', 0);
    }

    public function testSmallImageKeepsIntrinsicSizeWithoutUpscaling(): void
    {
        $this->assertDimensions($this->image([], $this->imageHtml(200, 100)), '200px', '100px');
    }

    public function testExplicitPixelAndPercentageWidthsScaleHeight(): void
    {
        foreach ([[240, '240px', '120px'], ['50%', '300px', '150px'], ['900px', '600px', '300px']] as [$requested, $width, $height]) {
            $this->assertDimensions($this->image(['width' => $requested]), $width, $height);
        }
    }

    public function testImageSizePresetsRespectAvailableWidth(): void
    {
        foreach ([
            [['sizeSlug' => 'medium'], 600, '300px', '150px'],
            [['className' => 'custom size-medium'], 600, '300px', '150px'],
            [['sizeSlug' => 'thumbnail'], 600, '150px', '75px'],
            [['className' => 'size-thumbnail'], 600, '150px', '75px'],
            [['sizeSlug' => 'medium'], 200, '200px', '100px'],
            [['sizeSlug' => 'medium', 'width' => 100], 600, '100px', '50px'],
        ] as [$attrs, $available, $width, $height]) {
            $this->assertDimensions($this->image($attrs, null, $available), $width, $height);
        }
    }

    public function testExplicitDimensionsAreKeptWhenTheyFitAndScaledTogetherWhenOversized(): void
    {
        $this->assertDimensions($this->image(['width' => 400, 'height' => 400]), '400px', '400px');
        $this->assertDimensions($this->image(['width' => 1200, 'height' => 800]), '600px', '400px');
        $this->assertDimensions($this->image(['width' => 1200, 'height' => 1], null, 100), '100px', '1px');
    }

    public function testExplicitDimensionsDoNotRequireIntrinsicMetadata(): void
    {
        $xpath = $this->image(
            ['width' => 300, 'height' => 100],
            '<img src="https://example.test/no-metadata.png" />'
        );

        $this->assertDimensions($xpath, '300px', '100px');
        $this->assertNodes($xpath, '//mj-image/@alt', 0);
    }

    public function testHeightOnlyDerivesWidthAndClampsToContainer(): void
    {
        $this->assertDimensions($this->image(['height' => 100]), '200px', '100px');
        $this->assertDimensions($this->image(['height' => 500]), '600px', '300px');
    }

    public function testHeightWithoutSourceOrIntrinsicSizeDoesNotForceDistortedDimensions(): void
    {
        $xpath = $this->image(['height' => 100], '<img src="" />');

        $this->assertNodes($xpath, '/test-root/mj-image', 1);
        $this->assertNodes($xpath, '//mj-image/@height | //mj-image/@width', 0);
    }

    public function testUnsupportedDimensionsFallBackToIntrinsicAspectRatio(): void
    {
        foreach ([['width' => 'auto'], ['width' => '-10px'], ['height' => '50%'], ['width' => 0, 'height' => 0]] as $attrs) {
            $this->assertDimensions($this->image($attrs), '600px', '300px');
        }
    }

    public function testImageLinkComesFromAnchorUnlessBlockOverridesIt(): void
    {
        $html = '<figure><a href="https://example.test/original">'
            . '<img src="https://example.test/news.png" width="1200" height="600" />'
            . '</a></figure>';

        self::assertSame(
            ['https://example.test/original'],
            $this->values($this->image([], $html), '//mj-image/@href')
        );
        self::assertSame(
            ['https://example.test/override'],
            $this->values($this->image(['href' => 'https://example.test/override'], $html), '//mj-image/@href')
        );
        $this->assertNodes($this->image(), '//mj-image/@href', 0);
    }

    public function testRoundedStyleAndAlignmentAreApplied(): void
    {
        $xpath = $this->image(['className' => 'custom is-style-rounded', 'align' => 'center']);

        self::assertSame(['999px'], $this->values($xpath, '//mj-image/@border-radius'));
        self::assertSame(['center'], $this->values($xpath, '//mj-image/@align'));
        $this->assertNodes($this->image(), '//mj-image/@border-radius', 0);
    }

    public function testPlainCaptionFollowsImageWithConfiguredFontAndColor(): void
    {
        $html = '<figure><img src="https://example.test/news.png" width="1200" height="600" />'
            . '<figcaption>Grüße aus Erlangen</figcaption></figure>';
        $xpath = $this->image(['color' => '#04316a'], $html);

        $this->assertNodes($xpath, '/test-root/mj-image/following-sibling::mj-text', 1);
        self::assertSame(['Grüße aus Erlangen'], $this->values($xpath, '//mj-text'));
        self::assertSame(['Georgia'], $this->values($xpath, '//mj-text/@font-family'));
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-text/@color'));
        self::assertSame(['14px'], $this->values($xpath, '//mj-text/@font-size'));
        self::assertSame(['16px 0'], $this->values($xpath, '//mj-text/@padding'));
    }

    public function testMarkupWithoutImageDoesNotProduceImageOrCaption(): void
    {
        self::assertSame('', ImageProcessor::render(
            [], '<figure><figcaption>Orphan caption</figcaption></figure>', 'Arial', 600
        ));
    }

    public function testRootImageHasExactlyOneSectionAndColumn(): void
    {
        $xpath = $this->parseMjml(BlockProcessor::render($this->imageBlock(), RenderContext::root(42, 600)));

        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-image', 1);
        $this->assertNodes($xpath, '//mj-section', 1);
        $this->assertNodes($xpath, '//mj-column', 1);
        $this->assertDimensions($xpath, '600px', '300px');
    }

    public function testPaddedColumnsPassDistinctWidthsToSiblingImages(): void
    {
        $columns = $this->container('core/columns', [
            $this->container('core/column', [$this->imageBlock()], [
                'width' => '40%',
                'style' => ['spacing' => ['padding' => [
                    'left' => 'var:preset|spacing|20', 'right' => 'var:preset|spacing|20',
                ]]],
            ]),
            $this->container('core/column', [$this->imageBlock()], ['width' => '60%']),
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($columns, RenderContext::root(42, 600)));

        self::assertSame(['200px', '360px'], $this->values($xpath, '//mj-image/@width'));
        self::assertSame(['100px', '180px'], $this->values($xpath, '//mj-image/@height'));
        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-image', 2);
        $this->assertNodes($xpath, '//mj-column//mj-column', 0);
    }

    public function testGridCellPaddingIsDeductedOnlyOnceFromImageContentWidth(): void
    {
        $grid = $this->container('core/group', [
            $this->container('core/group', [$this->imageBlock()], [
                'style' => ['spacing' => ['padding' => [
                    'left' => 'var:preset|spacing|20', 'right' => 'var:preset|spacing|20',
                ]]],
            ]),
        ], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
        $xpath = $this->parseMjml(BlockProcessor::render($grid, RenderContext::root(42, 600)));

        $this->assertNodes($xpath, '/test-root/mj-wrapper/mj-section/mj-column/mj-image', 1);
        self::assertSame(['0 20px 0 20px'], $this->values($xpath, '//mj-column/@padding'));
        $this->assertDimensions($xpath, '260px', '130px');
    }

    public function testImageOwnPaddingStillReducesWidthInsidePaddedGridCell(): void
    {
        $image = $this->imageBlock();
        $image['attrs']['style']['spacing']['padding'] = [
            'left' => 'var:preset|spacing|15', 'right' => 'var:preset|spacing|15',
        ];
        $grid = $this->container('core/group', [
            $this->container('core/group', [$image], [
                'style' => ['spacing' => ['padding' => [
                    'left' => 'var:preset|spacing|20', 'right' => 'var:preset|spacing|20',
                ]]],
            ]),
        ], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
        $xpath = $this->parseMjml(BlockProcessor::render($grid, RenderContext::root(42, 600)));

        // 300px cell - 40px cell padding - 20px image padding.
        $this->assertDimensions($xpath, '240px', '120px');
        self::assertSame(['0 20px 0 20px'], $this->values($xpath, '//mj-column/@padding'));
    }

    public function testDirectGridImageRetainsItsOwnPadding(): void
    {
        $image = $this->imageBlock();
        $image['attrs']['style']['spacing']['padding'] = [
            'left' => 'var:preset|spacing|15', 'right' => 'var:preset|spacing|15',
        ];
        $grid = $this->container('core/group', [$image], [
            'layout' => ['type' => 'grid', 'columnCount' => 2],
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($grid, RenderContext::root(42, 600)));

        $this->assertDimensions($xpath, '280px', '140px');
        self::assertSame(['0'], $this->values($xpath, '//mj-column/@padding'));
    }
}
