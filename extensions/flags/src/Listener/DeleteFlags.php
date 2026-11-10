<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Listener;

use Flarum\Flags\Event\Cleared;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Deleting;
use Illuminate\Contracts\Events\Dispatcher;

class DeleteFlags
{
    /**
     * Posts being deleted that have flags. The database removes their flags
     * along with them, so by Deleted there's no telling they had any.
     *
     * @var array<int, true>
     */
    protected array $flagged = [];

    public function __construct(
        protected Dispatcher $events
    ) {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Deleting::class, [$this, 'deleting']);
        $events->listen(Deleted::class, [$this, 'deleted']);
    }

    public function deleting(Deleting $event): void
    {
        if ($event->post->flags()->exists()) {
            $this->flagged[$event->post->id] = true;
        }
    }

    public function deleted(Deleted $event): void
    {
        $event->post->flags()->delete();

        if (isset($this->flagged[$event->post->id])) {
            unset($this->flagged[$event->post->id]);

            $this->events->dispatch(new Cleared($event->post, $event->actor));
        }
    }
}
