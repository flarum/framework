<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Exception;

class AppKeyNotAllowed extends WebsocketException
{
    public function __construct(string $key)
    {
        $this->trigger("App key not valid $key.", 4001);
    }
}
