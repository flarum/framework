<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\Config;
use HansOtt\PSR7Cookies\SetCookie;

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
     * Same Site cookie value.
     *
     * @var string
     */
    protected $samesite;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        // If necessary, we will use the forum's base URL to determine smart defaults for cookie settings
        $url = $config->url();

        // Get the cookie settings from the config or use the default values
        $this->prefix = $config['cookie.name'] ?? 'flarum';
        $this->path = $config['cookie.path'] ?? $url->getPath() ?: '/';
        $this->domain = $config['cookie.domain'];
        $this->secure = $config['cookie.secure'] ?? $url->getScheme() === 'https';
        $this->samesite = $config['cookie.samesite'];
    }

    /**
     * Make a new cookie instance.
     *
     * This method returns a cookie instance for use with the Set-Cookie HTTP header.
     * It will be pre-configured according to Flarum's base URL and protocol.
     *
     * @param  string       $name
     * @param  string       $value
     * @param  int|null     $maxAge
     * @return SetCookie
     */
    public function make(string $name, string $value, int $maxAge = null): SetCookie
    {
        return new SetCookie(
            $this->getName($name),
            $value,
            $maxAge ? time() + $maxAge : 0,
            $this->path,
            $this->domain ?? '',
            $this->secure,
            true,
            $this->samesite ?? 'lax'
        );
    }

    /**
     * Make an expired cookie instance.
     *
     * @param string $name
     * @return SetCookie
     */
    public function expire(string $name): SetCookie
    {
        return $this->make($name, '', -3600);
    }

    /**
     * Get a cookie name.
     *
     * @param string $name
     * @return string
     */
    public function getName(string $name): string
    {
        return $this->prefix.'_'.$name;
    }
}
