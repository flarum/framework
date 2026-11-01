<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

/**
 * Turns rendered post HTML into plain text a model can read: paragraph breaks
 * kept, links written as `text (url)`, everything else stripped.
 */
class HtmlToText
{
    public static function convert(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        // Code blocks keep their whitespace; protect it from the collapsing below.
        $blocks = [];
        $html = (string) preg_replace_callback('#<pre\b[^>]*>(.*?)</pre>#is', function (array $match) use (&$blocks) {
            $blocks[] = html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return "\n\n\u{E000}".(count($blocks) - 1)."\u{E000}\n\n";
        }, $html);

        $html = (string) preg_replace_callback('#<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)</a>#is', function (array $match) {
            $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(strip_tags($match[3]));

            if ($text === '' || $text === $url) {
                return $url;
            }

            return "$text ($url)";
        }, $html);

        $html = (string) preg_replace('#<img\b[^>]*\balt\s*=\s*(["\'])(.*?)\1[^>]*>#is', '$2', $html);
        $html = (string) preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = (string) preg_replace('#<li\b[^>]*>#i', "\n- ", $html);
        $html = (string) preg_replace('#</?(p|div|blockquote|ul|ol|h[1-6]|table|tr|details|summary)\b[^>]*>#i', "\n\n", $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = (string) preg_replace('/ *\n */', "\n", $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);

        $text = (string) preg_replace_callback("/\u{E000}(\d+)\u{E000}/u", fn (array $match) => $blocks[(int) $match[1]] ?? '', $text);

        return trim($text);
    }
}
