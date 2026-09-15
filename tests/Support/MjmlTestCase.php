<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Support;

use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

abstract class MjmlTestCase extends TestCase
{
    /**
     * These fixtures model already-parsed blocks. WordPress parsing is not mocked.
     */
    protected function container(string $name, array $children, array $attrs = []): array
    {
        return [
            'blockName' => $name,
            'attrs' => $attrs,
            'innerBlocks' => $children,
            'innerHTML' => '<div></div>',
            'innerContent' => array_merge(
                ['<div>'],
                array_fill(0, count($children), null),
                ['</div>']
            ),
        ];
    }

    protected function listBlock(string $text, array $attrs = []): array
    {
        // Plain labels and XML-compatible markup keep the structural parser strict.
        $html = '<ul><li>' . $text . '</li></ul>';
        return [
            'blockName' => 'core/list',
            'attrs' => $attrs,
            'innerBlocks' => [],
            'innerHTML' => $html,
            'innerContent' => [$html],
        ];
    }

    protected function parseMjml(string $markup): DOMXPath
    {
        $document = new DOMDocument();
        self::assertTrue(
            $document->loadXML('<test-root>' . $markup . '</test-root>', LIBXML_NONET),
            'Generated fixture markup must be well formed.'
        );

        return new DOMXPath($document);
    }

    protected function values(DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, 'Invalid test XPath: ' . $query);
        $values = [];
        foreach ($nodes as $node) {
            $values[] = $node->nodeValue;
        }
        return $values;
    }

    protected function assertNodes(DOMXPath $xpath, string $query, int $count): void
    {
        self::assertSame((float) $count, $xpath->evaluate('count(' . $query . ')'), $query);
    }
}
