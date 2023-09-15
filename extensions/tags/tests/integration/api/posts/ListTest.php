<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\posts;

use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;

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
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
                [
                    'id' => 3,
                    'username' => 'normal3',
                    'password' => '$2y$10$LO59tiT7uggl6Oe23o/O6.utnF6ipngYjvMvaxo1TciKqBttDNKim', // BCrypt hash for "too-obscure"
                    'email' => 'normal3@machine.local',
                    'is_email_confirmed' => 1,
                ]
            ],
            'groups' => [
                ['id' => 100, 'name_singular' => 'acme', 'name_plural' => 'acme']
            ],
            'group_user' => [
                ['group_id' => 100, 'user_id' => 2]
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'tag5.viewForum'],
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'no tags', 'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>', 'number' => 1],
                ['id' => 2, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'discussionTagged', 'content' => '[[1,5],[5]]', 'number' => 2],
                ['id' => 3, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>', 'number' => 3],
                ['id' => 4, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'discussionTagged', 'content' => '[[1,5],[5]]', 'number' => 4],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
            ],
            'post_mentions_tag' => [
                ['post_id' => 2, 'mentions_tag_id' => 1],
                ['post_id' => 2, 'mentions_tag_id' => 5],
                ['post_id' => 4, 'mentions_tag_id' => 1],
                ['post_id' => 4, 'mentions_tag_id' => 5],
            ],
        ]);
    }

    /**
     * @dataProvider authorizedUsers
     * @test
     */
    public function event_mentioned_tags_are_included_in_response_for_authorized_users(int $userId)
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => $userId
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $tagIds = array_map(function ($tag) {
            return $tag['id'];
        }, array_filter($data['included'], function ($item) {
            return $item['type'] === 'tags';
        }));

        $this->assertEqualsCanonicalizing([1, 5], $tagIds);
    }

    /**
     * @dataProvider unauthorizedUsers
     * @test
     */
    public function event_mentioned_tags_are_not_included_in_response_for_unauthorized_users(?int $userId)
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', [
                'authenticatedAs' => $userId
            ])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true);

        $tagIds = array_map(function ($tag) {
            return $tag['id'];
        }, array_filter($data['included'], function ($item) {
            return $item['type'] === 'tags';
        }));

        $this->assertEqualsCanonicalizing([1], $tagIds);
    }

    public function authorizedUsers()
    {
        return [
            'admin' => [1],
            'normal user with permission' => [2],
        ];
    }

    public function unauthorizedUsers()
    {
        return [
            'normal user without permission' => [3],
            'guest' => [null],
        ];
    }
}
