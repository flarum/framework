<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\Application;
use Flarum\Http\SessionAuthenticator;
use Flarum\User\Command\ConfirmEmail;
use Flarum\User\Exception\InvalidConfirmationTokenException;
use Illuminate\Contracts\Bus\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
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
        try {
            $token = array_get($request->getQueryParams(), 'token');

            $user = $this->bus->dispatch(
                new ConfirmEmail($token)
            );
        } catch (InvalidConfirmationTokenException $e) {
            return new HtmlResponse('Invalid confirmation token');
        }

        $session = $request->getAttribute('session');
        $this->authenticator->logIn($session, $user->id);

        return new RedirectResponse($this->app->url());
    }
}
