<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Discussion\Discussion;
use Flarum\Queue\AbstractJob;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Pusher\Pusher;

abstract class Job extends AbstractJob
{
    /**
     * Connections on each connected user's channel, by user ID, from the last
     * connectedUsers() call; absent where the server didn't say.
     *
     * @var array<int, int>
     */
    protected array $subscriptionCounts = [];

    protected function pusher(): Pusher
    {
        return resolve(Pusher::class);
    }

    protected function visibleTo(Discussion $model, ?User $user = null): bool
    {
        return $model->query()
            ->where($model->getKeyName(), $model->getKey())
            ->whereVisibleTo($user ?? new Guest)
            ->exists();
    }

    /**
     * @param Discussion|null $visible
     * @return Collection&iterable<User>
     * @throws \Pusher\PusherException
     */
    protected function connectedUsers(?Discussion $visible = null): Collection
    {
        $response = $this->pusher()->getChannels([
            'filter_by_prefix' => 'private-user=',
            'info' => 'subscription_count',
        ]);

        $users = Collection::make();

        /** @phpstan-ignore-next-line */
        if (! $response) {
            return $users;
        }

        $this->subscriptionCounts = [];

        foreach ($response->channels as $name => $channel) {
            $id = Str::after($name, 'private-user=');
            $users->put($name, $id);

            if (isset($channel->subscription_count)) {
                $this->subscriptionCounts[(int) $id] = (int) $channel->subscription_count;
            }
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = User::query()->find($users->unique()->values());

        if ($visible) {
            /** @var Collection&iterable<User> */
            return $users->filter(function (User $user) use ($visible) {
                return $this->visibleTo($visible, $user);
            })->values();
        }

        /** @var Collection&iterable<User> */
        return $users->values();
    }
}
