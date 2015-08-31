<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Support;

use Flarum\Support\ServiceProvider;
use Illuminate\Events\Dispatcher;

class Extension extends ServiceProvider
{
    public function listen(Dispatcher $events)
    {
    }
}
