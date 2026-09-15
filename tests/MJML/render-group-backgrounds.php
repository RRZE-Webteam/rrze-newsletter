<?php
declare(strict_types=1);

use RRZE\Newsletter\MJML\BlockProcessor\BlockProcessor;
use RRZE\Newsletter\MJML\BlockProcessor\RenderContext;
use RRZE\Newsletter\MJML\TemplateRenderer;

require __DIR__ . '/bootstrap.php';

function backgroundGroup(array $children, array $attrs = [], string $name = 'core/group'): array
{
    return ['blockName' => $name, 'attrs' => $attrs, 'innerBlocks' => $children, 'innerHTML' => '<div></div>'];
}
function backgroundText(string $id, array $colors): array
{
    return ['blockName' => 'core/list', 'attrs' => ['style' => ['color' => $colors, 'spacing' => ['padding' => ['top' => '20px']]]],
        'innerHTML' => '<ul><li id="' . $id . '">' . $id . '</li></ul>'];
}

$deep = backgroundText('deep', ['text' => '#000000']);
for ($i = 0; $i < 12; $i++) {
    $deep = backgroundGroup([$deep], ['style' => ['typography' => ['fontWeight' => '700']]]);
}
$link = backgroundText('link-parent', ['text' => '#000000']);
$link['innerHTML'] = '<ul><li><a id="link" href="https://example.test/news" style="color:#000000">News</a></li></ul>';
$dark = backgroundGroup([
    backgroundText('dark', ['text' => '#000000']),
    backgroundText('light', ['background' => '#ffffff', 'text' => '#000000']),
    backgroundText('readable', ['text' => '#ffffff']),
    $deep, $link,
    ['blockName' => 'core/button', 'attrs' => ['style' => ['color' => ['background' => '#ffcc00', 'text' => '#000000']]],
        'innerHTML' => '<a href="https://example.test/button">Button</a>'],
], ['style' => ['color' => ['background' => '#04316a']]]);

$fixtures = [];
foreach ([false, true] as $managed) {
    foreach (['group', 'column', 'grid'] as $layout) {
        $content = match ($layout) {
            'column' => backgroundGroup([backgroundGroup([$dark], [], 'core/column')], [], 'core/columns'),
            'grid' => backgroundGroup([$dark], ['layout' => ['type' => 'grid', 'columnCount' => 1]]),
            default => $dark,
        };
        $block = backgroundGroup([$content, backgroundText('sibling', ['text' => '#000000'])], ['customBackgroundColor' => '#ffffff']);
        $before = $block;
        $mjml = BlockProcessor::render($block, RenderContext::root(42, managedSpacing: $managed));
        if ($block !== $before) { throw new LogicException('Source block attributes were mutated.'); }
        $fixtures[$layout . ($managed ? '-managed' : '-manual')] = TemplateRenderer::renderTemplate([
            'title' => 'Group background regression', 'preview_text' => '', 'background_color' => '#ffffff',
            'body_width' => 680, 'managed_spacing' => $managed,
            'body' => $mjml,
        ]);
    }
}
echo json_encode($fixtures, JSON_THROW_ON_ERROR);
