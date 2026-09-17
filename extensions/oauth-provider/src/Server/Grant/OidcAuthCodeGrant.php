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
use Flarum\OAuthProvider\Models\AccessToken as AccessTokenModel;
use Flarum\OAuthProvider\Models\AuthCode as AuthCodeModel;
use League\OAuth2\Server\Grant\AuthCodeGrant;
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
 *   nonce + auth_time from the auth code row onto the access token row, keyed
 *   by the auth_code_id we extract from the decrypted code payload.
 * - ID token: the response type reads nonce + auth_time from the access token
 *   row when building claims.
 */
class OidcAuthCodeGrant extends AuthCodeGrant
{
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL
    ) {
        $response = parent::respondToAccessTokenRequest($request, $responseType, $accessTokenTTL);

        // After the parent has successfully minted an access token, copy
        // nonce + auth_time from the exchanged auth code onto it.
        $encryptedAuthCode = $this->getRequestParameter('code', $request, null);

        if (! is_string($encryptedAuthCode)) {
            return $response;
        }

        try {
            $payload = json_decode($this->decrypt($encryptedAuthCode), true);
        } catch (\Throwable) {
            return $response;
        }

        if (! is_array($payload) || empty($payload['auth_code_id'])) {
            return $response;
        }

        /** @var AuthCodeModel|null $authCode */
        $authCode = AuthCodeModel::query()->find($payload['auth_code_id']);

        if ($authCode === null) {
            return $response;
        }

        // The access token that was just persisted. There should be exactly
        // one non-revoked access token freshly issued for this user/client in
        // the last moment — find the newest one.
        /** @var AccessTokenModel|null $accessToken */
        $accessToken = AccessTokenModel::query()
            ->where('user_id', $authCode->user_id)
            ->where('client_id', $authCode->client_id)
            ->orderByDesc('created_at')
            ->first();

        if ($accessToken !== null) {
            $accessToken->nonce = $authCode->nonce;
            $accessToken->auth_time = $authCode->auth_time ?? new DateTimeImmutable();
            $accessToken->save();
        }

        return $response;
    }
}
