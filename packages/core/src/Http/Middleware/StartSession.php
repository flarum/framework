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
use Dflydev\FigCookies\SetCookie;
use Dflydev\FigCookies\SetCookies;
use Flarum\Http\Session;
use Flarum\Core\Guest;
use Flarum\Http\WriteSessionCookieTrait;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Stratigility\MiddlewareInterface;

class StartSession implements MiddlewareInterface
{
    use WriteSessionCookieTrait;

    /**
     * {@inheritdoc}
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        $this->collectGarbage();

        $session = $this->getSession($request);
        $actor = $this->getActor($session);

        $request = $request
            ->withAttribute('session', $session)
            ->withAttribute('actor', $actor);

        $response = $out ? $out($request, $response) : $response;

        return $this->addSessionCookieToResponse($response, $session, 'flarum_session');
    }

    private function getSession(Request $request)
    {
        $session = $request->getAttribute('session');

        if (! $session) {
            $session = Session::generate();
        }

        $session->extend()->save();

        return $session;
    }

    private function getActor(Session $session)
    {
        $actor = $session->user ?: new Guest;

        if ($actor->exists) {
            $actor->updateLastSeen()->save();
        }

        return $actor;
    }

    private function collectGarbage()
    {
        if ($this->hitsLottery()) {
            Session::whereRaw('last_activity <= ? - duration * 60', [time()])->delete();
        }
    }

    private function hitsLottery()
    {
        return mt_rand(1, 100) <= 1;
    }
}
