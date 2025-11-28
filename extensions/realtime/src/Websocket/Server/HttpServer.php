<?php

namespace Flarum\Realtime\Websocket\Server;

use Ratchet\Http\HttpServerInterface;

class HttpServer extends \Ratchet\Http\HttpServer
{
    /**
     * Create a new server instance.
     *
     * @param  \Ratchet\Http\HttpServerInterface  $component
     * @param  int  $maxRequestSize
     * @return void
     */
    public function __construct(HttpServerInterface $component, int $maxRequestSize = 4096)
    {
        parent::__construct($component);

        $this->_reqParser->maxSize = $maxRequestSize;
    }
}
