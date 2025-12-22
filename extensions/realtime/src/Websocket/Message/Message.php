<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Message;

use Flarum\Realtime\Websocket\Channel\Manager;
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
    }
}
