<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class HidePendingContentTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-approval');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'acme', 'email' => 'acme@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'editor', 'email' => 'editor@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'member', 'email' => 'member@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_approved' => 0, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_approved' => 0, 'is_private' => 1, 'number' => 1],
            ],
            // 4 is the Mods group, which also has core's moderator permissions.
            Group::class => [
                ['id' => 4, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
                ['id' => 5, 'name_singular' => 'Editor', 'name_plural' => 'Editors', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
                ['user_id' => 4, 'group_id' => 5],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.approvePosts'],
                ['group_id' => 4, 'permission' => 'discussion.hidePosts'],
                ['group_id' => 5, 'permission' => 'discussion.editPosts'],
            ],
        ]);
    }

    #[Test]
    public function hiding_the_first_post_leaves_the_discussion_pending()
    {
        $this->patchPost(['isHidden' => true]);

        $this->assertState(post: [1, 0], discussion: [0, 1, 0]);

        // Private: only its author and the moderator, who may approve it, see it.
        $this->assertSame(
            [null => 404, 2 => 200, 3 => 200, 4 => 404, 5 => 404],
            $this->discussionStatusFor([null, 2, 3, 4, 5])
        );
    }

    #[Test]
    public function restoring_the_hidden_first_post_approves_the_discussion()
    {
        $this->patchPost(['isHidden' => true]);
        $this->patchPost(['isHidden' => false]);

        $this->assertState(post: [1, 0], discussion: [1, 0, 1]);
        $this->assertSame(
            [null => 200, 5 => 200],
            $this->discussionStatusFor([null, 5])
        );
    }

    #[Test]
    public function restoring_the_hidden_first_post_of_a_deleted_author()
    {
        $this->database()->table('discussions')->where('id', 1)->update(['user_id' => null]);
        $this->database()->table('posts')->where('id', 1)->update(['user_id' => null]);

        $this->patchPost(['isHidden' => true]);
        $this->patchPost(['isHidden' => false]);

        $this->assertState(post: [1, 0], discussion: [1, 0, 1]);
    }

    #[Test]
    public function approving_the_hidden_first_post_approves_the_discussion_but_keeps_it_empty()
    {
        $this->patchPost(['isHidden' => true]);
        $this->patchPost(['isApproved' => true]);

        $this->assertState(post: [1, 0], discussion: [1, 0, 0]);

        // No longer private, but with no visible comments only its author and those who
        // may edit its posts (the moderator and the editor) see it.
        $this->assertSame(
            [null => 404, 2 => 200, 3 => 200, 4 => 200, 5 => 404],
            $this->discussionStatusFor([null, 2, 3, 4, 5])
        );
    }

    #[Test]
    public function hiding_restoring_and_approving_the_first_post()
    {
        foreach ([['isHidden' => true], ['isHidden' => false], ['isApproved' => true]] as $attributes) {
            $this->patchPost($attributes);
        }

        $post = $this->database()->table('posts')->find(1);
        $discussion = $this->database()->table('discussions')->find(1);

        $this->assertEquals(
            ['post' => [1, 0, null], 'discussion' => [1, 0]],
            ['post' => [$post->is_approved, $post->is_private, $post->hidden_at], 'discussion' => [$discussion->is_approved, $discussion->is_private]],
            'Restoring the hidden first post should approve its discussion.'
        );
    }

    protected function patchPost(array $attributes): void
    {
        $response = $this->send(
            $this->request('PATCH', '/api/posts/1', [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    /**
     * @param array{int, int} $post is_approved, is_private
     * @param array{int, int, int} $discussion is_approved, is_private, comment_count
     */
    protected function assertState(array $post, array $discussion): void
    {
        $p = $this->database()->table('posts')->find(1);
        $d = $this->database()->table('discussions')->find(1);

        $this->assertEquals($post, [$p->is_approved, $p->is_private]);
        $this->assertEquals($discussion, [$d->is_approved, $d->is_private, $d->comment_count]);
    }

    /**
     * @param array<int|null> $users
     * @return array<int|string, int>
     */
    protected function discussionStatusFor(array $users): array
    {
        $statuses = [];

        foreach ($users as $user) {
            $response = $this->send($this->request('GET', '/api/discussions/1', $user ? ['authenticatedAs' => $user] : []));

            $statuses[$user ?? ''] = $response->getStatusCode();
        }

        return $statuses;
    }
}
