<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

trait EventGeneratorTrait
{
    protected array $pendingEvents = [];

    /**
     * Raise a new event.
     */
    public function raise($event): void
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * Return and reset all pending events.
     */
    public function releaseEvents(): array
    {
        $events = $this->pendingEvents;

        $this->pendingEvents = [];

        return $events;
    }
}
