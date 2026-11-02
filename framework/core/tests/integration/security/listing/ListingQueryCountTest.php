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
 * Performance abuse: list endpoints with includes must use a bounded number of
 * queries, independent of the number of rows. Measured at N and 2N rows.
 */
class ListingQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private function seed(int $n): void
    {
        $tokens = $groups = $gu = [];
        $users = [$this->normalUser()];
        $t = Carbon::parse('2024-01-01');
        for ($i = 0; $i < $n; $i++) {
            $id = 100 + $i;
            $users[] = ['id' => $id, 'username' => 'u'.$id, 'email' => "u$id@machine.local", 'is_email_confirmed' => 1, 'joined_at' => $t];
            $tokens[] = ['id' => 1000 + $i, 'token' => 'tok'.$id.str_repeat('x', 30), 'user_id' => $id, 'type' => 'developer', 'last_activity_at' => $t, 'created_at' => $t];
            $groups[] = ['id' => 100 + $i, 'name_singular' => 'G'.$i, 'name_plural' => 'Gs'.$i, 'is_hidden' => $i % 2];
            $gu[] = ['user_id' => $id, 'group_id' => 100 + $i];
            $gu[] = ['user_id' => $id, 'group_id' => Group::MODERATOR_ID];
        }
        $this->prepareDatabase([User::class => $users, AccessToken::class => $tokens, Group::class => $groups, 'group_user' => $gu]);
    }

    public static function endpoints(): array
    {
        return [
            'users include groups (admin)' => ['/api/users', ['include' => 'groups'], 1],
            'users include groups (member)' => ['/api/users', ['include' => 'groups'], 2],
            'users filtered by joinedAt and group' => ['/api/users', ['include' => 'groups', 'filter' => ['joinedAt' => '2020-01-01..', 'group' => '4']], 1],
            'groups' => ['/api/groups', [], 1],
            'access tokens include user' => ['/api/access-tokens', ['include' => 'user'], 1],
            'access tokens include user.groups' => ['/api/access-tokens', ['include' => 'user.groups'], 1],
        ];
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function query_count_does_not_grow_with_rows(string $path, array $query, int $actor)
    {
        $this->seed(40);

        $small = $this->queries($path, $query + ['page' => ['limit' => 10]], $actor);
        $large = $this->queries($path, $query + ['page' => ['limit' => 40]], $actor);

        $this->assertLessThanOrEqual($small + 2, $large, "$path: $small queries for 10 rows, $large for 40");
        $this->assertLessThan(30, $large, "$path: $large queries");
    }

    private function queries(string $path, array $query, int $actor): int
    {
        $this->app();
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();
        $response = $this->send($this->request('GET', $path, ['authenticatedAs' => $actor])->withQueryParams($query));
        $n = count($db->getQueryLog());
        $db->disableQueryLog();
        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return $n;
    }
}
