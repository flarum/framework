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

use Flarum\Event\UserLoggedOut;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Http\Exception\TokenMismatchException;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class LogOutController implements ControllerInterface
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @param Application $app
     * @param Dispatcher $events
     */
    public function __construct(Application $app, Dispatcher $events)
    {
        $this->app = $app;
        $this->events = $events;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface
     * @throws TokenMismatchException
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $session = $request->getAttribute('session');

        if ($user = $session->user) {
            if (array_get($request->getQueryParams(), 'token') !== $session->csrf_token) {
                throw new TokenMismatchException;
            }

            $session->exists = false;

            $user->sessions()->delete();

            $this->events->fire(new UserLoggedOut($user));
        }

        return new RedirectResponse($this->app->url());
    }
}
