<?php

namespace RRZE\Newsletter\Mail;

defined('ABSPATH') || exit;

use DOMDocument;
use DOMElement;

/**
 * Contrast protection for late-rendered feeds in compiled email HTML.
 *
 * Resolve inline styles and the template's simple CSS selectors. Do not guess
 * colors behind images, transparency, effects or unsupported CSS. Only changed
 * feed fragments are serialized; the saved email and Outlook markup stay intact.
 */
final class Contrast
{
    private array $rules = [];
    private array $styles = [];
    private const PROPERTIES = ['color', 'background', 'background-color', 'background-image',
        'opacity', 'filter', 'mix-blend-mode', 'display', 'visibility'];

    public static function replace(string $html, array $fragments): string
    {
        if (!$fragments || !class_exists(DOMDocument::class)) {
            return strtr($html, $fragments);
        }
        $pattern = '~' . implode('|', array_map(fn ($key) => preg_quote($key, '~'), array_keys($fragments))) . '~';
        $originals = [];
        $prefix = 'rrze-contrast-' . bin2hex(random_bytes(8)) . '-';
        $analysis = preg_replace_callback($pattern, static function ($match) use (&$originals, $fragments, $prefix) {
            $index = count($originals);
            $originals[] = $fragments[$match[0]];
            return '<rrze-feed id="' . $prefix . $index . '">' . $fragments[$match[0]] . '</rrze-feed>';
        }, $html);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $analysis, LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $guard = new self();
        if (!$loaded || !$guard->readRules($document)) {
            return strtr($html, $fragments);
        }
        $output = $originals;
        foreach ($originals as $index => $original) {
            $root = $document->getElementById($prefix . $index);
            if ($root && $guard->protect($root)) {
                $output[$index] = '';
                foreach ($root->childNodes as $child) {
                    $output[$index] .= $document->saveHTML($child);
                }
            }
        }
        $index = 0;
        return preg_replace_callback($pattern, static function () use (&$index, $output) {
            return $output[$index++];
        }, $html);
    }

    private function protect(DOMElement $root): bool
    {
        $decisions = [];
        foreach ($root->getElementsByTagName('*') as $element) {
            $style = $this->style($element);
            $background = $this->background($element);
            $foreground = self::color($style['color']);
            $replacement = null;
            $hasText = false;
            foreach ($element->childNodes as $child) {
                $hasText = $hasText || ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) !== '');
            }
            if ($hasText && $background && $foreground && $foreground[3] === 1.0
                && self::ratio($foreground, $background) < 4.5) {
                $replacement = self::ratio([0, 0, 0], $background) >= self::ratio([255, 255, 255], $background)
                    ? '#000000' : '#ffffff';
            }
            $decisions[] = [$element, $style['color'], $replacement];
        }
        $changed = [];
        foreach ($decisions as [$element, $color, $replacement]) {
            if ($replacement) {
                $changed[spl_object_id($element)] = true;
            }
        }
        foreach ($decisions as [$element, $color, $replacement]) {
            // Freeze direct children too, including those without direct text:
            // correcting a parent must not change a child's original inheritance.
            if ($replacement || isset($changed[spl_object_id($element->parentNode)])) {
                $element->setAttribute('style', rtrim($element->getAttribute('style'), '; ') . ';color:' . ($replacement ?? $color) . ' !important;');
            }
        }
        return (bool) $changed;
    }

    private function background(DOMElement $element): ?array
    {
        $background = null;
        for ($node = $element; $node instanceof DOMElement; $node = $node->parentNode) {
            $style = $this->style($node);
            if (in_array(strtolower($node->tagName), ['script', 'style', 'noscript', 'svg', 'math', 'template'], true)
                || $style['display'] === 'none' || $style['visibility'] !== 'visible'
                || !is_numeric($style['opacity']) || (float) $style['opacity'] !== 1.0
                || $style['filter'] !== 'none' || $style['mix-blend-mode'] !== 'normal') {
                return null;
            }
            if ($background) {
                continue;
            }
            $color = self::color($style['background-color']);
            if ($style['background-image'] !== 'none' || !$color || ($color[3] > 0 && $color[3] < 1)) {
                return null;
            }
            if ($color[3] === 1.0) {
                $background = $color;
            }
        }
        return $background;
    }

    private function style(DOMElement $element): array
    {
        $id = spl_object_id($element);
        if (isset($this->styles[$id])) {
            return $this->styles[$id][1];
        }
        $parent = $element->parentNode instanceof DOMElement ? $this->style($element->parentNode) : [];
        $inherited = $parent['color'] ?? '#000000';
        $style = ['color' => strtolower($element->tagName) === 'a' && $element->hasAttribute('href') ? '#0000ee' : $inherited,
            'background-color' => $element->getAttribute('bgcolor') ?: 'transparent',
            'background-image' => $element->hasAttribute('background') ? 'unknown' : 'none',
            'opacity' => '1', 'filter' => 'none', 'mix-blend-mode' => 'normal',
            'display' => 'block', 'visibility' => $parent['visibility'] ?? 'visible'];
        $weights = [];
        foreach ($element->tagName === 'rrze-feed' ? [] : $this->rules as [$selector, $specificity, $declarations]) {
            if ($this->matches($element, $selector)) {
                $this->cascade($style, $weights, $declarations, $specificity);
            }
        }
        $this->cascade($style, $weights, self::declarations($element->getAttribute('style')), 1000000);
        foreach ($style as $property => $value) {
            if ($value === 'inherit' || ($value === 'unset' && in_array($property, ['color', 'visibility'], true))) {
                $style[$property] = $parent[$property] ?? ($property === 'color' ? '#000000' : 'unknown');
            }
        }
        // Retain the DOM object: PHP can otherwise reuse its object ID.
        $this->styles[$id] = [$element, $style];
        return $style;
    }

    private function cascade(array &$style, array &$weights, array $declarations, int $specificity): void
    {
        foreach ($declarations as [$property, $value, $important]) {
            $weight = [$important, $specificity];
            $values = [$property => $value];
            if ($property === 'background') {
                $values = ['background-color' => self::color($value) ? $value : 'unknown', 'background-image' => 'unknown'];
                if (self::color($value) || in_array($value, ['none', 'initial', 'unset'], true)) {
                    $values = ['background-color' => self::color($value) ? $value : 'transparent', 'background-image' => 'none'];
                }
            }
            foreach ($values as $name => $resolved) {
                if (!isset($weights[$name]) || $weight >= $weights[$name]) {
                    $weights[$name] = $weight;
                    $style[$name] = $resolved;
                }
            }
        }
    }

    private static function declarations(string $css): array
    {
        $declarations = [];
        $css = preg_replace('~/\*.*?\*/~s', '', $css);
        foreach (explode(';', $css) as $declaration) {
            $parts = explode(':', $declaration, 2);
            $name = strtolower(trim($parts[0]));
            if (count($parts) !== 2 || !in_array($name, self::PROPERTIES, true)) {
                continue;
            }
            $value = strtolower(trim($parts[1]));
            $important = (bool) preg_match('/\s*!important\s*$/', $value);
            $value = trim(preg_replace('/\s*!important\s*$/', '', $value));
            $declarations[] = [$name, $value, (int) $important];
        }
        return $declarations;
    }

    private function readRules(DOMDocument $document): bool
    {
        // External or conditional paint rules cannot be resolved on the server.
        foreach ($document->getElementsByTagName('link') as $link) {
            if (str_contains(strtolower($link->getAttribute('rel')), 'stylesheet')) {
                return false;
            }
        }
        foreach ($document->getElementsByTagName('style') as $sheet) {
            $css = preg_replace('~/\*.*?\*/~s', '', $sheet->textContent);
            if (str_contains(strtolower($css), '@import')) {
                return false;
            }
            preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $rules, PREG_SET_ORDER);
            foreach ($rules as $rule) {
                $declarations = self::declarations($rule[2]);
                if (!$declarations) {
                    continue;
                }
                if ($sheet->hasAttribute('media') && !in_array(trim($sheet->getAttribute('media')), ['', 'all'], true)) {
                    return false;
                }
                // Locate the rule in the original sheet to detect enclosing at-rules.
                $before = substr($css, 0, strpos($css, $rule[0]));
                if (substr_count($before, '{') !== substr_count($before, '}')) {
                    return false;
                }
                foreach (explode(',', trim($rule[1])) as $selector) {
                    $selector = trim($selector);
                    if (!preg_match('/^(?:[a-zA-Z][\w-]*|\*)?(?:[.#][\w-]+)*(?:\s+(?:[a-zA-Z][\w-]*|\*)?(?:[.#][\w-]+)*)*$/D', $selector) || $selector === '') {
                        return false;
                    }
                    preg_match_all('/(?:^|\s)[a-zA-Z][\w-]*/', $selector, $tags);
                    $specificity = substr_count($selector, '#') * 10000 + substr_count($selector, '.') * 100 + count($tags[0]);
                    $this->rules[] = [preg_split('/\s+/', $selector), $specificity, $declarations];
                }
            }
        }
        return true;
    }

    private function matches(DOMElement $element, array $selector): bool
    {
        $part = array_pop($selector);
        preg_match_all('/(^[\w*-]+)|([.#][\w-]+)/', $part, $tokens);
        foreach ($tokens[0] as $token) {
            if ($token[0] === '#' ? $element->getAttribute('id') !== substr($token, 1)
                : ($token[0] === '.' ? !in_array(substr($token, 1), preg_split('/\s+/', $element->getAttribute('class')), true)
                    : ($token !== '*' && strtolower($element->tagName) !== strtolower($token)))) {
                return false;
            }
        }
        if (!$selector) {
            return true;
        }
        for ($parent = $element->parentNode; $parent instanceof DOMElement; $parent = $parent->parentNode) {
            if ($this->matches($parent, $selector)) {
                return true;
            }
        }
        return false;
    }

    private static function color(string $value): ?array
    {
        $value = ['black' => '#000000', 'white' => '#ffffff', 'red' => '#ff0000', 'green' => '#008000',
            'blue' => '#0000ff', 'gray' => '#808080', 'grey' => '#808080', 'silver' => '#c0c0c0',
            'yellow' => '#ffff00', 'navy' => '#000080', 'teal' => '#008080', 'aqua' => '#00ffff',
            'lime' => '#00ff00', 'maroon' => '#800000', 'purple' => '#800080', 'fuchsia' => '#ff00ff',
            'olive' => '#808000'][$value] ?? $value;
        if ($value === 'transparent') {
            return [0, 0, 0, 0.0];
        }
        if (preg_match('/^#([a-f0-9]{3,4}|[a-f0-9]{6}|[a-f0-9]{8})$/i', $value, $match)) {
            $hex = $match[1];
            if (strlen($hex) <= 4) {
                $hex = implode('', array_map(fn ($char) => $char . $char, str_split($hex)));
            }
            return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)),
                strlen($hex) === 8 ? hexdec(substr($hex, 6, 2)) / 255.0 : 1.0];
        }
        if (preg_match('/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)(?:\s*,\s*([\d.]+))?\s*\)$/', $value, $match)) {
            $color = [(float) $match[1], (float) $match[2], (float) $match[3], (float) ($match[4] ?? 1)];
            return max(array_slice($color, 0, 3)) <= 255 && $color[3] <= 1 ? $color : null;
        }
        return null;
    }

    private static function ratio(array $first, array $second): float
    {
        $luminance = static function ($rgb) {
            $result = 0;
            foreach ([0.2126, 0.7152, 0.0722] as $index => $weight) {
                $channel = $rgb[$index] / 255;
                $result += $weight * ($channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4);
            }
            return $result;
        };
        $a = $luminance($first);
        $b = $luminance($second);
        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }
}
