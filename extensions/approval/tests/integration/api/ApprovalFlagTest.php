<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Approval\Tests\integration\api;

use Carbon\Carbon;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Event\Cleared;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Event\Saving;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

class ApprovalFlagTest extends TestCase
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
            ],
            Discussion::class => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'is_approved' => 1],
            ],
            Post::class => [
                ['id' => 1, 'discussion_id' => 1, 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>Text</p></t>', 'is_approved' => 1, 'number' => 1],
            ],
            Group::class => [
                ['id' => 4, 'name_singular' => 'Acme', 'name_plural' => 'Acme', 'is_hidden' => 0],
            ],
            'group_user' => [
                ['user_id' => 3, 'group_id' => 4],
            ],
            'group_permission' => [
                ['group_id' => 4, 'permission' => 'discussion.approvePosts'],
                ['group_id' => 4, 'permission' => 'discussion.viewFlags'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'postWithoutThrottle'],
            ],
        ]);
    }

    #[Test]
    public function approving_a_post_removes_its_approval_flag_but_keeps_user_flags()
    {
        $postId = $this->replyAwaitingApproval();

        $this->database()->table('flags')->insert(
            ['post_id' => $postId, 'user_id' => 3, 'type' => 'user', 'reason' => 'spam', 'created_at' => Carbon::now()]
        );

        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());

        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(0, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());
        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'user')->count());

        $response = $this->send($this->request('GET', '/api/flags', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $types = array_map(fn (array $flag) => $flag['attributes']['type'], json_decode((string) $response->getBody(), true)['data']);

        $this->assertEquals(['user'], $types);
    }

    #[Test]
    public function hiding_a_post_awaiting_approval_removes_its_approval_flag()
    {
        $postId = $this->replyAwaitingApprovalWithUserFlag();

        $this->patchPost($postId, 3, ['isHidden' => true]);

        $this->assertEquals(0, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());
        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'user')->count());
    }

    #[Test]
    public function restoring_a_post_awaiting_approval_removes_its_approval_flag()
    {
        $postId = $this->replyAwaitingApprovalWithUserFlag();

        // Hidden but still pending, as when its author hides it.
        $this->database()->table('posts')->where('id', $postId)->update(['hidden_at' => Carbon::now()]);

        $this->patchPost($postId, 3, ['isHidden' => false]);

        $this->assertTrue((bool) Post::query()->findOrFail($postId)->is_approved);
        $this->assertEquals(0, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());
        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'user')->count());
    }

    #[Test]
    public function a_post_held_again_while_being_approved_is_not_announced_and_keeps_its_approval_flag()
    {
        $postId = $this->replyAwaitingApproval();

        $approved = false;
        $dispatcher = $this->app()->getContainer()->make(Dispatcher::class);
        $dispatcher->listen(Saving::class, fn (Saving $event) => $event->post->is_approved = false);
        $dispatcher->listen(PostWasApproved::class, function () use (&$approved) {
            $approved = true;
        });

        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());
        $this->assertFalse($approved);
    }

    #[Test]
    public function approving_a_post_raises_cleared_after_post_was_approved()
    {
        $postId = $this->replyAwaitingApproval();

        $events = [];
        $dispatcher = $this->app()->getContainer()->make(Dispatcher::class);
        $dispatcher->listen(PostWasApproved::class, function () use (&$events) {
            $events[] = 'approved';
        });
        $dispatcher->listen(Cleared::class, function () use (&$events) {
            $events[] = 'cleared';
        });

        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 3,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(['approved', 'cleared'], $events);
    }

    #[Test]
    public function a_failed_approval_keeps_the_approval_flag()
    {
        $postId = $this->replyAwaitingApproval();

        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => 2,
                'json' => ['data' => ['attributes' => ['isApproved' => true]]],
            ])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(1, Flag::query()->where('post_id', $postId)->where('type', 'approval')->count());
    }

    /**
     * Approval must create the flag, not the test.
     */
    protected function replyAwaitingApproval(): int
    {
        $this->database()->table('group_permission')->where('permission', 'discussion.replyWithoutApproval')->delete();

        $response = $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => 'A reply awaiting approval'],
                        'relationships' => ['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertFalse((bool) Post::query()->findOrFail($postId)->is_approved);

        return $postId;
    }

    protected function replyAwaitingApprovalWithUserFlag(): int
    {
        $postId = $this->replyAwaitingApproval();

        $this->database()->table('flags')->insert(
            ['post_id' => $postId, 'user_id' => 3, 'type' => 'user', 'reason' => 'spam', 'created_at' => Carbon::now()]
        );

        return $postId;
    }

    protected function patchPost(int $postId, int $actor, array $attributes): void
    {
        $response = $this->send(
            $this->request('PATCH', "/api/posts/$postId", [
                'authenticatedAs' => $actor,
                'json' => ['data' => ['attributes' => $attributes]],
            ])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }
}
