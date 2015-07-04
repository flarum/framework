<?php namespace Flarum\Core\Support;

trait EventGenerator
{
    /**
     * @var array
     */
    protected $pendingEvents = [];

    /**
     * Raise a new event.
     *
     * @param mixed $event
     */
    public function raise($event)
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * Return and reset all pending events.
     *
     * @return array
     */
    public function releaseEvents()
    {
        $events = $this->pendingEvents;

        $this->pendingEvents = [];

        return $events;
    }
}
