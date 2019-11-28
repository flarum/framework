<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\Application;
use Illuminate\Support\Arr;

class CookieFactory
{
    /**
     * The prefix for the cookie names.
     *
     * @var string
     */
    protected $prefix;

    /**
     * A path scope for the cookies.
     *
     * @var string
     */
    protected $path;

    /**
     * A domain scope for the cookies.
     *
     * @var string
     */
    protected $domain;

    /**
     * Whether the cookie(s) can be requested only over HTTPS.
     *
     * @var bool
     */
    protected $secure;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        // Parse the forum's base URL so that we can determine the optimal cookie settings
        $url = parse_url(rtrim($app->url(), '/'));

        // Get the cookie settings from the config or use the default values
        $this->prefix = $app->config('cookie.name', 'flarum');
        $this->path = $app->config('cookie.path', Arr::get($url, 'path') ?: '/');
        $this->domain = $app->config('cookie.domain');
        $this->secure = $app->config('cookie.secure', Arr::get($url, 'scheme') === 'https');
    }

    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * It will be pre-configured according to Flarum's base URL and protocol.
     *
     * @param  string  $name
     * @param  string  $value
     * @param  int     $maxAge
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function make($name, $value = null, $maxAge = null)
    {
        $cookie = SetCookie::create($this->getName($name), $value);

        // Make sure we send both the MaxAge and Expires parameters (the former
        // is not supported by all browser versions)
        if ($maxAge) {
            $cookie = $cookie
                ->withMaxAge($maxAge)
                ->withExpires(time() + $maxAge);
        }

        if ($this->domain != null) {
            $cookie = $cookie->withDomain($this->domain);
        }

        return $cookie
            ->withPath($this->path)
            ->withSecure($this->secure)
            ->withHttpOnly(true);
    }

    /**
     * Make an expired cookie instance.
     *
     * @param string $name
     * @return \Dflydev\FigCookies\SetCookie
     */
    public function expire($name)
    {
        return $this->make($name)->expire();
    }

    /**
     * Get a cookie name.
     *
     * @param string $name
     * @return string
     */
    public function getName($name)
    {
        return $this->prefix.'_'.$name;
    }
}
