<?php


namespace Flarum\Realtime\Websocket\Exception;

class AppKeyNotAllowed extends WebsocketException
{
    public function __construct($key)
    {
        $this->trigger("App key not valid $key.", 4001);
    }
}
