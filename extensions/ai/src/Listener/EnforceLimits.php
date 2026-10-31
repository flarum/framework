<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Listener;

use Flarum\Ai\Usage\Attribution;
use Flarum\Ai\Usage\Limits;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\PromptingAgent;

/**
 * Runs just before every laravel/ai call, from any extension or none.
 * Throwing here stops the call before it reaches the provider.
 */
class EnforceLimits
{
    public function __construct(
        protected Limits $limits,
        protected Attribution $attribution
    ) {
    }

    public function handle(PromptingAgent|GeneratingEmbeddings $event): void
    {
        $provider = $event instanceof PromptingAgent ? $event->prompt->provider() : $event->provider;

        $this->limits->check($provider->name(), $this->attribution->feature(), $this->attribution->actor());
    }
}
