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
use Laravel\Ai\Events\Classifying;
use Laravel\Ai\Events\GeneratingAudio;
use Laravel\Ai\Events\GeneratingEmbeddings;
use Laravel\Ai\Events\GeneratingImage;
use Laravel\Ai\Events\GeneratingTranscription;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\Reranking;

/**
 * Runs just before every laravel/ai call, of every kind, from any extension or
 * none. Throwing here stops the call before it reaches the provider.
 *
 * @internal
 */
class EnforceLimits
{
    public function __construct(
        protected Limits $limits,
        protected Attribution $attribution
    ) {
    }

    public function handle(PromptingAgent|GeneratingEmbeddings|GeneratingImage|GeneratingAudio|GeneratingTranscription|Reranking|Classifying $event): void
    {
        [$provider, $agent] = $event instanceof PromptingAgent
            ? [$event->prompt->provider(), $event->prompt->agent]
            : [$event->provider, null];

        $this->limits->check($provider->name(), $this->attribution->for($agent));
    }
}
