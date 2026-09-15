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
        throw new \LogicException('Fixtures must not perform metadata or network lookups.');
    }
}
