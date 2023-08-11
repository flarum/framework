<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\Event\LoggedOut;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class LogOutController extends AbstractController
{
    public function __construct(
        protected Dispatcher $events,
        protected SessionAuthenticator $authenticator,
        protected Rememberer $rememberer,
        protected Factory $view,
        protected UrlGenerator $url
    ) {
    }

    public function __invoke(Request $request): Response|ResponseInterface
    {
        $session = $request->attributes->get('session');
        $actor = RequestUtil::getActor($request);

        $url = $request->query('return', $this->url->base('forum'));

        // If there is no user logged in, return to the index.
        if ($actor->isGuest()) {
            return new RedirectResponse($url);
        }

        // If a valid CSRF token hasn't been provided, show a view which will
        // allow the user to press a button to complete the log out process.
        $csrfToken = $session->token();

        if ($request->query('token') !== $csrfToken) {
            $return = $request->query('return');

            $view = $this->view->make('flarum.forum::log-out')
                ->with('url', $this->url->route('forum.logout').'?token='.$csrfToken.($return ? '&return='.urlencode($return) : ''));

            return new HtmlResponse($view->render());
        }

        $accessToken = $session->get('access_token');
        $response = new RedirectResponse($url);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->where('token', $accessToken)->delete();

        $this->events->dispatch(new LoggedOut($actor, false));

        return $this->rememberer->forget($response);
    }
}
