<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Grant;

use Flarum\OAuthProvider\Models\AccessToken;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Refresh token grant that carries the RFC 8707 resource of the refreshed
 * access token over to the new one.
 */
class ResourceRefreshTokenGrant extends RefreshTokenGrant
{
    use IssuesResourceBoundTokens;

    /**
     * Runs after client authentication, before the new token is issued.
     *
     * @param string $clientId
     * @return array<string, mixed>
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, $clientId): array
    {
        $data = parent::validateOldRefreshToken($request, $clientId);

        /** @var AccessToken|null $previous */
        $previous = AccessToken::query()->find($data['access_token_id'] ?? null);

        $this->tokenResource = $this->resolveTokenResource($request, $previous?->resource, (array) ($data['scopes'] ?? []));

        return $data;
    }
}
