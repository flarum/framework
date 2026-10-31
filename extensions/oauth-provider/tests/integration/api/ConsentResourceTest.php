<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\api;

use Carbon\Carbon;
use Flarum\OAuthProvider\Models\AccessToken;
use Flarum\OAuthProvider\Models\AuthCode;
use Flarum\OAuthProvider\Models\Consent;
use Flarum\OAuthProvider\Models\RefreshToken;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class ConsentResourceTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    public function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-oauth-provider');

        $now = Carbon::now();
        $later = $now->copy()->addHour()->toDateTimeString();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            'oauth_provider_clients' => [
                $this->clientRow('admin-client', true),
                $this->clientRow('https://client.example.com/metadata.json', false, extra: [
                    'name' => 'Example MCP Client',
                    'source' => 'metadata_document',
                    'logo_uri' => 'https://client.example.com/logo.png',
                ]),
            ],
            'oauth_provider_user_consents' => [
                ['id' => 1, 'user_id' => 2, 'client_id' => 'https://client.example.com/metadata.json', 'scopes' => '["mcp:read"]', 'revoked' => 0, 'created_at' => $now->toDateTimeString()],
                ['id' => 2, 'user_id' => 2, 'client_id' => 'admin-client', 'scopes' => '["openid"]', 'revoked' => 1, 'created_at' => $now->toDateTimeString()],
                ['id' => 3, 'user_id' => 3, 'client_id' => 'admin-client', 'scopes' => '["openid"]', 'revoked' => 0, 'created_at' => $now->toDateTimeString()],
                ['id' => 4, 'user_id' => 1, 'client_id' => 'admin-client', 'scopes' => '["openid"]', 'revoked' => 0, 'created_at' => $now->toDateTimeString()],
            ],
            'oauth_provider_access_tokens' => [
                ['id' => 'at-2-cimd-1', 'client_id' => 'https://client.example.com/metadata.json', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later, 'last_used_at' => $now->copy()->subDay()->toDateTimeString()],
                ['id' => 'at-2-cimd-2', 'client_id' => 'https://client.example.com/metadata.json', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later, 'last_used_at' => $now->copy()->subMinute()->toDateTimeString()],
                ['id' => 'at-3-cimd', 'client_id' => 'https://client.example.com/metadata.json', 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later],
                ['id' => 'at-3-admin', 'client_id' => 'admin-client', 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later],
            ],
            'oauth_provider_refresh_tokens' => [
                ['id' => 'rt-2-cimd', 'access_token_id' => 'at-2-cimd-2', 'revoked' => 0, 'expires_at' => $later],
                ['id' => 'rt-3-cimd', 'access_token_id' => 'at-3-cimd', 'revoked' => 0, 'expires_at' => $later],
            ],
            'oauth_provider_auth_codes' => [
                ['id' => 'ac-2-cimd', 'client_id' => 'https://client.example.com/metadata.json', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later],
            ],
        ]);
    }

    #[Test]
    public function guest_cannot_list_consents(): void
    {
        $this->assertSame(401, $this->send($this->request('GET', '/api/oauth-consents'))->getStatusCode());
    }

    #[Test]
    public function user_lists_only_their_active_consents(): void
    {
        $response = $this->send($this->request('GET', '/api/oauth-consents', ['authenticatedAs' => 2]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $data = $this->json($response)['data'];

        $this->assertCount(1, $data);
        $this->assertSame('1', $data[0]['id']);

        $attributes = $data[0]['attributes'];
        $this->assertSame('Example MCP Client', $attributes['clientName']);
        $this->assertSame('https://client.example.com/logo.png', $attributes['clientLogoUrl']);
        $this->assertSame('client.example.com', $attributes['clientSourceHost']);
        $this->assertSame(['mcp:read'], $attributes['scopes']);
        $this->assertNotNull($attributes['createdAt']);
        $this->assertEqualsWithDelta(Carbon::now()->subMinute()->getTimestamp(), Carbon::parse($attributes['lastUsedAt'])->getTimestamp(), 5);
    }

    #[Test]
    public function admin_sees_only_their_own_consents(): void
    {
        $response = $this->send($this->request('GET', '/api/oauth-consents', ['authenticatedAs' => 1]));

        $ids = array_column($this->json($response)['data'], 'id');
        $this->assertSame(['4'], $ids);
    }

    #[Test]
    public function user_cannot_delete_another_users_consent(): void
    {
        $response = $this->send($this->request('DELETE', '/api/oauth-consents/3', ['authenticatedAs' => 2]));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertNotNull(Consent::query()->find(3));
        $this->assertFalse(AccessToken::query()->findOrFail('at-3-admin')->revoked);
    }

    #[Test]
    public function admin_cannot_delete_another_users_consent(): void
    {
        $response = $this->send($this->request('DELETE', '/api/oauth-consents/3', ['authenticatedAs' => 1]));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertNotNull(Consent::query()->find(3));
    }

    #[Test]
    public function revoking_a_consent_revokes_that_users_tokens_for_the_client(): void
    {
        $response = $this->send($this->request('DELETE', '/api/oauth-consents/1', ['authenticatedAs' => 2]));

        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(Consent::query()->find(1));

        $this->assertTrue(AccessToken::query()->findOrFail('at-2-cimd-1')->revoked);
        $this->assertTrue(AccessToken::query()->findOrFail('at-2-cimd-2')->revoked);
        $this->assertTrue(RefreshToken::query()->findOrFail('rt-2-cimd')->revoked);
        $this->assertTrue(AuthCode::query()->findOrFail('ac-2-cimd')->revoked);

        // Another user's grant to the same client is untouched.
        $this->assertFalse(AccessToken::query()->findOrFail('at-3-cimd')->revoked);
        $this->assertFalse(RefreshToken::query()->findOrFail('rt-3-cimd')->revoked);
    }
}
