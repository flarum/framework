<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Dflydev\FigCookies\FigResponseCookies;
use Flarum\Http\CookieFactory;
use Flarum\Http\SessionConfig;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Session\Store;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use SessionHandlerInterface;

class StartSession implements Middleware
{
    protected array $config;

    public function __construct(
        protected SessionHandlerInterface $handler,
        protected CookieFactory $cookie,
        ConfigRepository $config,
        protected SessionConfig $sessionConfig
    ) {
        $this->config = (array) $config->get('session');
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

    private function makeSession(Request $request): Session
    {
        return new Store(
            $this->config['cookie'],
            $this->handler,
            Arr::get($request->getCookieParams(), $this->cookie->getName($this->config['cookie']))
        );
    }

    private function withCsrfTokenHeader(Response $response, Session $session): Response
    {
        return $response->withHeader('X-CSRF-Token', $session->token());
    }

    private function withSessionCookie(Response $response, Session $session): Response
    {
        // A cookie with no lifetime at all is what a browser discards when it
        // closes. The session itself is unaffected — it stays valid server-side
        // for its usual span; the browser simply stops offering it.
        $maxAge = $this->sessionConfig->cookieExpiresOnClose()
            ? null
            : $this->getSessionLifetimeInSeconds();

        return FigResponseCookies::set(
            $response,
            $this->cookie->make($session->getName(), $session->getId(), $maxAge)
        );
    }

    private function getSessionLifetimeInSeconds(): int
    {
        return $this->sessionConfig->lifetime() * 60;
    }
}
