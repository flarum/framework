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
     * @param  string  $path
     * @param  bool    $secure
     * @param  bool    $httpOnly
     * @param  string  $domain
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function make($name, $value = null, $maxAge = null, $path = null, $secure = null, $httpOnly = true, $domain = null)
    {
        $url = parse_url(rtrim($this->app->url(), '/'));

        if ($path === null) {
            $path = array_get($url, 'path') ?: '/';
        }

        if ($secure === null && array_get($url, 'scheme') === 'https') {
            $secure = true;
        }

        return SetCookie::create($name, $value)
            ->withMaxAge($maxAge)
            ->withPath($path)
            ->withSecure($secure)
            ->withHttpOnly($httpOnly)
            ->withDomain($domain);
    }
}
