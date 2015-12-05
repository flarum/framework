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

    public function remember(ResponseInterface $response, $token)
    {
        return FigResponseCookies::set(
            $response,
            $this->createCookie()
                ->withValue($token)
                ->withMaxAge(14 * 24 * 60 * 60)
        );
    }

    public function rememberUser(ResponseInterface $response, $userId)
    {
        $token = AccessToken::generate($userId);
        $token->lifetime = 60 * 60 * 24 * 14;
        $token->save();

        return $this->remember($response, $token->id);
    }

    public function forget(ResponseInterface $response)
    {
        return FigResponseCookies::set(
            $response,
            $this->createCookie()->withMaxAge(-2628000)
        );
    }

    private function createCookie()
    {
        return SetCookie::create($this->cookieName)
            ->withPath('/')
            ->withHttpOnly(true);
    }
}
