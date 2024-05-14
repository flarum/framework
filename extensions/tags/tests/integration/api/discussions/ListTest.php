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

class ListTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use RetrievesRepresentativeTags;

    /**
     * @inheritDoc
     */
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
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal3@machine.local',
                    'is_email_confirmed' => 1,
                ]
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'acme', 'name_plural' => 'acme']
            ],
            'group_user' => [
                ['group_id' => 100, 'user_id' => 2]
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'tag5.viewForum'],
                ['group_id' => 100, 'permission' => 'tag8.viewForum'],
                ['group_id' => 100, 'permission' => 'tag11.viewForum'],
                ['group_id' => 100, 'permission' => 'tag13.viewForum'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'no tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'open tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'open tag, restricted child tag', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'open tag, one restricted secondary tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'all closed',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 6, 'title' => 'closed parent, open child tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 7, 'title' => 'private, no tags', 'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 8, 'title' => 'private, open tags', 'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 9, 'title' => 'private, open tag, restricted child tag', 'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 10, 'title' => 'private, open tag, one restricted secondary tag',  'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 11, 'title' => 'private, all closed',  'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
                ['id' => 12, 'title' => 'private, closed parent, open child tag',  'user_id' => 1, 'comment_count' => 1, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 6, 'discussion_id' => 6, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 7, 'discussion_id' => 7, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 8, 'discussion_id' => 8, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 9, 'discussion_id' => 9, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 10, 'discussion_id' => 10, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 11, 'discussion_id' => 11, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 12, 'discussion_id' => 12, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 8, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 5],
                ['discussion_id' => 9, 'tag_id' => 2],
                ['discussion_id' => 9, 'tag_id' => 5],
                ['discussion_id' => 4, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 11],
                ['discussion_id' => 10, 'tag_id' => 1],
                ['discussion_id' => 10, 'tag_id' => 11],
                ['discussion_id' => 5, 'tag_id' => 6],
                ['discussion_id' => 5, 'tag_id' => 7],
                ['discussion_id' => 5, 'tag_id' => 8],
                ['discussion_id' => 11, 'tag_id' => 6],
                ['discussion_id' => 11, 'tag_id' => 7],
                ['discussion_id' => 11, 'tag_id' => 8],
                ['discussion_id' => 6, 'tag_id' => 12],
                ['discussion_id' => 6, 'tag_id' => 13],
                ['discussion_id' => 12, 'tag_id' => 12],
                ['discussion_id' => 12, 'tag_id' => 13],
            ],
        ]);
    }

    /**
     * @test
     */
    public function admin_sees_all()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4', '5', '6'], $ids);
    }

    /**
     * @test
     */
    public function user_sees_where_allowed()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4'], $ids);
    }

    /**
     * @test
     */
    public function guest_can_see_where_allowed()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '2'], $ids);
    }

    /**
     * @dataProvider seeWhereAllowedWhenMoreTagsAreRequiredThanAvailableDataProvider
     * @test
     */
    public function actor_can_see_where_allowed_when_more_tags_are_required_than_available(string $type, int $actorId, array $expectedDiscussions)
    {
        if ($type === 'secondary') {
            $this->setting('flarum-tags.min_secondary_tags', 1);
            $this->database()->table('tags')
                ->where(['position' => null, 'parent_id' => null])
                ->update(['position' => 200]);
        } elseif ($type === 'primary') {
            $this->setting('flarum-tags.min_primary_tags', 100);
            $this->database()->table('tags')
                ->where('position', '!=', null)
                ->update(['position' => null]);
        }

        if ($actorId) {
            $reqParams = [
                'authenticatedAs' => $actorId
            ];
        }

        $response = $this->send(
            $this->request('GET', '/api/discussions', $reqParams ?? [])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing($expectedDiscussions, $ids);
    }

    public function seeWhereAllowedWhenMoreTagsAreRequiredThanAvailableDataProvider(): array
    {
        return [
            // admin_can_see_where_allowed_when_more_primary_tags_are_required_than_available
            ['primary', 1, ['1', '2', '3', '4', '5', '6']],
            // user_can_see_where_allowed_when_more_primary_tags_are_required_than_available
            ['primary', 2, ['1', '2', '3', '4']],
            // guest_can_see_where_allowed_when_more_primary_tags_are_required_than_available
            ['primary', 0, ['1', '2']],
            // admin_can_see_where_allowed_when_more_secondary_tags_are_required_than_available
            ['secondary', 1, ['1', '2', '3', '4', '5', '6']],
            // user_can_see_where_allowed_when_more_secondary_tags_are_required_than_available
            ['secondary', 2, ['1', '2', '3', '4']],
            // guest_can_see_where_allowed_when_more_secondary_tags_are_required_than_available
            ['secondary', 0, ['1', '2']],
        ];
    }

    /**
     * @dataProvider filterByTagsDataProvider
     * @test
     */
    public function can_filter_by_authorized_tags(int $authenticatedAs, string $tags, array $expectedDiscussionIds)
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions', compact('authenticatedAs'))
                ->withQueryParams([
                    'filter' => [
                        'tag' => $tags
                    ]
                ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing($expectedDiscussionIds, array_map('intval', $ids));
    }

    public function filterByTagsDataProvider(): array
    {
        return [
            // Admin can filter by any tag.
            [1, 'primary-1,primary-2', [2, 3, 4]],
            [1, 'primary-1,primary-2,primary-restricted', [2, 3, 4, 5]],
            [1, 'primary-2-restricted-child-1', [6]],
            [1, 'untagged', [1]],

            // Normal user can only filter by tags he has access to.
            [3, 'primary-2-restricted-child-1', []],
            [3, 'primary-1', [2]],
            [3, 'primary-1,primary-2', [2]],
            [3, 'untagged', [1]],

            // Authorized User can only filter by tags he has access to.
            [2, 'primary-2-restricted-child-1', []],
            [2, 'primary-2-restricted-child-1,primary-restricted-child-restricted', []],
            [2, 'primary-1', [2, 4]],
            [2, 'primary-1,primary-2', [2, 3, 4]],
            [2, 'secondary-restricted', [4]],
            [2, 'untagged', [1]],
        ];
    }
}
