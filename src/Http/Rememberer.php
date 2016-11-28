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
use Psr\Http\Message\ResponseInterface;

class Rememberer
{
    protected $cookieName = 'flarum_remember';

    public function remember(ResponseInterface $response, AccessToken $token, $session = false)
    {
        $cookie = $this->createCookie()->withValue($token->id);

        if (! $session) {
            $lifetime = 60 * 60 * 24 * 14;

            $token->lifetime = $lifetime;
            $token->save();

            $cookie = $cookie->withMaxAge($lifetime);
        }

        return FigResponseCookies::set($response, $cookie);
    }

    public function rememberUser(ResponseInterface $response, $userId)
    {
        $token = AccessToken::generate($userId);

        return $this->remember($response, $token);
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::expire($response, $this->cookieName);
    }

    private function createCookie()
    {
        return SetCookie::create($this->cookieName)
            ->withPath('/')
            ->withHttpOnly(true);
    }
}
