<?php

declare(strict_types=1);

use RRZE\Newsletter\MJML\BlockProcessor\ButtonProcessor;
use RRZE\Newsletter\MJML\TemplateRenderer;

require __DIR__ . '/bootstrap.php';

// Synthetic version of the reported layout: 680px body, 40px side padding.
// Do not commit the original email, recipients, content or tracked URLs.
$fixtures = [
    'full' => ['width' => 100],
    'half' => ['width' => 50],
    'quarter' => ['width' => 25],
    'fractional' => ['width' => '33.3'],
    'above-maximum' => ['width' => 150],
    'below-minimum' => ['width' => 0],
    'outline' => ['width' => 100, 'className' => 'is-style-outline'],
    'automatic' => [],
    'unsupported' => ['width' => 'auto'],
];

$rendered = [];
foreach ($fixtures as $name => $attrs) {
    $button = ButtonProcessor::renderButton(
        $attrs,
        '<a href="https://example.test/news">Read more</a>',
        'Arial',
        'left',
        600
    );
    $rendered[$name] = TemplateRenderer::renderTemplate([
        'title' => 'Responsive button regression',
        'preview_text' => '',
        'background_color' => '#ffffff',
        'body_width' => 680,
        'link_color' => '#04316a',
        'link_text_decoration' => 'underline',
        'body' => '<mj-wrapper padding="40px"><mj-section padding="0"><mj-column padding="0">'
            . $button . '</mj-column></mj-section></mj-wrapper>',
    ]);
}

echo json_encode($rendered, JSON_THROW_ON_ERROR);
