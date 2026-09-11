<?php

declare(strict_types=1);

namespace RRZE\Newsletter\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RRZE\Newsletter\Parser;

final class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testItInterpolatesRawAndEscapedValues(): void
    {
        $result = $this->parser->parse(
            '{{=raw}}|{{%escaped}}',
            [
                'raw' => '<strong>Newsletter</strong>',
                'escaped' => '<strong>Newsletter</strong>',
            ]
        );

        self::assertSame(
            '<strong>Newsletter</strong>|&lt;strong&gt;Newsletter&lt;/strong&gt;',
            $result
        );
    }

    public function testItResolvesNestedValues(): void
    {
        $result = $this->parser->parse(
            '{{=subscriber.name}} <{{=subscriber.email}}>',
            [
                'subscriber' => [
                    'name' => 'Ada Lovelace',
                    'email' => 'ada@example.test',
                ],
            ]
        );

        self::assertSame('Ada Lovelace <ada@example.test>', $result);
    }

    public function testItRendersIfElseAndNegativeBlocks(): void
    {
        $template = '{{FNAME}}Hello {{=FNAME}}{{:FNAME}}Hello{{/FNAME}}'
            . '{{!UNSUB}} without unsubscribe link{{/!UNSUB}}';

        self::assertSame(
            'Hello Ada without unsubscribe link',
            $this->parser->parse($template, ['FNAME' => 'Ada', 'UNSUB' => ''])
        );
        self::assertSame(
            'Hello',
            $this->parser->parse($template, ['FNAME' => '', 'UNSUB' => '/unsubscribe'])
        );
    }

    public function testItIteratesArraysAndRestoresOuterVariables(): void
    {
        $template = '{{@items}}[{{=_key}}={{=_val.label}}]{{/@items}}'
            . '|{{=_key}}|{{=_val}}';

        $result = $this->parser->parse(
            $template,
            [
                '_key' => 'outer-key',
                '_val' => 'outer-value',
                'items' => [
                    'first' => ['label' => 'One'],
                    'second' => ['label' => 'Two'],
                ],
            ]
        );

        self::assertSame(
            '[first=One][second=Two]|outer-key|outer-value',
            $result
        );
    }

    public function testMissingValuesRenderAsEmptyStrings(): void
    {
        self::assertSame(
            'Before  after',
            $this->parser->parse('Before {{=missing}} after', [])
        );
    }
}
