<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Channel;

use Flarum\Realtime\Websocket\Exception\InvalidSignature;
use Flarum\Realtime\Websocket\Settings;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class Channel
{
    protected array $connections = [];
    protected ?Manager $manager;
    protected Settings $settings;

    public function __construct(protected string $name)
    {
        $this->manager = resolve(Manager::class);
        $this->settings = resolve(Settings::class);
    }

    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->saveConnection($connection);

        $connection->send(json_encode([
            'event' => 'pusher_internal:subscription_succeeded',
            'channel' => $this->getName(),
        ]));

        return true;
    }

    public function unsubscribe(ConnectionInterface $connection): bool
    {
        if (! $this->hasConnection($connection)) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        unset($this->connections[$connection->socketId]);

        return true;
    }

    public function hasConnections(): bool
    {
        return count($this->connections) > 0;
    }

    public function hasConnection(ConnectionInterface $connection): bool
    {
        /** @phpstan-ignore-next-line */
        return isset($this->connections[$connection->socketId]);
    }

    public function saveConnection(ConnectionInterface $connection): void
    {
        /** @phpstan-ignore-next-line */
        $this->connections[$connection->socketId] = $connection;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function broadcast(stdClass $payload): bool
    {
        collect($this->connections)
            ->each->send(json_encode($payload));

        return true;
    }

    public function broadcastToEveryoneExcept(stdClass $payload, ?string $socketId): bool
    {
        if (! $socketId) {
            return $this->broadcast($payload);
        }

        collect($this->connections)
            ->except($socketId)
            ->each->send(json_encode($payload));

        return true;
    }

    protected function verifySignature(ConnectionInterface $connection, stdClass $payload): void
    {
        /** @phpstan-ignore-next-line */
        $signature = "{$connection->socketId}:{$this->getName()}";

        if (isset($payload->channel_data)) {
            $signature .= ":{$payload->channel_data}";
        }

        $hash = hash_hmac('sha256', $signature, $this->settings->appSecret);

        if (! hash_equals(
            $hash,
            Str::after($payload->auth, ':')
        )
        ) {
            throw new InvalidSignature;
        }
    }
}
