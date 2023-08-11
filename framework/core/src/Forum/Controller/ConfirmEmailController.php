<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\SessionAccessToken;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Command\ConfirmEmail;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;

class ConfirmEmailController extends AbstractController
{
    public function __construct(
        protected Dispatcher $bus,
        protected UrlGenerator $url,
        protected SessionAuthenticator $authenticator
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
    {
        $token = $request->query('token');

        $user = $this->bus->dispatch(
            new ConfirmEmail($token)
        );

        $session = $request->attributes->get('session');
        $token = SessionAccessToken::generate($user->id);
        $this->authenticator->logIn($session, $token);

        return new RedirectResponse($this->url->to('forum')->base());
    }
}
