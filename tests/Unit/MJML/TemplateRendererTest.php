<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit\MJML;

use RRZE\Newsletter\MJML\TemplateRenderer;
use RRZE\Newsletter\Tests\Support\MjmlTestCase;

final class TemplateRendererTest extends MjmlTestCase
{
    public function testTemplateAssemblesTitlePreviewBodyAndLayoutAttributes(): void
    {
        $xpath = $this->parseMjml(TemplateRenderer::renderTemplate($this->data()));

        self::assertSame(['September news'], $this->values($xpath, '/test-root/mjml/mj-head/mj-title'));
        self::assertSame(['What is new this month'], $this->values($xpath, '//mj-preview'));
        self::assertSame(['#fafafa'], $this->values($xpath, '//mj-body/@background-color'));
        self::assertSame(['680px'], $this->values($xpath, '//mj-body/@width'));
        self::assertSame(['Hello readers'], $this->values($xpath, '//mj-body/mj-section/mj-column/mj-text'));
    }

    public function testMissingOrEmptyPreviewDoesNotRenderPreviewElement(): void
    {
        foreach ([true, false] as $omit) {
            $data = $this->data(['preview_text' => '']);
            if ($omit) {
                unset($data['preview_text']);
            }
            $xpath = $this->parseMjml(TemplateRenderer::renderTemplate($data));

            $this->assertNodes($xpath, '//mj-preview', 0);
            $this->assertNodes($xpath, '//mj-title | //mj-body', 2);
        }
    }

    public function testConfiguredLinkAppearanceAndMobileImageRuleRemainPresent(): void
    {
        $xpath = $this->parseMjml(TemplateRenderer::renderTemplate($this->data()));
        $css = implode('', $this->values($xpath, '//mj-style'));

        self::assertStringContainsString('color: #123456;', $css);
        self::assertStringContainsString('text-decoration: underline;', $css);
        self::assertStringContainsString('@media all and (max-width: 479px)', $css);
        self::assertStringContainsString('.mj-full-width-mobile img { height: auto !important; }', $css);
    }

    public function testFinalMarkupIsCompactedAndHoverRuleRemoved(): void
    {
        $markup = TemplateRenderer::renderTemplate($this->data());

        self::assertStringNotContainsString("\n", $markup);
        self::assertStringNotContainsString("\r", $markup);
        self::assertStringNotContainsString("\t", $markup);
        self::assertDoesNotMatchRegularExpression('/a:hover\s*\{/i', $markup);
        // A neighboring standalone rule must not be removed with the hover rule.
        self::assertStringContainsString('a:focus { outline: thin dotted #000; }', $markup);
    }

    public function testRecipientTokensInInsertedBodySurviveTemplateAssembly(): void
    {
        $body = '<mj-section><mj-column><mj-text>Hello {{=FNAME}}'
            . ' <a href="{{=UNSUB}}">Unsubscribe</a></mj-text></mj-column></mj-section>';
        $markup = TemplateRenderer::renderTemplate($this->data(['body' => $body]));
        $xpath = $this->parseMjml($markup);

        self::assertStringContainsString('Hello {{=FNAME}}', $markup);
        self::assertSame(['{{=UNSUB}}'], $this->values($xpath, '//mj-text/a/@href'));
        self::assertStringNotContainsString('{{=body}}', $markup);
    }

    public function testConsecutiveRendersDoNotReuseTitlePreviewOrBody(): void
    {
        TemplateRenderer::renderTemplate($this->data());
        $markup = TemplateRenderer::renderTemplate($this->data([
            'title' => 'Second newsletter', 'preview_text' => '', 'body' => '',
            'background_color' => '#ffffff', 'body_width' => 600,
        ]));
        $xpath = $this->parseMjml($markup);

        self::assertSame(['Second newsletter'], $this->values($xpath, '//mj-title'));
        self::assertSame(['600px'], $this->values($xpath, '//mj-body/@width'));
        self::assertSame(['#ffffff'], $this->values($xpath, '//mj-body/@background-color'));
        $this->assertNodes($xpath, '//mj-preview | //mj-body/*', 0);
        self::assertStringNotContainsString('Hello readers', $markup);
    }

    private function data(array $overrides = []): array
    {
        return array_replace([
            'title' => 'September news',
            'preview_text' => 'What is new this month',
            'background_color' => '#fafafa',
            'body_width' => 680,
            'link_color' => '#123456',
            'link_text_decoration' => 'underline',
            'body' => '<mj-section><mj-column><mj-text>Hello readers</mj-text></mj-column></mj-section>',
        ], $overrides);
    }
}
