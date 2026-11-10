<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration;

use Carbon\Carbon;
use Flarum\Extend;

/**
 * Issues real access tokens through flarum/oauth-provider's authorization code
 * flow (public client, S256 PKCE), as an MCP client would obtain them.
 */
trait IssuesOAuthTokens
{
    protected string $codeVerifier = 'mcp-test-verifier-0123456789-abcdefghijklmnopqrstuvwxyz';

    protected function setUpOAuth(): void
    {
        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize.post'),
        );

        $this->prepareDatabase([
            'oauth_provider_clients' => [[
                'id' => 'mcp-client',
                'name' => 'MCP test client',
                'secret' => null,
                'redirect_uris' => json_encode(['https://client.example/callback']),
                'scopes' => null,
                'confidential' => 0,
                'revoked' => 0,
                'source' => 'admin',
                'created_at' => Carbon::now()->toDateTimeString(),
            ]],
        ]);
    }

    /**
     * Signs in as the normal user (password "too-obscure"), approves consent
     * and exchanges the code.
     */
    protected function issueOAuthToken(string $scope, string $resource = 'http://localhost/mcp'): string
    {
        $login = $this->send($this->request('POST', '/login', [
            'json' => ['identification' => 'normal', 'password' => 'too-obscure'],
        ]));
        $this->assertSame(200, $login->getStatusCode(), 'Failed to log in');

        $query = [
            'client_id' => 'mcp-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://client.example/callback',
            'scope' => $scope,
            'state' => 'xyz',
            'resource' => $resource,
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $this->codeVerifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
        ];

        $authorize = $this->send(
            $this->request('POST', '/oauth/authorize?'.http_build_query($query), [
                'cookiesFrom' => $login,
                'json' => ['oauth_consent_approved' => '1'],
            ])->withQueryParams($query)->withAttribute('bypassCsrfToken', true)
        );
        $this->assertSame(302, $authorize->getStatusCode(), (string) $authorize->getBody());

        parse_str((string) parse_url($authorize->getHeaderLine('Location'), PHP_URL_QUERY), $params);
        $this->assertArrayHasKey('code', $params, $authorize->getHeaderLine('Location'));

        $token = $this->send(
            $this->request('POST', '/oauth/token', ['json' => [
                'grant_type' => 'authorization_code',
                'client_id' => 'mcp-client',
                'redirect_uri' => 'https://client.example/callback',
                'code' => $params['code'],
                'code_verifier' => $this->codeVerifier,
                'resource' => $resource,
            ]])->withAttribute('bypassCsrfToken', true)
        );
        $this->assertSame(200, $token->getStatusCode(), (string) $token->getBody());

        return (string) json_decode((string) $token->getBody(), true)['access_token'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function jwtClaims(string $jwt): array
    {
        return (array) json_decode(base64_decode(strtr(explode('.', $jwt)[1] ?? '', '-_', '+/')), true);
    }
}
