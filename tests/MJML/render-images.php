<?php

declare(strict_types=1);

namespace {
    use RRZE\Newsletter\MJML\BlockProcessor\ImageProcessor;
    use RRZE\Newsletter\MJML\TemplateRenderer;

    require __DIR__ . '/bootstrap.php';

    $fixtures = [
        'landscape' => [[], 1200, 600, 680],
        'portrait' => [[], 600, 1200, 680],
        'small' => [[], 200, 100, 680],
        'pixel-width' => [['width' => 400], 1200, 600, 680],
        'percentage-width' => [['width' => '50%'], 1200, 600, 680],
        'oversized-width' => [['width' => 1200], 1200, 600, 680],
        'medium' => [['sizeSlug' => 'medium'], 1200, 600, 680],
        'thumbnail' => [['sizeSlug' => 'thumbnail'], 1200, 600, 680],
        'narrow-container' => [[], 1200, 600, 260],
        'explicit-dimensions' => [['width' => 400, 'height' => 400], 1200, 600, 680],
        'oversized-explicit-dimensions' => [['width' => 1200, 'height' => 800], 1200, 600, 600],
        'height-only' => [['height' => 100], 1200, 600, 680],
    ];

    $rendered = [];
    foreach ($fixtures as $name => [$attrs, $width, $height, $availableWidth]) {
        $image = ImageProcessor::render(
            $attrs,
            '<img src="https://example.test/image.png" width="' . $width . '" height="' . $height . '" />',
            'Arial',
            $availableWidth
        );
        $rendered[$name] = TemplateRenderer::renderTemplate([
            'title' => 'Responsive image regression',
            'preview_text' => '',
            'background_color' => '#ffffff',
            'body_width' => $availableWidth,
            'link_color' => '#04316a',
            'link_text_decoration' => 'underline',
            'body' => '<mj-section padding="0"><mj-column padding="0">'
                . $image . '</mj-column></mj-section>',
        ]);
    }

    echo json_encode($rendered, JSON_THROW_ON_ERROR);
}
