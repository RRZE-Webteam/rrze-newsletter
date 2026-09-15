<?php

declare(strict_types=1);

namespace {
    // Standalone CLI fixtures only: never bootstrap the live WordPress site.
    if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
        exit(1);
    }
    define('ABSPATH', dirname(__DIR__, 2) . '/');
    require ABSPATH . 'vendor/autoload.php';
}

namespace RRZE\Newsletter {
    function plugin(): object
    {
        return new class {
            public function getDirectory(): string
            {
                return ABSPATH;
            }
        };
    }
}

namespace RRZE\Newsletter\MJML {
    // Serialization boundary only; this does not test WordPress escaping.
    function esc_attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}

namespace RRZE\Newsletter\MJML\BlockProcessor {
    function attachment_url_to_postid(string $url): int
    {
        throw new \LogicException('Image fixtures must not perform metadata or network lookups.');
    }
}

namespace {
    use RRZE\Newsletter\MJML\BlockProcessor\ImageProcessor;
    use RRZE\Newsletter\MJML\TemplateRenderer;

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
