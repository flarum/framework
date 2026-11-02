<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class RevocationTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post')
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [
                $this->clientRow('public-client', false),
                $this->clientRow('other-client', false),
                $this->clientRow('confidential-client', true),
            ],
        ]);
    }

    /**
     * Sent without CSRF bypass on purpose: the route is exempt in production.
     *
     * @param array<string, string> $params
     */
    protected function revoke(array $params, array $headers = []): ResponseInterface
    {
        $request = $this->request('POST', '/oauth/revoke', ['json' => $params]);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->send($request);
    }

    protected function tokenRow(string $jwt): AccessToken
    {
        return AccessToken::query()->findOrFail($this->jwtClaims($jwt)['jti']);
    }

    #[Test]
    public function revoking_an_access_token_revokes_it_and_its_refresh_token(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $response = $this->revoke(['token' => $tokens['access_token'], 'token_type_hint' => 'access_token', 'client_id' => 'public-client']);

        $this->assertSame(200, $response->getStatusCode());
        $row = $this->tokenRow($tokens['access_token']);
        $this->assertTrue($row->revoked);
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $row->id)->firstOrFail()->revoked);
    }

    #[Test]
    public function revoking_a_refresh_token_revokes_the_grant(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $response = $this->revoke(['token' => $tokens['refresh_token'], 'client_id' => 'public-client']);

        $this->assertSame(200, $response->getStatusCode());
        $row = $this->tokenRow($tokens['access_token']);
        $this->assertTrue($row->revoked);
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $row->id)->firstOrFail()->revoked);

        $refresh = $this->token(['grant_type' => 'refresh_token', 'client_id' => 'public-client', 'refresh_token' => $tokens['refresh_token']]);
        $this->assertSame(400, $refresh->getStatusCode());
        $this->assertSame('invalid_grant', $this->json($refresh)['error']);
    }

    #[Test]
    public function reusing_a_rotated_refresh_token_revokes_the_whole_grant(): void
    {
        $first = $this->publicClientTokens('public-client');
        $unrelated = $this->publicClientTokens('other-client');

        $rotated = $this->token(['grant_type' => 'refresh_token', 'client_id' => 'public-client', 'refresh_token' => $first['refresh_token']]);
        $this->assertSame(200, $rotated->getStatusCode(), (string) $rotated->getBody());
        $current = $this->json($rotated);
        $this->assertFalse($this->tokenRow($current['access_token'])->revoked);

        // The old refresh token shows up again: someone kept a copy.
        $reuse = $this->token(['grant_type' => 'refresh_token', 'client_id' => 'public-client', 'refresh_token' => $first['refresh_token']]);
        $this->assertSame(400, $reuse->getStatusCode());
        $this->assertSame('invalid_grant', $this->json($reuse)['error']);

        $row = $this->tokenRow($current['access_token']);
        $this->assertTrue($row->revoked);
        $this->assertTrue(RefreshToken::query()->where('access_token_id', $row->id)->firstOrFail()->revoked);
        $this->assertSame(0, AccessToken::query()->where('client_id', 'public-client')->where('revoked', false)->count());

        $refresh = $this->token(['grant_type' => 'refresh_token', 'client_id' => 'public-client', 'refresh_token' => $current['refresh_token']]);
        $this->assertSame(400, $refresh->getStatusCode());

        // Another client's grant for the same user is untouched.
        $this->assertFalse($this->tokenRow($unrelated['access_token'])->revoked);
    }

    #[Test]
    public function a_revoked_refresh_token_of_another_client_revokes_nothing(): void
    {
        $tokens = $this->publicClientTokens('public-client');
        $this->revoke(['token' => $tokens['refresh_token'], 'client_id' => 'public-client']);
        $other = $this->publicClientTokens('other-client');

        $response = $this->token(['grant_type' => 'refresh_token', 'client_id' => 'other-client', 'refresh_token' => $tokens['refresh_token']]);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertFalse($this->tokenRow($other['access_token'])->revoked);
    }

    #[Test]
    public function another_clients_token_is_left_alone_but_still_answers_200(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $response = $this->revoke(['token' => $tokens['access_token'], 'client_id' => 'other-client']);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($this->tokenRow($tokens['access_token'])->revoked);
    }

    #[Test]
    public function unknown_token_answers_200(): void
    {
        $this->assertSame(200, $this->revoke(['token' => 'garbage', 'client_id' => 'public-client'])->getStatusCode());
    }

    #[Test]
    public function confidential_client_must_authenticate(): void
    {
        $response = $this->revoke(['token' => 'garbage', 'client_id' => 'confidential-client', 'client_secret' => 'wrong']);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('invalid_client', $this->json($response)['error']);

        $response = $this->revoke(['token' => 'garbage'], ['Authorization' => 'Basic '.base64_encode('confidential-client:test-secret')]);
        $this->assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function missing_token_is_invalid_request(): void
    {
        $response = $this->revoke(['client_id' => 'public-client']);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_request', $this->json($response)['error']);
    }
}
