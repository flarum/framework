<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Channel;

use Carbon\Carbon;
use Flarum\Foundation\Config;
use Flarum\Realtime\Websocket\Concerns\Promises;
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use React\Promise\PromiseInterface;
use stdClass;

class Manager
{
    use Promises;

    private array $connections = [];
    private array $channels = [];
    private int $maxConnections;
    private bool $connectionsAllowed = true;
    private array $urls;
    private array $users = [];
    private array $userSockets = [];

    public function __construct(Settings $settings, Config $config)
    {
        $this->maxConnections = $settings->maxConnections;
        $this->urls = [
            parse_url($config->url(), PHP_URL_HOST),
            $settings->jsClientHost
        ];
    }

    public function allowsNewConnection(): bool
    {
        return $this->connectionsAllowed;
    }

    public function getUrls(): array
    {
        return $this->urls;
    }

    public function getChannels(): PromiseInterface
    {
        return $this->createFulfilledPromise(
            $this->channels
        );
    }

    public function connectionPonged(ConnectionInterface $connection): PromiseInterface
    {
        /** @phpstan-ignore-next-line */
        $connection->lastPongedAt = Carbon::now();

        return $this->updateConnectionInChannels($connection);
    }

    public function find(string $channel): ?Channel
    {
        return $this->channels[$channel] ?? null;
    }

    public function has(string $channel): bool
    {
        return isset($this->channels[$channel]);
    }

    public function subscribeToChannel(ConnectionInterface $connection, string $channel, stdClass $payload): PromiseInterface
    {
        $channel = $this->findOrCreate($channel);

        /** @phpstan-ignore-next-line */
        $this->connections[$connection->socketId] = true;

        $this->connectionsAllowed = count($this->connections) < $this->maxConnections;

        return $this->createFulfilledPromise(
            $channel->subscribe($connection, $payload)
        );
    }

    public function unsubscribeFromChannel(ConnectionInterface $connection, string $channel, stdClass $payload): PromiseInterface
    {
        if (! $this->has($channel)) {
            return $this->createFulfilledPromise(false);
        }

        $channel = $this->find($channel);

        return $this->createFulfilledPromise(
            $channel->unsubscribe($connection)
        );
    }

    public function unsubscribeFromAllChannels(ConnectionInterface $connection): PromiseInterface
    {
        // Remove connection from channels.
        $this->getChannels()->then(function (array $channels) use ($connection) {
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                $channel->unsubscribe($connection);

                if (! $channel->hasConnections()) {
                    unset($this->channels[$channel->getName()]);
                }
            }
        });

        /** @phpstan-ignore-next-line */
        unset($this->connections[$connection->socketId]);

        $this->connectionsAllowed = count($this->connections) < $this->maxConnections;

        return $this->createFulfilledPromise(true);
    }

    public function findOrCreate(string $channel): ?Channel
    {
        if (! $this->has($channel)) {
            $class = $this->getChannelClass($channel);

            $this->channels[$channel] = new $class($channel);
        }

        return $this->find($channel);
    }

    private function getChannelClass(string $channelName): string
    {
        if (Str::startsWith($channelName, 'private-')) {
            return PrivateChannel::class;
        }

        if (Str::startsWith($channelName, 'presence-')) {
            return PresenceChannel::class;
        }

        return Channel::class;
    }

    public function updateConnectionInChannels(ConnectionInterface $connection): PromiseInterface
    {
        return $this->getChannels()
            ->then(function ($channels) use ($connection) {
                /** @var Channel $channel */
                foreach ($channels as $channel) {
                    if ($channel->hasConnection($connection)) {
                        $channel->saveConnection($connection);
                    }
                }

                return true;
            });
    }

    public function userJoinedPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel, stdClass $payload): PromiseInterface
    {
        /** @phpstan-ignore-next-line */
        $this->users[$channel][$connection->socketId] = json_encode($user);
        /** @phpstan-ignore-next-line */
        $this->userSockets["{$channel}:{$user->user_id}"][] = $connection->socketId;

        return $this->createFulfilledPromise(true);
    }

    public function userLeftPresenceChannel(ConnectionInterface $connection, stdClass $user, string $channel): PromiseInterface
    {
        /** @phpstan-ignore-next-line */
        unset($this->users[$channel][$connection->socketId]);

        $deletableSocketKey = array_search(
            /** @phpstan-ignore-next-line */
            $connection->socketId,
            $this->userSockets["{$channel}:{$user->user_id}"]
        );

        if ($deletableSocketKey !== false) {
            unset($this->userSockets["{$channel}:{$user->user_id}"][$deletableSocketKey]);

            if (count($this->userSockets["{$channel}:{$user->user_id}"]) === 0) {
                unset($this->userSockets["{$channel}:{$user->user_id}"]);
            }
        }

        return $this->createFulfilledPromise(true);
    }

    public function getChannelMembers(string $channel): PromiseInterface
    {
        $members = $this->users[$channel] ?? [];

        /** @phpstan-ignore-next-line */
        $members = collect($members)->map(function ($user) {
            return json_decode($user);
        })->unique('user_id')->toArray();

        return $this->createFulfilledPromise($members);
    }

    public function getChannelsMembersCount(array $channelNames): PromiseInterface
    {
        $results = collect($channelNames)
            ->reduce(function ($results, $channel) {
                $results[$channel] = isset($this->users[$channel])
                    ? count($this->users[$channel])
                    : 0;

                return $results;
            }, []);

        return $this->createFulfilledPromise($results);
    }

    public function getMemberSockets(int|string $userId, string $channel): PromiseInterface
    {
        return $this->createFulfilledPromise($this->userSockets["$channel:$userId"] ?? []);
    }

    public function getChannelMember(ConnectionInterface $connection, string $channel): PromiseInterface
    {
        return $this->createFulfilledPromise(
            /** @phpstan-ignore-next-line */
            $this->users[$channel][$connection->socketId] ?? null
        );
    }
}
