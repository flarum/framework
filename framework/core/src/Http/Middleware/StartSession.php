<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Http\CookieFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Support\Arr;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Response;

class StartSession implements IlluminateMiddlewareInterface
{
    protected array $config;

    public function __construct(
        protected SessionHandlerInterface $handler,
        protected CookieFactory $cookie,
        ConfigRepository $config
    ) {
        $this->config = (array) $config->get('session');
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(
            'session',
            $session = $this->makeSession($request)
        );

        $session->start();
        $response = $next($request);
        $session->save();

        $this->setCsrfTokenHeader($response, $session);
        $this->setSessionCookie($response, $session);

        return $response;
    }

    private function makeSession(Request $request): Session
    {
        return new Store(
            $this->config['cookie'],
            $this->handler,
            Arr::get($request->getCookieParams(), $this->cookie->getName($this->config['cookie']))
        );
    }

    private function setCsrfTokenHeader(Response $response, Session $session): void
    {
        $response->headers->set('X-CSRF-Token', $session->token());
    }

    private function setSessionCookie(Response $response, Session $session): void
    {
        $response->headers->setCookie(
            $this->cookie->make($session->getName(), $session->getId(), $this->getSessionLifetimeInSeconds())
        );
    }

    private function getSessionLifetimeInSeconds(): int
    {
        return $this->config['lifetime'] * 60;
    }
}
