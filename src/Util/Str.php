<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @deprecated 0.1.0 Superseded by more complete Illuminate\Support namespace
 */
namespace Flarum\Util;

/**
 * Class Str
 * @deprecated 0.1.0 Superseded by more complete Illuminate\Support\Str class
 * @package Flarum\Util
 */
class Str
{
    /**
     * Create a slug out of the given string.
     *
     * Non-alphanumeric characters are converted to hyphens.
     *
     * @deprecated  0.1.0 Superseded by more complete Illuminate\Support\Str's slug function
     * @param string $str
     * @return string
     */
    public static function slug($str)
    {
        $str = strtolower($str);
        $str = preg_replace('/[^a-z0-9]/i', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = preg_replace('/-$|^-/', '', $str);

        return $str;
    }
}
