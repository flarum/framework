<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\ConfigureAi;
use Flarum\Settings\Event\Saved;

class ReconfigureAi
{
    public function __construct(
        protected ConfigureAi $configure
    ) {
    }

    public function handle(Saved $event): void
    {
        foreach (array_keys($event->settings) as $key) {
            if (str_starts_with($key, 'flarum-ai.')) {
                ($this->configure)();

                return;
            }
        }
    }
}
