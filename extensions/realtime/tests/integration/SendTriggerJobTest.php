<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Post\Event\Posted;
use Flarum\Post\Post;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Realtime\Push\Jobs\SendTriggerJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Queue\NullQueue;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;

/**
 * A post reaches the public channel when guests can see its discussion, and
 * connected members only when they can see it themselves.
 */
class SendTriggerJobTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2
                ['id' => 3, 'username' => 'other', 'email' => 'other@machine.local', 'is_email_confirmed' => 1],
            ],
            Discussion::class => [
                ['id' => 1, 'title' => 'Public', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 1, 'comment_count' => 2],
                // Hidden: only admin can see it.
                ['id' => 2, 'title' => 'Hidden', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 1, 'first_post_id' => 3, 'comment_count' => 2, 'hidden_at' => Carbon::now()],
            ],
            Post::class => [
                ['id' => 1, 'number' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 2, 'number' => 2, 'discussion_id' => 1, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
                ['id' => 3, 'number' => 1, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>First</p></t>'],
                ['id' => 4, 'number' => 2, 'discussion_id' => 2, 'created_at' => Carbon::now(), 'user_id' => 1, 'type' => 'comment', 'content' => '<t><p>Reply</p></t>'],
            ],
        ]);
    }

    #[Test]
    public function a_reply_in_a_public_discussion_is_pushed_publicly_and_to_connected_members(): void
    {
        $recipients = $this->trigger(postId: 2, connected: [1, 2, 3], actorId: 1);

        // The actor is never sent their own reply.
        $this->assertSame(['2', '3', 'public'], $recipients);
    }

    #[Test]
    public function a_reply_in_a_hidden_discussion_only_goes_to_members_who_can_see_it(): void
    {
        $recipients = $this->trigger(postId: 4, connected: [1, 2, 3], actorId: null);

        $this->assertSame(['1'], $recipients);
    }

    /**
     * Run the job for a post and return who each queued payload is for: a
     * user id, or `public`.
     *
     * @param int[] $connected
     * @return string[]
     */
    private function trigger(int $postId, array $connected, ?int $actorId): array
    {
        $this->app()->getContainer()->instance(Pusher::class, $this->pusher($connected));

        $post = Post::query()->findOrFail($postId);
        $actor = $actorId ? User::query()->findOrFail($actorId) : null;

        $pushed = [];
        (new SendTriggerJob(Posted::class, $post, $actor))($this->recordingQueue($pushed));

        $recipients = [];

        foreach ($pushed as $job) {
            $this->assertInstanceOf(SendGeneratedPayloadJob::class, $job);

            /** @var User|null $recipient */
            $recipient = (new \ReflectionProperty($job, 'recipient'))->getValue($job);
            $recipients[] = $recipient ? (string) $recipient->id : 'public';
        }

        sort($recipients);

        return $recipients;
    }

    /**
     * @param int[] $connected
     */
    private function pusher(array $connected): Pusher
    {
        return new class($connected) extends Pusher {
            /** @param int[] $connected */
            public function __construct(private array $connected)
            {
                parent::__construct('key', 'secret', 'app');
            }

            public function getChannels(array $params = []): object
            {
                $channels = [];

                foreach ($this->connected as $id) {
                    $channels["private-user=$id"] = (object) [];
                }

                return (object) ['channels' => $channels];
            }
        };
    }

    /**
     * @param array<int, object> $pushed
     */
    private function recordingQueue(array &$pushed): NullQueue
    {
        return new class($pushed) extends NullQueue {
            /** @param array<int, object> $pushed */
            public function __construct(private array &$pushed)
            {
            }

            public function push($job, $data = '', $queue = null)
            {
                $this->pushed[] = $job;

                return null;
            }
        };
    }
}
