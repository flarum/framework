<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\discussions;

use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for #4605.
 *
 * Tag::scopeWhereHasPermission caches resolved permitted-tag IDs in a
 * WeakMap keyed by User instance. Whenever the actor User instance is
 * not the same PHP object across calls — and there are several places
 * in the request flow that load the actor from the DB rather than
 * reusing the existing instance — the cache misses, and each miss
 * re-runs the full permission resolution, including a `SELECT id,
 * is_restricted FROM tags` to enumerate every tag for the PHP-side
 * filter.
 *
 * On a real forum the reporter measured ~30 of these duplicate
 * permission lookups per /api/discussions request. This test
 * reproduces the bug at smaller scale (multiple non-admin users with
 * varied group sets, each authoring a discussion) and asserts the
 * count of unconstrained `SELECT * FROM tags`-style queries stays
 * bounded after the fix.
 */
class PermissionScopeQueryCountTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $users = [$this->normalUser()];
        $discussions = [];
        $posts = [];
        $groupUser = [['group_id' => 100, 'user_id' => 2]];
        $groups = [
            ['id' => 100, 'name_singular' => 'acme', 'name_plural' => 'acme'],
            ['id' => 101, 'name_singular' => 'beta', 'name_plural' => 'beta'],
            ['id' => 102, 'name_singular' => 'gamma', 'name_plural' => 'gamma'],
            ['id' => 103, 'name_singular' => 'delta', 'name_plural' => 'delta'],
        ];

        // 10 distinct authors with varied group memberships, each
        // authoring a discussion. The varied groups force distinct
        // permission sets across authors, which is what the cache-miss
        // path was supposed to share.
        $groupAssignments = [
            10 => [100],
            11 => [101],
            12 => [102],
            13 => [103],
            14 => [100, 101],
            15 => [100, 102],
            16 => [101, 103],
            17 => [102, 103],
            18 => [100, 103],
            19 => [101, 102],
        ];

        foreach ($groupAssignments as $userId => $userGroups) {
            $discussionId = 100 + ($userId - 10);
            $users[] = [
                'id' => $userId,
                'username' => "author$userId",
                'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                'email' => "author$userId@machine.local",
                'is_email_confirmed' => 1,
            ];
            $discussions[] = [
                'id' => $discussionId,
                'title' => "Discussion by author$userId",
                'user_id' => $userId,
                'comment_count' => 1,
            ];
            $posts[] = [
                'id' => $discussionId,
                'discussion_id' => $discussionId,
                'user_id' => $userId,
                'type' => 'comment',
                'content' => '<t><p></p></t>',
            ];
            foreach ($userGroups as $gid) {
                $groupUser[] = ['group_id' => $gid, 'user_id' => $userId];
            }
        }

        $this->prepareDatabase([
            Tag::class => $this->tags(),
            User::class => $users,
            Group::class => $groups,
            'group_user' => $groupUser,
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'tag5.viewForum'],
                ['group_id' => 101, 'permission' => 'tag8.viewForum'],
                ['group_id' => 102, 'permission' => 'tag11.viewForum'],
                ['group_id' => 103, 'permission' => 'tag5.viewForum'],
            ],
            Discussion::class => $discussions,
            Post::class => $posts,
        ]);
    }

    #[Test]
    public function listing_discussions_does_not_re_enumerate_tags_per_permission_check(): void
    {
        $db = $this->database();
        $db->enableQueryLog();

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $log = $db->getQueryLog();
        $db->flushQueryLog();

        // The cache-miss path issues `select id, is_restricted from tags`
        // (no WHERE) — this is the "enumerate every tag for the PHP-side
        // filter" query. On the unfixed code this fires multiple times
        // per request as the WeakMap-keyed cache misses on recreated
        // actor instances. Match permissively: any select-from-tags
        // without an `id`-restricting WHERE clause counts.
        $tagEnumerations = array_filter(
            $log,
            function (array $q) {
                if (! preg_match('/^select .* from [`"]tags[`"](?:\s|$)/i', $q['query'])) {
                    return false;
                }

                // Exclude the visibility scope's own constraining queries.
                return ! preg_match('/where\s+.*[`"]id[`"]\s*(=|in\b)/i', $q['query']);
            }
        );

        // Filter to the specific signature of the regression: `select id,
        // is_restricted from tags` (or `select * from tags`) with no
        // narrowing WHERE. Other unconstrained tags queries — count(*)
        // for GlobalPolicy's tag-quota check, etc. — are unrelated and
        // bounded.
        $regressionShapeQueries = array_filter(
            $tagEnumerations,
            fn (array $q) => preg_match('/^select\s+(?:[`"]id[`"]\s*,\s*[`"]is_restricted[`"]|\*)\s+from\s+[`"]tags[`"]\s*$/i', $q['query']) === 1
        );

        $this->assertLessThanOrEqual(
            1,
            count($regressionShapeQueries),
            'Expected the discussion list to enumerate the tags table at most once, got '.count($regressionShapeQueries).' fetches of `select id, is_restricted from tags`. '.
            'This is the signature of #4605 — the cache-miss path was running once per recreated actor User instance.'
        );
    }
}
