<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\Models\ModelHealth;
use Laravel\Ai\Events\AgentFailed;
use Throwable;

/**
 * Records a model a call found gone, from any extension or none, so the admin
 * is told. Calls that fell back to another model have already recorded it.
 *
 * @internal
 */
class RecordMissingModel
{
    public function __construct(
        protected ModelHealth $health
    ) {
    }

    public function handle(AgentFailed $event): void
    {
        if (! ModelHealth::isMissingModel($event->exception)) {
            return;
        }

        try {
            $this->health->recordFailure($event->prompt->provider->name(), $event->prompt->model);
        } catch (Throwable) {
            // The call has failed already; its own error is the one to report.
        }
    }
}
