<?php

namespace RRZE\Newsletter;

defined('ABSPATH') || exit;

/**
 * [Template description]
 */
class Templates
{
    /**
     * [getContent description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @param  bool   $file     [description]
     * @return string           [description]
     */
    public static function getContent(string $template = '', array $data = []): string
    {
        return self::parseContent($template, $data);
    }

    /**
     * [parseContent description]
     * @param  string $template [description]
     * @param  array  $data     [description]
     * @return string           [description]
     */
    protected static function parseContent(string $template, array $data): string
    {
        $content = self::getTemplate($template);
        if (empty($content)) {
            return '';
        }
        if (empty($data)) {
            return $content;
        }
        $parser = new Parser();
        return $parser->parse($content, $data);
    }

    /**
     * [getTemplate description]
     * @param  string $template [description]
     * @return string           [description]
     */
    protected static function getTemplate(string $template): string
    {
        $content = '';
        $templateFile = sprintf(
            '%1$sincludes/templates/%2$s',
            plugin()->getDirectory(),
            $template
        );
        if (is_readable($templateFile)) {
            ob_start();
            include($templateFile);
            $content = ob_get_contents();
            @ob_end_clean();
        }
        return $content;
    }
}
