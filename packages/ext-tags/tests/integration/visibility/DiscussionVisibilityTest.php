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
use Flarum\Tags\Tests\integration\RetrievesRepresentativeTags;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Guest;
use Flarum\User\User;
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
            'tags' => $this->tags(),
            'users' => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag5.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag8.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag11.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag13.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag14.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'arbitraryAbility'],
                ['group_id' => Group::GUEST_ID, 'permission' => 'arbitraryAbility']
            ],
            'discussions' => [
                ['id' => 1, 'title' => 'no tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'open tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'open tag, restricted child tag', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'open tag, one restricted secondary tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'all closed',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 6, 'title' => 'closed parent, open child tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 7, 'title' => 'one closed primary tag',  'user_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 2, 'discussion_id' => 2, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 3, 'discussion_id' => 3, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 4, 'discussion_id' => 4, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 5, 'discussion_id' => 5, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 6, 'discussion_id' => 6, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
                ['id' => 7, 'discussion_id' => 7, 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p></p></t>'],
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
                ['discussion_id' => 6, 'tag_id' => 12],
                ['discussion_id' => 6, 'tag_id' => 13],
                ['discussion_id' => 7, 'tag_id' => 14],
            ],
        ]);
    }

    /**
     * @test
     */
    public function admin_sees_all()
    {
        $this->app();

        $user = User::find(1);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6, 7], $ids);
    }

    /**
     * @test
     */
    public function user_sees_where_allowed()
    {
        $this->app();

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 7], $ids);
    }

    /**
     * @test
     */
    public function user_sees_only_in_restricted_tags_without_global_perm()
    {
        $this->database()->table('group_permission')->where('permission', 'arbitraryAbility')->delete();

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([7], $ids);
    }

    /**
     * @test
     */
    public function guest_can_see_where_allowed()
    {
        $this->app();

        $user = new Guest();
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    /**
     * @test
     */
    public function guest_cant_see_without_global_perm()
    {
        $this->database()->table('group_permission')->where('permission', 'arbitraryAbility')->delete();

        $user = new Guest();
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([], $ids);
    }
}
