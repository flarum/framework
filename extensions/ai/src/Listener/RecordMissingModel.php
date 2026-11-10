<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\Models\ModelHealth;
use Flarum\Ai\Usage\Attribution;
use Laravel\Ai\Events\AgentFailed;
use Throwable;

/**
 * Records a model a feature's call found gone, so the admin is told. Calls
 * that fell back to another model have already recorded it. A call made
 * outside any feature isn't recorded: the admin can't choose its model.
 *
 * @internal
 */
class RecordMissingModel
{
    public function __construct(
        protected ModelHealth $health,
        protected Attribution $attribution
    ) {
    }

    public function handle(AgentFailed $event): void
    {
        if ($this->attribution->for($event->prompt->agent) === null
            || ! ModelHealth::isMissingModel($event->exception, $event->prompt->model)) {
            return;
        }

        try {
            $this->health->recordFailure($event->prompt->provider->name(), $event->prompt->model);
        } catch (Throwable) {
            // The call has failed already; its own error is the one to report.
        }
    }
}
