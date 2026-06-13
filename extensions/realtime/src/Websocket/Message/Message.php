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
use Ratchet\ConnectionInterface;
use stdClass;

class Message
{
    public function __construct(protected stdClass $payload, protected ConnectionInterface $connection, protected Manager $manager)
    {
    }

    public function respond(): void
    {
        $channel = $this->manager->find($this->payload->channel);

        optional($channel)
            ->broadcastToEveryoneExcept(
                $this->payload,
                /** @phpstan-ignore-next-line */
                $this->connection->socketId
            );

        $this->relayIndexTyping();
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
