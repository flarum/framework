<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\User\User;

trait DispatchEventsTrait
{
    /**
     * Dispatch all events for an entity.
     */
    public function dispatchEventsFor($entity, User $actor = null): void
    {
        foreach ($entity->releaseEvents() as $event) {
            $event->actor = $actor;

            $this->events->dispatch($event);
        }
    }
}
