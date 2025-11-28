<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

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
