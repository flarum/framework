<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Flarum\Foundation\Config;

class CookieFactory
{
    protected string $prefix;
    protected string $path;
    protected string $domain;
    protected bool $secure;
    protected ?string $samesite = null;

    public function __construct(Config $config)
    {
        // If necessary, we will use the forum's base URL to determine smart defaults for cookie settings
        $url = $config->url();

        // Get the cookie settings from the config or use the default values
        $this->prefix = (string) ($config['cookie.name'] ?? 'flarum');
        $this->path = (string) ($config['cookie.path'] ?? $url->getPath() ?: '/');
        $this->domain = (string) $config['cookie.domain'];
        $this->secure = (bool) ($config['cookie.secure'] ?? $url->getScheme() === 'https');
        $this->samesite = $config['cookie.samesite'];
    }

    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * It will be pre-configured according to Flarum's base URL and protocol.
     */
    public function make(string $name, ?string $value = null, ?int $maxAge = null): SetCookie
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

        // Explicitly set SameSite value, use sensible default if no value provided
        $cookie = $cookie->withSameSite(SameSite::{$this->samesite ?? 'lax'}());

        return $cookie
            ->withPath($this->path)
            ->withSecure($this->secure)
            ->withHttpOnly(true);
    }

    /**
     * Make an expired cookie instance.
     */
    public function expire(string $name): SetCookie
    {
        return $this->make($name)->expire();
    }

    /**
     * Get a cookie name.
     */
    public function getName(string $name): string
    {
        return $this->prefix.'_'.$name;
    }
}
