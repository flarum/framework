<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Flags\Flag;
use Flarum\Group\Group;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendFlaggedJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;

/**
 * Moderators' flag counts and flagged posts update live when flags are
 * cleared. A forum without a queue worker runs the broadcast as soon as it's
 * queued, so it must be queued once the flags are gone, or the other
 * moderators are sent what was there before.
 */
class RealtimeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /**
     * For each flag broadcast queued, how many flags its post still had.
     *
     * @var int[]
     */
    private array $broadcasts = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-flags', 'flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
                ['id' => 3, 'username' => 'mod', 'email' => 'mod@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['group_id' => Group::MODERATOR_ID, 'user_id' => 3],
            ],
            'group_permission' => [
                ['group_id' => Group::MODERATOR_ID, 'permission' => 'discussion.viewFlags'],
                ['group_id' => Group::MEMBER_ID, 'permission' => 'discussion.flagPosts'],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Flagged', 'user_id' => 2, 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'first_post_id' => 1, 'comment_count' => 3, 'last_post_number' => 3],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>Flagged twice</p></t>'],
                ['id' => 3, 'number' => 3, 'discussion_id' => 1, 'user_id' => 1, 'created_at' => Carbon::now(), 'type' => 'comment', 'content' => '<t><p>Not flagged</p></t>'],
            ],
            Flag::class => [
                ['id' => 1, 'post_id' => 2, 'user_id' => 1, 'type' => 'user', 'created_at' => Carbon::now()],
                ['id' => 2, 'post_id' => 2, 'user_id' => 3, 'type' => 'user', 'created_at' => Carbon::now()],
            ],
        ]);
    }

    private function recordBroadcasts(): void
    {
        $broadcasts = &$this->broadcasts;

        $this->app()->getContainer()->instance(Queue::class, new class($broadcasts) extends NullQueue {
            /** @param int[] $broadcasts */
            public function __construct(private array &$broadcasts)
            {
            }

            public function push($job, $data = '', $queue = null)
            {
                if ($job instanceof SendFlaggedJob) {
                    $this->broadcasts[] = Flag::query()->where('post_id', 2)->count();
                }

                return null;
            }
        });
    }

    #[Test]
    public function dismissing_flags_broadcasts_once_they_are_gone(): void
    {
        $this->recordBroadcasts();

        $response = $this->send($this->request('DELETE', '/api/posts/2/flags', ['authenticatedAs' => 3]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([0], $this->broadcasts, 'One broadcast for the post, queued after its flags were deleted.');
    }

    #[Test]
    public function deleting_a_flagged_post_broadcasts(): void
    {
        $this->recordBroadcasts();

        $response = $this->send($this->request('DELETE', '/api/posts/2', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([0], $this->broadcasts);
    }

    #[Test]
    public function deleting_a_post_without_flags_does_not(): void
    {
        $this->recordBroadcasts();

        $response = $this->send($this->request('DELETE', '/api/posts/3', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([], $this->broadcasts);
    }

    #[Test]
    public function flagging_a_post_still_broadcasts(): void
    {
        $this->recordBroadcasts();

        $response = $this->send(
            $this->request('POST', '/api/flags', [
                'authenticatedAs' => 2,
                'json' => [
                    'data' => [
                        'type' => 'flags',
                        'attributes' => ['reason' => 'off_topic'],
                        'relationships' => ['post' => ['data' => ['type' => 'posts', 'id' => '3']]],
                    ],
                ],
            ])
        );

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(1, $this->broadcasts);
    }
}
