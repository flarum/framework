<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\Scope\ScopeRegistry;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * OpenID Connect Discovery 1.0 §4 — `/.well-known/openid-configuration`.
 *
 * Allows OIDC clients to auto-configure from an issuer URL without hard-coding
 * endpoint paths or supported features.
 */
class OpenIdConfigurationController implements RequestHandlerInterface
{
    public function __construct(
        protected UrlGenerator $url,
        protected ScopeRegistry $scopes,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $issuer = rtrim($this->url->to('forum')->base(), '/');

        return new JsonResponse([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer.'/oauth/authorize',
            'token_endpoint' => $issuer.'/oauth/token',
            'userinfo_endpoint' => $issuer.'/oauth/userinfo',
            'jwks_uri' => $issuer.'/.well-known/jwks.json',
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'scopes_supported' => array_keys($this->scopes->all()),
            'token_endpoint_auth_methods_supported' => [
                'client_secret_post',
                'client_secret_basic',
                'none',
            ],
            'claims_supported' => [
                'sub',
                'iss',
                'aud',
                'exp',
                'iat',
                'auth_time',
                'nonce',
                'name',
                'picture',
                'email',
                'email_verified',
            ],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'code_challenge_methods_supported' => ['S256', 'plain'],
        ]);
    }
}
