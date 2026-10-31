<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Flarum\OAuthProvider\Server\RedirectUriMatcher;

/**
 * Validation rules shared by metadata-document and dynamically registered clients.
 */
class ClientMetadata
{
    public const int MAX_URI_LENGTH = 2048;

    /**
     * @return list<string>
     * @throws ClientMetadataException
     */
    public static function redirectUris(mixed $value): array
    {
        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            throw new ClientMetadataException('redirect_uris must be a non-empty array.', 'invalid_redirect_uri');
        }

        foreach ($value as $uri) {
            if (! is_string($uri) || ! self::isAllowedRedirectUri($uri)) {
                throw new ClientMetadataException('Redirect URIs must be https:// URLs or loopback http:// URLs.', 'invalid_redirect_uri');
            }
        }

        return array_values(array_unique($value));
    }

    public static function isAllowedRedirectUri(string $uri): bool
    {
        if (strlen($uri) > self::MAX_URI_LENGTH) {
            return false;
        }

        $parts = parse_url($uri);

        if ($parts === false || empty($parts['host']) || isset($parts['fragment']) || isset($parts['user'])) {
            return false;
        }

        return match (strtolower($parts['scheme'] ?? '')) {
            'https' => true,
            'http' => RedirectUriMatcher::isLoopback($uri),
            default => false,
        };
    }

    /**
     * An optional https URL (logo_uri, client_uri); anything else is dropped.
     */
    public static function optionalUrl(mixed $value): ?string
    {
        if (! is_string($value) || $value === '' || strlen($value) > self::MAX_URI_LENGTH) {
            return null;
        }

        $parts = parse_url($value);

        return $parts !== false && strtolower($parts['scheme'] ?? '') === 'https' && ! empty($parts['host'])
            ? $value
            : null;
    }

    public static function name(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
