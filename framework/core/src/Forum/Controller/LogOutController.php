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

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Event\UserLoggedOut;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Exception\TokenMismatchException;
use Flarum\Http\Rememberer;
use Flarum\Http\SessionAuthenticator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

class LogOutController implements ControllerInterface
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
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param Application $app
     * @param Dispatcher $events
     * @param SessionAuthenticator $authenticator
     * @param Rememberer $rememberer
     * @param Factory $view
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(
        Application $app,
        Dispatcher $events,
        SessionAuthenticator $authenticator,
        Rememberer $rememberer,
        Factory $view,
        SettingsRepositoryInterface $settings
    ) {
        $this->app = $app;
        $this->events = $events;
        $this->authenticator = $authenticator;
        $this->rememberer = $rememberer;
        $this->view = $view;
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request)
    {
        $session = $request->getAttribute('session');
        $actor = $request->getAttribute('actor');

        $url = array_get($request->getQueryParams(), 'return', $this->app->url());

        // If there is no user logged in, return to the index.
        if ($actor->isGuest()) {
            return new RedirectResponse($url);
        }

        // If a valid CSRF token hasn't been provided, show a view which will
        // allow the user to press a button to complete the log out process.
        $csrfToken = $session->get('csrf_token');

        if (array_get($request->getQueryParams(), 'token') !== $csrfToken) {
            $view = $this->view->make('flarum.forum::log-out')
                ->with('csrfToken', $csrfToken)
                ->with('forumTitle', $this->settings->get('forum_title'));

            return new HtmlResponse($view->render());
        }

        $response = new RedirectResponse($url);

        $this->authenticator->logOut($session);

        $actor->accessTokens()->delete();

        $this->events->fire(new UserLoggedOut($actor));

        return $this->rememberer->forget($response);
    }
}
