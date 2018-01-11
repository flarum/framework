<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Flarum\User\AuthToken;
use Flarum\User\EmailToken;
use Flarum\User\PasswordToken;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class CollectGarbage implements MiddlewareInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $this->collectGarbageSometimes();

        return $out ? $out($request, $response) : $response;
    }

    private function collectGarbageSometimes()
    {
        // In order to save performance, we only execute this query
        // from time to time (with 2% chance).
        if (! $this->hit()) {
            return;
        }

        AccessToken::whereRaw('last_activity <= ? - lifetime', [time()])->delete();

        $earliestToKeep = date('Y-m-d H:i:s', time() - 24 * 60 * 60);

        EmailToken::where('created_at', '<=', $earliestToKeep)->delete();
        PasswordToken::where('created_at', '<=', $earliestToKeep)->delete();
        AuthToken::where('created_at', '<=', $earliestToKeep)->delete();
    }

    private function hit()
    {
        return mt_rand(1, 100) <= 2;
    }
}
