<?php

namespace Flarum\Realtime\Websocket\Channel;

use Ratchet\ConnectionInterface;
use stdClass;

class PrivateChannel extends Channel
{
    public function subscribe(ConnectionInterface $connection, stdClass $payload): bool
    {
        $this->verifySignature($connection, $payload);

        return parent::subscribe($connection, $payload);
    }
}
