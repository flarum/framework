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
use DateTimeImmutable;
use Flarum\OAuthProvider\Models\AccessToken as AccessTokenModel;
use Flarum\OAuthProvider\Models\AuthCode as AuthCodeModel;
use Flarum\OAuthProvider\Server\Entity\AuthCodeEntity;
use Flarum\OAuthProvider\Server\RequestType\AuthorizationRequest;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest as LeagueAuthorizationRequest;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authorization code grant with OIDC-required nonce + auth_time propagation.
 *
 * The base league/oauth2-server grant doesn't know about OIDC. This subclass
 * stores the nonce + authentication time on both the auth code and the access
 * token rows so the ID token issued on code exchange can include them as
 * required by OpenID Connect Core 1.0 §3.1.3.7 and §2.
 *
 * Nonce lifecycle:
 * - Authorize: the controller reads `nonce` from the query and stores it on
 *   the auth code row *after* the parent grant has created the row.
 * - Token exchange: the parent grant creates a new access token; we then copy
 *   nonce + auth_time from the auth code row (found by the auth_code_id in the
 *   decrypted code payload) onto the row of the token that was just issued.
 * - ID token: the response type reads nonce + auth_time from the access token
 *   row when building claims.
 *
 * It also only accepts S256 PKCE, and carries the RFC 8707 `resource` from the
 * authorization request through the auth code row onto the access token.
 */
class OidcAuthCodeGrant extends AuthCodeGrant
{
    use IssuesResourceBoundTokens;

    /**
     * The resource of the auth code being issued; set for the duration of
     * completeAuthorizationRequest().
     */
    protected ?string $authCodeResource = null;

    public function validateAuthorizationRequest(ServerRequestInterface $request): AuthorizationRequest
    {
        $authRequest = parent::validateAuthorizationRequest($request);

        if ($authRequest->getCodeChallenge() !== null && $authRequest->getCodeChallengeMethod() !== 'S256') {
            throw new OAuthServerException(
                'The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.',
                3,
                'invalid_request',
                400,
                'Code challenge method must be `S256`.',
                $this->errorRedirectUri($authRequest),
            );
        }

        $resource = $this->getQueryStringParameter('resource', $request);

        if ($resource !== null) {
            if (! is_string($resource) || ! $this->resources->has($resource)) {
                throw static::invalidTarget($this->errorRedirectUri($authRequest));
            }

            $scopes = array_map(fn (ScopeEntityInterface $scope) => $scope->getIdentifier(), $authRequest->getScopes());

            if (! $this->resources->allowsScopes($resource, $scopes)) {
                throw new OAuthServerException(
                    'The requested scope is invalid, unknown, or malformed',
                    5,
                    'invalid_scope',
                    400,
                    'Scopes requested with a resource must belong to that resource.',
                    $this->errorRedirectUri($authRequest),
                );
            }
        }

        return AuthorizationRequest::fromLeague($authRequest, $resource);
    }

    public function completeAuthorizationRequest(LeagueAuthorizationRequest $authorizationRequest)
    {
        $this->authCodeResource = $authorizationRequest instanceof AuthorizationRequest
            ? $authorizationRequest->getResource()
            : null;

        try {
            return parent::completeAuthorizationRequest($authorizationRequest);
        } finally {
            $this->authCodeResource = null;
        }
    }

    /**
     * Mirrors AbstractGrant::issueAuthCode(), setting the resource before the
     * code is persisted.
     *
     * @param ScopeEntityInterface[] $scopes
     */
    protected function issueAuthCode(
        DateInterval $authCodeTTL,
        ClientEntityInterface $client,
        $userIdentifier,
        $redirectUri,
        array $scopes = []
    ): AuthCodeEntityInterface {
        $authCode = $this->authCodeRepository->getNewAuthCode();
        $authCode->setExpiryDateTime((new DateTimeImmutable())->add($authCodeTTL));
        $authCode->setClient($client);
        $authCode->setUserIdentifier($userIdentifier);

        if ($redirectUri !== null) {
            $authCode->setRedirectUri($redirectUri);
        }

        foreach ($scopes as $scope) {
            $authCode->addScope($scope);
        }

        if ($authCode instanceof AuthCodeEntity) {
            $authCode->setResource($this->authCodeResource);
        }

        $attempts = self::MAX_RANDOM_TOKEN_GENERATION_ATTEMPTS;

        while (true) {
            $authCode->setIdentifier($this->generateUniqueIdentifier());

            try {
                $this->authCodeRepository->persistNewAuthCode($authCode);

                return $authCode;
            } catch (UniqueTokenIdentifierConstraintViolationException $e) {
                if (--$attempts === 0) {
                    throw $e;
                }
            }
        }
    }

    protected function errorRedirectUri(LeagueAuthorizationRequest $authRequest): string
    {
        $redirectUri = $authRequest->getRedirectUri() ?? (array) $authRequest->getClient()->getRedirectUri();
        $redirectUri = is_array($redirectUri) ? (string) reset($redirectUri) : $redirectUri;

        return $authRequest->getState() === null
            ? $redirectUri
            : $this->makeRedirectUri($redirectUri, ['state' => $authRequest->getState()]);
    }

    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        $authCode = $this->findAuthCode($request);

        $this->tokenResource = $this->resolveTokenResource($request, $authCode?->resource, $authCode->scopes ?? []);

        try {
            $response = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);
            $issuedId = $this->issuedAccessTokenId;
        } finally {
            $this->tokenResource = null;
            $this->issuedAccessTokenId = null;
        }

        // After the parent has successfully minted an access token, copy
        // nonce + auth_time from the exchanged auth code onto exactly that token.
        if ($authCode === null || $issuedId === null) {
            return $response;
        }

        AccessTokenModel::query()
            ->whereKey($issuedId)
            ->update([
                'nonce' => $authCode->nonce,
                'auth_time' => $authCode->auth_time ?? Carbon::now(),
            ]);

        return $response;
    }

    protected function findAuthCode(ServerRequestInterface $request): ?AuthCodeModel
    {
        $encryptedAuthCode = $this->getRequestParameter('code', $request, null);

        if (! is_string($encryptedAuthCode)) {
            return null;
        }

        try {
            $payload = json_decode($this->decrypt($encryptedAuthCode), true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload) || empty($payload['auth_code_id'])) {
            return null;
        }

        return AuthCodeModel::query()->find($payload['auth_code_id']);
    }
}
