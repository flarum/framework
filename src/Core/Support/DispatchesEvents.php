<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Support;

trait DispatchesEvents
{
    /**
     * Dispatch all events for an entity.
     *
     * @param object $entity
     */
    public function dispatchEventsFor($entity)
    {
        foreach ($entity->releaseEvents() as $event) {
            event($event);
        }
    }
}
