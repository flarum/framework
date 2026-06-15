<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Message;

use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class Message
{
    public function __construct(protected stdClass $payload, protected ConnectionInterface $connection, protected Manager $manager)
    {
    }

    public function respond(): void
    {
        if (! $this->isAuthorizedClientEvent()) {
            return;
        }

        $channel = $this->manager->find($this->payload->channel);

        $channel->broadcastToEveryoneExcept(
            $this->payload,
            /** @phpstan-ignore-next-line */
            $this->connection->socketId
        );

        $this->relayIndexTyping();
    }

    /**
     * Client-originated events are only permitted under the same rules Pusher
     * enforces, so a connection cannot forge events into channels it has no
     * business broadcasting to:
     *
     *   - the event name must be prefixed `client-`;
     *   - the target must be a private/presence channel (never a public one);
     *   - the channel must already exist and the sending connection must be
     *     subscribed to it.
     *
     * Without this, any connection holding the public app key could inject forged
     * events (e.g. spoofed notifications) into another user's private channel
     * without ever authorising a subscription.
     */
    protected function isAuthorizedClientEvent(): bool
    {
        $channelName = $this->payload->channel ?? null;
        $event = $this->payload->event ?? null;

        if (! is_string($channelName) || ! is_string($event)) {
            return false;
        }

        if (! Str::startsWith($event, 'client-')) {
            return false;
        }

        if (! Str::startsWith($channelName, ['private-', 'presence-'])) {
            return false;
        }

        $channel = $this->manager->find($channelName);

        return $channel !== null && $channel->hasConnection($this->connection);
    }

    /**
     * In addition to relaying the raw typing event to the discussion's own channel,
     * feed it into the coalesced index-typing presence so the discussion list can
     * show an ambient dot. See {@link IndexTypingPresence}.
     */
    protected function relayIndexTyping(): void
    {
        if ($this->payload->event !== 'client-typing'
            || ! preg_match('/^private-typing=(\d+)$/', $this->payload->channel, $m)) {
            return;
        }

        resolve(IndexTypingPresence::class)->touch((int) $m[1]);
    }
}
