<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\visibility;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Support\Arr;

class DiscussionVisibilityTest extends TestCase
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
            'discussions' => [
                ['id' => 1, 'title' => 'no tags', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'open tags', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'open tag, restricted child tag', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'open tag, one restricted secondary tag', 'created_at'  => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'all closed', 'created_at'  => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 2, 'tag_id' => 1],
                ['discussion_id' => 3, 'tag_id' => 2],
                ['discussion_id' => 3, 'tag_id' => 5],
                ['discussion_id' => 4, 'tag_id' => 1],
                ['discussion_id' => 4, 'tag_id' => 11],
                ['discussion_id' => 5, 'tag_id' => 6],
                ['discussion_id' => 5, 'tag_id' => 7],
                ['discussion_id' => 5, 'tag_id' => 8],
            ],
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag5.viewDiscussions'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.viewDiscussions'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.viewDiscussions']
            ]
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
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4', '5'], $ids);
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

        // 5 isnt included because parent access doesnt necessarily give child access
        // 6, 7, 8 aren't included because child access shouldnt work unless parent
        // access is also given.
        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '2', '3', '4'], $ids);
    }

    /**
     * @test
     */
    public function guest_cant_see_restricted_or_children_of_restricted()
    {
        $response = $this->send(
            $this->request('GET', '/api/discussions')
        );

        $this->assertEquals(200, $response->getStatusCode());

        $data = json_decode($response->getBody()->getContents(), true)['data'];

        $ids = Arr::pluck($data, 'id');
        $this->assertEqualsCanonicalizing(['1', '2'], $ids);
    }
}
