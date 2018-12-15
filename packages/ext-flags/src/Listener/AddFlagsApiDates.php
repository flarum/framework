<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Event\ConfigureModelDates;
use Flarum\User\User;

class AddFlagsApiDates
{
    public function handle(ConfigureModelDates $event)
    {
        if ($event->isModel(User::class)) {
            $event->dates[] = 'read_flags_at';
        }
    }
}
