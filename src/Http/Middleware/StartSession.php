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
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Store;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SessionHandlerInterface;

class StartSession implements Middleware
{
    /**
     * @var SessionHandlerInterface
     */
    protected $handler;

    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param SessionHandlerInterface $handler
     * @param CookieFactory $cookie
     * @param ConfigRepository $config
     */
    public function __construct(SessionHandlerInterface $handler, CookieFactory $cookie, ConfigRepository $config)
    {
        $this->handler = $handler;
        $this->cookie = $cookie;
        $this->config = $config->get('session');
    }

    public function process(Request $request, Handler $handler): Response
    {
        $request = $request->withAttribute(
            'session',
            $session = $this->makeSession($request)
        );

        $session->start();
        $response = $handler->handle($request);
        $session->save();

        $response = $this->withCsrfTokenHeader($response, $session);

        return $this->withSessionCookie($response, $session);
    }

    private function makeSession(Request $request)
    {
        return new Store(
            $this->config['cookie'],
            $this->handler,
            array_get($request->getCookieParams(), $this->cookie->getName($this->config['cookie']))
        );
    }

    private function withCsrfTokenHeader(Response $response, Session $session)
    {
        return $response->withHeader('X-CSRF-Token', $session->token());
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
        return $this->config['lifetime'] * 60;
    }
}
