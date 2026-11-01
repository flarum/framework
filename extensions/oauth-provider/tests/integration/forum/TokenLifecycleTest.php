<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TokenLifecycleTest extends TestCase
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
                ->exemptRoute('oauthProvider.authorize.post'),
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [
                $this->clientRow('public-client', false),
                $this->clientRow('restricted-client', false, extra: ['scopes' => json_encode(['openid'])]),
            ],
        ]);
    }

    #[Test]
    public function an_admin_client_only_gets_its_allowed_scopes(): void
    {
        $tokens = $this->publicClientTokens('restricted-client', ['scope' => 'openid profile email']);

        $this->assertSame(['openid'], $this->jwtClaims($tokens['access_token'])['scopes'] ?? null, 'Scopes the admin did not allow must not be granted');
    }

    #[Test]
    public function an_admin_client_without_allowed_scopes_is_unrestricted(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'openid profile email']);

        $this->assertSame(['openid', 'profile', 'email'], $this->jwtClaims($tokens['access_token'])['scopes'] ?? null);
    }

    #[Test]
    public function userinfo_requires_the_openid_scope(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'profile email']);

        $response = $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']));

        $this->assertSame(403, $response->getStatusCode(), 'UserInfo is for OpenID Connect tokens only: '.$response->getBody());
        $this->assertStringContainsString('error="insufficient_scope"', $response->getHeaderLine('WWW-Authenticate'));
        $this->assertStringStartsWith('Bearer', $response->getHeaderLine('WWW-Authenticate'));
    }

    #[Test]
    public function userinfo_refuses_tokens_of_a_revoked_client(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'openid profile']);

        $before = $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']));
        $this->assertSame(200, $before->getStatusCode());

        Client::query()->whereKey('public-client')->update(['revoked' => true]);

        $after = $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']));
        $this->assertSame(401, $after->getStatusCode(), 'A revoked client\'s tokens must stop working: '.$after->getBody());
    }

    #[Test]
    public function refreshed_id_token_keeps_the_original_auth_time(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'openid profile']);
        $authTime = $this->jwtClaims($tokens['id_token'])['auth_time'] ?? null;
        $this->assertIsInt($authTime, 'The first ID token carries auth_time');

        $refreshed = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => 'public-client',
            'refresh_token' => $tokens['refresh_token'],
        ]);
        $this->assertSame(200, $refreshed->getStatusCode(), (string) $refreshed->getBody());

        $idToken = $this->json($refreshed)['id_token'] ?? null;
        $this->assertIsString($idToken, 'A refresh with the openid scope returns an ID token');
        $this->assertSame($authTime, $this->jwtClaims($idToken)['auth_time'] ?? null, 'auth_time must survive a refresh');
    }

    #[Test]
    public function a_revoked_client_cannot_refresh(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'openid profile']);

        Client::query()->whereKey('public-client')->update(['revoked' => true]);

        $refreshed = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => 'public-client',
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $this->assertSame(401, $refreshed->getStatusCode(), (string) $refreshed->getBody());
        $this->assertSame('invalid_client', $this->json($refreshed)['error']);
    }

    #[Test]
    public function userinfo_refuses_tokens_of_a_deleted_client(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'openid profile']);

        Client::query()->whereKey('public-client')->delete();

        $after = $this->send($this->request('GET', '/oauth/userinfo')->withHeader('Authorization', 'Bearer '.$tokens['access_token']));
        $this->assertSame(401, $after->getStatusCode());
    }
}
