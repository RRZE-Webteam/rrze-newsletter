<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML\BlockProcessor;

use DOMXPath;
use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\ButtonProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class ButtonProcessorTest extends MjmlTestCase
{
    private function button(array $attrs = [], string $html = '', int $width = 600): DOMXPath
    {
        return $this->parseMjml(ButtonProcessor::renderButton(
            $attrs + ['text' => 'Read more'],
            $html,
            'Georgia',
            'left',
            $width
        ));
    }

    private function buttonBlock(array $attrs, string $html = ''): array
    {
        return [
            'blockName' => 'core/button',
            'attrs' => $attrs,
            'innerHTML' => $html,
            'innerContent' => [$html],
            'innerBlocks' => [],
        ];
    }

    public function testDefaultButtonHasReadableTypographyAndNoInventedLink(): void
    {
        $xpath = $this->button();

        self::assertSame(['Read more'], $this->values($xpath, '/test-root/mj-button'));
        foreach ([
            'font-family' => 'Georgia',
            'font-size' => '16px',
            'font-weight' => 'normal',
            'color' => '#ffffff',
            'background-color' => '#32373c',
            'inner-padding' => '12px 24px',
            'border-radius' => '4px',
        ] as $attribute => $expected) {
            self::assertSame([$expected], $this->values($xpath, '//mj-button/@' . $attribute));
        }
        $this->assertNodes($xpath, '//mj-button/@href | //mj-button/@width | //mj-button/@border', 0);
    }

    public function testAnchorMarkupProvidesTextAndLinkMetadata(): void
    {
        $xpath = $this->parseMjml(ButtonProcessor::renderButton(
            [],
            '<div><a href="https://example.test/news" target="_blank" rel="noopener" title="Details">'
                . 'Mehr <strong>Neuigkeiten</strong></a></div>',
            'Arial'
        ));

        self::assertSame(['Mehr Neuigkeiten'], $this->values($xpath, '//mj-button'));
        self::assertSame(['Neuigkeiten'], $this->values($xpath, '//mj-button/strong'));
        self::assertSame(['https://example.test/news'], $this->values($xpath, '//mj-button/@href'));
        self::assertSame(['_blank'], $this->values($xpath, '//mj-button/@target'));
        self::assertSame(['noopener'], $this->values($xpath, '//mj-button/@rel'));
        self::assertSame(['Details'], $this->values($xpath, '//mj-button/@title'));
    }

    public function testBlockAttributesOverrideStoredAnchorValues(): void
    {
        $xpath = $this->button([
            'text' => ' Updated ',
            'url' => ' https://example.test/new ',
            'linkTarget' => '_self',
            'rel' => 'nofollow',
            'title' => 'New title',
        ], '<a href="https://example.test/old" target="_blank" rel="noopener" title="Old">Old</a>');

        foreach ([
            'href' => 'https://example.test/new',
            'target' => '_self',
            'rel' => 'nofollow',
            'title' => 'New title',
        ] as $attribute => $expected) {
            self::assertSame([$expected], $this->values($xpath, '//mj-button/@' . $attribute));
        }
        self::assertSame(['Updated'], $this->values($xpath, '//mj-button'));
    }

    public function testButtonElementCanSupplyLabelWithoutAnchor(): void
    {
        $xpath = $this->parseMjml(ButtonProcessor::renderButton(
            [], '<button><em>Subscribe</em></button>', 'Arial'
        ));

        self::assertSame(['Subscribe'], $this->values($xpath, '//mj-button/em'));
        $this->assertNodes($xpath, '//mj-button/@href', 0);
    }

    public function testMissingLabelsAreSkippedAndAttributeTextCanRecoverMissingAnchor(): void
    {
        foreach (['', '<div>No button</div>', '<a href="https://example.test"> </a>'] as $html) {
            self::assertSame('', ButtonProcessor::renderButton([], $html, 'Arial'), $html);
        }
        self::assertSame(
            ['From attributes'],
            $this->values($this->button(['text' => 'From attributes'], '<div></div>'), '//mj-button')
        );
    }

    public function testOutlineStyleUsesTransparentBackgroundAndTextColoredBorder(): void
    {
        $xpath = $this->button([
            'className' => 'custom is-style-outline',
            'color' => '#04316a',
            'background-color' => '#eeeeee',
        ]);

        self::assertSame(['transparent'], $this->values($xpath, '//mj-button/@background-color'));
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-button/@color'));
        self::assertSame(['2px solid #04316a'], $this->values($xpath, '//mj-button/@border'));
    }

    public function testExplicitBorderAndCornerRadiiOverrideOutlineDefaults(): void
    {
        $xpath = $this->button([
            'className' => 'is-style-outline no-border-radius',
            'style' => ['border' => [
                'width' => '3px',
                'style' => 'dashed',
                'color' => '#ff0000',
                'radius' => ['topLeft' => '1px', 'topRight' => '2px', 'bottomRight' => '3px', 'bottomLeft' => '4px'],
            ]],
        ]);

        self::assertSame(['3px dashed #ff0000'], $this->values($xpath, '//mj-button/@border'));
        self::assertSame(['1px 2px 3px 4px'], $this->values($xpath, '//mj-button/@border-radius'));
    }

    public function testBorderNeedsBothWidthAndColor(): void
    {
        $xpath = $this->button(['style' => ['border' => ['width' => '1px']], 'border-color' => '#123456']);
        self::assertSame(['1px solid #123456'], $this->values($xpath, '//mj-button/@border'));

        foreach ([
            ['style' => ['border' => ['width' => '1px']]],
            ['border-color' => '#123456'],
        ] as $attrs) {
            $this->assertNodes($this->button($attrs), '//mj-button/@border', 0);
        }
    }

    public function testRadiusSupportsScalarCornersAndSquareStyle(): void
    {
        foreach ([
            [['style' => ['border' => ['radius' => '8px']]], '8px'],
            [['style' => ['border' => ['radius' => 0]]], '0'],
            [['className' => 'custom no-border-radius'], '0'],
            [['style' => ['border' => ['radius' => ['topLeft' => '5px']]]], '5px 0 0 0'],
        ] as [$attrs, $expected]) {
            self::assertSame([$expected], $this->values($this->button($attrs), '//mj-button/@border-radius'));
        }
    }

    public function testPaddingSupportsPresetsCssUnitsAndZero(): void
    {
        foreach ([
            ['var:preset|spacing|30', '40px 40px'],
            ['1.5rem', '1.5rem 1.5rem'],
            ['10%', '10% 10%'],
            [0, '0 0'],
            ['auto', '0 0'],
            [false, '12px 24px'],
        ] as [$padding, $expected]) {
            $xpath = $this->button(['style' => ['spacing' => ['padding' => $padding]]]);
            self::assertSame([$expected], $this->values($xpath, '//mj-button/@inner-padding'), var_export($padding, true));
        }
    }

    public function testIndividualPaddingSidesKeepOrderAndFillMissingOppositeSides(): void
    {
        foreach ([
            [['top' => '2px', 'right' => '4px', 'bottom' => '6px', 'left' => '8px'], '2px 4px 6px 8px'],
            [['bottom' => '6px', 'left' => '8px'], '6px 8px 6px 8px'],
            [[], '12px 24px 12px 24px'],
        ] as [$padding, $expected]) {
            $xpath = $this->button(['style' => ['spacing' => ['padding' => $padding]]]);
            self::assertSame([$expected], $this->values($xpath, '//mj-button/@inner-padding'));
        }
    }

    public function testWidthPercentageIsPreservedAndClampedToBounds(): void
    {
        foreach ([[50, 600, '50%'], ['33.3', 500, '33.3%'], [150, 600, '100%'], [0, 600, '1%'], [-25, 600, '1%'], [1, 20, '1%']] as [$width, $available, $expected]) {
            self::assertSame(
                [$expected],
                $this->values($this->button(['width' => $width], '', $available), '//mj-button/@width')
            );
        }
        $this->assertNodes($this->button(['width' => 'auto']), '//mj-button/@width', 0);
    }

    public function testFullWidthButtonStaysFluidRegardlessOfRenderTimeContainerWidth(): void
    {
        foreach ([160, 240, 320, 479, 480, 600, 680] as $availableWidth) {
            self::assertSame(
                ['100%'],
                $this->values($this->button(['width' => 100], '', $availableWidth), '//mj-button/@width')
            );
        }
    }

    public function testTypographyAndFontSizeAreInheritedWithChildOverrides(): void
    {
        $xpath = $this->parseMjml(ButtonProcessor::renderButtons(
            [
                'font-size' => '22px',
                'style' => ['typography' => [
                    'fontWeight' => '700', 'lineHeight' => '1.8',
                    'letterSpacing' => '2px', 'textDecoration' => 'underline', 'textTransform' => 'uppercase',
                ]],
            ],
            [
                $this->buttonBlock(['text' => 'Inherited']),
                $this->buttonBlock([
                    'text' => 'Custom', 'customFontSize' => 18,
                    'style' => ['typography' => ['fontWeight' => '400', 'lineHeight' => '1.2']],
                ]),
                $this->buttonBlock(['text' => 'Preset', 'fontSize' => 'small']),
            ],
            'Georgia',
            600
        ));

        self::assertSame(['Inherited', 'Custom', 'Preset'], $this->values($xpath, '//mj-button'));
        self::assertSame(['22px', '18px', '14px'], $this->values($xpath, '//mj-button/@font-size'));
        self::assertSame(['700', '400', '700'], $this->values($xpath, '//mj-button/@font-weight'));
        self::assertSame(['1.8', '1.2', '1.8'], $this->values($xpath, '//mj-button/@line-height'));
        foreach (['letter-spacing' => '2px', 'text-decoration' => 'underline', 'text-transform' => 'uppercase'] as $attribute => $expected) {
            self::assertSame(array_fill(0, 3, $expected), $this->values($xpath, '//mj-button/@' . $attribute));
        }
    }

    public function testGroupAlignmentPrecedenceAndSupportedAliases(): void
    {
        foreach ([
            [[], 'left'],
            [['align' => 'center'], 'center'],
            [['align' => 'left', 'contentJustification' => 'right'], 'right'],
            [['contentJustification' => 'right', 'layout' => ['justifyContent' => 'center']], 'center'],
            [['layout' => ['justifyContent' => 'flex-end']], 'right'],
            [['layout' => ['justifyContent' => 'end']], 'right'],
            [['layout' => ['justifyContent' => 'flex-start']], 'left'],
            [['layout' => ['justifyContent' => 'start']], 'left'],
            [['layout' => ['justifyContent' => 'space-between']], 'left'],
        ] as [$attrs, $expected]) {
            $xpath = $this->parseMjml(ButtonProcessor::renderButtons(
                $attrs, [$this->buttonBlock(['text' => 'Read'])], 'Arial', 600
            ));
            self::assertSame([$expected], $this->values($xpath, '//mj-button/@align'), var_export($attrs, true));
        }
    }

    public function testButtonGroupIgnoresUnsupportedAndEmptyChildren(): void
    {
        $xpath = $this->parseMjml(ButtonProcessor::renderButtons(
            [],
            [
                $this->buttonBlock(['text' => 'First']),
                $this->listBlock('Unsupported'),
                $this->buttonBlock([]),
                $this->buttonBlock(['text' => 'Last']),
            ],
            'Arial',
            600
        ));
        self::assertSame(['First', 'Last'], $this->values($xpath, '/test-root/mj-button'));
        self::assertSame('', ButtonProcessor::renderButtons([], [], 'Arial', 600));
    }

    public function testHtmlParsingRestoresLibxmlErrorMode(): void
    {
        $original = libxml_use_internal_errors();
        try {
            foreach ([false, true] as $mode) {
                libxml_use_internal_errors($mode);
                ButtonProcessor::renderButton([], '<a href="https://example.test">Read</a>', 'Arial');
                self::assertSame($mode, libxml_use_internal_errors());
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($original);
        }
    }

    public function testButtonsInsidePaddedColumnUseRemainingContentWidth(): void
    {
        $button = $this->buttonBlock(['width' => 50], '<a href="https://example.test">Read</a>');
        $columnAttrs = [
            'width' => '50%',
            'style' => ['spacing' => ['padding' => [
                'left' => 'var:preset|spacing|20', 'right' => 'var:preset|spacing|20',
            ]]],
        ];
        // Exercise both direct core/button and core/buttons dispatch.
        $columns = $this->container('core/columns', [
            $this->container('core/column', [$button], $columnAttrs),
            $this->container('core/column', [
                $this->container('core/buttons', [$button], ['layout' => ['justifyContent' => 'center']]),
            ], $columnAttrs),
        ]);
        $xpath = $this->parseMjml(BlockProcessor::render($columns, RenderContext::root(42, 600)));

        // Half of each column's content box, including after a mobile resize.
        self::assertSame(['50%', '50%'], $this->values($xpath, '//mj-button/@width'));
        self::assertSame(['left', 'center'], $this->values($xpath, '//mj-button/@align'));
        $this->assertNodes($xpath, '/test-root/mj-section/mj-column/mj-button', 2);
        $this->assertNodes($xpath, '//mj-column//mj-column | //mj-column//mj-section', 0);
    }

    public function testGridCellKeepsInheritedTypographyWithoutReusingCellPaddingOnButton(): void
    {
        $button = $this->buttonBlock(['width' => 100], '<a href="https://example.test">Read</a>');
        $grid = $this->container('core/group', [
            $this->container('core/group', [$button], [
                'customTextColor' => '#04316a',
                'style' => [
                    'typography' => ['fontWeight' => '700', 'lineHeight' => '1.8'],
                    'spacing' => ['padding' => [
                        'left' => 'var:preset|spacing|20', 'right' => 'var:preset|spacing|20',
                    ]],
                ],
            ]),
        ], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
        $xpath = $this->parseMjml(BlockProcessor::render($grid, RenderContext::root(42, 600)));

        self::assertSame(['100%'], $this->values($xpath, '//mj-button/@width'));
        self::assertSame(['12px 24px'], $this->values($xpath, '//mj-button/@inner-padding'));
        self::assertSame(['700'], $this->values($xpath, '//mj-button/@font-weight'));
        self::assertSame(['1.8'], $this->values($xpath, '//mj-button/@line-height'));
        self::assertSame(['#04316a'], $this->values($xpath, '//mj-button/@color'));
    }
}
