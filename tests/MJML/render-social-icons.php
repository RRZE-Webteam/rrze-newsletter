<?php
declare(strict_types=1);

use RRZE\Newsletter\MJML\SocialIcons;
use RRZE\Newsletter\MJML\BlockProcessor\SocialLinksProcessor;
use RRZE\Newsletter\MJML\TemplateRenderer;

// Isolated fixtures: no live database, WordPress bootstrap or external requests.
if (PHP_SAPI !== 'cli' || defined('ABSPATH')) {
    exit(1);
}
require __DIR__ . '/bootstrap.php';

$links = [];
foreach (SocialIcons::getServices() as $service => $details) {
    $links[] = ['blockName' => 'core/social-link', 'attrs' => [
        'service' => $service,
        'url' => $service === 'mail' ? 'mailto:team@example.test' : 'https://example.test/' . $service,
    ]];
}
$fallback = ['blockName' => 'core/social-link', 'attrs' => ['service' => 'future-service', 'url' => 'https://example.test/future?a=1&b=2', 'label' => '<b>Future & news</b>']];
$fixtures = [
    'all' => SocialLinksProcessor::render([], $links),
    'labels' => SocialLinksProcessor::render(['showLabels' => true], $links),
    'fallback' => SocialLinksProcessor::render([], [$links[0], $fallback, ['blockName' => 'core/social-link', 'attrs' => ['service' => 'future-service', 'url' => 'javascript:alert(1)']], end($links)]),
];
foreach ($fixtures as &$markup) {
    $markup = TemplateRenderer::renderTemplate([
        'title' => 'Social icon regression', 'preview_text' => '', 'background_color' => '#ffffff',
        'body_width' => 680, 'link_color' => '#04316a', 'link_text_decoration' => 'underline',
        'body' => '<mj-section><mj-column>' . $markup . '</mj-column></mj-section>',
    ]);
}
unset($markup);
echo json_encode($fixtures, JSON_THROW_ON_ERROR);
