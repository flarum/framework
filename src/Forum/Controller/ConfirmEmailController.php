<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\Application;
use Flarum\Http\SessionAuthenticator;
use Flarum\User\Command\ConfirmEmail;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\RedirectResponse;

class ConfirmEmailController implements RequestHandlerInterface
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @param Dispatcher $bus
     * @param Application $app
     * @param SessionAuthenticator $authenticator
     */
    public function __construct(Dispatcher $bus, Application $app, SessionAuthenticator $authenticator)
    {
        $this->bus = $bus;
        $this->app = $app;
        $this->authenticator = $authenticator;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     */
    public function handle(Request $request): ResponseInterface
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        $user = $this->bus->dispatch(
            new ConfirmEmail($token)
        );

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, $user->id);

        return new RedirectResponse($this->app->url());
    }
}
