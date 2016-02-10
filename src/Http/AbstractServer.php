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

use Flarum\Core\AuthToken;
use Flarum\Core\EmailToken;
use Flarum\Core\PasswordToken;
use Flarum\Foundation\AbstractServer as BaseAbstractServer;
use Flarum\Foundation\Application;
use Zend\Diactoros\Server;
use Zend\Stratigility\MiddlewareInterface;

abstract class AbstractServer extends BaseAbstractServer
{
    public function listen()
    {
        $app = $this->getApp();

        $this->collectGarbage($app);

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

    private function collectGarbage()
    {
        if ($this->hitsLottery()) {
            AccessToken::whereRaw('last_activity <= ? - lifetime', [time()])->delete();

            $earliestToKeep = date('Y-m-d H:i:s', time() - 24 * 60 * 60);

            EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
            PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
            AuthToken::where('created_at', '<=', $earliestToKeep)->delete();
        }
    }

    private function hitsLottery()
    {
        return mt_rand(1, 100) <= 2;
    }
}
