<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Console;

use Illuminate\Console\Scheduling\Event;

class WeeklySchedule
{
    /**
     * @param string|null $seed Given one, such as the forum's URL, the task runs
     *                          on a day and at a time of its own rather than at
     *                          the start of the week, so that a task which calls
     *                          an outside service isn't run by every forum at once.
     */
    public function __construct(
        protected ?string $seed = null
    ) {
    }

    public function __invoke(Event $event): void
    {
        if ($this->seed === null) {
            $event->weekly()->withoutOverlapping();

            return;
        }

        // The minute of the week to run at, from Sunday 00:00 to Saturday 23:59.
        $minute = crc32($this->seed) % (7 * 24 * 60);

        $event->weeklyOn(intdiv($minute, 24 * 60), sprintf('%02d:%02d', intdiv($minute, 60) % 24, $minute % 60))
            ->withoutOverlapping();
    }
}
