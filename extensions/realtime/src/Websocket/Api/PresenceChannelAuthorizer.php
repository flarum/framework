<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Api;

use Flarum\User\User;

class PresenceChannelAuthorizer
{
    /** @var array<string, callable[]> */
    private array $guards = [];

    public function add(string $channel, callable $callback): void
    {
        $this->guards[$channel][] = $callback;
    }

    /**
     * Run all registered guards for the given channel.
     * Returns false if any guard explicitly returns false; true otherwise.
     */
    public function authorize(string $channel, User $actor): bool
    {
        foreach ($this->guards[$channel] ?? [] as $guard) {
            if ($guard($actor, $channel) === false) {
                return false;
            }
        }

        return true;
    }
}
