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

use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Contracts\Session\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthenticateWithSession implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $request->getAttribute('session');

        $actor = $this->getActor($session);

        $actor->setSession($session);

        $request = $request->withAttribute('actor', $actor);

        return $handler->handle($request);
    }

    private function getActor(Session $session)
    {
        $actor = User::find($session->get('user_id')) ?: new Guest;

        if ($actor->exists) {
            $actor->updateLastSeen()->save();
        }

        return $actor;
    }
}
