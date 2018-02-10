<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\SessionManager;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StartSession implements MiddlewareInterface
{
    /**
     * @var SessionManager
     */
    protected $manager;

    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @param SessionManager $manager
     * @param CookieFactory $cookie
     */
    public function __construct(SessionManager $manager, CookieFactory $cookie)
    {
        $this->manager = $manager;
        $this->cookie = $cookie;
    }

    public function process(Request $request, DelegateInterface $delegate)
    {
        $request = $request->withAttribute('session',
            $session = $this->startSession($request)
        );

        $this->collectGarbage($session);

        $response = $delegate->process($request);

        $session->save();

        $response = $this->withCsrfTokenHeader($response, $session);

        return $this->withSessionCookie($response, $session);
    }

    private function startSession(Request $request)
    {
        $session = $this->manager->driver();

        $id = array_get($request->getCookieParams(), $this->cookie->getName($session->getName()));

        $session->setId($id);
        $session->start();

        return $session;
    }

    private function collectGarbage(Session $session)
    {
        $config = $this->manager->getSessionConfig();

        if ($this->configHitsLottery($config)) {
            $session->getHandler()->gc($this->getSessionLifetimeInSeconds());
        }
    }

    private function configHitsLottery(array $config)
    {
        return random_int(1, $config['lottery'][1]) <= $config['lottery'][0];
    }

    private function withCsrfTokenHeader(Response $response, Session $session)
    {
        $response = $response->withHeader('X-CSRF-Token', $session->token());

        return $response;
    }

    private function withSessionCookie(Response $response, Session $session)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make($session->getName(), $session->getId(), $this->getSessionLifetimeInSeconds())
        );
    }

    private function getSessionLifetimeInSeconds()
    {
        return ($this->manager->getSessionConfig()['lifetime'] ?? null) * 60;
    }
}
