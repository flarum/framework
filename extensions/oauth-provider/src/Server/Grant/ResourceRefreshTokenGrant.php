<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Grant;

use Carbon\Carbon;
use DateInterval;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\RefreshToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Refresh token grant that carries the RFC 8707 resource of the refreshed
 * access token over to the new one, and treats a reused (already rotated or
 * revoked) refresh token as a sign of theft: the whole grant is revoked.
 */
class ResourceRefreshTokenGrant extends RefreshTokenGrant
{
    use IssuesResourceBoundTokens;

    /** When the user originally authenticated; refreshed ID tokens keep it (OIDC Core §12.2). */
    protected ?Carbon $previousAuthTime = null;

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ): ResponseTypeInterface {
        try {
            $response = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
            $issuedId = $this->issuedAccessTokenId;
            $authTime = $this->previousAuthTime;
        } finally {
            $this->issuedAccessTokenId = null;
            $this->previousAuthTime = null;
        }

        if ($issuedId !== null && $authTime !== null) {
            AccessToken::query()->whereKey($issuedId)->update(['auth_time' => $authTime]);
        }

        return $response;
    }

    /**
     * Runs after client authentication, before the new token is issued.
     *
     * @param string $clientId
     * @return array<string, mixed>
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, $clientId): array
    {
        try {
            $data = parent::validateOldRefreshToken($request, $clientId);
        } catch (OAuthServerException $e) {
            $this->detectReuse($request, (string) $clientId);

            throw $e;
        }

        /** @var AccessToken|null $previous */
        $previous = AccessToken::query()->find($data['access_token_id'] ?? null);

        $this->tokenResource = $this->resolveTokenResource($request, $previous?->resource, (array) ($data['scopes'] ?? []));
        $this->previousAuthTime = $previous?->auth_time;

        return $data;
    }

    /**
     * When the presented refresh token is genuine, belongs to this client, is
     * unexpired and has been revoked, revokes every access and refresh token
     * of that user and client (OAuth 2.0 Security BCP §4.14.2) and fails with
     * invalid_grant. Otherwise returns and the original error stands.
     *
     * @throws OAuthServerException
     */
    protected function detectReuse(ServerRequestInterface $request, string $clientId): void
    {
        $encrypted = $this->getRequestParameter('refresh_token', $request);

        if (! is_string($encrypted)) {
            return;
        }

        try {
            $data = json_decode($this->decrypt($encrypted), true);
        } catch (\Throwable) {
            return;
        }

        if (! is_array($data)
            || ($data['client_id'] ?? null) !== $clientId
            || ($data['expire_time'] ?? 0) < time()
            || ! is_string($data['refresh_token_id'] ?? null)) {
            return;
        }

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = RefreshToken::query()->find($data['refresh_token_id']);

        if ($refreshToken === null || ! $refreshToken->revoked) {
            return;
        }

        $family = AccessToken::query()
            ->where('client_id', $clientId)
            ->where('user_id', $data['user_id'] ?? null);

        RefreshToken::query()
            ->whereIn('access_token_id', (clone $family)->select('id'))
            ->update(['revoked' => true]);
        $family->update(['revoked' => true]);

        throw OAuthServerException::invalidGrant('The refresh token has already been used; all tokens of this grant have been revoked.');
    }
}
