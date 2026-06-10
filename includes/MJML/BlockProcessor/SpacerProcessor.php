<?php

namespace RRZE\Newsletter\MJML\BlockProcessor;

defined('ABSPATH') || exit;

use RRZE\Newsletter\MJML\AttributeHandler;

final class SpacerProcessor
{
    /**
     * Render a core/spacer block as an MJML spacer.
     *
     * @param array<string, mixed> $attrs Block attributes.
     * @return string Rendered MJML spacer.
     */
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
