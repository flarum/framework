<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Server;

use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\ClientRegistration\AllowedClients;
use Flarum\OAuthProvider\Scope\ScopeRegistry;

/**
 * The documents served at /.well-known/oauth-authorization-server (RFC 8414)
 * and /.well-known/openid-configuration (OIDC Discovery 1.0).
 */
class AuthorizationServerMetadata
{
    public function __construct(
        protected UrlGenerator $url,
        protected ScopeRegistry $scopes,
        protected AllowedClients $allowed,
    ) {
    }

    public function issuer(): string
    {
        return rtrim($this->url->to('forum')->base(), '/');
    }

    /**
     * @return array<string, mixed>
     */
    public function oauth(): array
    {
        $route = fn (string $name) => $this->url->to('forum')->route($name);
        $authMethods = ['client_secret_basic', 'client_secret_post', 'none'];

        return array_filter([
            'issuer' => $this->issuer(),
            'authorization_endpoint' => $route('oauthProvider.authorize'),
            'token_endpoint' => $route('oauthProvider.token'),
            'jwks_uri' => $route('oauthProvider.jwks'),
            'registration_endpoint' => $this->allowed->selfRegistered() ? $route('oauthProvider.register') : null,
            'revocation_endpoint' => $route('oauthProvider.revoke'),
            'scopes_supported' => array_keys($this->scopes->all()),
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'token_endpoint_auth_methods_supported' => $authMethods,
            'revocation_endpoint_auth_methods_supported' => $authMethods,
            'code_challenge_methods_supported' => ['S256'],
            'authorization_response_iss_parameter_supported' => true,
            'client_id_metadata_document_supported' => $this->allowed->selfRegistered() ?: null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    public function openId(): array
    {
        return [
            ...$this->oauth(),
            'userinfo_endpoint' => $this->url->to('forum')->route('oauthProvider.userinfo'),
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
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
        ];
    }
}
