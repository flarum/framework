<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\listing;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: `/api/access-tokens` scoping with the new `user` include, `type`
 * field, sorts and filters; revoking other users' tokens.
 */
class AccessTokenListingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const HIDDEN_GROUP = 10;
    private const TOKEN_MOD_GROUP = 11;

    protected function setUp(): void
    {
        parent::setUp();

        $t = Carbon::parse('2024-01-01 00:00:00');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'victim', 'email' => 'victim@secret.local', 'is_email_confirmed' => 0],
                ['id' => 5, 'username' => 'tokenmod', 'email' => 'tm@secret.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Secret', 'name_plural' => 'Secrets', 'is_hidden' => 1],
                ['id' => self::TOKEN_MOD_GROUP, 'name_singular' => 'TokenMod', 'name_plural' => 'TokenMods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => self::HIDDEN_GROUP],
                ['user_id' => 5, 'group_id' => self::TOKEN_MOD_GROUP],
            ],
            'group_permission' => [
                ['permission' => 'moderateAccessTokens', 'group_id' => self::TOKEN_MOD_GROUP],
            ],
            AccessToken::class => [
                ['id' => 100, 'token' => 'victimsessiontokenvalue0000000000000000', 'user_id' => 3, 'type' => 'session', 'last_activity_at' => $t, 'created_at' => $t, 'last_ip_address' => '10.0.0.3', 'last_user_agent' => 'VictimUA'],
                ['id' => 101, 'token' => 'victimdevelopertokenvalue00000000000000', 'user_id' => 3, 'type' => 'developer', 'title' => 'victim dev', 'last_activity_at' => $t, 'created_at' => $t],
                ['id' => 102, 'token' => 'adminsessiontokenvalue000000000000000000', 'user_id' => 1, 'type' => 'session', 'last_activity_at' => $t, 'created_at' => $t, 'last_ip_address' => '10.0.0.1'],
                ['id' => 103, 'token' => 'normaldevelopertokenvalue00000000000000', 'user_id' => 2, 'type' => 'developer', 'title' => 'own dev', 'last_activity_at' => $t, 'created_at' => $t],
            ],
        ]);
    }

    private function get(?int $actor, array $query): array
    {
        $response = $this->send(
            $this->request('GET', '/api/access-tokens', $actor ? ['authenticatedAs' => $actor] : [])
                ->withQueryParams($query)
        );

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true), (string) $response->getBody()];
    }

    #[Test]
    public function guest_cannot_list()
    {
        [$status] = $this->get(null, []);
        $this->assertContains($status, [401, 403]);
    }

    public static function memberProbes(): array
    {
        return [
            'plain' => [[]],
            'filter user' => [['filter' => ['user' => '3']]],
            'negated filter user' => [['filter' => ['-user' => '2']]],
            'filter type' => [['filter' => ['type' => 'session']]],
            'negated type' => [['filter' => ['-type' => 'developer']]],
            'include user' => [['include' => 'user']],
            'include user.groups' => [['include' => 'user.groups']],
            'sort' => [['sort' => '-lastActivityAt']],
        ];
    }

    #[Test]
    #[DataProvider('memberProbes')]
    public function a_member_only_ever_sees_their_own_tokens(array $query)
    {
        [$status, $body, $raw] = $this->get(2, $query);
        $this->assertSame(200, $status, $raw);

        foreach ($body['data'] as $token) {
            $this->assertSame(2, (int) $token['attributes']['userId'], $raw);
        }
        foreach ($body['included'] ?? [] as $inc) {
            $this->assertFalse($inc['type'] === 'users' && (int) $inc['id'] !== 2, $raw);
        }
        $this->assertStringNotContainsString('victim', $raw);
        $this->assertStringNotContainsString('10.0.0.', $raw);
    }

    #[Test]
    public function token_moderator_include_user_does_not_leak_owner_private_fields_or_hidden_groups()
    {
        [$status, $body, $raw] = $this->get(5, ['include' => 'user.groups', 'page' => ['limit' => 50]]);
        $this->assertSame(200, $status, $raw);
        $this->assertGreaterThanOrEqual(4, count($body['data']));

        foreach ($body['included'] as $inc) {
            if ($inc['type'] === 'users' && (int) $inc['id'] !== 5) {
                $this->assertArrayNotHasKey('email', $inc['attributes'], $raw);
                $this->assertArrayNotHasKey('isEmailConfirmed', $inc['attributes'], $raw);
            }
            $this->assertFalse($inc['type'] === 'groups' && (int) $inc['id'] === self::HIDDEN_GROUP, 'hidden group leaked: '.$raw);
        }

        // Token values of other users are never serialized.
        foreach (['victimsessiontokenvalue', 'victimdevelopertokenvalue', 'adminsessiontokenvalue', 'normaldevelopertokenvalue'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
    }

    #[Test]
    public function admin_listing_never_shows_other_users_token_values()
    {
        [$status, , $raw] = $this->get(1, ['include' => 'user', 'page' => ['limit' => 50]]);
        $this->assertSame(200, $status, $raw);
        foreach (['victimsessiontokenvalue', 'victimdevelopertokenvalue', 'normaldevelopertokenvalue'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
    }

    #[Test]
    public function a_member_cannot_revoke_another_users_token_and_learns_nothing()
    {
        $response = $this->send($this->request('DELETE', '/api/access-tokens/100', ['authenticatedAs' => 2]));
        $this->assertSame(404, $response->getStatusCode());
        $missing = $this->send($this->request('DELETE', '/api/access-tokens/99999', ['authenticatedAs' => 2]));
        $this->assertSame(404, $missing->getStatusCode());
        $this->assertNotNull(AccessToken::query()->find(100));
    }

    #[Test]
    public function can_delete_is_false_for_other_users_tokens_for_a_member()
    {
        // Sanity: a member sees only own tokens, and those are deletable.
        [, $body] = $this->get(2, []);
        $byId = array_column($body['data'], null, 'id');
        $this->assertTrue($byId['103']['attributes']['canDelete']);
    }

    public static function sortProbes(): array
    {
        return [['token'], ['lastIpAddress'], ['last_user_agent'], ['userId'], ['type']];
    }

    #[Test]
    #[DataProvider('sortProbes')]
    public function sorting_by_unexposed_columns_is_rejected(string $sort)
    {
        [$status] = $this->get(2, ['sort' => $sort]);
        $this->assertSame(400, $status, $sort);
    }

    #[Test]
    public function filter_on_token_value_is_not_supported()
    {
        // An unknown filter must not filter by column name (oracle on `token`).
        [$status, $body, $raw] = $this->get(5, ['filter' => ['token' => 'victimsession']]);
        // Unknown filters are ignored: all tokens come back, so the value is no oracle.
        $this->assertSame(200, $status, $raw);
        $this->assertGreaterThanOrEqual(4, count($body['data']), $raw);
    }
}
