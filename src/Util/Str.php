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
     * Non-alphanumeric characters are converted to hyphens.
     *
     * @param string $str
     * @return string
     */
    public static function slug($str)
    {
        $str = strtolower($str);
        $str = preg_replace('/á|à|ả|ã|ạ|ă|ắ|ằ|ẳ|ẵ|ặ|â|ấ|ầ|ẩ|ẫ|ậ/i', 'a', $str);
        $str = preg_replace('/đ/i', 'd', $str);
        $str = preg_replace('/é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ/i', 'e', $str);
        $str = preg_replace('/í|ì|ỉ|ĩ|ị/i', 'i', $str);
        $str = preg_replace('/ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ/i', 'o', $str);
        $str = preg_replace('/ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự/i', 'u', $str);
        $str = preg_replace('/ý|ỳ|ỷ|ỹ|ỵ/i', 'y', $str);
        $str = preg_replace('/[^a-z0-9]/i', '-', $str);
        $str = preg_replace('/-+/', '-', $str);
        $str = preg_replace('/-$|^-/', '', $str);

        return $str ?: '-';
    }
}
