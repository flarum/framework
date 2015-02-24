<?php  namespace Flarum\Core\Support;

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
