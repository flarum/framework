<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core;

use Flarum\Core\Support\Locked;
use Flarum\Core;

class Forum extends Model
{
    use Locked;

    public function getTitleAttribute()
    {
        return Core::config('forum_title');
    }
}
