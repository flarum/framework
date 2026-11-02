<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\ClientRegistration;

use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Flarum\OAuthProvider\Server\RedirectUriMatcher;

/**
 * Validation rules shared by metadata-document and dynamically registered clients.
 */
class ClientMetadata
{
    public const int MAX_URI_LENGTH = 2048;
    public const int MAX_REDIRECT_URIS = 10;
    public const int MAX_REDIRECT_URI_LENGTH = 2000;

    /**
     * Schemes that run code or read local data in the browser; never a redirect target.
     */
    protected const array DANGEROUS_SCHEMES = ['javascript', 'data', 'vbscript', 'file', 'about', 'blob', 'filesystem'];

    /**
     * @return list<string>
     * @throws ClientMetadataException
     */
    public static function redirectUris(mixed $value, bool $allowPrivateUseSchemes = false): array
    {
        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            throw new ClientMetadataException('redirect_uris must be a non-empty array.', 'invalid_redirect_uri');
        }

        if (count($value) > self::MAX_REDIRECT_URIS) {
            throw new ClientMetadataException('At most '.self::MAX_REDIRECT_URIS.' redirect URIs may be registered.', 'invalid_redirect_uri');
        }

        foreach ($value as $uri) {
            if (! is_string($uri) || ! self::isAllowedRedirectUri($uri, $allowPrivateUseSchemes)) {
                throw new ClientMetadataException($allowPrivateUseSchemes
                    ? 'Redirect URIs must be https:// URLs, loopback http:// URLs or native app URIs without a fragment.'
                    : 'Redirect URIs must be https:// URLs or loopback http:// URLs.', 'invalid_redirect_uri');
            }
        }

        return array_values(array_unique($value));
    }

    /**
     * Private-use schemes (RFC 8252 §7.1, e.g. `com.example.app:/callback`)
     * are only for clients an admin registered: any app can claim a scheme,
     * so a self-registered client could use one to impersonate another app.
     */
    public static function isAllowedRedirectUri(string $uri, bool $allowPrivateUseSchemes = false): bool
    {
        if (strlen($uri) > self::MAX_REDIRECT_URI_LENGTH) {
            return false;
        }

        $parts = parse_url($uri);

        if ($parts === false || isset($parts['fragment']) || isset($parts['user'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if ($scheme === 'https' || $scheme === 'http') {
            return ! empty($parts['host']) && ($scheme === 'https' || RedirectUriMatcher::isLoopback($uri));
        }

        return $allowPrivateUseSchemes
            && preg_match('/^[a-z][a-z0-9+.-]*$/', $scheme) === 1
            && ! in_array($scheme, self::DANGEROUS_SCHEMES, true)
            && (! empty($parts['host']) || ! empty($parts['path']));
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

    /**
     * @return list<string>|null Null when the client registered no restriction.
     * @throws ClientMetadataException
     */
    public static function scopes(mixed $value, ScopeRegistry $registry): ?array
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new ClientMetadataException('scope must be a space-separated string.');
        }

        $scopes = array_values(array_unique(array_filter(explode(' ', $value), fn (string $scope) => $scope !== '')));

        if ($scopes === []) {
            return null;
        }

        $unknown = array_values(array_filter($scopes, fn (string $scope) => ! $registry->has($scope)));

        if ($unknown !== []) {
            throw new ClientMetadataException('Unknown scope: '.implode(' ', $unknown).'.');
        }

        return $scopes;
    }

    /**
     * A display name with control and format characters (Unicode Cc/Cf, which
     * include the bidi overrides and isolates) removed, so a client can't
     * reorder or hide text on the consent screen.
     */
    public static function name(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = preg_replace('/[\p{Cc}\p{Cf}]+/u', '', $value);

        // Null when the value isn't valid UTF-8.
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
