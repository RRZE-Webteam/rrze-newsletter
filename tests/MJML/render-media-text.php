<?php

declare(strict_types=1);

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\TemplateRenderer;

require __DIR__ . '/bootstrap.php';

$text = ['blockName' => 'core/list', 'attrs' => [], 'innerBlocks' => [],
    'innerHTML' => '<ul><li><strong>Newsletter content</strong> with <a href="https://example.test/news">a link</a>.</li></ul>'];
$text['innerContent'] = [$text['innerHTML']];
$media = ['blockName' => 'core/media-text', 'attrs' => [], 'innerBlocks' => [$text],
    'innerHTML' => '<div class="wp-block-media-text"><figure><a href="https://example.test/image-link"><img src="https://example.test/media.jpg" width="1200" height="600" alt="Media fixture" /></a></figure><div></div></div>'];
$fixtures = [];
foreach (['left', 'right'] as $side) {
    foreach ([true, false] as $stacked) {
        foreach ([true, false] as $managed) {
            $block = $media;
            $block['attrs'] = ['mediaPosition' => $side, 'mediaWidth' => 35, 'isStackedOnMobile' => $stacked, 'customBackgroundColor' => '#e8edf5'];
            $name = $side . '-' . ($stacked ? 'stacked' : 'inline') . '-' . ($managed ? 'managed' : 'expert');
            $fixtures[$name] = [$block, $managed];
        }
    }
}
$fixtures['nested'] = [['blockName' => 'core/columns', 'attrs' => [], 'innerHTML' => '<div></div>', 'innerBlocks' => [
    ['blockName' => 'core/column', 'attrs' => [], 'innerHTML' => '<div></div>', 'innerBlocks' => [$media]],
    ['blockName' => 'core/column', 'attrs' => [], 'innerHTML' => '<div></div>', 'innerBlocks' => [$text]],
]], true];
$rendered = [];
foreach ($fixtures as $name => [$block, $managed]) {
    $rendered[$name] = TemplateRenderer::renderTemplate([
        'title' => 'Media and text regression', 'preview_text' => '', 'background_color' => '#ffffff',
        'body_width' => 680, 'managed_spacing' => $managed,
        'body' => BlockProcessor::render($block, RenderContext::root(0, managedSpacing: $managed)),
    ]);
}
echo json_encode($rendered, JSON_THROW_ON_ERROR);
