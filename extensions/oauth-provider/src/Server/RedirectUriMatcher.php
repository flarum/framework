<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server;

/**
 * Redirect URI matching: exact, except that public clients may use any port on
 * a registered loopback URI (RFC 8252 §7.3). Replaces league's validator, which
 * relaxes the port for every client and doesn't know `localhost`.
 */
class RedirectUriMatcher
{
    public const array LOOPBACK_HOSTS = ['127.0.0.1', '[::1]', 'localhost'];

    /**
     * @param list<string> $registered
     */
    public function matches(string $requested, array $registered, bool $publicClient): bool
    {
        if (in_array($requested, $registered, true)) {
            return true;
        }

        if (! $publicClient) {
            return false;
        }

        $requestedParts = $this->loopbackParts($requested);

        if ($requestedParts === null) {
            return false;
        }

        foreach ($registered as $uri) {
            if ($this->loopbackParts($uri) === $requestedParts) {
                return true;
            }
        }

        return false;
    }

    /**
     * The URI without its port, or null when it isn't a loopback URI.
     *
     * @return array<string, string>|null
     */
    protected function loopbackParts(string $uri): ?array
    {
        $parts = parse_url($uri);

        if ($parts === false || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if (! in_array(strtolower($parts['host']), self::LOOPBACK_HOSTS, true)) {
            return null;
        }

        unset($parts['port']);
        ksort($parts);

        return array_map(strval(...), $parts);
    }

    public static function isLoopback(string $uri): bool
    {
        $host = parse_url($uri, PHP_URL_HOST);

        return is_string($host) && in_array(strtolower($host), self::LOOPBACK_HOSTS, true);
    }
}
