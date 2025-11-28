<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Message;

use Flarum\Realtime\Websocket\Channel\Manager;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;

class Factory
{
    public static function forMessage(
        MessageInterface $message,
        ConnectionInterface $connection,
        Manager $manager
    ): Message {
        $payload = json_decode($message->getPayload());

        return Str::startsWith($payload->event, 'pusher:')
            ? new ProtocolMessage($payload, $connection, $manager)
            : new Message($payload, $connection, $manager);
    }
}
