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

use Flarum\Api\AccessToken;
use Flarum\Event\UserLoggedOut;
use Flarum\Foundation\Application;
use Flarum\Http\Controller\ControllerInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\RedirectResponse;

class LogoutController implements ControllerInterface
{
    use WriteRememberCookieTrait;

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
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $user = $request->getAttribute('actor');

        if ($user->exists) {
            $token = array_get($request->getQueryParams(), 'token');

            AccessToken::where('user_id', $user->id)->findOrFail($token);

            $user->accessTokens()->delete();

            $this->events->fire(new UserLoggedOut($user));
        }

        return $this->withForgetCookie(new RedirectResponse($this->app->url()));
    }
}
