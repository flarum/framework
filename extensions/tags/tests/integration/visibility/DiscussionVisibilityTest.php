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
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

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
            Tag::class => $this->tags(),
            User::class => [
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
            Discussion::class => [
                ['id' => 1, 'title' => 'no tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'open tags', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'open tag, restricted child tag', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'open tag, one restricted secondary tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'all closed',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 6, 'title' => 'closed parent, open child tag',  'user_id' => 1, 'comment_count' => 1],
                ['id' => 7, 'title' => 'one closed primary tag',  'user_id' => 1, 'comment_count' => 1],
            ],
            Post::class => [
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

    #[Test]
    public function admin_sees_all()
    {
        $this->app();

        $user = User::find(1);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5, 6, 7], $ids);
    }

    #[Test]
    public function user_sees_where_allowed()
    {
        $this->app();

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 7], $ids);
    }

    #[Test]
    public function user_sees_only_in_restricted_tags_without_global_perm()
    {
        $this->database()->table('group_permission')->where('permission', 'arbitraryAbility')->delete();

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([7], $ids);
    }

    #[Test]
    public function guest_can_see_where_allowed()
    {
        $this->app();

        $user = new Guest();
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([1, 2], $ids);
    }

    #[Test]
    public function guest_cant_see_without_global_perm()
    {
        $this->database()->table('group_permission')->where('permission', 'arbitraryAbility')->delete();

        $user = new Guest();
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertEqualsCanonicalizing([], $ids);
    }

    #[Test]
    public function permission_on_child_does_not_grant_visibility_when_parent_is_off_limits()
    {
        // The visibility scope's clause:
        //   ->whereIn('tags.id', $permittedIds)
        //   ->where(fn($q) => $q->whereIn('tags.parent_id', $permittedIds)
        //                       ->orWhereNull('tags.parent_id'))
        //
        // means a tag is visible only if EITHER its parent is also in the
        // permitted set OR the tag has no parent. This is a load-bearing
        // protection: an admin who restricts a parent tag must not have
        // their restriction silently undermined by a per-child permission
        // grant on a descendant.
        //
        // Concretely in this fixture: tag 8 (Primary Restricted Child
        // Restricted) has parent tag 6 (Primary Restricted). The user has
        // tag8.arbitraryAbility but NOT tag6.arbitraryAbility — so any
        // discussion tagged with tag 8 alone should NOT be visible.
        $this->app();

        $this->database()->table('discussion_tag')->insert([
            ['discussion_id' => 1, 'tag_id' => 8],
        ]);

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertNotContains(1, $ids, 'Permission on a restricted child tag must not bypass the parent-tag restriction.');
    }

    #[Test]
    public function permission_on_child_grants_visibility_when_parent_is_unrestricted()
    {
        // Symmetric case: tag 5 (Primary 2 Child Restricted) has parent
        // tag 2 (Primary 2, unrestricted). The user has tag5.arbitraryAbility
        // and the global perm. A discussion tagged with tag 5 alone should
        // be visible — the parent_id IS allowed (it's unrestricted, so any
        // user with the global perm has access).
        //
        // Discussion 3 in the fixture has tags [2, 5] which already covers
        // this; this test makes the property explicit with a single-tag
        // case to isolate the parent_id clause.
        $this->app();

        $this->database()->table('discussion_tag')->insert([
            ['discussion_id' => 1, 'tag_id' => 5],
        ]);

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertContains(1, $ids, 'Permission on a restricted child tag whose parent is unrestricted should grant visibility.');
    }

    #[Test]
    public function root_restricted_tag_with_explicit_permission_is_visible()
    {
        // Tag 14 (Primary Restricted 3) is restricted, has no parent, and
        // the user has tag14.arbitraryAbility. The orWhereNull('tags.parent_id')
        // branch of the visibility scope should let this through.
        // Discussion 7 in the fixture covers this implicitly; this test
        // pins the property explicitly.
        $this->app();

        $user = User::find(2);
        $discussions = Discussion::whereVisibleTo($user, 'arbitraryAbility')->get();

        $ids = Arr::pluck($discussions, 'id');
        $this->assertContains(7, $ids, 'Permission on a restricted root tag (no parent) should grant visibility.');
    }
}
