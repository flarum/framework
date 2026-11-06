<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\Models\ModelHealth;
use Flarum\Settings\Event\Saved;

/**
 * Once the admin has chosen other models or connections, stops warning about
 * the ones no feature uses any more.
 *
 * @internal
 */
class PruneModelIssues
{
    public function __construct(
        protected ModelHealth $health
    ) {
    }

    public function handle(Saved $event): void
    {
        foreach (array_keys($event->settings) as $key) {
            if (str_starts_with($key, 'flarum-ai.') && $key !== ModelHealth::SETTING) {
                $this->health->prune();

                return;
            }
        }
    }
}
