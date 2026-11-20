<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\visibility;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Test;

/**
 * `Discussion::whereVisibleTo($actor, $ability)` must agree with the tags
 * DiscussionPolicy (`$actor->can($ability, $discussion)`) for discussion
 * abilities other than `view`.
 */
class DiscussionAbilityScopeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'position' => 0],
                ['id' => 2, 'name' => 'Restricted, not granted', 'slug' => 'restricted-not-granted', 'position' => 1, 'is_restricted' => true],
                ['id' => 3, 'name' => 'Restricted, granted', 'slug' => 'restricted-granted', 'position' => 2, 'is_restricted' => true],
                ['id' => 4, 'name' => 'Open › Restricted, granted', 'slug' => 'open-restricted-granted', 'position' => 0, 'parent_id' => 1, 'is_restricted' => true],
                ['id' => 5, 'name' => 'Restricted, not granted › Restricted, granted', 'slug' => 'restricted-restricted-granted', 'position' => 0, 'parent_id' => 2, 'is_restricted' => true],
            ],
            User::class => [
                $this->normalUser(),
            ],
            'group_permission' => [
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag2.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag3.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag3.discussion.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag4.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag4.discussion.arbitraryAbility'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag5.viewForum'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'tag5.discussion.arbitraryAbility'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'untagged', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 2, 'title' => 'open tag', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 3, 'title' => 'restricted tag, ability not granted', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 4, 'title' => 'restricted tag, ability granted', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 5, 'title' => 'open parent, granted restricted child', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 6, 'title' => 'ungranted restricted parent, granted restricted child', 'user_id' => 1, 'comment_count' => 1],
                ['id' => 7, 'title' => 'granted and ungranted restricted tags', 'user_id' => 1, 'comment_count' => 1],
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
                ['discussion_id' => 4, 'tag_id' => 3],
                ['discussion_id' => 5, 'tag_id' => 1],
                ['discussion_id' => 5, 'tag_id' => 4],
                ['discussion_id' => 6, 'tag_id' => 2],
                ['discussion_id' => 6, 'tag_id' => 5],
                ['discussion_id' => 7, 'tag_id' => 2],
                ['discussion_id' => 7, 'tag_id' => 3],
            ],
        ]);
    }

    /**
     * @return array{0: int[], 1: int[]} IDs allowed by the policy, IDs returned by the scope.
     */
    protected function policyAndScope(string $ability): array
    {
        $user = User::find(2);

        $allowed = Discussion::query()->get()
            ->filter(fn (Discussion $discussion) => $user->can($ability, $discussion))
            ->pluck('id')
            ->all();

        $scoped = Discussion::whereVisibleTo($user, $ability)->pluck('id')->all();

        return [$allowed, $scoped];
    }

    #[Test]
    public function scope_matches_policy_for_holder_of_prefixed_global_permission()
    {
        $this->app();

        [$allowed, $scoped] = $this->policyAndScope('arbitraryAbility');

        $this->assertEqualsCanonicalizing([1, 2, 4, 5], $allowed, 'Policy');
        $this->assertEqualsCanonicalizing([1, 2, 4, 5], $scoped, 'Scope');
    }

    #[Test]
    public function scope_matches_policy_without_global_permission()
    {
        // Discussion 2 also gets the granted restricted tag 3: open tags
        // don't count without the global permission.
        $this->database()->table('group_permission')->where('permission', 'discussion.arbitraryAbility')->delete();
        $this->database()->table('discussion_tag')->insert([['discussion_id' => 2, 'tag_id' => 3]]);

        $this->app();

        [$allowed, $scoped] = $this->policyAndScope('arbitraryAbility');

        $this->assertEqualsCanonicalizing([2, 4, 5], $allowed, 'Policy');
        $this->assertEqualsCanonicalizing([2, 4, 5], $scoped, 'Scope');
    }

    #[Test]
    public function restricted_parent_without_the_grant_still_blocks_its_child()
    {
        // Discussion 6 loses its parent tag 2, leaving only the granted child
        // 5. The policy ignores parents and would allow it; the scope
        // stays stricter.
        $this->database()->table('group_permission')->where('permission', 'discussion.arbitraryAbility')->delete();
        $this->database()->table('discussion_tag')->where('discussion_id', 6)->where('tag_id', 2)->delete();

        $this->app();

        [, $scoped] = $this->policyAndScope('arbitraryAbility');

        $this->assertNotContains(6, $scoped);
    }

    #[Test]
    public function in_restricted_tags_scope_still_adds_discussions()
    {
        // Extend before database(), which boots the app.
        $this->extend(
            (new Extend\ModelVisibility(Discussion::class))
                ->scope(function (User $actor, Builder $query) {
                    $query->where('discussions.id', 3);
                }, 'arbitraryAbilityInRestrictedTags')
        );

        $this->database()->table('group_permission')->where('permission', 'discussion.arbitraryAbility')->delete();

        $this->app();

        [, $scoped] = $this->policyAndScope('arbitraryAbility');

        $this->assertEqualsCanonicalizing([3, 4, 5], $scoped);
    }

    #[Test]
    public function view_still_requires_the_permission_in_every_tag()
    {
        $this->database()->table('group_permission')->where('permission', 'viewForum')->delete();

        $this->app();

        // 2 and 5 have the open tag 1, which needs the global permission.
        $ids = Discussion::whereVisibleTo(User::find(2))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([3, 4, 6, 7], $ids);
    }

    #[Test]
    public function tag_moderator_sees_hidden_discussions_with_an_open_tag()
    {
        $this->database()->table('group_permission')->insert([
            ['group_id' => Group::MEMBER_ID, 'permission' => 'tag3.discussion.hide'],
            ['group_id' => Group::MEMBER_ID, 'permission' => 'tag4.discussion.hide'],
        ]);
        $this->database()->table('discussion_tag')->insert([['discussion_id' => 2, 'tag_id' => 3]]);
        $this->database()->table('discussions')->whereIn('id', [2, 4, 5])->update([
            'hidden_at' => Carbon::now(),
            'hidden_user_id' => 1,
        ]);

        $response = $this->send(
            $this->request('GET', '/api/discussions', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode());

        $ids = array_map('intval', array_column(json_decode($response->getBody()->getContents(), true)['data'], 'id'));

        foreach ([2, 4, 5] as $id) {
            $this->assertTrue(User::find(2)->can('hide', Discussion::find($id)), "Policy allows hiding $id");
            $this->assertContains($id, $ids, "Hidden discussion $id is listed");
        }
    }
}
