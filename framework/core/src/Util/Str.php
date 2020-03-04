<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Util;

use Illuminate\Support\Str as Laravel;

class Str
{
    /**
     * @deprecated
     */
    public static function slug($str)
    {
        return Laravel::slug($str);
    }
}
