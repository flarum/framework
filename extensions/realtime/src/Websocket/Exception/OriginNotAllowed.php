<?php


namespace Flarum\Realtime\Websocket\Exception;

class OriginNotAllowed extends WebsocketException
{
    public function __construct()
    {
        $this->trigger("The origin is not allowed.", 4009);
    }
}
