<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\security\listing;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Security: can an actor infer attributes hidden from them (email, hidden last seen
 * time, hidden group membership, email confirmation) through `/api/users` filters,
 * negated filters, sorts, fulltext or counts?
 *
 * Actors: guest (0), member (2), hidden-online member (3), member of a hidden
 * group (4), custom "token moderator" without user perms (5), default moderator (6), admin (1).
 */
class UserListingOracleTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const HIDDEN_GROUP = 10;
    private const TOKEN_MOD_GROUP = 11;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'hider', 'email' => 'hider@secret.local', 'is_email_confirmed' => 0, 'joined_at' => '2024-01-10 08:00:00', 'last_seen_at' => '2024-06-01 12:00:00', 'preferences' => json_encode(['discloseOnline' => false])],
                ['id' => 4, 'username' => 'hiddenmember', 'email' => 'hm@secret.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-03-15 10:00:00', 'last_seen_at' => '2025-01-01 00:00:00'],
                ['id' => 5, 'username' => 'tokenmod', 'email' => 'tm@secret.local', 'is_email_confirmed' => 1, 'joined_at' => '2024-04-01 10:00:00'],
                ['id' => 6, 'username' => 'moderator', 'email' => 'mod@secret.local', 'is_email_confirmed' => 1, 'joined_at' => '2020-01-01 00:00:00'],
            ],
            Group::class => [
                ['id' => self::HIDDEN_GROUP, 'name_singular' => 'Secret', 'name_plural' => 'Secrets', 'is_hidden' => 1],
                ['id' => self::TOKEN_MOD_GROUP, 'name_singular' => 'TokenMod', 'name_plural' => 'TokenMods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 4, 'group_id' => self::HIDDEN_GROUP],
                ['user_id' => 5, 'group_id' => self::TOKEN_MOD_GROUP],
                ['user_id' => 6, 'group_id' => Group::MODERATOR_ID],
            ],
            'group_permission' => [
                ['permission' => 'moderateAccessTokens', 'group_id' => self::TOKEN_MOD_GROUP],
            ],
        ]);
    }

    private function list(?int $actor, array $query): array
    {
        $response = $this->send(
            $this->request('GET', '/api/users', $actor ? ['authenticatedAs' => $actor] : [])
                ->withQueryParams($query + ['page' => ['limit' => 50]])
        );

        $body = json_decode((string) $response->getBody(), true);

        return [$response->getStatusCode(), $body];
    }

    private function ids(?int $actor, array $query): array
    {
        [$status, $body] = $this->list($actor, $query);
        $this->assertSame(200, $status, json_encode($body));
        $ids = array_map('intval', array_column($body['data'], 'id'));
        sort($ids);

        return $ids;
    }

    #[Test]
    public function guest_cannot_list_users()
    {
        [$status] = $this->list(null, []);
        $this->assertContains($status, [401, 403]);
    }

    public static function memberActors(): array
    {
        return ['member' => [2], 'token moderator (no user perms)' => [5]];
    }

    #[Test]
    #[DataProvider('memberActors')]
    public function email_filter_and_its_negation_are_ignored_without_edit_credentials(int $actor)
    {
        $all = $this->ids($actor, []);
        $this->assertSame($all, $this->ids($actor, ['filter' => ['email' => 'hider@secret.local']]));
        $this->assertSame($all, $this->ids($actor, ['filter' => ['-email' => 'hider@secret.local']]));
    }

    #[Test]
    #[DataProvider('memberActors')]
    public function last_seen_filter_open_ranges_and_negation_are_ignored_without_permission(int $actor)
    {
        $all = $this->ids($actor, []);
        // Binary-search style probes on user 3's hidden last-seen time.
        foreach (['2024-06-01', '..2024-06-01', '2024-06-02..', '2024-05-01..2024-06-30'] as $probe) {
            $this->assertSame($all, $this->ids($actor, ['filter' => ['lastSeenAt' => $probe]]), $probe);
            $this->assertSame($all, $this->ids($actor, ['filter' => ['-lastSeenAt' => $probe]]), '-'.$probe);
        }
    }

    #[Test]
    #[DataProvider('memberActors')]
    public function sorting_by_last_seen_is_rejected_without_permission(int $actor)
    {
        foreach (['lastSeenAt', '-lastSeenAt'] as $sort) {
            [$status] = $this->list($actor, ['sort' => $sort]);
            $this->assertSame(400, $status, $sort);
        }
    }

    public static function forbiddenSorts(): array
    {
        return [['email'], ['-email'], ['isEmailConfirmed'], ['password'], ['suspendedUntil'], ['last_seen_at'], ['preferences']];
    }

    #[Test]
    #[DataProvider('forbiddenSorts')]
    public function sorting_by_private_columns_is_rejected(string $sort)
    {
        [$status] = $this->list(2, ['sort' => $sort]);
        $this->assertSame(400, $status, $sort);
    }

    #[Test]
    #[DataProvider('memberActors')]
    public function last_seen_at_of_a_hider_is_not_serialized(int $actor)
    {
        [, $body] = $this->list($actor, []);
        foreach ($body['data'] as $user) {
            if ((int) $user['id'] === 3) {
                $this->assertArrayNotHasKey('lastSeenAt', $user['attributes']);
                $this->assertArrayNotHasKey('email', $user['attributes']);
                $this->assertArrayNotHasKey('isEmailConfirmed', $user['attributes']);
            }
        }
    }

    #[Test]
    public function group_filter_on_a_hidden_group_is_indistinguishable_from_a_nonexistent_group()
    {
        foreach ([(string) self::HIDDEN_GROUP, 'secret', 'secrets'] as $probe) {
            $this->assertSame($this->ids(2, ['filter' => ['group' => '99999']]), $this->ids(2, ['filter' => ['group' => $probe]]), $probe);
            $this->assertSame($this->ids(2, []), $this->ids(2, ['filter' => ['-group' => $probe]]), '-'.$probe);
        }
    }

    #[Test]
    public function hidden_group_is_not_in_a_members_included_groups()
    {
        [, $body] = $this->list(2, ['include' => 'groups']);
        foreach ($body['data'] as $user) {
            foreach ($user['relationships']['groups']['data'] ?? [] as $g) {
                $this->assertNotSame((string) self::HIDDEN_GROUP, (string) $g['id']);
            }
        }
        foreach ($body['included'] ?? [] as $inc) {
            $this->assertFalse($inc['type'] === 'groups' && (int) $inc['id'] === self::HIDDEN_GROUP);
        }
    }

    #[Test]
    public function fulltext_does_not_match_email()
    {
        $this->assertSame([], $this->ids(2, ['filter' => ['q' => 'secret.local']]));
    }

    #[Test]
    public function the_admin_can_use_all_of_them()
    {
        $this->assertSame([3], $this->ids(1, ['filter' => ['email' => 'hider@secret.local']]));
        $this->assertSame([3], $this->ids(1, ['filter' => ['lastSeenAt' => '2024-06-01']]));
        $this->assertSame([4], $this->ids(1, ['filter' => ['group' => (string) self::HIDDEN_GROUP]]));
    }
}
