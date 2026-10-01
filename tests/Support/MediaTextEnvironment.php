<?php

declare(strict_types=1);

namespace RRZE\Newsletter\MJML\BlockProcessor;

use RRZE\Newsletter\Tests\Support\ApplicationEnvironment as App;

function get_post_thumbnail_id(int $postId): int { return (int) (App::$meta[$postId]['_thumbnail_id'] ?? 0); }
function __(string $text, string $domain = 'default'): string { return $text; }
function esc_html(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); }
function esc_url(string $url, array $protocols = ['http', 'https']): string
{
    // A narrow protocol boundary, not a replacement for WordPress URL sanitation.
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return $scheme && !in_array(strtolower($scheme), $protocols, true) ? '' : esc_html($url);
}
