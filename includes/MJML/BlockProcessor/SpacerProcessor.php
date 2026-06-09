<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class SpacerProcessor
{
    public static function render(array $attrs): string
    {
        $heightParts = explode('|', (string) ($attrs['height'] ?? '0'));
        $spacerAttrs = [
            'height' => absint(end($heightParts)) . 'px',
        ];

        return '<mj-spacer '
            . AttributeHandler::arrayToAttributes($spacerAttrs)
            . ' />';
    }
}
