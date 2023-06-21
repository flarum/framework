<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

trait DispatchEventsTrait
{
    /**
     * Dispatch all events for an entity.
     */
    public function dispatchEventsFor(mixed $entity, User $actor = null): void
    {
        if (! method_exists($entity, 'releaseEvents')) {
            throw new \InvalidArgumentException(
                'The entity must use the EventGeneratorTrait trait in order to dispatch events.'
            );
        }

        foreach ($entity->releaseEvents() as $event) {
            $event->actor = $actor;

            $this->events->dispatch($event);
        }
    }
}
