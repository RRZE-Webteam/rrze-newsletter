<?php

declare(strict_types=1);

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\TemplateRenderer;

require __DIR__ . '/bootstrap.php';

function spacingContainer(string $name, array $children, array $attrs = []): array
{
    return ['blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $children, 'innerHTML' => '<div></div>'];
}

$image = ['blockName' => 'core/image', 'attrs' => [], 'innerHTML' => '<figure><img src="https://example.test/image.png" width="1200" height="600" /></figure>'];
$fixtures = ['root' => $image];
foreach ([1, 5, 12] as $depth) {
    $block = $image;
    for ($i = 0; $i < $depth; $i++) {
        $block = spacingContainer('core/group', [$block], ['style' => ['spacing' => ['padding' => ['left' => '200px', 'right' => '200px']]]]);
    }
    $fixtures['depth-' . $depth] = $block;
}
$cell = $fixtures['depth-5'];
$fixtures['columns'] = spacingContainer('core/columns', [spacingContainer('core/column', [$cell]), spacingContainer('core/column', [$cell])]);
$fixtures['grid'] = spacingContainer('core/group', [$cell, $cell], ['layout' => ['type' => 'grid', 'columnCount' => 2]]);
$fixtures['button'] = ['blockName' => 'core/button', 'attrs' => ['width' => 100, 'style' => ['spacing' => ['padding' => ['left' => '300px', 'right' => '300px']]]], 'innerHTML' => '<a href="https://example.test/news" style="margin:100px">Read more</a>'];

$rendered = [];
foreach ($fixtures as $name => $block) {
    $rendered[$name] = TemplateRenderer::renderTemplate([
        'title' => 'Managed spacing regression',
        'preview_text' => '',
        'background_color' => '#ffffff',
        'body_width' => 680,
        'managed_spacing' => true,
        'body' => BlockProcessor::render($block, RenderContext::root(0, managedSpacing: true)),
    ]);
}
echo json_encode($rendered, JSON_THROW_ON_ERROR);
