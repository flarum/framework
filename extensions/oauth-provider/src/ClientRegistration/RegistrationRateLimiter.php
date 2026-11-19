<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Fixed-window, per-address counter for unauthenticated client creation
 * (dynamic registration and metadata document fetches). IPv6 addresses are
 * counted per /64, the smallest block a single subscriber usually gets.
 */
class RegistrationRateLimiter
{
    public function __construct(protected Cache $cache)
    {
    }

    /**
     * Counts an attempt and reports whether the address is now over the limit.
     */
    public function tooManyAttempts(string $bucket, string $ip, int $limit, int $window): bool
    {
        $key = 'flarum-oauth-provider.rate.'.$bucket.'.'.sha1(self::addressKey($ip));

        // add() only writes when the key is absent, so the window starts once
        // and increment() never resets it.
        $this->cache->add($key, 0, $window);

        return (int) $this->cache->increment($key) > $limit;
    }

    public static function addressKey(string $ip): string
    {
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return $ip;
        }

        if (strlen($packed) === 16) {
            // IPv4-mapped IPv6 is the IPv4 address.
            if (str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
                return (string) inet_ntop(substr($packed, 12));
            }

            return (string) inet_ntop(substr($packed, 0, 8).str_repeat("\0", 8)).'/64';
        }

        return $ip;
    }
}
