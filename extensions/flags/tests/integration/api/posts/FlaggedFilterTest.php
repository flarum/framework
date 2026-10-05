<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration\api\posts;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * `filter[flagged]` lists posts with open flags, for those who may see them,
 * as a moderation queue: what's been flagged most recently comes first.
 */
class FlaggedFilterTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-flags');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'mod', 'email' => 'mod@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'tagmod', 'email' => 'tagmod@machine.local', 'is_email_confirmed' => 1],
            ],
            Group::class => [
                ['id' => 5, 'name_singular' => 'Tag mod', 'name_plural' => 'Tag mods', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 3],
                ['group_id' => 5, 'user_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'discussion.viewFlags'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag1.viewForum'],
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'tag1.discussion.viewFlags'],
                ['group_id' => 5, 'permission' => 'tag1.viewForum'],
                ['group_id' => 5, 'permission' => 'tag1.discussion.viewFlags'],
            ],
            Tag::class => [
                // Per-tag permissions only apply to restricted tags.
                ['id' => 1, 'name' => 'Support', 'slug' => 'support', 'position' => 0, 'is_restricted' => true],
                ['id' => 2, 'name' => 'General', 'slug' => 'general', 'position' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'In support', 'user_id' => 1, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 2],
                ['id' => 2, 'title' => 'In general', 'user_id' => 1, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 3, 'comment_count' => 2],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 2, 'tag_id' => 2],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now()->subDays(3), 'type' => 'comment', 'content' => '<t><p>One</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now()->subDays(2), 'type' => 'comment', 'content' => '<t><p>Two</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 2, 'user_id' => 1, 'created_at' => Carbon::now()->subDays(1), 'type' => 'comment', 'content' => '<t><p>Three</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'user_id' => 1, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>Four</p></t>'],
            ],
            Flag::class => [
                // Flagged in an order matching neither the posts' ids nor their dates.
                ['id' => 1, 'post_id' => 2, 'user_id' => 2, 'type' => 'user', 'created_at' => Carbon::now()->subMinutes(1)],
                ['id' => 2, 'post_id' => 1, 'user_id' => 2, 'type' => 'user', 'created_at' => Carbon::now()->subMinutes(10)],
                ['id' => 3, 'post_id' => 3, 'user_id' => 2, 'type' => 'user', 'created_at' => Carbon::now()->subMinutes(30)],
                ['id' => 4, 'post_id' => 3, 'user_id' => 3, 'type' => 'user', 'created_at' => Carbon::now()->subMinutes(40)],
            ],
        ]);
    }

    #[Test]
    public function moderators_see_flagged_posts_most_recently_flagged_first(): void
    {
        $this->assertSame(['2', '1', '3'], $this->flaggedPosts(3));
    }

    #[Test]
    public function members_who_cannot_see_flags_see_none(): void
    {
        $this->assertSame([], $this->flaggedPosts(2));
    }

    #[Test]
    public function moderators_of_a_tag_see_only_its_flagged_posts(): void
    {
        $this->assertSame(['2', '1'], $this->flaggedPosts(4));
    }

    #[Test]
    public function dismissed_posts_leave_the_list(): void
    {
        $response = $this->send($this->request('DELETE', '/api/posts/1/flags', ['authenticatedAs' => 3]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame(['2', '3'], $this->flaggedPosts(3));
    }

    /**
     * @return string[]
     */
    private function flaggedPosts(int $actor): array
    {
        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => $actor])
                ->withQueryParams(['filter' => ['flagged' => '1']])
        );

        $body = (string) $response->getBody();

        $this->assertEquals(200, $response->getStatusCode(), $body);

        return array_column(json_decode($body, true)['data'], 'id');
    }
}
