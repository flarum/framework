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
use Illuminate\Support\Str;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StartSession implements MiddlewareInterface
{
    const COOKIE_NAME = 'session';

    /**
     * @var CookieFactory
     */
    protected $cookie;

    /**
     * @param CookieFactory $cookie
     */
    public function __construct(CookieFactory $cookie)
    {
        $this->cookie = $cookie;
    }

    public function process(Request $request, DelegateInterface $delegate)
    {
        $session = $this->startSession();

        $request = $request->withAttribute('session', $session);

        $response = $delegate->process($request);

        $response = $this->withCsrfTokenHeader($response, $session);

        return $this->withSessionCookie($response, $session);
    }

    private function startSession()
    {
        $session = new Session;

        $session->setName($this->cookie->getName(self::COOKIE_NAME));
        $session->start();

        if (! $session->has('csrf_token')) {
            $session->set('csrf_token', Str::random(40));
        }

        return $session;
    }

    private function withCsrfTokenHeader(Response $response, SessionInterface $session)
    {
        if ($session->has('csrf_token')) {
            $response = $response->withHeader('X-CSRF-Token', $session->get('csrf_token'));
        }

        return $response;
    }

    private function withSessionCookie(Response $response, SessionInterface $session)
    {
        return FigResponseCookies::set(
            $response,
            $this->cookie->make(self::COOKIE_NAME, $session->getId())
        );
    }
}
