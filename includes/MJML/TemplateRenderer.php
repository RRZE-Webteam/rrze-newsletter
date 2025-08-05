<?php

namespace RRZE\Newsletter\MJML;

defined('ABSPATH') || exit;

use RRZE\Newsletter\Templates;

/**
 * Class TemplateRenderer
 * 
 * Renders MJML templates with provided data.
 * 
 * @package RRZE\Newsletter\MJML
 */
class TemplateRenderer
{
    /**
     * Render the MJML template with the provided data
     * 
     * @param array $data Data to be used in the template
     * @return string Rendered MJML template
     */
    public static function renderTemplate(array $data): string
    {
        $tpl = preg_replace('/\s+/', ' ', Templates::getContent('newsletter.mjml', $data));
        // Remove a:hover styles, as they are not supported by MJML.
        $tpl = preg_replace('/a:hover\s*{[^}]*}/i', '', $tpl);
        return str_replace(PHP_EOL, '', $tpl);
    }
}