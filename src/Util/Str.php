<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Util;

class Str
{
    /**
     * Create a slug out of the given string.
     *
     * nonsafe URL characters are converted to hyphens.
     *
     * @param string $string
     * @return string
     */
    public static function slug($string)
    {
        // Regex for finding the nonsafe URL characters (many need escaping): & +$,:;=?@"#{}|^~[`%!']./()*\
        $nonsafeChars = '/[& +$,:;=?@"#{}|^~[`%!\'\]\.\/\(\)\*\\]/g';

        // Note: we trim hyphens after truncating because truncating can cause dangling hyphens.
        // Example string:                                    // " ⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
        $string = trim($string);                              // "⚡⚡ Don't forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
        $string = preg_replace('/\'/gi', '', $string);        // "⚡⚡ Dont forget: URL fragments should be i18n-friendly, hyphenated, short, and clean."
        $string = preg_replace($nonsafeChars, '-', $string);  // "⚡⚡-Dont-forget--URL-fragments-should-be-i18n-friendly--hyphenated--short--and-clean-"
        $string = preg_replace('/-{2,}/g', '-', $string);     // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-short-and-clean-"
        $string = substr($string, 0, 64);                     // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated-"
        $string = preg_replace('/^-+|-+$/gm', '', $string);   // "⚡⚡-Dont-forget-URL-fragments-should-be-i18n-friendly-hyphenated"
        $string = strtolower($string);                        // "⚡⚡-dont-forget-url-fragments-should-be-i18n-friendly-hyphenated"

        return $string ?: '-';
    }
}
