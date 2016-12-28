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

use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\Application;

class CookieFactory
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * make a new cookie instance.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $maxAge
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function make($name, $value = null, $maxAge = null)
    {
        $url = parse_url(rtrim($this->app->url(), '/'));

        $path = array_get($url, 'path') ?: '/';

        $secure = array_get($url, 'scheme') === 'https';

        return SetCookie::create($name, $value)
            ->withMaxAge($maxAge)
            ->withPath($path)
            ->withSecure($secure)
            ->withHttpOnly(true)
            ->withDomain(null);
    }
}
