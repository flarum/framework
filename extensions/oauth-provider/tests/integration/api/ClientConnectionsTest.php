<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration\api;

use Carbon\Carbon;
use Flarum\OAuthProvider\Tests\integration\InteractsWithOAuth;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Who has connected an application, for admins: a count on each client, and
 * the members behind it through the users list's `oauthClient` filter.
 */
class ClientConnectionsTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use InteractsWithOAuth;

    /**
     * A metadata-document client: its id is a URL.
     */
    protected const string CIMD = 'https://client.example.com/metadata.json';

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
                ['id' => 4, 'username' => 'gone', 'email' => 'gone@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_permission' => [
                ['permission' => 'searchUsers', 'group_id' => 3],
            ],
            'oauth_provider_clients' => [
                $this->clientRow('admin-client', true),
                $this->clientRow(self::CIMD, false, extra: ['name' => 'Example MCP Client', 'source' => 'metadata_document']),
            ],
            'oauth_provider_user_consents' => [
                ['id' => 1, 'user_id' => 2, 'client_id' => self::CIMD, 'scopes' => '["mcp:read","mcp:write"]', 'revoked' => 0, 'created_at' => $now->copy()->subDays(2)->toDateTimeString()],
                ['id' => 2, 'user_id' => 3, 'client_id' => self::CIMD, 'scopes' => '["mcp:read"]', 'revoked' => 0, 'created_at' => $now->copy()->subDay()->toDateTimeString()],
                // Revoked: not a connection any more.
                ['id' => 3, 'user_id' => 4, 'client_id' => self::CIMD, 'scopes' => '["mcp:read"]', 'revoked' => 1, 'created_at' => $now->toDateTimeString()],
                ['id' => 4, 'user_id' => 2, 'client_id' => 'admin-client', 'scopes' => '["openid"]', 'revoked' => 0, 'created_at' => $now->toDateTimeString()],
            ],
            'oauth_provider_access_tokens' => [
                ['id' => 'at-2-cimd', 'client_id' => self::CIMD, 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later, 'last_used_at' => $now->copy()->subHour()->toDateTimeString()],
                ['id' => 'at-3-cimd-old', 'client_id' => self::CIMD, 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later, 'last_used_at' => $now->copy()->subDays(3)->toDateTimeString()],
                ['id' => 'at-3-cimd', 'client_id' => self::CIMD, 'user_id' => 3, 'revoked' => 0, 'expires_at' => $later, 'last_used_at' => $now->copy()->subMinute()->toDateTimeString()],
                ['id' => 'at-2-admin', 'client_id' => 'admin-client', 'user_id' => 2, 'revoked' => 0, 'expires_at' => $later],
            ],
        ]);
    }

    /**
     * @param array<string, string> $filter
     * @return list<int>
     */
    protected function userIds(array $filter, int $actorId = 1): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', ['authenticatedAs' => $actorId])->withQueryParams(['filter' => $filter])
        );

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $ids = array_map('intval', array_column($this->json($response)['data'], 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function each_client_says_how_many_members_connected_it_and_when_it_was_last_used(): void
    {
        $response = $this->send($this->request('GET', '/api/oauth-provider-clients', ['authenticatedAs' => 1]));

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
        $clients = array_column($this->json($response)['data'], 'attributes', 'id');

        $this->assertSame(2, $clients[self::CIMD]['connectionCount']);
        $this->assertEqualsWithDelta(Carbon::now()->subMinute()->getTimestamp(), Carbon::parse($clients[self::CIMD]['lastUsedAt'])->getTimestamp(), 5);

        $this->assertSame(1, $clients['admin-client']['connectionCount']);
        $this->assertNull($clients['admin-client']['lastUsedAt']);
    }

    #[Test]
    public function admins_find_the_members_who_connected_a_client(): void
    {
        // The revoked consent is not a connection.
        $this->assertSame([2, 3], $this->userIds(['oauthClient' => self::CIMD]));
        $this->assertSame([2], $this->userIds(['oauthClient' => 'admin-client']));
    }

    #[Test]
    public function admins_find_the_members_who_did_not_connect_a_client(): void
    {
        $this->assertSame([1, 4], $this->userIds(['-oauthClient' => self::CIMD]));
    }

    #[Test]
    public function only_admins_find_who_connected_a_client(): void
    {
        $this->assertSame([], $this->userIds(['oauthClient' => self::CIMD], actorId: 2));
        $this->assertSame([], $this->userIds(['-oauthClient' => self::CIMD], actorId: 2));
    }
}
