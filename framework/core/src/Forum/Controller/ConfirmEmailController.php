<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\SessionAccessToken;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Command\ConfirmEmail;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class ConfirmEmailController implements RequestHandlerInterface
{
    public function __construct(
        protected Dispatcher $bus,
        protected UrlGenerator $url,
        protected SessionAuthenticator $authenticator
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $user = $this->bus->dispatch(
            new ConfirmEmail($token)
        );

        $session = $request->getAttribute('session');
        $token = SessionAccessToken::generate($user->id);
        $this->authenticator->logIn($session, $token);

        return new RedirectResponse($this->url->to('forum')->base());
    }
}
