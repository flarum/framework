<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class GlobalLogOutController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $events,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer,
        protected UrlGenerator $url
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();
        $actor->emailTokens()->delete();
        $actor->passwordTokens()->delete();

        $this->events->dispatch(new LoggedOut($actor, true));

        return $this->rememberer->forget(new EmptyResponse());
    }
}
