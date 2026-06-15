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
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for TagFilter correctness and query-count behaviour.
 *
 * Correctness cases mirror the data provider in ListTest but are kept here
 * so they can be run independently and extended without cluttering ListTest.
 *
 * The query-count test is the key regression guard for the optimisation:
 * resolving N tag slugs must produce exactly 1 tag-lookup query regardless
 * of how many slugs are in the filter string.
 */
class TagFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => $this->tags(),
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'normal3',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim',
                    'email' => 'normal3@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'acme', 'name_plural' => 'acme'],
            ],
            'group_user' => [
                ['group_id' => 100, 'user_id' => 2],
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'tag5.viewForum'],
                ['group_id' => 100, 'permission' => 'tag8.viewForum'],
                ['group_id' => 100, 'permission' => 'tag11.viewForum'],
                ['group_id' => 100, 'permission' => 'tag13.viewForum'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'no tags',                            'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'tag primary-1',                      'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'tags primary-2 + primary-2-child-r', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'tags primary-1 + secondary-r',       'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'all restricted tags',                 'user_id' => 1, 'comment_count' => 1],
                ['id' => 6, 'title' => 'restricted-parent + open child',      'user_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 6, 'discussion_id' => 6, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            'discussion_tag' => [
                // discussion 2: primary-1
                ['discussion_id' => 2, 'tag_id' => 1],
                // discussion 3: primary-2 + primary-2-child-restricted
                ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 5],
                // discussion 4: primary-1 + secondary-restricted
                ['discussion_id' => 4, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 11],
                // discussion 5: primary-restricted + primary-restricted-child-1 + primary-restricted-child-restricted
                ['discussion_id' => 5, 'tag_id' => 6],
                ['discussion_id' => 5, 'tag_id' => 7],
                ['discussion_id' => 5, 'tag_id' => 8],
                // discussion 6: primary-2-restricted + primary-2-restricted-child-1
                ['discussion_id' => 6, 'tag_id' => 12],
                ['discussion_id' => 6, 'tag_id' => 13],
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Correctness
    // -------------------------------------------------------------------------

    #[Test]
    #[DataProvider('filterCorrectness')]
    public function returns_expected_discussions_for_tag_filter(int $actorId, string $tagFilter, array $expectedIds): void
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => $actorId])
                ->withQueryParams(['filter' => ['tag' => $tagFilter]])
        );

        $this->assertSame(200, $response->getStatusCode());

        $ids = array_map('intval', Arr::pluck(
            json_decode($response->getBody()->getContents(), true)['data'],
            'id'
        ));

        $this->assertEqualsCanonicalizing($expectedIds, $ids);
    }

    public static function filterCorrectness(): array
    {
        return [
            // Single known tag
            'admin: single tag' => [1, 'primary-1', [2, 4]],

            // OR filter (comma = OR within one filter[tag] value)
            'admin: two tags OR' => [1, 'primary-1,primary-2', [2, 3, 4]],

            // Three-way OR including restricted tag
            'admin: three tags OR inc restricted' => [1, 'primary-1,primary-2,primary-restricted', [2, 3, 4, 5]],

            // Untagged special case
            'admin: untagged' => [1, 'untagged', [1]],

            // Unknown slug → no discussions (null-ID path)
            'admin: completely unknown slug' => [1, 'slug-does-not-exist', []],

            // Restricted tag — actor without permission → tag invisible → id = null → no discussions
            'normal user: restricted tag not accessible' => [3, 'primary-2-restricted-child-1', []],

            // Restricted tag — authorised actor CAN see that tag
            'authorised user: accessible restricted child' => [2, 'secondary-restricted', [4]],

            // Actor without access to a tag sees no results for it
            'normal user: single accessible tag' => [3, 'primary-1', [2]],

            // Mix of accessible + inaccessible in an OR — only accessible tag's discussions returned
            'normal user: OR with one inaccessible tag' => [3, 'primary-1,primary-2', [2]],

            // Untagged for non-admin
            'normal user: untagged' => [3, 'untagged', [1]],

            // Multiple restricted tags both inaccessible
            'normal user: two restricted tags both inaccessible' => [3, 'primary-2-restricted-child-1,primary-restricted-child-restricted', []],
        ];
    }

    // -------------------------------------------------------------------------
    // Query count — the key regression guard for the optimisation
    // -------------------------------------------------------------------------

    #[Test]
    public function resolving_multiple_tag_slugs_issues_exactly_one_tag_lookup_query(): void
    {
        // Boot the app before enabling query log so the connection object exists.
        $db = $this->database();
        $db->enableQueryLog();

        $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['tag' => 'primary-1,primary-2,primary-restricted']])
        );

        $log = $db->getQueryLog();
        $db->flushQueryLog();

        // Isolate slug-resolution queries: these are the ones that use a WHERE IN
        // on the slug column to look up tag IDs from slugs.
        $slugResolutionQueries = array_filter(
            $log,
            fn (array $q) => str_contains($q['query'], '"slug"') || str_contains($q['query'], '`slug`')
        );

        $this->assertCount(
            1,
            $slugResolutionQueries,
            'Expected exactly 1 slug-resolution query for all tag slugs, got '.count($slugResolutionQueries).'. Queries: '.
            implode(' | ', array_column(array_values($slugResolutionQueries), 'query'))
        );
    }

    // -------------------------------------------------------------------------
    // Custom slug drivers — resolution must delegate to the active driver,
    // not assume the literal `slug` column.
    // -------------------------------------------------------------------------

    private function useIdWithSlugDriver(): void
    {
        // The 'id_with_slug' driver is shipped by flarum-tags (extend.php).
        $this->setting('slug_driver_'.Tag::class, 'id_with_slug');
    }

    #[Test]
    public function id_with_slug_driver_produces_id_dash_slug_urls(): void
    {
        $this->useIdWithSlugDriver();

        // The serialized `slug` attribute (used for URL building) is `<id>-<slug>`.
        $response = $this->send(
            $this->request('GET', '/api/tags', ['authenticatedAs' => 1])
        );

        $tags = json_decode($response->getBody()->getContents(), true)['data'];
        $slugs = [];
        foreach ($tags as $tag) {
            $slugs[(int) $tag['id']] = $tag['attributes']['slug'];
        }

        // Tag 1 is primary-1 → "1-primary-1".
        $this->assertSame('1-primary-1', $slugs[1] ?? null);
    }

    #[Test]
    public function tag_filter_resolves_slugs_via_a_custom_driver(): void
    {
        $this->useIdWithSlugDriver();

        // The id_with_slug driver resolves by the leading id; the trailing text
        // is cosmetic. Both the bare id and the full `id-slug` form must work.
        // Tag 1 is primary-1, on discussions 2 and 4.
        foreach (['1', '1-primary-1'] as $tagFilter) {
            $response = $this->send(
                $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                    ->withQueryParams(['filter' => ['tag' => $tagFilter]])
            );

            $this->assertSame(200, $response->getStatusCode());

            $ids = array_map('intval', Arr::pluck(
                json_decode($response->getBody()->getContents(), true)['data'],
                'id'
            ));

            $this->assertEqualsCanonicalizing([2, 4], $ids, "filter[tag]=$tagFilter");
        }
    }

    #[Test]
    public function tag_filter_with_custom_driver_respects_or_groups_and_visibility(): void
    {
        $this->useIdWithSlugDriver();

        // OR of tag 1 (primary-1: discussions 2,4) and tag 2 (primary-2: discussion 3).
        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                ->withQueryParams(['filter' => ['tag' => '1,2']])
        );

        $ids = array_map('intval', Arr::pluck(
            json_decode($response->getBody()->getContents(), true)['data'],
            'id'
        ));

        $this->assertEqualsCanonicalizing([2, 3, 4], $ids);

        // A normal user cannot see restricted tag 11 (secondary-restricted), so
        // filtering by its id returns nothing for them.
        $restricted = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['tag' => '11']])
        );

        $restrictedIds = array_map('intval', Arr::pluck(
            json_decode($restricted->getBody()->getContents(), true)['data'],
            'id'
        ));

        $this->assertSame([], $restrictedIds);
    }

    #[Test]
    public function batch_slug_driver_does_not_issue_a_query_per_slug(): void
    {
        $this->useIdWithSlugDriver();

        // Resolving more slugs must not cost more queries: a batch driver resolves
        // them together, so a 3-slug filter issues the same number of queries as a
        // 1-slug filter. An N+1 (per-slug fromSlug) would make the counts diverge.
        $countQueriesFor = function (string $filter): int {
            $db = $this->database();
            $db->flushQueryLog();
            $db->enableQueryLog();

            $this->send(
                $this->request('GET', '/api/discussions', ['authenticatedAs' => 1])
                    ->withQueryParams(['filter' => ['tag' => $filter]])
            );

            $count = count($db->getQueryLog());
            $db->flushQueryLog();

            return $count;
        };

        $oneSlug = $countQueriesFor('1');
        $threeSlugs = $countQueriesFor('1,2,6');

        $this->assertSame(
            $oneSlug,
            $threeSlugs,
            "Batch slug resolution should be query-count flat: 1 slug took $oneSlug queries, 3 slugs took $threeSlugs."
        );
    }
}
