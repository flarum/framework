<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\posts;

use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Support\Arr;

class IncludeFlagsVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setup(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'mod',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ],
                [
                    'id' => 4,
                    'username' => 'tod',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'tod@machine.local',
                    'is_email_confirmed' => 1,
                ],
                [
                    'id' => 5,
                    'username' => 'ted',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'ted@machine.local',
                    'is_email_confirmed' => 1,
                ],
            ],
            'group_user' => [
                ['group_id' => 5, 'user_id' => 2],
                ['group_id' => 6, 'user_id' => 3],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'group5', 'name_plural' => 'group5', 'color' => null, 'icon' => 'fas fa-crown', 'is_hidden' => false],
                ['id' => 6, 'name_singular' => 'group1', 'name_plural' => 'group1', 'color' => null, 'icon' => 'fas fa-cog', 'is_hidden' => false],
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => 5, 'permission' => 'tag1.viewForum'],
                ['group_id' => 5, 'permission' => 'discussion.viewFlags'],
                ['group_id' => 6, 'permission' => 'tag1.discussion.viewFlags'],
                ['group_id' => 6, 'permission' => 'tag1.viewForum'],
            ],
            Tag::class => [
                ['id' => 1, 'name' => 'Tag 1', 'slug' => 'tag-1', 'is_primary' => false, 'position' => null, 'parent_id' => null, 'is_restricted' => true],
                ['id' => 2, 'name' => 'Tag 2', 'slug' => 'tag-2', 'is_primary' => true, 'position' => 2, 'parent_id' => null, 'is_restricted' => false],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Test1', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'Test2', 'user_id' => 1, 'comment_count' => 1],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],

                ['id' => 4, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            Flag::class => [
                ['id' => 1, 'post_id' => 1, 'user_id' => 1],
                ['id' => 2, 'post_id' => 1, 'user_id' => 5],
                ['id' => 3, 'post_id' => 1, 'user_id' => 3],
                ['id' => 4, 'post_id' => 2, 'user_id' => 5],
                ['id' => 5, 'post_id' => 3, 'user_id' => 1],

                ['id' => 6, 'post_id' => 4, 'user_id' => 1],
                ['id' => 7, 'post_id' => 5, 'user_id' => 5],
                ['id' => 8, 'post_id' => 5, 'user_id' => 5],
            ],
        ]);
    }

    /**
     * @dataProvider listFlagsIncludesDataProvider
     * @test
     */
    public function user_sees_where_allowed_with_included_tags(int $actorId, array $expectedIncludes)
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => $actorId,
            ])->withQueryParams([
                'include' => 'flags'
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $responseBody = json_decode($response->getBody()->getContents(), true);

        $data = $responseBody['data'];

        $this->assertEquals(['1', '2', '3', '4', '5'], Arr::pluck($data, 'id'));
        $this->assertEqualsCanonicalizing(
            $expectedIncludes,
            collect($responseBody['included'] ?? [])
            ->filter(fn ($include) => $include['type'] === 'flags')
            ->pluck('id')
            ->map(strval(...))
            ->all()
        );
    }

    public function listFlagsIncludesDataProvider(): array
    {
        return [
            'admin_sees_all' => [1, [1, 2, 3, 4, 5, 6, 7, 8]],
            'user_with_general_permission_sees_where_unrestricted_tag' => [2, [6, 7, 8]],
            'user_with_tag1_permission_sees_tag1_flags' => [3, [1, 2, 3, 4, 5]],
            'normal_user_sees_none' => [4, []],
            'normal_user_sees_own' => [5, [2, 7, 4, 8]],
        ];
    }
}
