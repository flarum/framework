<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Foundation\Application;
use Flarum\Http\Exception\TokenMismatchException;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

class LogOutController implements RequestHandlerInterface
{
    use AssertPermissionTrait;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var SessionAuthenticator
     */
    protected $authenticator;

    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param Application $app
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param Factory $view
     * @param UrlGenerator $url
     */
    public function __construct(
        Application $app,
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        Factory $view,
        UrlGenerator $url
    ) {
        $this->app = $app;
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->view = $view;
        $this->url = $url;
    }

    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        $actor = $request->getAttribute('actor');

        $url = Arr::get($request->getQueryParams(), 'return', $this->app->url());

        // If there is no user logged in, return to the index.
        if ($actor->isGuest()) {
            return new RedirectResponse($url);
        }

        // If a valid CSRF token hasn't been provided, show a view which will
        // allow the user to press a button to complete the log out process.
        $csrfToken = $session->token();

        if (Arr::get($request->getQueryParams(), 'token') !== $csrfToken) {
            $return = Arr::get($request->getQueryParams(), 'return');

            $view = $this->view->make('flarum.forum::log-out')
                ->with('url', $this->url->to('forum')->route('logout').'?token='.$csrfToken.($return ? '&return='.urlencode($return) : ''));

            return new HtmlResponse($view->render());
        }

        $response = new RedirectResponse($url);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();

        $this->events->dispatch(new LoggedOut($actor));

        return $this->rememberer->forget($response);
    }
}
