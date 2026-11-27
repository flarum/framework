<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * `mentionCount` counts mentions the reader may not be allowed to see: posts in
 * restricted tags and private discussions are in the stored total. The field is
 * therefore only sent to the user themselves and to holders of
 * `user.viewMentionCount`; everyone else gets no attribute at all. Holding a
 * moderation permission such as `discussion.hidePosts`, or access to the tag,
 * is not what grants it.
 */
class UserMentionCountVisibilityTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-mentions', 'flarum-tags');

        $now = Carbon::now();

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'position' => 0, 'parent_id' => null],
                ['id' => 2, 'name' => 'Restricted', 'slug' => 'restricted', 'position' => 1, 'parent_id' => null, 'is_restricted' => true],
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'allowed', 'name_plural' => 'allowed'],
            ],
            User::class => [
                // id 2, no access to the restricted tag.
                $this->normalUser(),
                // id 3, the profile owner, allowed into the restricted tag.
                ['id' => 3, 'username' => 'owner', 'email' => 'owner@machine.local', 'is_email_confirmed' => 1],
                // id 4, a moderator, also allowed into the restricted tag.
                ['id' => 4, 'username' => 'moderator', 'email' => 'moderator@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['group_id' => 100, 'user_id' => 3],
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 4],
                ['group_id' => 100, 'user_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => 100, 'permission' => 'tag2.viewForum'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag2.viewForum'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'open', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 3, 'first_post_id' => 1, 'comment_count' => 2, 'last_post_number' => 2],
                ['id' => 2, 'title' => 'restricted', 'created_at' => $now, 'last_posted_at' => $now, 'user_id' => 3, 'first_post_id' => 3, 'comment_count' => 2, 'last_post_number' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => $now, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>one</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => $now, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>two</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 2, 'created_at' => $now, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>three</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => $now, 'user_id' => 3, 'type' => 'comment', 'content' => '<t><p>four</p></t>'],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
            'post_mentions_user' => [
                ['post_id' => 1, 'mentions_user_id' => 3],
                ['post_id' => 2, 'mentions_user_id' => 3],
                ['post_id' => 3, 'mentions_user_id' => 3],
                ['post_id' => 4, 'mentions_user_id' => 3],
            ],
        ]);

        // The stored total: all four mentions, including the restricted tag.
        $this->database()->table('users')->where('id', 3)->update(['mention_count' => 4]);
    }

    #[Test]
    public function a_guest_does_not_get_the_attribute()
    {
        $response = $this->send($this->request('GET', '/api/users/3'));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertArrayNotHasKey('mentionCount', $attributes);
        $this->assertArrayNotHasKey('mentionCountRevision', $attributes, 'The revision must be hidden whenever the count is.');
    }

    #[Test]
    public function a_member_without_access_to_the_restricted_tag_does_not_get_the_attribute()
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertArrayNotHasKey('mentionCount', $attributes);
        $this->assertArrayNotHasKey('mentionCountRevision', $attributes, 'The revision must be hidden whenever the count is.');
    }

    #[Test]
    public function the_user_sees_their_own_count_and_it_matches_their_mentions_tab()
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertSame(4, $attributes['mentionCount']);
        $this->assertArrayHasKey('mentionCountRevision', $attributes, 'The revision must be sent whenever the count is.');

        $mentions = $this->send($this->request('GET', '/api/posts?filter[type]=comment&filter[mentioned]=3', ['authenticatedAs' => 3]));
        $this->assertEquals(200, $mentions->getStatusCode(), (string) $mentions->getBody());
        $this->assertCount(4, json_decode((string) $mentions->getBody(), true)['data']);
    }

    #[Test]
    public function a_moderator_without_the_permission_does_not_get_the_attribute()
    {
        // Moderators hold `discussion.hidePosts` and can see the restricted
        // tag, but neither says anything about reading a profile statistic.
        $moderator = User::query()->findOrFail(4);
        $this->assertTrue($moderator->hasPermission('discussion.hidePosts'));
        $this->assertTrue($moderator->can('viewForum', Tag::query()->findOrFail(2)));
        $this->assertFalse($moderator->hasPermission('user.viewMentionCount'));

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 4]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertArrayNotHasKey('mentionCount', $attributes);
        $this->assertArrayNotHasKey('mentionCountRevision', $attributes, 'The revision must be hidden whenever the count is.');
    }

    #[Test]
    public function a_member_with_the_permission_sees_the_stored_total()
    {
        $this->database()->table('group_permission')->insert([
            'group_id' => Group::MEMBER_ID,
            'permission' => 'user.viewMentionCount',
        ]);

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        // The restricted-tag mentions are in the total even though this member
        // cannot see them: the field is a profile read for privileged viewers.
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertSame(4, $attributes['mentionCount']);
        $this->assertArrayHasKey('mentionCountRevision', $attributes, 'The revision must be sent whenever the count is.');
    }

    #[Test]
    public function an_admin_gets_the_attribute()
    {
        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 1]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $this->assertSame(4, $attributes['mentionCount']);
        $this->assertArrayHasKey('mentionCountRevision', $attributes, 'The revision must be sent whenever the count is.');
    }

    #[Test]
    public function the_mentions_tab_of_a_viewer_without_access_is_smaller_than_the_stored_total()
    {
        // This is the gap the field policy closes: the list already hides the
        // restricted posts, so a public number would disagree with it.
        $response = $this->send($this->request('GET', '/api/posts?filter[type]=comment&filter[mentioned]=3', ['authenticatedAs' => 2]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(2, json_decode((string) $response->getBody(), true)['data']);
        $this->assertSame(4, (int) User::query()->findOrFail(3)->mention_count);
    }
}
