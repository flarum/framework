<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server\Grant;

use DateInterval;
use DateTimeImmutable;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\ResourceServer\ResourceRegistry;
use Flarum\OAuthProvider\Server\Entity\AccessTokenEntity;
use Flarum\OAuthProvider\Server\RedirectUriMatcher;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\RequestEvent;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shared by the auth code and refresh token grants: binds new access tokens to
 * an RFC 8707 resource and applies Flarum's redirect URI rules.
 */
trait IssuesResourceBoundTokens
{
    protected ResourceRegistry $resources;

    /**
     * The resource the next access token is bound to; set by the grant once
     * the request has been validated.
     */
    protected ?string $tokenResource = null;

    /**
     * The token family the next access token joins: the authorization code it
     * descends from. Set by the grant once the request has been validated.
     */
    protected ?string $tokenGrantId = null;

    /**
     * Identifier of the access token this grant persisted most recently.
     */
    protected ?string $issuedAccessTokenId = null;

    public function setResourceRegistry(ResourceRegistry $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * Mirrors AbstractGrant::issueAccessToken(), setting the resource before
     * the token is persisted.
     *
     * @param ScopeEntityInterface[] $scopes
     */
    protected function issueAccessToken(
        DateInterval $accessTokenTTL,
        ClientEntityInterface $client,
        $userIdentifier,
        array $scopes = []
    ): AccessTokenEntityInterface {
        $accessToken = $this->accessTokenRepository->getNewToken($client, $scopes, $userIdentifier);
        $accessToken->setExpiryDateTime((new DateTimeImmutable())->add($accessTokenTTL));
        $accessToken->setPrivateKey($this->privateKey);

        if ($accessToken instanceof AccessTokenEntity) {
            $accessToken->setResource($this->tokenResource);
            $accessToken->setGrantId($this->tokenGrantId);
        }

        $attempts = self::MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS;

        while (true) {
            $accessToken->setIdentifier($this->generateUniqueIdentifier());

            try {
                $this->accessTokenRepository->persistNewAccessToken($accessToken);
                $this->issuedAccessTokenId = $accessToken->getIdentifier();

                return $accessToken;
            } catch (UniqueTokenIdentifierConstraintViolationException $e) {
                if (--$attempts === 0) {
                    throw $e;
                }
            }
        }
    }

    protected function validateRedirectUri(
        string $redirectUri,
        ClientEntityInterface $client,
        ServerRequestInterface $request
    ): void {
        $registered = array_values((array) $client->getRedirectUri());

        if (! (new RedirectUriMatcher())->matches($redirectUri, $registered, ! $client->isConfidential())) {
            $this->getEmitter()->emit(new RequestEvent(RequestEvent::CLIENT_AUTHENTICATION_FAILED, $request));

            throw OAuthServerException::invalidClient($request);
        }
    }

    /**
     * The resource a token request ends up bound to: the one already granted,
     * or a newly requested one when nothing was granted. A request for a
     * different resource than the one granted is refused.
     *
     * @param list<string> $scopes
     */
    protected function resolveTokenResource(ServerRequestInterface $request, ?string $granted, array $scopes): ?string
    {
        $requested = $this->getRequestParameter('resource', $request);

        if ($requested === null) {
            return $granted;
        }

        if (! is_string($requested)) {
            throw static::invalidTarget();
        }

        if ($granted !== null) {
            return $requested === $granted ? $granted : throw static::invalidTarget();
        }

        if (! $this->resources->has($requested) || ! $this->resources->allowsScopes($requested, $scopes)) {
            throw static::invalidTarget();
        }

        return $requested;
    }

    /**
     * Revokes every access and refresh token descending from one
     * authorization code; other grants of the same user and client stay valid.
     *
     * The family is marked revoked first: a request still issuing tokens into
     * it checks the mark once they are stored (see assertFamilyNotRevoked()),
     * so tokens stored after these updates are revoked as well.
     */
    protected function revokeTokenFamily(string $grantId): void
    {
        AuthCode::query()->whereKey($grantId)->update(['family_revoked' => true]);
        AccessToken::query()->where('grant_id', $grantId)->update(['revoked' => true]);
        RefreshToken::query()->where('grant_id', $grantId)->update(['revoked' => true]);
    }

    /**
     * Runs once the new tokens are stored: when the family was revoked in the
     * meantime, by a request that presented the same code or refresh token,
     * revokes them too and fails.
     *
     * @throws OAuthServerException
     */
    protected function assertFamilyNotRevoked(?string $grantId): void
    {
        if ($grantId === null || ! AuthCode::query()->whereKey($grantId)->where('family_revoked', true)->exists()) {
            return;
        }

        $this->revokeTokenFamily($grantId);

        throw OAuthServerException::invalidGrant('This grant has been revoked because its code or refresh token was used more than once.');
    }

    public static function invalidTarget(?string $redirectUri = null): OAuthServerException
    {
        return new OAuthServerException(
            'The requested resource is invalid, unknown, or not allowed for this request.',
            3,
            'invalid_target',
            400,
            null,
            $redirectUri,
        );
    }
}
