<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Templates;

final class TemplatesTest extends TestCase
{
    public function testMissingTemplateReturnsEmptyString(): void
    {
        $name = 'missing-unit-test-template.mjml';
        self::assertFileDoesNotExist(dirname(__DIR__, 2) . '/includes/templates/' . $name);

        self::assertSame('', Templates::getContent($name));
        self::assertSame('', Templates::getContent($name, ['title' => 'Ignored']));
    }

    public function testNoDataReturnsTheUnparsedTemplate(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/templates/newsletter.mjml');
        self::assertNotFalse($source);
        self::assertSame($source, Templates::getContent('newsletter.mjml'));
        self::assertStringContainsString('{{=title}}', $source);
        self::assertStringContainsString('{{preview_text}}', $source);
    }

    public function testProvidedDataIsInterpolatedAndMissingValuesAreEmpty(): void
    {
        $content = Templates::getContent('newsletter.mjml', ['title' => 'September news']);

        self::assertStringContainsString('<mj-title>September news</mj-title>', $content);
        self::assertStringNotContainsString('<mj-preview>', $content);
        self::assertStringNotContainsString('{{', $content);
    }

    public function testTemplateContentIsReturnedWithoutLeakingOutputOrBuffers(): void
    {
        $originalLevel = ob_get_level();
        ob_start();
        try {
            echo 'caller-prefix';
            $content = Templates::getContent('newsletter.mjml', ['title' => 'Buffered']);

            self::assertSame($originalLevel + 1, ob_get_level());
            self::assertSame('caller-prefix', ob_get_contents());
            self::assertStringContainsString('<mj-title>Buffered</mj-title>', $content);
        } finally {
            while (ob_get_level() > $originalLevel) {
                ob_end_clean();
            }
        }
    }
}
