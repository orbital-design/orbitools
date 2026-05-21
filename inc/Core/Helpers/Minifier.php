<?php

namespace Orbitools\Core\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lightweight minifier for inline CSS and JS.
 *
 * Not a full minifier — just strips comments, collapses whitespace,
 * and tightens punctuation so inline output is compact.
 */
class Minifier
{
    /**
     * Minify a CSS string.
     *
     * @param string $css Raw CSS.
     * @return string Minified CSS.
     */
    public static function css(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);
        // Collapse whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        // Remove spaces around { } : ; ,
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        // Remove trailing semicolons before closing brace
        $css = str_replace(';}', '}', $css);

        return trim($css);
    }

    /**
     * Minify a JS string.
     *
     * Strips full-line comments and collapses whitespace.
     * Intentionally conservative — does not strip inline comments
     * or rewrite operators to avoid breaking code.
     *
     * @param string $js Raw JS.
     * @return string Minified JS.
     */
    public static function js(string $js): string
    {
        // Remove multi-line comments
        $js = preg_replace('!/\*.*?\*/!s', '', $js);
        // Remove single-line comments that occupy the entire line
        $js = preg_replace('#^\s*//.*$#m', '', $js);
        // Collapse whitespace
        $js = preg_replace('/\s+/', ' ', $js);

        return trim($js);
    }
}
