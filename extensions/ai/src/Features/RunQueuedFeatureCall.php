<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use Flarum\Ai\Ai;
use Flarum\Queue\AbstractJob;
use Flarum\User\Guest;
use Flarum\User\User;
use Laravel\Ai\Contracts\Agent;

/**
 * A feature call made on the queue, by {@see FeatureCall::queue()}. Made again
 * in the worker as the same feature, for the same member, so it's checked and
 * counted there like any other. A member deleted before the job runs takes it
 * with them.
 *
 * @internal
 */
class RunQueuedFeatureCall extends AbstractJob
{
    /**
     * @param User|null $actor The member or guest it's for; null for the forum itself.
     * @param array<int, mixed> $attachments
     */
    public function __construct(
        public string $feature,
        public ?User $actor,
        public Agent $agent,
        public string $prompt,
        public array $attachments = [],
        // A guest isn't a stored model, so it travels as a flag.
        public bool $guest = false
    ) {
        parent::__construct();

        if ($actor?->isGuest()) {
            $this->actor = null;
            $this->guest = true;
        }
    }

    public function handle(Ai $ai): void
    {
        $call = $ai->feature($this->feature);

        $call = match (true) {
            $this->guest => $call->as(new Guest()),
            $this->actor !== null => $call->as($this->actor),
            default => $call->asSystem(),
        };

        $call->prompt($this->agent, $this->prompt, $this->attachments);
    }
}
