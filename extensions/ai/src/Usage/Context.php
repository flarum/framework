<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Flarum\User\User;

/**
 * Who and what an AI call is for.
 *
 * @internal
 */
class Context
{
    public function __construct(
        public readonly string $feature,
        /** Null when the call is the forum's own, from `asSystem()`. */
        public readonly ?User $actor
    ) {
    }

    /**
     * How the ledger records who the call was for.
     */
    public function actorKind(): string
    {
        return match (true) {
            $this->actor === null => 'system',
            $this->actor->isGuest() => 'guest',
            default => 'member',
        };
    }

    public function userId(): ?int
    {
        return $this->actor !== null && ! $this->actor->isGuest() ? $this->actor->id : null;
    }
}
