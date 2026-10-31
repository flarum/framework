<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\forum;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Http\UrlGenerator;
use Flarum\OAuthProvider\Extend\Resource;
use Flarum\OAuthProvider\Extend\Scope;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\Client;
use Flarum\OAuthProvider\ResourceServer\TokenValidator;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class TokenValidatorTest extends TestCase
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
            new Resource(fn (UrlGenerator $url) => $url->to('forum')->base().'/mcp', ['mcp:read']),
        );

        $this->extension('flarum-oauth-provider');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            'oauth_provider_clients' => [$this->clientRow('public-client', false)],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function validator(): TokenValidator
    {
        return $this->app()->getContainer()->make(TokenValidator::class);
    }

    protected function accessToken(): string
    {
        return $this->publicClientTokens('public-client', ['scope' => 'openid mcp:read', 'resource' => self::RESOURCE])['access_token'];
    }

    #[Test]
    public function valid_token_resolves_user_client_and_scopes(): void
    {
        $jwt = $this->accessToken();

        $validated = $this->validator()->validate($jwt, self::RESOURCE);

        $this->assertNotNull($validated);
        $this->assertSame(2, $validated->user->id);
        $this->assertSame('public-client', $validated->clientId);
        $this->assertSame(['openid', 'mcp:read'], $validated->scopes);
        $this->assertSame($this->jwtClaims($jwt)['jti'], $validated->tokenId);
        $this->assertSame(self::RESOURCE, $validated->resource);
    }

    #[Test]
    public function validator_is_a_singleton(): void
    {
        $this->assertSame($this->validator(), $this->validator());
    }

    #[Test]
    public function token_for_another_audience_is_refused(): void
    {
        $jwt = $this->accessToken();

        $this->assertNull($this->validator()->validate($jwt, 'http://localhost/other'));
    }

    #[Test]
    public function token_without_resource_is_refused(): void
    {
        $jwt = $this->publicClientTokens('public-client')['access_token'];

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));
        $this->assertNull($this->validator()->validate($jwt, 'public-client'));
    }

    #[Test]
    public function expired_token_is_refused(): void
    {
        $jwt = $this->accessToken();

        Carbon::setTestNow(Carbon::now()->addHours(2));

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));
    }

    #[Test]
    public function revoked_token_is_refused(): void
    {
        $jwt = $this->accessToken();

        AccessToken::query()->where('id', $this->jwtClaims($jwt)['jti'])->update(['revoked' => true]);

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));
    }

    #[Test]
    public function token_of_a_revoked_client_is_refused(): void
    {
        $jwt = $this->accessToken();

        Client::query()->where('id', 'public-client')->update(['revoked' => true]);

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));
    }

    #[Test]
    public function tampered_and_malformed_tokens_are_refused(): void
    {
        $jwt = $this->accessToken();
        [$header, $payload, $signature] = explode('.', $jwt);
        $claims = $this->jwtClaims($jwt);
        $claims['sub'] = '1';
        $forged = $header.'.'.rtrim(strtr(base64_encode(json_encode($claims)), '+/', '-_'), '=').'.'.$signature;

        $this->assertNull($this->validator()->validate($forged, self::RESOURCE));
        $this->assertNull($this->validator()->validate('not-a-jwt', self::RESOURCE));
        $this->assertNull($this->validator()->validate('', self::RESOURCE));
    }

    #[Test]
    public function token_of_a_deleted_user_is_refused(): void
    {
        $jwt = $this->accessToken();
        $tokenId = $this->jwtClaims($jwt)['jti'];

        // Keep the token row; only the user lookup should fail.
        AccessToken::query()->where('id', $tokenId)->update(['user_id' => 999]);

        $this->assertNull($this->validator()->validate($jwt, self::RESOURCE));
    }

    #[Test]
    public function last_used_at_is_touched_at_most_once_a_minute(): void
    {
        $jwt = $this->accessToken();
        $tokenId = $this->jwtClaims($jwt)['jti'];

        $this->assertNull(AccessToken::query()->find($tokenId)->last_used_at);

        $first = Carbon::now()->addSecond()->startOfSecond();
        Carbon::setTestNow($first);
        $this->assertNotNull($this->validator()->validate($jwt, self::RESOURCE));
        $this->assertEquals($first, AccessToken::query()->find($tokenId)->last_used_at);

        Carbon::setTestNow($first->copy()->addSeconds(30));
        $this->validator()->validate($jwt, self::RESOURCE);
        $this->assertEquals($first, AccessToken::query()->find($tokenId)->last_used_at);

        Carbon::setTestNow($first->copy()->addSeconds(61));
        $this->validator()->validate($jwt, self::RESOURCE);
        $this->assertEquals($first->copy()->addSeconds(61), AccessToken::query()->find($tokenId)->last_used_at);
    }
}
