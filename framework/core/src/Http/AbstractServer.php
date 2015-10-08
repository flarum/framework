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

use Flarum\Foundation\Application;
use Zend\Diactoros\Server;
use Flarum\Foundation\AbstractServer as BaseAbstractServer;
use Zend\Stratigility\MiddlewareInterface;

abstract class AbstractServer extends BaseAbstractServer
{
    public function listen()
    {
        $app = $this->getApp();

        $server = Server::createServer(
            $this->getMiddleware($app),
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE,
            $_FILES
        );

        $server->listen();
    }

    /**
     * @param Application $app
     * @return MiddlewareInterface
     */
    abstract protected function getMiddleware(Application $app);
}
