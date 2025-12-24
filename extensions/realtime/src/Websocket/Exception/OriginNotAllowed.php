<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Exception;

class OriginNotAllowed extends WebsocketException
{
    public function __construct()
    {
        $this->trigger('The origin is not allowed.', 4009);
    }
}
