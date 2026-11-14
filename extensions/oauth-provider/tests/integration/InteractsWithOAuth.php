<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface;

/**
 * Drives the authorize / token endpoints the way an OAuth client would.
 * Use from a Flarum\Testing\integration\TestCase that exempts `login`,
 * `oauthProvider.authorize` and `oauthProvider.authorize.post` from CSRF.
 */
trait InteractsWithOAuth
{
    protected const string VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk-and-some-more';

    /**
     * @return array<string, mixed>
     */
    protected function clientRow(string $id, bool $confidential, array $redirectUris = ['https://testapp.example.com/callback'], array $extra = []): array
    {
        return [
            'id' => $id,
            'name' => "Client $id",
            'secret' => $confidential ? hash('sha256', 'test-secret') : null,
            'redirect_uris' => json_encode($redirectUris),
            'scopes' => null,
            'confidential' => (int) $confidential,
            'revoked' => 0,
            'source' => 'admin',
            'created_at' => Carbon::now()->toDateTimeString(),
            ...$extra,
        ];
    }

    protected function loginNormalUser(): ResponseInterface
    {
        $response = $this->send(
            $this->request('POST', '/login', [
                'json' => ['identification' => 'normal', 'password' => 'too-obscure'],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), 'Failed to log in');

        return $response;
    }

    /**
     * @param array<string, string> $query
     */
    protected function authorize(array $query, ?ResponseInterface $login = null, ?bool $approve = true): ResponseInterface
    {
        $login ??= $this->loginNormalUser();
        $path = '/oauth/authorize?'.http_build_query($query);

        $options = ['cookiesFrom' => $login];

        if ($approve !== null) {
            $options['json'] = ['oauth_consent_approved' => $approve ? '1' : '0'];
        }

        return $this->send(
            $this->request($approve === null ? 'GET' : 'POST', $path, $options)
                ->withQueryParams($query)
                ->withAttribute('bypassCsrfToken', true)
        );
    }

    /**
     * @return array<string, string>
     */
    protected function redirectParams(ResponseInterface $response): array
    {
        $this->assertEquals(302, $response->getStatusCode(), (string) $response->getBody());

        parse_str((string) parse_url($response->getHeaderLine('Location'), PHP_URL_QUERY), $params);

        return $params;
    }

    /**
     * @param array<string, string> $params
     */
    protected function token(array $params): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/oauth/token', ['json' => $params])
                ->withAttribute('bypassCsrfToken', true)
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body, (string) $response->getBody());

        return $body;
    }

    protected function challenge(string $verifier = self::VERIFIER): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    /**
     * Authorization code → token response for a public client using S256 PKCE.
     *
     * @param array<string, string> $query Extra authorize parameters.
     * @param array<string, string> $tokenParams Extra token request parameters.
     * @return array<string, mixed>
     */
    protected function publicClientTokens(string $clientId, array $query = [], array $tokenParams = []): array
    {
        $redirectUri = $query['redirect_uri'] ?? 'https://testapp.example.com/callback';

        $response = $this->exchangeCode($clientId, $this->publicClientCode($clientId, $query), $redirectUri, $tokenParams);

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response);
    }

    /**
     * An authorization code for a public client using S256 PKCE.
     *
     * @param array<string, string> $query Extra authorize parameters.
     */
    protected function publicClientCode(string $clientId, array $query = []): string
    {
        return $this->redirectParams($this->authorize([
            'client_id' => $clientId,
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            ...$query,
        ]))['code'];
    }

    /**
     * @param array<string, string> $tokenParams Extra token request parameters.
     */
    protected function exchangeCode(string $clientId, string $code, string $redirectUri = 'https://testapp.example.com/callback', array $tokenParams = []): ResponseInterface
    {
        return $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code' => $code,
            'code_verifier' => self::VERIFIER,
            ...$tokenParams,
        ]);
    }

    protected function refresh(string $clientId, string $refreshToken): ResponseInterface
    {
        return $this->token(['grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $refreshToken]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function jwtClaims(string $jwt): array
    {
        $payload = explode('.', $jwt)[1] ?? '';
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $this->assertIsArray($claims);

        return $claims;
    }
}
