<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The admin access tokens page lists `/api/access-tokens?include=user`, filters by user and type
 * and sorts by the dates.
 */
class ListWithUserTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const int HIDDEN_GROUP = 10;
    private const int TOKEN_MODERATORS = 11;
    private const int OWNER_COUNT = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $t = Carbon::parse('2024-01-01 00:00:00');
        $users = [
            $this->normalUser(),
            ['id' => 3, 'username' => 'owner', 'email' => 'owner@private.local', 'is_email_confirmed' => 0],
            ['id' => 5, 'username' => 'tokenmod', 'email' => 'tm@machine.local', 'is_email_confirmed' => 1],
        ];
        $tokens = [
            ['id' => 100, 'token' => 'ownersessiontokenvalue00000000000000000', 'user_id' => 3, 'type' => 'session', 'last_activity_at' => $t, 'created_at' => $t, 'last_ip_address' => '10.0.0.3'],
            ['id' => 101, 'token' => 'ownerdevelopertokenvalue000000000000000', 'user_id' => 3, 'type' => 'developer', 'title' => 'owner dev', 'last_activity_at' => $t, 'created_at' => $t],
            ['id' => 103, 'token' => 'normaldevelopertokenvalue00000000000000', 'user_id' => 2, 'type' => 'developer', 'title' => 'own dev', 'last_activity_at' => $t, 'created_at' => $t],
        ];

        for ($i = 0; $i < self::OWNER_COUNT; $i++) {
            $userId = 20 + $i;
            $users[] = ['id' => $userId, 'username' => 'owner'.$userId, 'email' => 'owner'.$userId.'@machine.local', 'is_email_confirmed' => 1];
            $tokens[] = ['id' => 200 + $i, 'token' => 'token'.$userId, 'user_id' => $userId, 'type' => 'developer', 'last_activity_at' => $t, 'created_at' => $t];
        }

        $this->prepareDatabase([
            User::class => $users,
            AccessToken::class => $tokens,
            Group::class => [
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Hidden', 'name_plural' => 'Hiddens', 'is_hidden' => 1],
                ['id' => self::TOKEN_MODERATORS, 'name_singular' => 'TokenMod', 'name_plural' => 'TokenMods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => self::HIDDEN_GROUP],
                ['user_id' => 5, 'group_id' => self::TOKEN_MODERATORS],
            ],
            'group_permission' => [
                ['permission' => 'moderateAccessTokens', 'group_id' => self::TOKEN_MODERATORS],
            ],
        ]);
    }

    /**
     * @return array{int, array<string, mixed>, string}
     */
    private function list(int $actor, array $query): array
    {
        $response = $this->send(
            $this->request('GET', '/api/access-tokens', ['authenticatedAs' => $actor])->withQueryParams($query + ['page' => ['limit' => 50]])
        );
        $raw = (string) $response->getBody();

        return [$response->getStatusCode(), json_decode($raw, true), $raw];
    }

    #[Test]
    public function a_member_only_ever_gets_their_own_tokens_and_themselves()
    {
        $queries = [
            [],
            ['filter' => ['user' => '3']],
            ['filter' => ['-user' => '2']],
            ['filter' => ['type' => 'session']],
            ['filter' => ['-type' => 'developer']],
            ['include' => 'user'],
            ['include' => 'user.groups'],
            ['sort' => '-lastActivityAt'],
        ];

        foreach ($queries as $query) {
            [$status, $body, $raw] = $this->list(2, $query);

            $this->assertSame(200, $status, $raw);

            foreach ($body['data'] as $token) {
                $this->assertSame(2, (int) $token['attributes']['userId'], $raw);
            }

            foreach ($body['included'] ?? [] as $included) {
                $this->assertFalse($included['type'] === 'users' && (int) $included['id'] !== 2, $raw);
            }

            $this->assertStringNotContainsString('owner', $raw);
            $this->assertStringNotContainsString('10.0.0.', $raw);
        }
    }

    #[Test]
    public function included_owners_carry_only_what_the_actor_may_see()
    {
        [$status, $body, $raw] = $this->list(5, ['include' => 'user.groups']);

        $this->assertSame(200, $status, $raw);
        $this->assertGreaterThanOrEqual(3 + self::OWNER_COUNT, count($body['data']));

        foreach ($body['included'] as $included) {
            if ($included['type'] === 'users' && (int) $included['id'] !== 5) {
                $this->assertArrayNotHasKey('email', $included['attributes'], $raw);
                $this->assertArrayNotHasKey('isEmailConfirmed', $included['attributes'], $raw);
            }

            $this->assertFalse($included['type'] === 'groups' && (int) $included['id'] === self::HIDDEN_GROUP, $raw);
        }

        foreach (['ownersessiontokenvalue', 'ownerdevelopertokenvalue', 'normaldevelopertokenvalue'] as $token) {
            $this->assertStringNotContainsString($token, $raw);
        }
    }

    /**
     * Serializing each included owner checks `editCredentials` for the email field, which asks
     * `isAdmin()` and so reads the owner's groups: they are eager loaded, not one query per owner.
     */
    #[Test]
    public function owners_groups_are_loaded_in_one_query()
    {
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        [$status, $body, $raw] = $this->list(1, ['include' => 'user']);

        $log = $db->getQueryLog();
        $db->disableQueryLog();

        $this->assertSame(200, $status, $raw);

        $owners = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'users' && (int) $r['id'] >= 20);
        $this->assertCount(self::OWNER_COUNT, $owners);

        $perOwner = 0;

        foreach ($log as $query) {
            if (stripos($query['query'], 'group_user') === false || stripos($query['query'], ' in (') !== false) {
                continue;
            }

            foreach ($query['bindings'] as $binding) {
                if ((int) $binding >= 20) {
                    $perOwner++;
                }
            }
        }

        $this->assertSame(0, $perOwner, "Token owners' groups were loaded one owner at a time ($perOwner queries).");
    }

    #[Test]
    public function only_the_dates_are_sortable()
    {
        foreach (['createdAt', '-lastActivityAt'] as $sort) {
            $this->assertSame(200, $this->list(1, ['sort' => $sort])[0], $sort);
        }

        foreach (['token', 'lastIpAddress', 'last_user_agent', 'userId', 'type'] as $sort) {
            $this->assertSame(400, $this->list(2, ['sort' => $sort])[0], $sort);
        }
    }
}
