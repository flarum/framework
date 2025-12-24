<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Message;

use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use stdClass;

class ProtocolMessage extends Message
{
    public function respond(): void
    {
        $eventName = Str::camel(Str::after($this->payload->event, ':'));

        if (method_exists($this, $eventName) && $eventName !== 'respond') {
            call_user_func([$this, $eventName], $this->connection, $this->payload->data ?? new stdClass());
        }
    }

    protected function ping(ConnectionInterface $connection): void
    {
        $this->manager
            ->connectionPonged($connection)
            ->then(function () use ($connection) {
                $connection->send(json_encode(['event' => 'pusher:pong']));
            });
    }

    protected function subscribe(ConnectionInterface $connection, stdClass $payload): void
    {
        $this->manager->subscribeToChannel($connection, $payload->channel, $payload);
    }

    protected function unsubscribe(ConnectionInterface $connection, stdClass $payload): void
    {
        $this->manager->unsubscribeFromChannel($connection, $payload->channel, $payload);
    }
}
