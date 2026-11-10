<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\user;

use Flarum\Group\Group;
use Flarum\Group\Permission;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The permissions granted to a user depend only on the set of group IDs the
 * user belongs to, yet they were loaded (and memoized) per User instance. Any
 * code path that checks an ability against each serialized user — extension
 * serializer flags, policies, visibility scopers — therefore issued one
 * identical `group_permission` query per user.
 *
 * These tests pin the fix: permissions are cached per group-ID set for the
 * lifetime of the app instance, and invalidated when permissions change.
 */
class GroupPermissionCacheTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $users = [];
        $groupUser = [];

        // Four users sharing one custom group.
        for ($i = 0; $i < 4; $i++) {
            $userId = 10 + $i;
            $users[] = [
                'id' => $userId,
                'username' => 'shared'.$userId,
                'email' => 'shared'.$userId.'@machine.local',
                'is_email_confirmed' => 1,
                'password' => 'foobar',
            ];
            $groupUser[] = ['user_id' => $userId, 'group_id' => 5];
        }

        // One user in an additional group — a distinct group set.
        $users[] = [
            'id' => 14,
            'username' => 'distinct14',
            'email' => 'distinct14@machine.local',
            'is_email_confirmed' => 1,
            'password' => 'foobar',
        ];
        $groupUser[] = ['user_id' => 14, 'group_id' => 5];
        $groupUser[] = ['user_id' => 14, 'group_id' => 6];

        // An unactivated user, who is treated as a guest.
        $users[] = [
            'id' => 15,
            'username' => 'unconfirmed15',
            'email' => 'unconfirmed15@machine.local',
            'is_email_confirmed' => 0,
            'password' => 'foobar',
        ];

        $this->prepareDatabase([
            User::class => array_merge([$this->normalUser()], $users),
            Group::class => [
                ['id' => 5, 'name_singular' => 'Five', 'name_plural' => 'Fives'],
                ['id' => 6, 'name_singular' => 'Six', 'name_plural' => 'Sixes'],
            ],
            'group_user' => $groupUser,
            Permission::class => [
                ['group_id' => 5, 'permission' => 'custom.five'],
                ['group_id' => 6, 'permission' => 'custom.six'],
            ],
        ]);
    }

    private function countPermissionQueries(callable $callback): int
    {
        $db = $this->database();
        $db->flushQueryLog();
        $db->enableQueryLog();

        $callback();

        $count = 0;

        foreach ($db->getQueryLog() as $query) {
            if (stripos($query['query'], 'group_permission') !== false) {
                $count++;
            }
        }

        return $count;
    }

    #[Test]
    public function users_sharing_a_group_set_trigger_a_single_permission_query()
    {
        $this->app();

        $queries = $this->countPermissionQueries(function () {
            foreach ([10, 11, 12, 13] as $id) {
                // A fresh instance per user, as relationship hydration produces
                // during serialization — the per-instance memo cannot help here.
                $user = User::query()->findOrFail($id);

                $this->assertContains('custom.five', $user->getPermissions());
            }
        });

        $this->assertSame(1, $queries, 'Users sharing one group set must share one group_permission query.');
    }

    #[Test]
    public function distinct_group_sets_are_each_loaded_once()
    {
        $this->app();

        $queries = $this->countPermissionQueries(function () {
            $shared = User::query()->findOrFail(10);
            $this->assertContains('custom.five', $shared->getPermissions());

            $distinct = User::query()->findOrFail(14);
            $permissions = $distinct->getPermissions();
            $this->assertContains('custom.five', $permissions);
            $this->assertContains('custom.six', $permissions);

            // Same set as user 10 again — must reuse the cached load.
            $sharedAgain = User::query()->findOrFail(11);
            $this->assertContains('custom.five', $sharedAgain->getPermissions());
        });

        $this->assertSame(2, $queries, 'Each distinct group set must be loaded exactly once.');
    }

    #[Test]
    public function permission_changes_are_visible_to_fresh_instances()
    {
        $this->app();

        // Warm whatever caching is in place.
        User::query()->findOrFail(10)->getPermissions();

        Permission::unguarded(fn () => Permission::query()->create(['group_id' => 5, 'permission' => 'custom.granted-later']));

        $this->assertContains(
            'custom.granted-later',
            User::query()->findOrFail(11)->getPermissions(),
            'A permission granted after the first load must be visible to fresh instances.'
        );
    }

    #[Test]
    public function revoked_permissions_disappear_for_fresh_instances()
    {
        $this->app();

        $this->assertContains('custom.five', User::query()->findOrFail(10)->getPermissions());

        Permission::query()->where('group_id', 5)->where('permission', 'custom.five')->first()->delete();

        $this->assertNotContains(
            'custom.five',
            User::query()->findOrFail(11)->getPermissions(),
            'A revoked permission must not be served from a stale cache.'
        );
    }

    #[Test]
    public function permission_changes_only_invalidate_the_affected_group_sets()
    {
        $this->app();

        // Warm both cached sets: {guest, member, 5} and {guest, member, 5, 6}.
        User::query()->findOrFail(10)->getPermissions();
        User::query()->findOrFail(14)->getPermissions();

        // Change a permission of group 6 — only the set containing group 6
        // should be invalidated.
        Permission::unguarded(fn () => Permission::query()->create(['group_id' => 6, 'permission' => 'custom.six-later']));

        $queries = $this->countPermissionQueries(function () {
            // Untouched set: must be served from cache, no new query.
            $this->assertNotContains('custom.six-later', User::query()->findOrFail(11)->getPermissions());
        });

        $this->assertSame(0, $queries, 'A permission change to another group must not evict unrelated cached sets.');

        $queries = $this->countPermissionQueries(function () {
            // Affected set: must be reloaded and reflect the change.
            $this->assertContains('custom.six-later', User::query()->findOrFail(14)->getPermissions());
        });

        $this->assertSame(1, $queries, 'The affected set must be reloaded exactly once.');
    }

    #[Test]
    public function group_membership_changes_take_effect_for_fresh_instances()
    {
        $this->app();

        // Warm the {guest, member, 5} set.
        $this->assertNotContains('custom.six', User::query()->findOrFail(10)->getPermissions());

        // Add the user to group 6: their group set — and thus their cache key —
        // changes, so no invalidation is needed for correctness.
        $this->database()->table('group_user')->insert(['user_id' => 10, 'group_id' => 6]);

        $this->assertContains(
            'custom.six',
            User::query()->findOrFail(10)->getPermissions(),
            'A membership change must be reflected immediately via the changed group set.'
        );
    }

    #[Test]
    public function deleting_a_group_removes_its_permissions_for_fresh_instances()
    {
        $this->app();

        // Warm the {guest, member, 5, 6} set.
        $this->assertContains('custom.six', User::query()->findOrFail(14)->getPermissions());

        Group::query()->findOrFail(6)->delete();

        $this->assertNotContains(
            'custom.six',
            User::query()->findOrFail(14)->getPermissions(),
            'Permissions of a deleted group must not survive via a stale cache.'
        );
    }

    #[Test]
    public function the_admin_permission_endpoint_invalidates_the_cache()
    {
        $this->app();

        // Warm the {guest, member, 5} set.
        $this->assertNotContains('custom.endpoint', User::query()->findOrFail(10)->getPermissions());

        // The endpoint writes through the query builder (no model events), so
        // it must reset the cache itself.
        $response = $this->send(
            $this->request('POST', '/api/permission', [
                'authenticatedAs' => 1,
                'json' => ['permission' => 'custom.endpoint', 'groupIds' => [5]],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());

        $this->assertContains(
            'custom.endpoint',
            User::query()->findOrFail(11)->getPermissions(),
            'A permission granted through the admin endpoint must be visible immediately.'
        );
    }

    #[Test]
    public function unactivated_users_share_the_guest_permission_load()
    {
        $this->app();

        $queries = $this->countPermissionQueries(function () {
            $unconfirmed = User::query()->findOrFail(15);
            $permissions = $unconfirmed->getPermissions();

            // No member/custom-group permissions for an unactivated account.
            $this->assertNotContains('custom.five', $permissions);
        });

        $this->assertSame(1, $queries);
    }
}
