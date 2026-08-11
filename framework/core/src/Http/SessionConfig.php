<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * How long sessions and the tokens behind them last.
 *
 * Every value here is answered the same way: `config.php` first, then the
 * settings table, then the default that has always applied. Putting a value in
 * `config.php` is therefore also a way to take it away from the admin panel —
 * a site owner can pin session lengths where an administrator cannot loosen
 * them, which is the point of keeping the option in a file rather than only in
 * the database.
 */
class SessionConfig
{
    /**
     * How long a session may sit idle before it is discarded, in minutes.
     */
    public const DEFAULT_LIFETIME = 120;

    public function __construct(
        protected Config $config,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * How long tokens of the given type stay valid, in seconds, or `null` to
     * leave the decision to the token class.
     *
     * Zero is a real answer rather than an absent one — it means the token
     * never expires — so it has to survive the fall through the sources below.
     */
    public function tokenLifetime(string $type): ?int
    {
        $lifetime = $this->config->accessTokenLifetime($type);

        if ($lifetime !== null) {
            return $lifetime;
        }

        $lifetime = $this->settings->get("session.tokens.$type");

        if (! is_numeric($lifetime) || $lifetime < 0) {
            return null;
        }

        return (int) $lifetime;
    }

    /**
     * How long a session may sit idle before it is discarded, in minutes.
     */
    public function lifetime(): int
    {
        $lifetime = $this->config->sessionLifetime();

        if ($lifetime !== null) {
            return $lifetime;
        }

        $lifetime = $this->settings->get('session.lifetime');

        if (! is_numeric($lifetime) || $lifetime <= 0) {
            return self::DEFAULT_LIFETIME;
        }

        return (int) $lifetime;
    }

    /**
     * Whether session cookies should be discarded when the browser closes.
     *
     * This is about the cookie, not the token behind it: the session remains
     * valid server-side for its lifetime, but the browser stops presenting it
     * once the window is gone. Shared computers are the usual reason to want
     * it.
     */
    public function cookieExpiresOnClose(): bool
    {
        $value = $this->config->sessionCookieExpiresOnClose();

        if ($value !== null) {
            return $value;
        }

        return (bool) $this->settings->get('session.cookie_expires_on_close');
    }

    /**
     * Whether any of this is pinned in `config.php`, and so cannot be changed
     * from the admin panel.
     */
    public function configOverride(): bool
    {
        return $this->config->sessionConfigOverride();
    }
}
