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
use Flarum\OAuthProvider\Server\Repository\RefreshTokenRepository;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Refresh token grant that carries the RFC 8707 resource of the grant over to
 * the new access token, and treats a reused (already rotated or revoked)
 * refresh token as a sign of theft: every token descending from the same
 * authorization code is revoked.
 *
 * A refresh token is used up atomically once the request has been validated,
 * so of two concurrent refreshes with one token only one succeeds; the other
 * counts as reuse.
 */
class ResourceRefreshTokenGrant extends RefreshTokenGrant
{
    use IssuesResourceBoundTokens {
        issueAccessToken as issueResourceBoundAccessToken;
    }

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
            $this->assertFamilyNotRevoked($this->tokenGrantId);
        } finally {
            $this->tokenResource = null;
            $this->tokenGrantId = null;
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

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = RefreshToken::query()->find($data['refresh_token_id'] ?? null);

        if ($refreshToken === null) {
            throw OAuthServerException::invalidRefreshToken('Token has been revoked');
        }

        $scopes = array_values((array) ($data['scopes'] ?? []));

        $this->assertScopesWithinGrant($request, $scopes);
        $this->tokenResource = $this->resolveTokenResource($request, $refreshToken->resource, $scopes);

        // Every check has passed: use the token up. Of concurrent refreshes
        // with one token, only one gets past this point.
        $consumed = $this->refreshTokenRepository instanceof RefreshTokenRepository
            && $this->refreshTokenRepository->consumeRefreshToken($refreshToken->id);

        if (! $consumed) {
            throw $this->revokeReusedGrant($refreshToken);
        }

        $this->tokenGrantId = $refreshToken->grant_id;

        /** @var AccessToken|null $previous */
        $previous = AccessToken::query()->find($data['access_token_id'] ?? null);
        $this->previousAuthTime = $previous?->auth_time;

        return $data;
    }

    /**
     * Applies the client's current allowed scopes, which an admin may have
     * narrowed since the grant; the parent grant never finalizes scopes.
     *
     * @param ScopeEntityInterface[] $scopes
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        $userIdentifier,
        array $scopes = []
    ): AccessTokenEntityInterface {
        $scopes = $this->scopeRepository->finalizeScopes($scopes, $this->getIdentifier(), $client, $userIdentifier);

        return $this->issueResourceBoundAccessToken($accessTokenTTL, $client, $userIdentifier, $scopes);
    }

    /**
     * The parent's check that a refresh asks for no scope beyond the grant's,
     * run before the token is used up so a bad request doesn't cost it.
     *
     * @param list<string> $granted
     * @throws OAuthServerException
     */
    protected function assertScopesWithinGrant(ServerRequestInterface $request, array $granted): void
    {
        $requested = $this->validateScopes(
            $this->getRequestParameter('scope', $request, implode(self::SCOPE_DELIMITER_STRING, $granted))
        );

        foreach ($requested as $scope) {
            if (! in_array($scope->getIdentifier(), $granted, true)) {
                throw OAuthServerException::invalidScope($scope->getIdentifier());
            }
        }
    }

    /**
     * Revokes every token descending from the same authorization code as a
     * reused refresh token and returns the error to answer with.
     */
    protected function revokeReusedGrant(RefreshToken $refreshToken): OAuthServerException
    {
        if ($refreshToken->grant_id !== null) {
            $this->revokeTokenFamily($refreshToken->grant_id);
        } else {
            RefreshToken::query()->whereKey($refreshToken->id)->update(['revoked' => true]);
            AccessToken::query()->whereKey($refreshToken->access_token_id)->update(['revoked' => true]);
        }

        return OAuthServerException::invalidGrant('The refresh token has already been used; all tokens of this grant have been revoked.');
    }

    /**
     * When the presented refresh token is genuine, belongs to this client, is
     * unexpired and has been revoked, revokes every token of its grant (OAuth
     * 2.0 Security BCP §4.14.2) and fails with invalid_grant. Otherwise
     * returns and the original error stands.
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

        throw $this->revokeReusedGrant($refreshToken);
    }
}
