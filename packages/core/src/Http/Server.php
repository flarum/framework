<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Server as DiactorosServer;

class Server
{
    protected $requestHandler;

    public function __construct(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    public function listen()
    {
        DiactorosServer::createServer(
            [$this->requestHandler, 'handle'],
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        )->listen();
    }
}
