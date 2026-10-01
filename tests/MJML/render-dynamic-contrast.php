<?php

declare(strict_types=1);

use RRZE\Newsletter\Mail\Contrast;
use RRZE\Newsletter\MJML\TemplateRenderer;

require __DIR__ . '/bootstrap.php';

$input = stream_get_contents(STDIN);
if ($input !== '') {
    $input = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
    echo Contrast::replace($input['html'], $input['fragments']);
    exit;
}

echo json_encode([
    'mjml' => TemplateRenderer::renderTemplate([
        'title' => 'Dynamic contrast regression', 'preview_text' => '', 'background_color' => '#ffffff',
        'body_width' => 680, 'link_color' => 'inherit', 'link_text_decoration' => 'underline', 'managed_spacing' => true,
        'body' => '<mj-section background-color="#ffffff"><mj-column><mj-text font-family="Arial" color="#000000">RSS_BLOCK_light</mj-text></mj-column></mj-section>'
            . '<mj-section background-color="#102030"><mj-column><mj-text font-family="Arial" color="#000000">ICS_BLOCK_dark</mj-text></mj-column></mj-section>',
    ]),
    'fragments' => [
        'RSS_BLOCK_light' => '<div class="rrze-newsletter-rss"><h3 style="color:#fff"><a href="https://example.test/news">Grüße aus dem RSS-Feed</a></h3><p style="color:rgb(255,255,255)">Nested feed text</p><p style="color:#123456">Already readable</p></div>',
        'ICS_BLOCK_dark' => '<div class="rrze-newsletter-ics"><h3 style="color:#000">Calendar event</h3><p style="color:black">Description <span style="background:white"><b>Light background</b></span></p><p style="color:#000"><a href="https://example.test/event">Event link</a></p></div>',
    ],
], JSON_THROW_ON_ERROR);
