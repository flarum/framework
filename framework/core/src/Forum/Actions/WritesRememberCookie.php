<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;

trait WritesRememberCookie
{
    protected function withRememberCookie(ResponseInterface $response, $token)
    {
        // Set a long-living cookie (two weeks) with the remember token
        return FigResponseCookies::set(
            $response,
            SetCookie::create('flarum_remember', $token)
                ->withMaxAge(14 * 24 * 60 * 60)
                ->withPath('/')
                ->withHttpOnly(true)
        );
    }

    protected function withForgetCookie(ResponseInterface $response)
    {
        // Delete the cookie by setting it to an expiration date in the past
        return FigResponseCookies::set(
            $response,
            SetCookie::create('flarum_remember')
                ->withMaxAge(-2628000)
                ->withPath('/')
                ->withHttpOnly(true)
        );
    }
}
