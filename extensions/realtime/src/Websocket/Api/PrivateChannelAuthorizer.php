<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\User\User;

/**
 * Private channels registered by other extensions through
 * {@link \Flarum\Realtime\Extend\Realtime::authorizePrivateChannel()}.
 *
 * Unlike presence guards, which can only narrow a channel realtime already
 * defines, these grant access to a channel realtime knows nothing about. So
 * they fail closed: a subscription is authorized only when at least one guard
 * is registered for the subject and every guard returns exactly `true`.
 */
class PrivateChannelAuthorizer
{
    /** @var array<string, callable[]> */
    private array $guards = [];

    public function add(string $subject, callable $callback): void
    {
        $this->guards[$subject][] = $callback;
    }

    public function has(string $subject): bool
    {
        return ! empty($this->guards[$subject]);
    }

    public function authorize(string $subject, User $actor, int $id): bool
    {
        if (! $this->has($subject)) {
            return false;
        }

        foreach ($this->guards[$subject] as $guard) {
            if ($guard($actor, $id) !== true) {
                return false;
            }
        }

        return true;
    }
}
