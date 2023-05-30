<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Post\Event\Deleted;

class DeleteFlags
{
    public function handle(Deleted $event): void
    {
        $event->post->flags()->delete();
    }
}
