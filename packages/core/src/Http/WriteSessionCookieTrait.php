<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface as Response;

trait WriteSessionCookieTrait
{
    protected function addSessionCookieToResponse(Response $response, Session $session, $cookieName)
    {
        return FigResponseCookies::set(
            $response,
            SetCookie::create($cookieName, $session->exists ? $session->id : null)
                ->withMaxAge($session->exists ? $session->duration * 60 : -2628000)
                ->withPath('/')
                ->withHttpOnly(true)
        );
    }
}
