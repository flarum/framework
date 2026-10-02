<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Post\Event\Deleting;

class DeleteFlags
{
    public function handle(Deleting $event): void
    {
        $event->post->flags->each->delete();
    }
}
