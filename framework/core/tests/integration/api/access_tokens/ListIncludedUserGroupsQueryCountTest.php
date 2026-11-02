<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\access_tokens;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The admin access tokens page lists `/api/access-tokens?include=user`. Serializing
 * each included owner checks `editCredentials` for the email field, which asks
 * `isAdmin()` and so reads the owner's groups: without eager loading that is one
 * `group_user` query per token owner.
 */
class ListIncludedUserGroupsQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const OWNER_COUNT = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $users = [];
        $tokens = [];

        for ($i = 0; $i < self::OWNER_COUNT; $i++) {
            $userId = 10 + $i;
            $users[] = ['id' => $userId, 'username' => 'owner'.$userId, 'email' => 'owner'.$userId.'@machine.local', 'is_email_confirmed' => 1];
            $tokens[] = ['id' => 100 + $i, 'token' => 'token'.$userId, 'user_id' => $userId, 'last_activity_at' => Carbon::parse('2021-01-01 02:00:00'), 'type' => 'developer'];
        }

        $this->prepareDatabase([
            User::class => $users,
            AccessToken::class => $tokens,
        ]);
    }

    #[Test]
    public function owners_groups_are_loaded_in_one_query()
    {
        $this->app();

        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/access-tokens', ['authenticatedAs' => 1])
                ->withQueryParams(['include' => 'user', 'page' => ['limit' => 50]])
        );

        $log = $db->getQueryLog();
        $db->disableQueryLog();

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $body = json_decode((string) $response->getBody(), true);
        $owners = array_filter($body['included'] ?? [], fn (array $r) => $r['type'] === 'users' && (int) $r['id'] >= 10);
        $this->assertCount(self::OWNER_COUNT, $owners);

        $perOwner = 0;

        foreach ($log as $query) {
            if (stripos($query['query'], 'group_user') === false || stripos($query['query'], ' in (') !== false) {
                continue;
            }

            foreach ($query['bindings'] as $binding) {
                if ((int) $binding >= 10) {
                    $perOwner++;
                }
            }
        }

        $this->assertSame(0, $perOwner, "Token owners' groups were loaded one owner at a time ($perOwner queries).");
    }
}
