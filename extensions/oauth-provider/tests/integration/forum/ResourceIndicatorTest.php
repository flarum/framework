<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\Extend\Resource;
use Flarum\OAuthProvider\Extend\Scope;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ResourceIndicatorTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    protected const string RESOURCE = 'http://localhost/mcp';

    public function setUp(): void
    {
        parent::setUp();

        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('login')
                ->exemptRoute('oauthProvider.authorize')
                ->exemptRoute('oauthProvider.authorize.post'),
            new Scope('mcp:read', 'Read the forum'),
            new Scope('mcp:write', 'Post to the forum'),
            new Scope('tasks:read', 'Read tasks'),
            new Resource(fn (UrlGenerator $url) => $url->to('forum')->base().'/mcp', ['mcp:read', 'mcp:write']),
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [$this->clientRow('public-client', false)],
        ]);
    }

    #[Test]
    public function resource_becomes_the_access_token_audience(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'mcp:read', 'resource' => self::RESOURCE]);

        $claims = $this->jwtClaims($tokens['access_token']);
        $this->assertSame([self::RESOURCE], (array) $claims['aud']);
        $this->assertSame(self::RESOURCE, AccessToken::query()->find($claims['jti'])->resource);
    }

    #[Test]
    public function without_resource_the_audience_is_the_client_id(): void
    {
        $tokens = $this->publicClientTokens('public-client');

        $claims = $this->jwtClaims($tokens['access_token']);
        $this->assertSame(['public-client'], (array) $claims['aud']);
        $this->assertNull(AccessToken::query()->find($claims['jti'])->resource);
    }

    #[Test]
    public function resource_can_be_given_on_the_token_request_only(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'mcp:read'], ['resource' => self::RESOURCE]);

        $this->assertSame([self::RESOURCE], (array) $this->jwtClaims($tokens['access_token'])['aud']);
    }

    #[Test]
    public function unknown_resource_is_invalid_target(): void
    {
        $params = $this->redirectParams($this->authorize([
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'mcp:read',
            'state' => 'xyz',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            'resource' => 'https://elsewhere.example.com/mcp',
        ]));

        $this->assertSame('invalid_target', $params['error']);
        $this->assertSame('xyz', $params['state']);
    }

    #[Test]
    public function scopes_of_another_resource_are_refused(): void
    {
        $params = $this->redirectParams($this->authorize([
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'openid mcp:read tasks:read',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            'resource' => self::RESOURCE,
        ]));

        $this->assertSame('invalid_scope', $params['error']);
    }

    #[Test]
    public function token_request_for_a_different_resource_is_invalid_target(): void
    {
        $code = $this->redirectParams($this->authorize([
            'client_id' => 'public-client',
            'response_type' => 'code',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'scope' => 'mcp:read',
            'code_challenge' => $this->challenge(),
            'code_challenge_method' => 'S256',
            'resource' => self::RESOURCE,
        ]))['code'];

        $response = $this->token([
            'grant_type' => 'authorization_code',
            'client_id' => 'public-client',
            'redirect_uri' => 'https://testapp.example.com/callback',
            'code' => $code,
            'code_verifier' => self::VERIFIER,
            'resource' => 'https://elsewhere.example.com/mcp',
        ]);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('invalid_target', $this->json($response)['error']);
    }

    #[Test]
    public function resource_is_carried_over_on_refresh(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'mcp:read mcp:write', 'resource' => self::RESOURCE]);

        $response = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => 'public-client',
            'refresh_token' => $tokens['refresh_token'],
            'scope' => 'mcp:read',
        ]);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $claims = $this->jwtClaims($this->json($response)['access_token']);

        $this->assertSame([self::RESOURCE], (array) $claims['aud']);
        $this->assertSame(['mcp:read'], $claims['scopes']);
        $this->assertSame(self::RESOURCE, AccessToken::query()->find($claims['jti'])->resource);
    }

    #[Test]
    public function refresh_for_a_different_resource_is_invalid_target(): void
    {
        $tokens = $this->publicClientTokens('public-client', ['scope' => 'mcp:read', 'resource' => self::RESOURCE]);

        $response = $this->token([
            'grant_type' => 'refresh_token',
            'client_id' => 'public-client',
            'refresh_token' => $tokens['refresh_token'],
            'resource' => 'https://elsewhere.example.com/mcp',
        ]);

        $this->assertSame('invalid_target', $this->json($response)['error']);
    }
}
