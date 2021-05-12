<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\flags;

use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class ListWithTagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags');
        $this->extension('flarum-tags');

        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'Unrestricted', 'slug' => '1', 'position' => 0, 'parent_id' => null],
                ['id' => 2, 'name' => 'Mods can view discussions', 'slug' => '2', 'position' => 0, 'parent_id' => null, 'is_restricted' => true],
                ['id' => 3, 'name' => 'Mods can view flags', 'slug' => '3', 'position' => 0, 'parent_id' => null, 'is_restricted' => true],
                ['id' => 4, 'name' => 'Mods can view discussions and flags', 'slug' => '4', 'position' => 0, 'parent_id' => null, 'is_restricted' => true],
            ],
            'users' => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'mod',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal2@machine.local',
                    'is_email_confirmed' => 1,
                ]
            ],
            'group_user' => [
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 3]
            ],
            'group_permission' => [
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'discussion.viewFlags'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag2.viewDiscussions'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag3.discussion.viewFlags'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag4.viewDiscussions'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag4.discussion.viewFlags'],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'no tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'has tags where mods can view discussions but not flags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'has tags where mods can view flags but not discussions', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'has tags where mods can view discussions and flags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'has unrestricted tag', 'user_id' => 1, 'comment_count' => 1],
            ],
            'discussion_tag' => [
                ['discussion_id' => 2, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 3],
                ['discussion_id' => 4, 'tag_id' => 4],
                ['discussion_id' => 5, 'tag_id' => 1],
            ],
            'posts' => [
                // From regular ListTest
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                // In tags
                ['id' => 4, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 6, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 7, 'discussion_id' => 5, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            'flags' => [
                // From regular ListTest
                ['id' => 1, 'post_id' => 1, 'user_id' => 1],
                ['id' => 2, 'post_id' => 1, 'user_id' => 2],
                ['id' => 3, 'post_id' => 1, 'user_id' => 3],
                ['id' => 4, 'post_id' => 2, 'user_id' => 2],
                ['id' => 5, 'post_id' => 3, 'user_id' => 1],
                // In tags
                ['id' => 6, 'post_id' => 4, 'user_id' => 1],
                ['id' => 7, 'post_id' => 5, 'user_id' => 1],
                ['id' => 8, 'post_id' => 6, 'user_id' => 1],
                ['id' => 9, 'post_id' => 7, 'user_id' => 1],
            ]
        ]);
    }

    /**
     * @test
     */
    public function admin_can_see_one_flag_per_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 1
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '4', '5', '6', '7', '8', '9'], $ids);
    }

    /**
     * @test
     */
    public function regular_user_sees_own_flags()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 2
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['2', '4'], $ids);
    }

    /**
     * @test
     */
    public function mod_can_see_one_flag_per_post()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags', [
                'authenticatedAs' => 3
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        // 7 is included, even though mods can't view discussions.
        // This is because the UI doesnt allow discussions.viewFlags without viewDiscussions.
        $this->assertEqualsCanonicalizing(['1', '4', '5', '7', '8', '9'], $ids);
    }

    /**
     * @test
     */
    public function guest_cant_see_flags()
    {
        $response = $this->send(
            $this->request('GET', '/api/flags')
        );

        $this->assertEquals(401, $response->getStatusCode());
    }
}
